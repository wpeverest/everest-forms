# Subscription Plan Field — UI Redesign

**Date:** 2026-05-22
**Branch:** EVF-2435-new-payment-gateway-trial-period

---

## Goal

Replace the current stacked vertical layout inside each subscription plan choice item with a pill-segmented tab control, reducing vertical footprint while improving visual clarity.

---

## Current State

Each choice `<li>` in `includes/abstracts/class-evf-form-fields.php` renders:

1. Choice header row (drag handle, radio, label input, price input, +/× buttons)
2. `<h2>Recurring Details</h2>` + interval number input + period select (always visible)
3. `<div class="evf-trail-period-wrapper">` — "Enable Trial Period" toggle
4. `<div class="evf-subscription-trail-period-option">` — Trial interval input + period select (hidden when toggle off)
5. `<div class="evf-expiry-date-wrapper">` — "Enable Expiry Date" toggle
6. `<div class="evf-subscription-expiry-date-field">` — date input (hidden when toggle off)

CSS lives in `assets/css/admin.scss` under `.everest-forms-field-option-payment-subscription-plan`.
JS init in `assets/js/admin/form-builder.js` → `init_payment_subscription_plan_field()`.

**Problems:** `<h2>` headings too heavy, everything stacked without grouping, excessive vertical height per choice.

---

## Approved Design

### Layout

A **pill/segmented tab control** placed directly below the choice header row, containing three tabs:

| Tab | Label | Always visible? |
|-----|-------|----------------|
| 1 | `↻ Recurring` | Yes — no toggle, always active |
| 2 | `Trial` | Has enable/disable toggle inside panel |
| 3 | `Expiry` | Has enable/disable toggle inside panel |

Active tab indicator: white pill with subtle box-shadow inside a gray track (`#e4e4e6`), active color `#6c47e8` (EVF purple).

**Green dot** (`#2fb344`, 5×5px) appears on the tab label when that option is enabled, so state is visible without switching tabs.

### Tab Panels

**Recurring panel:**
- Purple "Always active" badge (`background: #ede9fe; color: #6c47e8`)
- Interval number input + period select (Day/Week/Month/Year)
- No toggle — always editable

**Trial panel:**
- "Enable Trial Period" label + toggle switch (top row)
- Interval number input + period select (below toggle)
- When toggle OFF: inputs rendered but visually dimmed (`opacity: 0.38`), not hidden

**Expiry panel:**
- "Enable Expiry Date" label + toggle switch (top row)
- Flatpickr date input (below toggle)
- When toggle OFF: date input dimmed, not hidden

> **Behaviour change from current:** Current code hides Trial/Expiry inputs with `show()`/`hide()`. New design keeps inputs visible but dimmed when toggle is off (better UX — user can see what values they set before disabling).

### Visual Spec

```
┌──────────────────────────────────────────┐
│ ⠿  ○  [First tt Choice    ]  [7.00]  + × │  ← choice-row (unchanged)
├──────────────────────────────────────────┤
│  ┌────────────────────────────────────┐  │
│  │ [↻ Recurring] [ Trial ● ] [Expiry] │  │  ← pill tab strip (padding: 7px 10px)
│  └────────────────────────────────────┘  │
│  ─────────────────────────────────────── │
│  [ panel content for active tab ]        │  ← tab-panel (padding: 10px 11px)
└──────────────────────────────────────────┘
```

### Dimensions & Tokens

| Property | Value |
|----------|-------|
| Tab strip background | `#e4e4e6` |
| Tab strip border-radius | `6px` |
| Tab strip padding | `2px` |
| Active tab background | `#fff` |
| Active tab color | `#6c47e8` |
| Active tab shadow | `0 1px 3px rgba(0,0,0,.13)` |
| Inactive tab color | `#8c8f94` |
| Panel padding | `10px 11px` |
| Input border | `1px solid #dcdcde` |
| Input border-radius | `4px` |
| Toggle ON color | `#2fb344` |
| "Always active" badge bg | `#ede9fe` |
| "Always active" badge color | `#6c47e8` |
| Dot indicator | `#2fb344`, 5×5px circle |

---

## Files to Change

### 1. `includes/abstracts/class-evf-form-fields.php`

Replace the current subscription plan sub-details HTML block (lines ~1349–1420) with new structure:

```html
<div class="evf-subscription-plan-tabs">
  <!-- Tab strip -->
  <div class="evf-spt-strip">
    <div class="evf-spt-tab evf-spt-tab--recurring evf-spt-tab--active" data-tab="recurring">
      ↻ Recurring
    </div>
    <div class="evf-spt-tab evf-spt-tab--trial" data-tab="trial">
      Trial <span class="evf-spt-dot" style="display:none;"></span>
    </div>
    <div class="evf-spt-tab evf-spt-tab--expiry" data-tab="expiry">
      Expiry <span class="evf-spt-dot" style="display:none;"></span>
    </div>
  </div>

  <!-- Recurring panel -->
  <div class="evf-spt-panel evf-spt-panel--recurring">
    <span class="evf-spt-always-badge">↻ Always active</span>
    <div class="evf-spt-input-row">
      <input type="number" name="...interval_count..." value="">
      <select name="...recurring_period...">...</select>
    </div>
  </div>

  <!-- Trial panel -->
  <div class="evf-spt-panel evf-spt-panel--trial" style="display:none;">
    <div class="evf-spt-toggle-row">
      <label>Enable Trial Period</label>
      <input type="checkbox" class="evf-enable-trial-period" name="...trail_period_enable...">
      <!-- toggle chrome spans -->
    </div>
    <div class="evf-spt-input-row evf-spt-panel-detail">
      <input type="number" name="...trail_interval_count..." value="">
      <select name="...trail_recurring_period...">...</select>
    </div>
  </div>

  <!-- Expiry panel -->
  <div class="evf-spt-panel evf-spt-panel--expiry" style="display:none;">
    <div class="evf-spt-toggle-row">
      <label>Enable Expiry Date</label>
      <input type="checkbox" class="evf-enable-expiry-date" name="...subscription_expiry_enable...">
    </div>
    <div class="evf-spt-panel-detail">
      <input type="text" class="evf-radio-subscription-expiry-input ..." value="">
    </div>
  </div>
</div>
```

All existing `name` attributes and CSS classes used by JS must be preserved on the inputs.

### 2. `assets/css/admin.scss`

Under `.everest-forms-field-option-payment-subscription-plan`, replace `.evf-trail-period-wrapper`, `.evf-expiry-date-wrapper`, and related rules with new `.evf-spt-*` BEM classes matching the spec tokens above.

Remove old `.evf-subscription-plan-sub-details h2` rule.

### 3. `assets/js/admin/form-builder.js`

In `init_payment_subscription_plan_field()`:

- **Tab switching:** delegate click on `.evf-spt-tab` → hide all panels, show matching `.evf-spt-panel`, toggle `evf-spt-tab--active` class.
- **Toggle behaviour:** on `.evf-enable-trial-period` / `.evf-enable-expiry-date` change → toggle `.evf-spt-panel-detail` dimmed state (`opacity + pointer-events`) + show/hide the dot on the corresponding tab.
- **Dot init:** on page load, set dot visibility based on saved checkbox state.
- **Flatpickr init:** unchanged — still targets `.evf-radio-subscription-expiry-input`.
- **Remove:** old `show()`/`hide()` calls for `.evf-subscription-trail-period-option` and `.evf-subscription-expiry-date`.

---

## Behaviour Notes

- Default active tab: **Recurring** (always first, always active).
- Tab state is UI-only — not persisted. On page reload, Recurring tab is active by default.
- Dot indicator reflects saved toggle state on load and updates live on toggle change.
- No changes to form data model — all `name` attributes unchanged.
- Flatpickr re-init still needed for dynamically added choices (existing `evf_after_field_append` hook covers this).

---

## Out of Scope

- Frontend (form render) — no changes.
- Other payment field types (multiple, checkbox).
- Any gateway-specific logic (Stripe/PayPal recurring enable/disable).
