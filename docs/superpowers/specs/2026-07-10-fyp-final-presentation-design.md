# AEOS365 — FYP Final Presentation: Design Spec

**Date:** 2026-07-10
**Owner:** Emam Hosen (lead architect)
**Repo:** `c:\laragon\www\aeos365-Presentation` (separate git repo)
**Source of truth:** `FYP Report Final V1.pdf` (107 pp, final, June 2026) + the running AEOS365 product
**Deadline context:** FYP examiner demo — quality bar is 100/100: mind-blowing, zero UI/content bugs, fully responsive on every device/media size, robust, offline-safe.

---

## 1. Goal

Replace the outdated **proposal-era** slide deck (22 slides, cover says "Final Year Project *Proposal*" / "January 2026", light-theme, CDN-dependent, fixed-pixel, iframe-based) with a **ground-up rebuild**: one cohesive, self-contained, fully-responsive, dual-theme presentation driven by the **final** report, proving the product is real via embedded screenshots + a live-demo segment.

## 2. Locked decisions

| Decision | Choice |
|----------|--------|
| Migration approach | **Ground-up rebuild** (reuse content, not proposal code) |
| Visual direction | **Dual light + dark toggle** (dark "living data-OS" primary) |
| Proof of realness | **Real democorp screenshots + a live-demo segment** (screenshots are the fallback) |
| Duration / size | **15–20 min, ~24 slides** (~45s each) |
| Aeon (AI assistant) | **Live & demoable** → hero slide (#12) + live moment (#14); also woven throughout. Report under-sells it; presentation must correct that WITHOUT starving other innovations. |
| Screenshots | **I capture** via Playwright over the running apps (democorp seeded data), consistent framing + deck theme |
| 36-module framing | **Ecosystem vision + honest depth tiers** — all 36 shown, `Delivered` vs `Scaffolded` badges, deep-dive only built modules. Must survive an examiner clicking around. |
| Live-demo scope | **Aeon + one signature module flow** (rehearsable, low-risk, screenshot fallback baked in) |
| Accent | Electric **cyan/teal** on dark slate |
| Typography | **Space Grotesk** (display) + **Inter** (body), vendored locally |
| Motion | **Cinematic "living data-OS"** — rich CSS/SVG/Canvas motion (reveals, ambient constellation canvas, aurora drift, breathing glow, animated diagrams/counters). **NO WebGL/Three.js.** 60fps, offline, `prefers-reduced-motion`-safe. (Decision added 2026-07-10.) |

## 3. Deck engine (the responsiveness + no-bugs + offline foundation)

- **Single-document deck.** One `index.html`; each slide is a `<section>`. NOT iframes. This removes the old deck's iframe-scaling fragility — one theme system, one responsive system, one document. Eliminates a whole class of UI bugs.
- **Zero external network dependency.** No Tailwind CDN, no FontAwesome CDN, no Google Fonts fetch. Fonts vendored as local `woff2`; icons inline SVG; one hand-authored `assets/aeos-deck.css`. Deck must render fully correct with networking disabled.
- **Genuine responsiveness.** Fluid type via `clamp()`; `grid`/`flex` layouts; **container queries** for slide-internal reflow. Verified across: 320px phone (portrait+landscape), tablet, 1080p laptop, 1440p, 4K projector. Nothing overflows; nothing clips; no horizontal scroll on the body.
- **Theming via CSS custom properties.** `:root` = dark tokens; `[data-theme="light"]` overrides. Toggle persists to `localStorage`. Both themes must be independently bug-free and legible on a projector.
- **Player controls:** keyboard nav (←/→, Home/End, `F` fullscreen, `T` theme, `S` speaker notes), progress bar, slide counter (`n / N`), theme toggle button, optional speaker-notes panel. Deep-linkable via `#slide-n` hash.
- **Component kit (shared CSS classes):** slide shell, eyebrow/kicker, title, lede, panel/glass-card, stat tile, diagram frame, screenshot frame (browser chrome), badge (`Delivered`/`Scaffolded`), two-col / grid layouts, footer (slide #, section name).
- **Archive:** old proposal deck moved to `/archive/` (kept, not deleted).

## 4. Narrative (24 slides) — mapped to report

| # | Slide | Report source | Notes |
|---|-------|---------------|-------|
| 1 | Cover | Title page | AEOS365, **Final Year Project** (fix from "Proposal"), **June 2026**, 4-member team + IDs, SaaS+Standalone badges |
| 2 | The Problem | Abstract, §1.1–1.2 | SME ERP: expensive, rigid, fragmented, data silos, licensing cost |
| 3 | Research Questions & Objectives | §1.3–1.4, Table 1.1 | 4 RQs + objectives |
| 4 | Literature & The Gap | Ch 2 | SAP/Odoo/Zoho reviewed → 8 distilled research gaps |
| 5 | What is AEOS365 | §1.5, Abstract | Thesis + pillars; "Aero Enterprise Operating System" |
| 6 | System Architecture | §3.6, §4.2 | Modular monorepo, layered dep graph (contracts→core→auth→hrmac→platform→products), Laravel 12 + React 18 + Inertia v2. Aeon callout: sits above the platform. |
| 7 | The 36-Module Ecosystem | §1.8 | Grid with honest `Delivered`/`Scaffolded` tiers |
| 8 | Innovation ① HRMAC | §1.9, §4.6 | 4-level hierarchical access control (headline differentiator) |
| 9 | Innovation ② Multi-Tenancy | §3.6, §4.7 | DB-per-tenant isolation via Stancl |
| 10 | Innovation ③ Dual-Mode + BYOC | §4.11 | SaaS + Standalone from one codebase; data sovereignty |
| 11 | Innovation ④ Zero-Trust Security | §3.7, §4.8 | Field-level PII encryption, financial immutability, typed audit (200+ events), STRIDE. Aeon tie-in: conversational access still HRMAC-gated. |
| 12 | **Innovation ⑤ Aeon — the OS gets a brain** ⭐ | §4.10 | Provider-agnostic (Gemini default), RAG over tenant data, confirm-gated agentic actions, MySQL JSON embeddings. Hero slide. |
| 13 | "Let's see it live" | — | Transition |
| 14 | **[LIVE] Aeon + one signature module flow** | demo | Scripted; screenshot fallback slides (14a/14b) baked in |
| 15 | Product tour | live app | Real democorp screenshots: platform command-center, HRM, billing |
| 16 | Implementation highlights | Ch 4 | Package anatomy, contract-driven inter-package APIs |
| 17 | Testing & Evaluation | Ch 5 | PHPUnit + Playwright + k6 + OWASP ZAP; OWASP Top-10 results (Table 5.2) |
| 18 | Performance & Results | §5.6–5.7, §6.1 | Benchmarks, requirements traceability, objective achievement |
| 19 | Answering the RQs | §6.4 | RQ1–4 with verdicts |
| 20 | AEOS365 vs SAP / Odoo / Zoho | §6.3, Table 2.2 | Distilled comparative matrix |
| 21 | Challenges & Limitations | §6.6, §7.3 | Honest |
| 22 | Future Work | §7.4 | |
| 23 | Conclusion & Contributions | §7.1–7.6 | Research contributions + impact |
| 24 | Team & Roles · Thank you / Q&A | §1.7 | |

References available as an optional appendix section (not counted in the 24).

## 5. Content-accuracy rules (zero-error bar)

- Every factual claim traces to the report or to observed product behavior. No invented metrics. If a test/benchmark number is quoted, it comes verbatim from Ch 5 / Appendix D.
- Module tiers (`Delivered` vs `Scaffolded`) reflect the ACTUAL built state (verify against packages + recent commits), not the report's aspirational 36.
- Aeon capabilities shown = only what is actually demoable live. Anything not working is framed as "designed", not claimed as done.
- Team names/IDs/emails exactly as the report title page.
- Cover metadata corrected: "Final Year Project" (not Proposal), "June 2026".

## 6. Build-time dependencies (from Boss)

- The three apps **running** + **test logins**: SaaS tenant (`{tenant}.aeos365.test`), platform-admin, standalone (`aeos365-standalone.test`). Needed to capture fresh democorp screenshots and rehearse Aeon live. Confirm creds before capturing.

## 7. Out of scope

- No changes to the product code, the report PDF, or the landing site.
- No unrelated refactors.
- References/citation formatting beyond a single optional appendix slide.

## 8. Success criteria

1. Deck renders pixel-correct with networking disabled (offline-safe).
2. No horizontal body scroll, no clipped/overflowing content at 320px, 768px, 1080p, 1440p, 4K, portrait + landscape.
3. Both light and dark themes independently bug-free and projector-legible.
4. Every slide's content traces to the final report or observed product behavior.
5. Aeon reads as a genuine differentiator (hero + live) without eclipsing the other four innovations.
6. Live-demo segment has a baked-in screenshot fallback that stands alone if the live app fails.
7. Old proposal deck archived, not lost.
8. Deck feels alive: every slide has entry reveals, an ambient animated backdrop, and animated diagrams/counters where relevant — holding ~60fps and fully collapsing under `prefers-reduced-motion: reduce` with all content still present and legible.
