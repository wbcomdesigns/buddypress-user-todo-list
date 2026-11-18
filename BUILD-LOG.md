# BUILD-LOG — Todo for BuddyPress & BuddyBoss

**Task:** Migrate the admin wrapper from the OLD Wbcom tab wrapper to the new card-panel wrapper.
**Plugin:** `buddypress-user-todo-list` · **Branch:** `3.6.0` · **Version:** `3.5.1` → `3.6.0`
**Pattern source:** `buddypress-contact-me` (wbcom-pro) + `references/wbcom-wrapper-migration.md`
**Date:** 2026-06-05

---

## Summary

The plugin's admin used the OLD shared wrapper (`admin/wbcom/wbcom-admin-settings.php`,
`class Wbcom_Admin_Settings`) plus a legacy `nav-tab-wrapper` Settings-API page rendered by
`Bptodo_Admin::bptodo_admin_options_page`. That whole surface was replaced with the jetonomy/
contact-me card-panel: a single submenu under the shared `wbcomplugins` hub, a sidebar-nav
shell, an Overview dashboard, and one card-styled view per settings group.

All option keys, option groups, sanitizers, nonces, and the custom General-tab save path were
preserved byte-for-byte. This is a UX-only migration + the approved safe fixes — zero data-shape
changes.

---

## Files DELETED (git rm — 24 files)

Legacy wrapper (whole dir):
- `admin/wbcom/wbcom-admin-settings.php` (carried the `wbcom_addons_cards` AJAX handler)
- `admin/wbcom/assets/css/wbcom-admin-setting.css`
- `admin/wbcom/assets/js/wbcom-admin-setting.js`
- `admin/wbcom/templates/wbcom-license-page.php`
- `admin/wbcom/templates/wbcom-plugins-page.php`
- `admin/wbcom/templates/wbcom-support-page.php`

Old tab partials (rendered the `.wbcom-*` markup, now superseded by the new views):
- `admin/inc/bptodo-general-settings.php`
- `admin/inc/bptodo-group-settings.php`
- `admin/inc/bptodo-retention-settings.php`
- `admin/inc/bptodo-shortcodes-settings.php`
- `admin/inc/bptodo-welcome-page.php`

Old admin assets (only used by the deleted page / retired dependency):
- `admin/assets/css/bptodo-admin.css`, `bptodo-admin.min.css`, `bptodo-admin-rtl.css`
- `admin/assets/css/selectize.css`, `admin/assets/js/selectize.min.js` (selectize retired → select2)
- `admin/assets/fonts/*` (6 FontAwesome font files — wrapper-only)
- `admin/assets/images/todo-list.png` (wrapper-only, unreferenced)

## Files CREATED (10)

- `includes/admin/class-bptodo-admin-panel.php` — controller (`Bptodo_Admin_Panel`): `get_tabs()`,
  `register()`, `add_menu()`, `takeover_hub_landing()` (Part 16), `enqueue_assets()` (screen-scoped),
  `suppress_foreign_notices()`, `is_our_screen()`, `render_hub()`, `render_page()`.
- `includes/admin/views/page-layout.php` — header card + sidebar nav + body slot + `wp-header-end` marker.
- `includes/admin/views/hub.php` — WB Plugins hub landing (wrapper-helper-slug filtered).
- `includes/admin/views/overview.php` — stats grid (COUNT(*) via `WP_Query` found_posts) + config snapshot + quick actions.
- `includes/admin/views/settings-general.php` — General (custom self-POST, preserved).
- `includes/admin/views/settings-group.php` — Group Todo (options.php Settings API).
- `includes/admin/views/settings-retention.php` — Data Retention (options.php Settings API).
- `includes/admin/views/settings-shortcodes.php` — Shortcodes reference (informational).
- `assets/css/admin.css` — token-driven card CSS (ported from contact-me, `bcm`→`bptodo`, license block dropped, plugin extras added: wb-switch toggle, status pills, checkline, input helpers, shortcode tables, select2 sizing).
- `assets/js/admin.js` — `window.bptodoToast()` + `window.bptodoConfirm()` (promise modal) + select2 role picker init + `data-bptodo-confirm` interceptor.

## Files MODIFIED (6)

- `includes/class-bptodo.php` — dropped the `admin/wbcom/...` require; added the new controller
  require; in `define_admin_hooks()` UN-wired the legacy UI methods (menu/enqueue/admin-css/notice
  suppression) and now `new Bptodo_Admin_Panel()->register()`. Kept settings registration, the
  custom save handler, and the frontend `bptodo_variables` enqueue wired to the legacy class.
- `admin/class-bptodo-admin.php` — retained as a non-UI service class. Removed the dead UI methods
  (`bptodo_add_menu_page`, `bptodo_admin_options_page`, `bptodo_plugin_settings_tabs`,
  `bptodo_admin_variables`, legacy `enqueue_styles`/`enqueue_scripts`, `bptodo_show_notice`,
  `bptodo_hide_all_admin_notices_from_setting_page`, the five `bptodo_*_content` includes + their
  `add_settings_section` calls, and the unused `$plugin_slug`/`$plugin_settings_tabs` props).
  Class docblock rewritten to its post-migration scope. **Safe fix:** added
  `current_user_can( 'manage_options' )` to `bptodo_save_general_settings()`. Ran phpcbf on the
  retained code (75 → 21 WPCS errors; the 21 left are all pre-existing in untouched methods).
- `admin/assets/js/bp-todo-list-admin.js` — kept (still loaded on the network plugins screen for
  "Network Deactivate"). Removed the dead selectize init (page gone) and replaced native
  `confirm()`/`alert()` with `BPTodo.modal`/`BPTodo.toast` (graceful fallbacks).
- `assets/js/bptodo-front.js` — replaced the native `confirm()` fallback (line ~460) with the
  promise-based `BPTodo.modal.confirm({...})` (also fixed a latent wrong-signature call), and the
  native `alert('Invalid data')` fallback (line ~1186) with `BPTodo.toast.error`.
- `bp-user-todo-list.php` — Version `3.5.1`→`3.6.0`; `BPTODO_VERSION` `3.5.1`→`3.6.0`; network
  enqueue now loads `bptodo-modern-js` + its CSS as a dependency so the modal/toast helper is
  available on the network screen.
- `readme.txt` — `Stable tag: 3.5.1`→`3.6.0` + a `= 3.6.0 =` changelog stub.

---

## Option keys preserved (old → new proof)

`register_setting` + `update_option` keys are byte-identical before/after:

| Tab | Option key | Save mechanism (unchanged) | Sanitizer (unchanged) |
|-----|-----------|----------------------------|------------------------|
| General | `user_todo_list_settings` | custom self-POST (`bptodo-save-settings`, nonce `bptodo`) → `Bptodo_Admin::bptodo_save_general_settings` | inline in handler |
| Group Todo | `group-todo-list-settings` | Settings API (options.php) | `bptodo_sanitize_group_settings` |
| Data Retention | `data-retention-settings` | Settings API (options.php) | `bptodo_sanitize_retention_settings` |
| Shortcodes | `user-todo-list-shortcodes` | informational (registered, never written) | n/a |

General-tab POST field names diffed old-vs-new: **identical** (all 11 `$_POST[...]` keys, incl.
`bptodo_hide_button` which the form still does not render — exactly as before, so the conditional
`isset()` write keeps the same behavior).

**Note on Playbook 7.1 (sentinel-guarded merge):** NOT required here. Each settings tab owns a
*distinct* option key in its *own* form — no single option key spans multiple tabs — so there is
no cross-tab overwrite/data-loss risk. The sentinel pattern only applies when one option is split
across tabs. Documented intentionally; the existing per-tab save mechanisms were kept verbatim.

## Safe fixes applied

1. **Capability check** added to `Bptodo_Admin::bptodo_save_general_settings()`
   (`current_user_can('manage_options')` before writing the option).
2. **`wbcom_addons_cards` cap gap** — resolved by deletion: the handler lived only in
   `admin/wbcom/wbcom-admin-settings.php`, which is removed. No external references remained
   (grep-verified). The whole shared `wp_ajax_wbcom_addons_cards` surface is gone.
3. **Native alert/confirm removed** — admin (`bp-todo-list-admin.js`) and front
   (`bptodo-front.js`) now use the modern `BPTodo.modal`/`BPTodo.toast` helper (per the brief's
   preference to wire to the existing `assets/js/bptodo-modern.js`). The new `admin.js` ships its
   own `bptodoToast`/`bptodoConfirm` for the settings screen.
4. **Version bump** 3.5.1 → 3.6.0 across plugin header, constant, and readme Stable tag +
   changelog stub.

## Verify results

- `php -l` — clean on every created/modified PHP file (11 files).
- `node --check` — clean on `admin.js`, `bp-todo-list-admin.js`, `bptodo-front.js`.
- WPCS (wpcs MCP):
  - New controller + all 7 view files: **0 errors** (alignment warnings auto-fixed with phpcbf).
  - Retained legacy `admin/class-bptodo-admin.php`: 136 errors at HEAD → **21 after migration**;
    every remaining error is pre-existing in code I did not change (`bptodo_variables`,
    `bp_todo_get_localized_strings`, the save handler's Yoda/inline-comment style). **No new
    errors introduced.**
- Dangling-reference grep (Playbook 2.1): no PHP references the deleted wrapper paths/handles or
  deleted admin assets (only comment text mentions them).
- Template-variable contract (Playbook 12.2): every `page-layout.php` `@var` (`$active`, `$bptodo_tabs`,
  `$page_url`, `$view`, `$view_path`) is set by `render_page()`.
- Isolated runtime load: `Bptodo_Admin_Panel` instantiates, `get_tabs()` returns the five tabs
  (overview, general, group, retention, shortcodes), all required methods present — no fatal.
- ABSPATH guard present in every new PHP file.

---

## TODOs deferred (out of scope)

- **Frontend CSS drift (logged, NOT fixed):** `assets/css/bptodo-front.css` has ~10 responsive
  breakpoints (target is 3) and several sub-40px tap targets. Out of scope for the wrapper
  migration per the brief; align to the `/wp-plugin-development` responsive contract in a
  dedicated frontend pass.
- **Retained legacy class WPCS (21 pre-existing errors):** Yoda conditions, inline-comment
  full-stops, the `$_SERVER['REQUEST_URI']` esc_url in `bptodo_variables`, and the
  `cdnjs.cloudflare.com` Font Awesome CDN enqueue (Playbook flags third-party CDN — should be
  bundled or replaced with dashicons). Not introduced by this migration; left for a focused PCP
  sweep so this change stays UX-only.
- **Member AJAX handlers** (`public/class-bptodo-public.php`, 6 self-service handlers) — left
  as-is per the brief: nonce + login gated, no `nopriv`, correct as designed.
- **Browser smoke test not run** — plugin is intentionally NOT activated on this site (BuddyPress
  is not active here, and the brief forbids activation/DB writes). The card-panel renders should
  be verified in a browser on a BuddyPress-active site before release (Playbook Part 13).

## Needs human eyes

- The General-tab success banner now flows through `add_settings_error()` registered in
  `render_page()` (before the shell's `settings_errors()`), keyed to the `general` group. Confirm
  visually that "Settings saved." appears in-panel after a General save round-trip on a live
  BuddyPress site.
- Network "Network Deactivate" path only runs under `BPTODO_ENABLE_ADVANCED_MULTISITE` (off by
  default). Verify the modal helper loads on the network plugins screen if/when that flag is on.

---

## 2026-06-05 — Wiring-bug fix pass (WRAPPER-AUDIT findings #1, #2, #3)

Three wiring defects from `WRAPPER-AUDIT.md` (Severity-Ranked Findings table). No behaviour change for
end users; all three are dead/incorrect wiring removed or corrected.

### #1 (Medium) — `Bptodo_Admin` constructor arity mismatch
- **File:** `includes/class-bptodo.php:~190`
- **Was:** `new Bptodo_Admin( $this->get_plugin_name(), $this->get_plugin_prefix(), $this->get_version() )`
- **Now:** `new Bptodo_Admin()`
- **Why fix (a) not (b):** `Bptodo_Admin` has no `plugin_name` / `plugin_prefix` / `version`
  properties and uses none of those values. Its only version reference is the `BPTODO_VERSION`
  constant (and `BPTODO_PLUGIN_URL`) read directly inside `bptodo_variables()`. The constructor is
  a genuine no-arg constructor, so the minimal correct fix is to stop passing the three unused
  arguments (PHP was silently discarding them; strict static analysis flags it). Added a code
  comment documenting why no args are passed.

### #2 (Medium) — dead `register_setting` for the hyphen-keyed General option
- **File:** `admin/class-bptodo-admin.php:~180` (`bptodo_register_general_settings()`)
- **Removed:** `register_setting( 'user-todo-list-settings', 'user-todo-list-settings' );`
- **Why:** This registered a HYPHEN-keyed option `user-todo-list-settings` that is never written or
  read. The General tab does not use the Settings API at all — it saves the UNDERSCORE key
  `user_todo_list_settings` via the custom self-POST handler `bptodo_save_general_settings()`
  (`update_option( 'user_todo_list_settings', ... )`) and reads it back through `Bptodo_Globals`.
  Confirmed no `settings_fields( 'user-todo-list-settings' )` exists in any view, and there were no
  matching `add_settings_section()` / `add_settings_field()` calls to remove (the docblock already
  noted "no add_settings_section() callbacks are needed"). Updated the method docblock to record
  that the General tab intentionally bypasses the Settings API. The Group Todo and Data Retention
  `register_setting()` calls (with their sanitizers) are untouched.

### #3 (Low) — orphan `hide_button` save
- **File:** `admin/class-bptodo-admin.php:~260` (`bptodo_save_general_settings()`)
- **Removed:** the `if ( isset( $_POST['bptodo_hide_button'] ) ) { $settings['hide_button'] = ... }`
  branch.
- **Why:** `bptodo_hide_button` is never rendered in `settings-general.php`, never mapped in
  `Bptodo_Globals`, and never read anywhere in PHP or JS (grep confirmed the only two hits were
  these two lines). It could never be set from the UI and had no consumer — a pure orphan write.
  Removed the dead branch.

### Verification
- `php -l includes/class-bptodo.php` → No syntax errors detected.
- `php -l admin/class-bptodo-admin.php` → No syntax errors detected.
- WPCS (wpcs MCP `wpcs_check_file`) on both files, compared against the pre-edit `HEAD` copies:
  - `includes/class-bptodo.php`: 30 errors before / 30 errors after — **0 new errors** (all
    pre-existing: FileName convention, `dirname(__FILE__)`, trailing whitespace, brace spacing).
  - `admin/class-bptodo-admin.php`: 21 errors + 3 warnings before / 21 errors + 3 warnings after —
    **0 new errors** (all pre-existing: Yoda conditions, inline-comment full-stops,
    `$_SERVER['REQUEST_URI']` sanitization, CDN/footer enqueue warnings).
- Real General-tab option path confirmed intact: save `update_option( 'user_todo_list_settings', $settings )`
  at `admin/class-bptodo-admin.php:282`; read `get_option( 'user_todo_list_settings' )` at
  `inc/class-bptodo-globals.php:55`. No lingering `register_setting` / `settings_fields` for the
  removed hyphen key.
- Not committed / not pushed, per the brief.
