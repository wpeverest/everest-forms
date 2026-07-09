# Golden visual-regression harness (Style Customizer v2, Phase 0)

The single highest-leverage safety net for the v2 work: it proves that a form re-rendered
through the new CSS-variable pipeline (or after a legacy→v2 migration) looks **pixel-identical**
to before. Build the baseline **before** the frontend refactor / migration; nothing merges
until this stays green.

**Exit criterion:** worst drift ≤ **0.5%** across a corpus of **≥15 real styled forms** (both
legacy engines) at 3 breakpoints.

## Setup

```bash
cd addons/StyleCustomizer/V2/tests/visual-regression
npm init -y
npm i playwright pixelmatch pngjs
npx playwright install chromium
cp forms.sample.json forms.json   # then edit: baseUrl, cookie, and the real form ids
```

Get a logged-in cookie (for previewing drafts) from your browser devtools, or from the
project's WP-CLI/Playwright auth setup. Populate `forms.json` with ≥15 real styled forms.

## Run

```bash
# 1) Baseline — current (legacy) rendering, engine flag OFF
node capture.mjs --label baseline --config forms.json

# 2) Make the change: enable EVF_STYLE_V2 / run the migration on those forms

# 3) Current — new rendering
node capture.mjs --label current --config forms.json

# 4) Diff (fails >0.5%; writes screens/diff/*.diff.png for offenders)
node compare.mjs --baseline baseline --current current
```

## Notes

- Screenshots target `#evf-{id}` (the real form wrapper) at CSS-pixel scale for machine-stable
  diffs; animations/carets are frozen during capture.
- Wire `compare.mjs`'s exit code into CI as the Phase 0 gate.
- Breakpoints here (768 / 480) must match `Compiler::breakpoints()` so preview, compiled CSS
  and this harness all agree.
