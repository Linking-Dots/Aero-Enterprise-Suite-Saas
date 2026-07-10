# AEOS365 FYP Final Presentation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the AEOS365 FYP presentation from scratch as one self-contained, offline-safe, fully-responsive, dual-theme HTML deck driven by the final report, with Aeon as a hero thread and a live-demo segment backed by real democorp screenshots.

**Architecture:** A single `index.html` where each slide is a `<section>` (no iframes). One shared `assets/aeos-deck.css` (CSS custom-property theming, fluid `clamp()` type, container queries) + one `assets/deck.js` player (keyboard nav, progress, theme toggle persisted to `localStorage`, fullscreen, hash deep-link, speaker notes). Fonts vendored locally as `woff2`, icons inline SVG — zero external network dependency. Screenshots captured via Playwright over the running democorp apps.

**Tech Stack:** Hand-authored HTML/CSS/JS (no framework, no build step, no CDN). Playwright MCP for screenshot capture + responsive QA. Repo: `c:\laragon\www\aeos365-Presentation` (separate git repo).

## Global Constraints

- **Zero CDN / zero external fetch.** No Tailwind CDN, no FontAwesome, no Google Fonts `<link>`. Deck must render pixel-correct with networking disabled. Fonts local `woff2`; icons inline SVG; all CSS/JS local.
- **Single-document deck.** All slides are `<section class="slide">` inside one `index.html`. No iframes.
- **Fully responsive.** Fluid type via `clamp()`; `grid`/`flex`; container queries. Must pass at 320px, 768px, 1080p, 1440p, 4K, in BOTH portrait and landscape, with no horizontal body scroll and no clipped content.
- **Dual theme.** `:root` = dark (primary); `[data-theme="light"]` overrides. Both must be independently bug-free and projector-legible. Toggle persists to `localStorage`.
- **Accent:** electric cyan/teal on dark slate. **Type:** Space Grotesk (display) + Inter (body), vendored.
- **Zero-error content bar.** Every claim traces to `FYP Report Final V1.pdf` (extracted to `report.txt`) or observed product behavior. No invented metrics. Module tiers reflect ACTUAL built state. Aeon claims limited to what is demoable live.
- **Cover metadata corrected:** "Final Year Project" (NOT "Proposal"), "June 2026".
- **Team (verbatim from title page):** Emam Hosen (2231091007), MST. Khurshida Haque Mili (2231091012), Anowar Hossain Rahat (2231091001), Sultana Parvin (2231091016). Supervisor: Tama Shill, Lecturer, CSE, Uttara University.
- **Commits authored by Emam Hosen only** — never add a `Co-Authored-By: Claude` trailer.
- Old proposal deck archived to `/archive/`, never deleted.

---

## Phase A — Engine & Design System

### Task 1: Archive old deck + scaffold new structure

**Files:**
- Move: `slides/*` and old `index.html` → `archive/`
- Create: `index.html` (new shell), `assets/aeos-deck.css`, `assets/deck.js`, `assets/fonts/`, `assets/img/`, `README.md`

**Interfaces:**
- Produces: the file skeleton every later task writes into. `index.html` links `assets/aeos-deck.css` and `assets/deck.js`.

- [ ] **Step 1:** Move the old deck into an archive folder (keep git history):

```bash
cd "c:/laragon/www/aeos365-Presentation"
mkdir -p archive assets/fonts assets/img
git mv index.html archive/index.html
git mv slides archive/slides
```

- [ ] **Step 2:** Create the new `index.html` shell (slides get appended in later tasks):

```html
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
<title>AEOS365 — Final Year Project</title>
<link rel="stylesheet" href="assets/aeos-deck.css"/>
</head>
<body>
<main id="deck">
  <!-- slides appended here in later tasks -->
</main>
<div id="deck-ui"><!-- progress, counter, controls injected by deck.js --></div>
<script src="assets/deck.js"></script>
</body>
</html>
```

- [ ] **Step 3:** Create placeholder `assets/aeos-deck.css` and `assets/deck.js` with a one-line comment each (filled by Tasks 2–4).

- [ ] **Step 4:** Verify structure: `ls -R assets archive` shows the tree; open `index.html` in a browser — blank page, no console errors, no network requests to external hosts (DevTools Network tab empty of third-party).

- [ ] **Step 5:** Commit:

```bash
git add -A && git commit -m "chore(deck): archive proposal deck, scaffold new self-contained structure"
```

---

### Task 2: Vendor fonts + theme tokens

**Files:**
- Create: `assets/fonts/space-grotesk-*.woff2`, `assets/fonts/inter-*.woff2`
- Modify: `assets/aeos-deck.css` (add `@font-face`, `:root` dark tokens, `[data-theme="light"]` tokens)

**Interfaces:**
- Produces: CSS custom properties consumed by every component: `--bg`, `--bg-elev`, `--panel`, `--panel-border`, `--text`, `--text-dim`, `--accent`, `--accent-dim`, `--accent-contrast`, `--good`, `--warn`, `--grid-line`, `--shadow`; font vars `--font-display`, `--font-body`.

- [ ] **Step 1:** Download the two font families' needed weights as `woff2` into `assets/fonts/` (Space Grotesk 500/700; Inter 400/500/600/700). Source from the local system or an already-vendored copy in the monorepo if present; otherwise fetch the `woff2` files once and save locally (they must ship in-repo — no runtime CDN).

- [ ] **Step 2:** Add `@font-face` blocks (local `src: url(...) format('woff2')`, `font-display: swap`) and token roots to `assets/aeos-deck.css`:

```css
@font-face{font-family:'Space Grotesk';src:url('fonts/space-grotesk-700.woff2') format('woff2');font-weight:700;font-display:swap}
/* ...repeat per weight... */
:root{ /* dark (primary) */
  --bg:#0a0f1a; --bg-elev:#0f1626; --panel:rgba(255,255,255,.04);
  --panel-border:rgba(255,255,255,.09); --text:#eaf1ff; --text-dim:#9fb0cc;
  --accent:#22d3ee; --accent-dim:#0e7490; --accent-contrast:#00121a;
  --good:#34d399; --warn:#fbbf24; --grid-line:rgba(255,255,255,.05);
  --shadow:0 30px 80px rgba(0,0,0,.55);
  --font-display:'Space Grotesk',system-ui,sans-serif;
  --font-body:'Inter',system-ui,sans-serif;
}
[data-theme="light"]{
  --bg:#f7f9fc; --bg-elev:#ffffff; --panel:rgba(15,23,42,.03);
  --panel-border:rgba(15,23,42,.10); --text:#0f172a; --text-dim:#475569;
  --accent:#0891b2; --accent-dim:#67e8f9; --accent-contrast:#ffffff;
  --good:#059669; --warn:#b45309; --grid-line:rgba(15,23,42,.06);
  --shadow:0 24px 60px rgba(15,23,42,.14);
}
html,body{margin:0;background:var(--bg);color:var(--text);font-family:var(--font-body);-webkit-font-smoothing:antialiased}
```

- [ ] **Step 3:** Verify: temporarily add `<h1 style="font-family:var(--font-display)">Test</h1><p>Body</p>` to `index.html`, open in browser, confirm both custom fonts render (DevTools → Network shows fonts loaded from `assets/fonts/`, no external host). Toggle `data-theme="light"` on `<html>` in DevTools — colors flip. Remove the test markup.

- [ ] **Step 4:** Commit: `git add -A && git commit -m "feat(deck): vendor Space Grotesk + Inter, add dual-theme token system"`

---

### Task 3: Deck player (`deck.js`)

**Files:**
- Modify: `assets/deck.js`

**Interfaces:**
- Consumes: `<section class="slide">` elements inside `#deck`; `#deck-ui` container.
- Produces: runtime behavior — `goTo(n)`, keyboard nav, `#slide-n` hash sync, theme persistence key `aeos-deck-theme`, body class `is-fullscreen`, speaker-notes toggle reading `<aside class="notes">` inside each slide.

- [ ] **Step 1:** Implement the player. Full code:

```js
(() => {
  const deck = document.getElementById('deck');
  const slides = () => [...deck.querySelectorAll('.slide')];
  let i = 0;

  const ui = document.getElementById('deck-ui');
  ui.innerHTML = `
    <div class="dk-progress"><span></span></div>
    <div class="dk-controls">
      <button data-act="prev" aria-label="Previous">&#8592;</button>
      <span class="dk-count"></span>
      <button data-act="next" aria-label="Next">&#8594;</button>
      <button data-act="theme" aria-label="Toggle theme">&#9681;</button>
      <button data-act="full" aria-label="Fullscreen">&#9974;</button>
      <button data-act="notes" aria-label="Speaker notes">&#9776;</button>
    </div>`;

  const clamp = n => Math.max(0, Math.min(slides().length - 1, n));
  function render(){
    const all = slides();
    all.forEach((s,idx)=>s.classList.toggle('active', idx===i));
    ui.querySelector('.dk-count').textContent = `${i+1} / ${all.length}`;
    ui.querySelector('.dk-progress span').style.width = `${((i+1)/all.length)*100}%`;
    if (location.hash !== `#slide-${i+1}`) history.replaceState(null,'',`#slide-${i+1}`);
    all[i].scrollIntoView({behavior:'instant',block:'start'});
  }
  function goTo(n){ i = clamp(n); render(); }

  // theme
  const THEME_KEY='aeos-deck-theme';
  const saved = localStorage.getItem(THEME_KEY);
  if (saved) document.documentElement.setAttribute('data-theme', saved);
  function toggleTheme(){
    const t = document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';
    document.documentElement.setAttribute('data-theme',t);
    localStorage.setItem(THEME_KEY,t);
  }

  function toggleFull(){
    if (!document.fullscreenElement) document.documentElement.requestFullscreen?.();
    else document.exitFullscreen?.();
  }
  function toggleNotes(){ document.body.classList.toggle('show-notes'); }

  ui.addEventListener('click', e=>{
    const a = e.target.closest('button')?.dataset.act; if(!a) return;
    if(a==='prev') goTo(i-1); else if(a==='next') goTo(i+1);
    else if(a==='theme') toggleTheme(); else if(a==='full') toggleFull();
    else if(a==='notes') toggleNotes();
  });
  window.addEventListener('keydown', e=>{
    if(e.key==='ArrowRight'||e.key==='PageDown'||e.key===' ') { e.preventDefault(); goTo(i+1); }
    else if(e.key==='ArrowLeft'||e.key==='PageUp') { e.preventDefault(); goTo(i-1); }
    else if(e.key==='Home') goTo(0); else if(e.key==='End') goTo(slides().length-1);
    else if(e.key.toLowerCase()==='t') toggleTheme();
    else if(e.key.toLowerCase()==='f') toggleFull();
    else if(e.key.toLowerCase()==='s') toggleNotes();
  });

  // deep link on load
  const m = location.hash.match(/#slide-(\d+)/);
  if (m) i = clamp(parseInt(m[1],10)-1);
  render();
})();
```

- [ ] **Step 2:** Add minimal player-UI CSS to `assets/aeos-deck.css` (progress bar fixed top, controls fixed bottom-right, `.slide` full-viewport with `.active` shown). Ensure controls are keyboard-focusable and don't overlap slide content on small screens.

- [ ] **Step 3:** Verify with 3 temporary `<section class="slide">A</section>` blocks: arrows/space navigate, counter updates, progress advances, `T` toggles theme and persists across reload, `F` fullscreens, `#slide-2` deep-link lands on slide 2. Remove temp slides.

- [ ] **Step 4:** Commit: `git add -A && git commit -m "feat(deck): player — keyboard nav, progress, persisted theme, fullscreen, deep-link, notes"`

---

### Task 4: Component kit + responsive foundation

**Files:**
- Modify: `assets/aeos-deck.css`

**Interfaces:**
- Produces: reusable classes every slide task uses: `.slide`, `.slide-inner`, `.eyebrow`, `.s-title`, `.s-lede`, `.panel`, `.stat`, `.stat .n`, `.stat .l`, `.grid`, `.cols-2`, `.cols-3`, `.diagram`, `.shot` (screenshot frame w/ chrome), `.badge`, `.badge--done`, `.badge--scaffold`, `.s-foot`, `.notes`.

- [ ] **Step 1:** Add the layout + component CSS. Core rules (fluid + container-query based):

```css
.slide{min-height:100dvh;display:none;place-items:center;padding:clamp(20px,4vw,72px);
  background:radial-gradient(120% 80% at 100% 0,var(--bg-elev),var(--bg))}
.slide.active{display:grid}
.slide-inner{width:min(1200px,100%);container-type:inline-size}
.eyebrow{font-family:var(--font-display);letter-spacing:.18em;text-transform:uppercase;
  font-size:clamp(11px,1.1vw,14px);color:var(--accent)}
.s-title{font-family:var(--font-display);font-weight:700;line-height:1.02;
  font-size:clamp(30px,6vw,74px);letter-spacing:-.02em;margin:.2em 0 .3em}
.s-lede{font-size:clamp(15px,1.8vw,22px);color:var(--text-dim);max-width:60ch;line-height:1.5}
.panel{background:var(--panel);border:1px solid var(--panel-border);border-radius:18px;
  padding:clamp(16px,2vw,28px);box-shadow:var(--shadow);backdrop-filter:blur(6px)}
.grid{display:grid;gap:clamp(12px,1.5vw,22px)}
.cols-2{grid-template-columns:repeat(2,1fr)} .cols-3{grid-template-columns:repeat(3,1fr)}
@container (max-width:720px){.cols-2,.cols-3{grid-template-columns:1fr}}
.stat .n{font-family:var(--font-display);font-weight:700;font-size:clamp(26px,4vw,48px);color:var(--accent)}
.stat .l{color:var(--text-dim);font-size:clamp(12px,1.3vw,15px)}
.shot{border-radius:14px;overflow:hidden;border:1px solid var(--panel-border);box-shadow:var(--shadow);background:var(--bg-elev)}
.shot .bar{height:34px;display:flex;align-items:center;gap:6px;padding:0 12px;background:var(--bg-elev);border-bottom:1px solid var(--panel-border)}
.shot .bar i{width:10px;height:10px;border-radius:50%;background:var(--panel-border)}
.shot img{display:block;width:100%;height:auto}
.badge{display:inline-flex;align-items:center;gap:6px;font-size:clamp(10px,1vw,12px);
  padding:3px 10px;border-radius:999px;border:1px solid var(--panel-border);font-weight:600}
.badge--done{color:var(--good);border-color:color-mix(in srgb,var(--good) 40%,transparent)}
.badge--scaffold{color:var(--text-dim)}
.s-foot{position:fixed;left:clamp(16px,3vw,40px);bottom:clamp(12px,2vh,22px);
  color:var(--text-dim);font-size:12px;letter-spacing:.04em}
.notes{display:none} body.show-notes .slide.active .notes{display:block;
  position:fixed;right:16px;bottom:64px;max-width:340px;background:var(--bg-elev);
  border:1px solid var(--panel-border);border-radius:12px;padding:14px;font-size:13px;color:var(--text-dim)}
body{overflow-x:hidden}
```

- [ ] **Step 2:** Build a temporary demo slide exercising every component (title, eyebrow, 2-col panels, 3 stat tiles, a `.shot` with a placeholder image, both badges, a `.notes` block).

- [ ] **Step 3:** Verify responsiveness with Playwright at 320×568, 768×1024, 1920×1080, 2560×1440, 3840×2160, plus a 1024×1366 portrait: no horizontal scroll, no clipped text, columns collapse to 1 under 720px container. Screenshot each into `scratchpad/` for inspection.

- [ ] **Step 4:** Verify both themes on the demo slide (toggle, re-screenshot). Remove the demo slide.

- [ ] **Step 5:** Commit: `git add -A && git commit -m "feat(deck): component kit + fluid/container-query responsive foundation"`

---

### Task 5: Offline + harness gate

**Files:** none (verification task)

- [ ] **Step 1:** Serve the deck locally: `python -m http.server 8099` in the repo, open `http://localhost:8099`.
- [ ] **Step 2:** In DevTools → Network, hard-reload with "Disable cache" and inspect: **zero requests to any non-localhost host**. If any external request appears, fix it (inline the asset) before proceeding.
- [ ] **Step 3:** Set DevTools to Offline, reload: deck still renders fully (fonts, layout intact).
- [ ] **Step 4:** No code change if clean; if a fix was needed, commit: `git commit -am "fix(deck): remove residual external asset for offline safety"`

---

## Phase B — Screenshot capture

### Task 6: Capture democorp screenshots

**Files:**
- Create: `assets/img/aeon-*.png`, `assets/img/platform-*.png`, `assets/img/hrm-*.png`, `assets/img/billing-*.png`

**Interfaces:**
- Produces: the real product images embedded by slide Tasks 11, 12, 15.

- [ ] **Step 1:** Confirm apps + creds BEFORE capturing (memory logins may be stale). Ask the Boss to confirm running URLs + logins, or verify: tenant `superadmin@uatco.test` / `Password123!` at `uatco.aeos365.test`; platform `landlord@aeos365.test` / `Password123!` at `admin.aeos365.test`. Confirm the democorp tenant + Aeon are reachable. **Do not fabricate a screenshot** — if an app is down, flag it and pause this task.
- [ ] **Step 2:** With Playwright MCP, set a consistent viewport (1600×1000), log in, and capture, in the product's dark theme where available (to blend with the deck): platform command-center dashboard; HRM (a payroll or people view); billing/subscription command center; **Aeon** open with a real query + response + a confirm-gated action prompt visible.
- [ ] **Step 3:** Save PNGs to `assets/img/` with the names above. Crop/frame consistently. Verify each renders inside a `.shot` frame at multiple widths (no distortion; `width:100%;height:auto`).
- [ ] **Step 4:** Commit: `git add -A && git commit -m "assets(deck): real democorp screenshots — platform, HRM, billing, Aeon"`

---

## Phase C — Slide build

> Each slide task: append `<section class="slide">…</section>` blocks to `index.html` using ONLY the component classes from Task 4, wire content from the cited report sections, then verify render + responsiveness at 320/1080/4K in both themes via Playwright before commit. Content below is the actual copy/data to use.

### Task 7: Slides 1–3 (Cover, Problem, Research Questions & Objectives)

**Files:** Modify `index.html`

- [ ] **Step 1 — Slide 1 Cover:** Title `AEOS365`; lede "A Comprehensive Multi-Module SaaS Platform for Enterprise Resource Management"; badges `SaaS` + `Standalone`; team grid (4 members with IDs from Global Constraints); supervisor Tama Shill; "Department of CSE · Uttara University · Dhaka"; footer right: **"Final Year Project · June 2026"** (NOT Proposal/January). Add a `.notes` with a 2-sentence opener.
- [ ] **Step 2 — Slide 2 The Problem:** Title "The SME ERP Problem". Three panels from Abstract + §1.1–1.2: (a) Expensive & rigid licensing; (b) Fragmented tools → data silos; (c) Inaccessible to SMEs in developing economies. One stat/pull-quote from the abstract.
- [ ] **Step 3 — Slide 3 RQs & Objectives:** Title "Research Questions & Objectives". List the 4 RQs verbatim from §6.4/Table 1.1: RQ1 modular ERP at SME cost; RQ2 HRMAC finer-grained than RBAC; RQ3 single codebase → SaaS + Standalone; RQ4 BYOC data sovereignty vs SaaS convenience. Side panel: objectives from §1.4. (Read `report.txt` §1.3–1.4 for exact wording before writing.)
- [ ] **Step 4:** Verify render + responsive + both themes (Playwright screenshots at 320/1080/4K). Fix any overflow.
- [ ] **Step 5:** Commit: `git add -A && git commit -m "feat(deck): slides 1-3 — cover, problem, research questions"`

### Task 8: Slides 4–5 (Literature & Gap, What is AEOS365)

**Files:** Modify `index.html`

- [ ] **Step 1 — Slide 4 Literature & The Gap:** Title "The Gap in Existing Systems". Left: existing commercial systems reviewed (SAP, Odoo, Zoho, others per §2.4). Right: the 8 research gaps distilled (§2.7): dual-mode deployment, BYOC provisioning, 4-level HRBAC at SME price, contract-driven inter-package API, field-level PII encryption, dual-layer financial immutability, typed audit 200+ events, native emerging-market payment gateway. Use compact `.panel` list; don't crowd — summarize each gap to ≤6 words.
- [ ] **Step 2 — Slide 5 What is AEOS365:** Title "Aero Enterprise Operating System". Lede = the one-line thesis (unify all enterprise ops in one affordable, scalable, modular system). Five pillar tiles: Modular monorepo · Dual-mode · HRMAC · Zero-trust security · Aeon AI. (Sets up the innovation slides.)
- [ ] **Step 3:** Verify render + responsive + both themes. Fix overflow (the 8-gap list is the crowding risk — verify at 320px).
- [ ] **Step 4:** Commit: `git add -A && git commit -m "feat(deck): slides 4-5 — literature gap, what is AEOS365"`

### Task 9: Slides 6–7 (Architecture, 36-Module Ecosystem)

**Files:** Modify `index.html`

**Interfaces:**
- Consumes: the module-tier data below.

- [ ] **Step 1 — Slide 6 System Architecture:** Title "One Codebase, Layered by Contract". Diagram (CSS/SVG, no external lib) of the dependency layering: `aero-contracts → aero-core → aero-auth → aero-hrmac → aero-platform → product packages`, with `aero-ui` spanning frontend and **Aeon (`aero-assistant`) sitting above the platform** as an intelligence layer. Tech-stack strip: Laravel 12 · React 18 · Inertia v2 · MySQL · Stancl Tenancy. Source §3.6, §4.2.
- [ ] **Step 2 — Slide 7 The 36-Module Ecosystem:** Title "36 Modules. One Platform." Responsive grid of module tiles, each with a `.badge--done` or `.badge--scaffold`. **Tier data (verify against packages/ + recent commits before finalizing; adjust if state changed):**
  - **Delivered (deep):** HRM, Platform Admin, Billing/Subscriptions, Audit, Access Control (HRMAC), Auth, Newsletter, Affiliates, Analytics, Onboarding, Integrations, Aero-UI, Assistant (Aeon).
  - **Scaffolded / roadmap:** CRM, Finance, IMS (Inventory), SCM, Project, POS, Commerce, CMS, DMS, Manufacturing (EAM), Healthcare, Education, Real Estate, Field Service, Quality, Compliance, IoT, Helpdesk, Forms, Workflow, Custom Fields, i18n, License, plus infrastructure packages.
  - Add a small legend + honest caption: "Architecture supports all 36; depth shown reflects current build state." Do not imply scaffolded modules are complete.
- [ ] **Step 3:** Verify the module grid reflows cleanly (this is the densest slide) at 320/768/1080/4K, both themes.
- [ ] **Step 4:** Commit: `git add -A && git commit -m "feat(deck): slides 6-7 — architecture + honest 36-module ecosystem grid"`

### Task 10: Slides 8–11 (Innovations 1–4)

**Files:** Modify `index.html`

- [ ] **Step 1 — Slide 8 HRMAC:** Title "HRMAC — 4-Level Access Control". Explain the hierarchy (Module → Sub → Component → Action) and contrast with flat RBAC. Source §1.9, §4.6. Use a 4-tier diagram + a one-line "route → `hrmac:module.sub.component.action`" example.
- [ ] **Step 2 — Slide 9 Multi-Tenancy:** Title "True Isolation — Database per Tenant". Stancl Tenancy, complete data isolation, tenant vs central DB. Source §3.6, §4.7. Diagram: one central DB + N isolated tenant DBs.
- [ ] **Step 3 — Slide 10 Dual-Mode + BYOC:** Title "One Codebase, Two Deployments". SaaS (multi-tenant subdomains) vs Standalone (self-hosted) from the same packages (AeroMode); BYOC = bring-your-own-cloud DB for data sovereignty. Source §4.11, ADR-003/005.
- [ ] **Step 4 — Slide 11 Zero-Trust Security:** Title "Security by Construction". Four panels: field-level PII encryption (`EncryptedField` on national_id, account_number, tax_id, medical_notes…); financial immutability (Payslip/JournalEntry/Invoice locked); typed audit service (200+ events); STRIDE threat model. Aeon tie-in line: "Conversational access is still HRMAC-gated." Source §3.7, §4.8, Table 3.4.
- [ ] **Step 5:** Verify all four render + responsive + both themes.
- [ ] **Step 6:** Commit: `git add -A && git commit -m "feat(deck): slides 8-11 — HRMAC, multi-tenancy, dual-mode/BYOC, zero-trust security"`

### Task 11: Slides 12–14 (Aeon hero, transition, live+fallback)

**Files:** Modify `index.html` (uses `assets/img/aeon-*.png` from Task 6)

- [ ] **Step 1 — Slide 12 Aeon (HERO):** Title "Aeon — the Operating System Gets a Brain". Full-bleed treatment. Capabilities (only what is demoable, per zero-error bar): provider-agnostic (Gemini default), RAG over the tenant's own data, **confirm-gated agentic actions** (it proposes, you approve), MySQL JSON embeddings (no pgvector dependency). Embed `aeon-*.png` in a `.shot` frame. Source §4.10 + observed behavior. This slide should visibly out-design the others (it's the hero).
- [ ] **Step 2 — Slide 13 Transition:** Full-screen "Let's see it live" with a subtle animated accent. Minimal text.
- [ ] **Step 3 — Slide 14 Live + fallback:** Primary content = a clear "LIVE DEMO" cue with the scripted flow written in `.notes` (Aeon query → confirm-gated action → one signature module flow, e.g. HRM payroll or platform command-center). Bake in fallback: the slide itself shows the `aeon-*.png` + module screenshot so if the live app fails, this slide stands alone as proof. Label the fallback subtly ("captured from the live system").
- [ ] **Step 4:** Verify hero slide is striking at 1080/4K and still clean at 320; screenshots not distorted; both themes.
- [ ] **Step 5:** Commit: `git add -A && git commit -m "feat(deck): slides 12-14 — Aeon hero, live-demo transition + fallback"`

### Task 12: Slides 15–16 (Product tour, Implementation)

**Files:** Modify `index.html` (uses `platform-*.png`, `hrm-*.png`, `billing-*.png`)

- [ ] **Step 1 — Slide 15 Product Tour:** Title "It's Real — democorp". 3 `.shot` frames: platform command-center, HRM, billing. Caption each with one line of what it does. Real screenshots only.
- [ ] **Step 2 — Slide 16 Implementation Highlights:** Title "How It's Built". Package standard anatomy (controllers/services/models/routes/Form Requests per package), contract-driven inter-package APIs (`aero-contracts`), `DB::transaction()` write discipline. Source Ch 4, §4.3, §4.9.
- [ ] **Step 3:** Verify render + responsive + both themes.
- [ ] **Step 4:** Commit: `git add -A && git commit -m "feat(deck): slides 15-16 — product tour + implementation highlights"`

### Task 13: Slides 17–20 (Testing, Performance, RQ answers, Comparison)

**Files:** Modify `index.html`

- [ ] **Step 1 — Slide 17 Testing & Evaluation:** Title "Tested Four Ways". PHPUnit (unit + feature), Playwright E2E, k6 performance, OWASP ZAP security. Include the OWASP Top-10 2021 result summary from Table 5.2 (use the report's actual pass/finding values — read §5.5 + Table 5.2; no invented numbers).
- [ ] **Step 2 — Slide 18 Performance & Results:** Title "Results". Performance benchmarks from §5.6 + requirements traceability §5.7 + objective achievement §6.1. Use `.stat` tiles with the report's real figures only. If a figure isn't in the report, omit it.
- [ ] **Step 3 — Slide 19 Answering the RQs:** Title "The Questions, Answered". RQ1–4 each with the report's verdict from §6.4 (one-line answer each).
- [ ] **Step 4 — Slide 20 Comparison:** Title "AEOS365 vs SAP · Odoo · Zoho". Distilled comparative matrix from §6.3 / Table 2.2 — pick ~8 highest-signal dimensions (dual-mode, BYOC, 4-level access, field PII encryption, audit depth, SME price, modularity, self-host). Mark support per platform. Responsive table that reflows to stacked cards under 720px.
- [ ] **Step 5:** Verify — the comparison table is the reflow risk; confirm it becomes stacked cards on mobile, both themes.
- [ ] **Step 6:** Commit: `git add -A && git commit -m "feat(deck): slides 17-20 — testing, results, RQ answers, comparison"`

### Task 14: Slides 21–24 (Challenges, Future Work, Conclusion, Team/Thanks) + optional References

**Files:** Modify `index.html`

- [ ] **Step 1 — Slide 21 Challenges & Limitations:** Title "Honest Limitations". From §6.6, §7.3 — real challenges faced + current limitations. Honesty reads as credibility to examiners.
- [ ] **Step 2 — Slide 22 Future Work:** From §7.4. Include Aeon deepening as one thread among several.
- [ ] **Step 3 — Slide 23 Conclusion & Contributions:** Title "Contributions". Research contributions §7.2 + impact §7.6, tied back to the 8 gaps.
- [ ] **Step 4 — Slide 24 Team & Thanks / Q&A:** Team roles from §1.7, supervisor acknowledgement, "Thank you — Questions?" with contact. Optional final `.slide` References appendix (top citations) reachable via End key.
- [ ] **Step 5:** Verify render + responsive + both themes.
- [ ] **Step 6:** Commit: `git add -A && git commit -m "feat(deck): slides 21-24 — challenges, future work, conclusion, team/Q&A"`

---

## Phase D — QA & polish

### Task 15: Full responsive + theme QA sweep

**Files:** fixes across `index.html`, `assets/aeos-deck.css` as needed

- [ ] **Step 1:** With Playwright, walk all 24 slides at each breakpoint (320×568, 360×800, 768×1024 portrait, 1024×768 landscape, 1366×768, 1920×1080, 2560×1440, 3840×2160), in BOTH themes. Screenshot every slide×breakpoint into `scratchpad/qa/`.
- [ ] **Step 2:** Review each screenshot for: horizontal scroll, clipped/overflowing text, overlapping elements, unreadable contrast, distorted images, controls covering content. Log each defect.
- [ ] **Step 3:** Fix every logged defect (fluid sizing, container-query breakpoints, `min-width:0` on grid children, image `max-width`). Re-screenshot the fixed slides to confirm.
- [ ] **Step 4:** Verify keyboard nav still lands cleanly on every slide and the counter reads `24/24` (or 25 with references) at End.
- [ ] **Step 5:** Commit: `git add -A && git commit -m "fix(deck): responsive + theme QA sweep across all breakpoints"`

### Task 16: Content-accuracy + offline final gate

**Files:** fixes as needed

- [ ] **Step 1:** Re-read `report.txt` section-by-section against the slides. For every number, claim, and label on a slide, confirm it traces to the report or observed product behavior. Correct any drift. Confirm the cover says "Final Year Project · June 2026" and team IDs match verbatim.
- [ ] **Step 2:** Confirm module tiers (Slide 7) still match the real build state (spot-check 2–3 "Delivered" modules exist and render in the product; 2–3 "Scaffolded" are honestly labeled).
- [ ] **Step 3:** Re-run the offline gate (Task 5 steps 1–3): zero external requests, renders offline.
- [ ] **Step 4:** Spell/grammar pass on all slide copy.
- [ ] **Step 5:** Final commit + tag: `git add -A && git commit -m "docs(deck): content-accuracy pass + offline re-verify"` then `git tag fyp-final-v1`.
- [ ] **Step 6:** Report completion to the Boss with a link to `index.html` and a note on any items that needed live-app confirmation.

---

## Self-Review

**Spec coverage:** ✅ Engine/offline/responsive/dual-theme (Tasks 1–5, 15); screenshots (Task 6); all 24 narrative slides incl. Aeon hero+live and honest module tiers (Tasks 7–14); content-accuracy zero-error gate (Task 16); archive-not-delete (Task 1); build-time dependency confirmation (Task 6 Step 1). Every §4 slide and every §8 success criterion maps to a task.

**Placeholder scan:** No "TBD/TODO/handle edge cases". Where exact report wording is needed (RQs, objectives, gap phrasing, OWASP/perf figures), the step explicitly instructs reading the cited `report.txt` section and forbids inventing numbers — that is a deliberate accuracy control, not a placeholder, because the report is the authoritative source and must be quoted, not paraphrased from memory.

**Type/name consistency:** CSS class names (`.slide`, `.slide-inner`, `.panel`, `.stat`, `.shot`, `.badge--done/--scaffold`, `.eyebrow`, `.s-title`, `.notes`), JS keys (`aeos-deck-theme`, `#slide-n`, `.active`, `show-notes`), and token vars (`--accent`, `--panel`, etc.) are defined in Tasks 2–4 and reused consistently by Tasks 7–14. Image filenames (`aeon-*`, `platform-*`, `hrm-*`, `billing-*`) defined in Task 6 and consumed in Tasks 11–12.
