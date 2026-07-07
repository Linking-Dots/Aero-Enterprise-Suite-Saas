# Aeon — AI Assistant Design

**Date:** 2026-07-07
**Status:** Design — awaiting approval
**Package:** `packages/aero-assistant` (rebuilt) · Brand/product name: **Aeon**
**Author:** Lead Architect (with Boss / Emam Hosen)

---

## 1. Summary

**Aeon** is AEOS365's built-in AI assistant: it **guides users** ("how do I…?", "where is…?"),
**answers questions over the user's own context**, and **performs tasks on request** ("create an
employee", "apply leave") — always inside the user's existing HRMAC permissions, with a
confirm-before-write safety gate and full audit.

The model backend is **provider-agnostic** (configurable). **Google Gemini** is the default
because of its free quota and is what we test against now; any other provider (OpenAI, Anthropic,
or any OpenAI-compatible / self-hosted endpoint) drops in via config with **no code change**.

This document supersedes the earlier `aero-assistant` scaffold, which assumed PostgreSQL+pgvector,
a fictional self-hosted model, Laravel 11, and non-@aero/ui frontend — all incompatible with our
stack (MySQL, Laravel 12, @aero/ui, real hosted LLM API).

### Non-goals (YAGNI)
- No self-hosted model training pipeline (drop the old `ai-server/` + `training/`).
- No pgvector / external vector database (KB is small; MySQL + in-PHP cosine is sufficient).
- No autonomous multi-step task execution without confirmation (write actions always confirm).

---

## 2. Constraints & principles

| Constraint | Consequence |
|---|---|
| Package-first | All logic in `packages/aero-assistant`; host apps stay dumb wrappers. |
| Dual-mode | Works identically in SaaS tenant context and Standalone. Conversations/usage tenant-scoped. |
| HRMAC | Every tool call is gated by the user's own permissions; Aeon can never exceed them. |
| Audit | Every write action logged via `AuditService::log()`; PII exposure via `logAccess()`. |
| Models | Tables extend `TenantModel` / `CentralModel`, never bare `Model`. |
| Transactions | All writes in `DB::transaction()`. |
| Frontend | 100% `@aero/ui`, theme-reactive, no inline `style={}`, Inertia v2 `router.*`. Renders in both shells (drawer + command-mode rail). |
| MySQL | No pgvector. Embeddings stored as JSON; cosine similarity in PHP. |
| Secrets | Provider API keys live in host `.env` (gitignored), never committed. |

---

## 3. Verified environment facts (2026-07-07, live against Boss's key)

- Gemini key format `AQ.…` is **valid** (Google's newer key format); auth via `x-goog-api-key`
  header or `?key=` query param — both HTTP 200.
- **Chat model:** confirmed working — `gemini-2.5-flash`, `gemini-3-flash-preview`,
  `gemini-flash-latest`. **Default = `gemini-flash-latest`** (auto-tracks newest flash; Boss has a
  Google AI Pro subscription and wants latest). ⚠️ `gemini-2.5-pro` / `gemini-3-pro-preview`
  returned **429** on this key — Gemini *API* quota for pro models is billing-gated separately from
  the consumer Pro subscription. Pro model = one env change once API quota is enabled.
- **Embeddings:** `gemini-embedding-001` works, native **3072 dims**, supports
  `outputDimensionality`. `text-embedding-004` is **404 / not available** on this key.
  **Default embedding = `gemini-embedding-001` @ `outputDimensionality=768`** (lean JSON storage,
  fast in-PHP cosine).
- Endpoint base: `https://generativelanguage.googleapis.com/v1beta`.

---

## 4. Architecture

```
┌─────────────────────────── Frontend (@aero/ui) ───────────────────────────┐
│  FloatingAeonButton (✨)  →  AeonDrawer (slide-over chat, streaming)       │
│  /aeon page (history + full chat)     ·   ProposedActionCard (confirm)     │
│  useAeon() hook  ·  command-mode rail entry                                │
└───────────────────────────────────┬───────────────────────────────────────┘
                                     │ Inertia / JSON (router.*)
┌───────────────────────────────────▼───────────────────────────────────────┐
│  AeonController  ·  AeonPageController          (aero-assistant, HRMAC)     │
│         │                                                                   │
│         ▼                                                                   │
│  AeonService  ── orchestrates one turn ──────────────────────────────┐     │
│    1. load conversation + context (page, module, tenant)             │     │
│    2. RagService.retrieve(query) → top-K knowledge chunks            │     │
│    3. AiProvider.chat(messages, tools) → text and/or tool calls      │     │
│    4. ToolRegistry: read tools auto-run; write tools → ProposedAction │     │
│    5. persist messages + UsageLog; return payload                    │     │
│         │                    │                      │                 │     │
│         ▼                    ▼                      ▼                 │     │
│   RagService          ToolRegistry           AiProvider (contract)   │     │
│   (embed+cosine)      (AssistantTool[])      ├─ GeminiProvider ★      │     │
│         │                    │               ├─ OpenAiProvider        │     │
│         ▼                    ▼               ├─ AnthropicProvider      │     │
│  assistant_embeddings   existing HRMAC-      └─ OpenAiCompatible      │     │
│  (MySQL JSON vectors)   guarded services        (Ollama/Azure/…)     │     │
└──────────────────────────────────────────────────────────────────────┘     │
```

### 4.1 Model provider layer (the "any API" part)
- **Contract** `Aero\Contracts\Ai\AiProvider` (lives in `aero-contracts`):
  - `chat(array $messages, array $tools = [], array $opts = []): AiChatResult`
  - `stream(array $messages, array $tools, callable $onDelta): AiChatResult`
  - `embed(array $texts, array $opts = []): array` (returns vectors)
  - `isAvailable(): bool`
- **Drivers** in `aero-assistant/src/Providers/Models/`:
  `GeminiProvider` (default), `OpenAiProvider`, `AnthropicProvider`, `OpenAiCompatibleProvider`.
- **Normalization:** internal canonical message + tool-call shape; each driver translates to/from
  its wire format (Gemini `functionDeclarations` / `functionCall`, OpenAI `tools`/`tool_calls`,
  Anthropic `tools`/`tool_use`). Feature code never sees provider-specific shapes.
- **Selection:** `config/aeon.php` reads `AEON_PROVIDER` (default `gemini`) + per-provider block
  (key, model, endpoint, embedding model, dims). Switching provider = env change only.
- **Resilience:** timeouts, retry w/ backoff on 429/5xx, graceful "assistant unavailable" message
  on failure (never a broken screen).

### 4.2 Knowledge / RAG (MySQL, no pgvector)
- **Sources indexed:** `docs/**`; each module's `config/module.php` (features, routes, nav);
  curated how-to snippets (authored markdown under the package); route/nav map for "where is X".
- **Pipeline:** chunk (size ~1000 / overlap ~200) → `AiProvider.embed()` → store vector as JSON in
  `assistant_embeddings` with `{source_type, module, context_scope, checksum}`.
- **Retrieval:** embed the query, compute cosine similarity in PHP over candidate rows
  (filtered by module/scope), return top-K (default 5) above a threshold.
- **Command:** `php artisan aeon:index [--fresh] [--module=hrm]`; checksum-guarded so re-runs only
  re-embed changed chunks (protects free-tier quota).

### 4.3 Agentic tools (safe + auditable)
- **Interface** `AssistantTool`: `name()`, `description()`, `parameters()` (JSON schema),
  `requiresConfirmation(): bool`, `hrmac(): ?string`, `handle(array $args, User $user): ToolResult`.
- **Each tool wraps an existing HRMAC-guarded service** — never raw DB.
- **Turn loop:**
  - Provider returns tool call(s).
  - `hrmac()` gate checked against the *current user*; denied → tool refuses (Aeon explains it can't).
  - **Read / navigation tools** (`requiresConfirmation=false`) execute immediately, feed results back.
  - **Write tools** (`requiresConfirmation=true`) do **not** run; they return a **ProposedAction**
    (human-readable summary + exact args) rendered as a confirm card. On user confirm, the tool runs
    inside `DB::transaction()` + `AuditService::log()`.
- **Starter tool set (proof of pattern):**
  - `navigate_to` (route the user to a page) — read.
  - `search` (global search over entities the user can see) — read.
  - `explain_page` (context-aware help for current route) — read.
  - `hrm.create_employee` — write (confirm).
  - `hrm.apply_leave` — write (confirm).
- **Extensible:** registry is open — any module registers its own tools via its provider →
  "evolving." Tools discovered through `AbstractModuleProvider`.

### 4.4 Access control & plan gating
- New HRMAC module `aeon.*`: `aeon.use`, `aeon.view_history`, `aeon.perform_actions`, `aeon.admin`.
- Registered via the package's module provider + `config/module.php` (schema_version 2.0).
- **Plan/product gating** (SaaS): feature flags per tier (basic / professional / enterprise) —
  `basic_chat`, `conversation_history`, `rag_powered`, `perform_actions`, `max_messages_per_day` —
  wired to the plan + product subscription model. Standalone = all features on by module install.

---

## 5. Data model (MySQL, tenant-scoped)

| Table | Key columns | Notes |
|---|---|---|
| `aeon_conversations` | `id, user_id, title, context_json, archived_at, timestamps` | extends TenantModel |
| `aeon_messages` | `id, conversation_id, role, content, tool_calls_json, tokens, provider, model, timestamps` | role ∈ user/assistant/tool |
| `aeon_embeddings` | `id, source_type, module, context_scope, chunk_text, vector_json, dims, checksum, timestamps` | vector as JSON; indexed by (source_type, module) |
| `aeon_usage_logs` | `id, user_id, conversation_id, provider, model, prompt_tokens, completion_tokens, latency_ms, created_at` | analytics + quota |

(Table prefix `aeon_`; migrations add the columns the models expect — no schema drift.)

---

## 6. Frontend (@aero/ui, both shells)

- **FloatingAeonButton** — ✨ bottom-right on every authenticated page (feature-flagged).
- **AeonDrawer** — slide-over chat: streaming responses, markdown, **source citations**,
  **ProposedActionCard** (summary + Confirm/Cancel) for write tools, "thinking" state.
- **/aeon page** — full-height chat + conversation history sidebar; archive/delete.
- **useAeon()** hook — open/close, send, stream, conversation state.
- **Command-mode rail** entry so it renders in the command shell too.
- All theme-drawer settings apply (mode/card-style/density/radius/borders/motion/accent).

---

## 7. Config & setup

`config/aeon.php` (publishable) + host `.env`:

```env
AEON_PROVIDER=gemini
AEON_ENABLED=true

# Gemini (default, free-tier)
GEMINI_API_KEY=************            # host .env only, gitignored
GEMINI_MODEL=gemini-flash-latest       # verified; pins: gemini-2.5-flash / gemini-3-flash-preview
GEMINI_EMBED_MODEL=gemini-embedding-001
GEMINI_EMBED_DIMS=768

# RAG
AEON_RAG_ENABLED=true
AEON_RAG_TOP_K=5
AEON_RAG_THRESHOLD=0.65
AEON_CHUNK_SIZE=1000
AEON_CHUNK_OVERLAP=200

# UI
AEON_FLOATING_BUTTON=true
AEON_DEDICATED_PAGE=true
```

Other providers use analogous blocks (`OPENAI_*`, `ANTHROPIC_*`, `AEON_COMPAT_ENDPOINT`, …).

---

## 8. Build milestones (each independently shippable)

1. **M1 — Provider layer + chat MVP:** `AiProvider` contract, `GeminiProvider`, `AeonService` chat
   turn, `aeon_conversations`/`aeon_messages`, minimal drawer, `/aeon` page. No RAG, no tools.
   → "Ask Aeon anything" works end-to-end against Gemini.
2. **M2 — RAG guide:** `aeon_embeddings`, `RagService`, `aeon:index`, source citations.
   → Aeon answers AEOS-specific how-to from indexed docs/modules.
3. **M3 — Agentic tools:** `ToolRegistry`, `AssistantTool`, read tools + confirm-gated write tools,
   ProposedActionCard, audit wiring. → Aeon performs tasks safely.
4. **M4 — Access & gating:** `aeon.*` HRMAC module, plan-tier flags, usage logs + quota guard,
   admin surface (index management). → production-grade governance.

Each milestone: TDD, then live verification in a host app, then review.

---

## 9. Open questions / assumptions

- **Resolved:** default chat model `gemini-flash-latest` (verified working; Boss on Google AI Pro).
  Pro models (`gemini-2.5-pro` / `gemini-3-pro-preview`) are API-quota-gated (429) — enable later
  via one env change. Fallback `gemini-2.5-flash-lite` if rate limits bite.
- **Assumption:** starter write tools = HRM (create employee, apply leave), since HRM is the
  deepest-seeded module. Other modules add tools later.
- **Assumption:** conversations persist per-user, tenant-scoped; no cross-tenant memory.
```
