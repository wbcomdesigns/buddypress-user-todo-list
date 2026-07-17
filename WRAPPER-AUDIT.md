# WRAPPER-AUDIT.md — Todo for BuddyPress & BuddyBoss

**Plugin:** `buddypress-user-todo-list`
**Branch / Version:** 3.6.0
**Wrapper type:** NEW card-panel (`includes/admin/views/shell.php` present)
**Audit date:** 2026-06-05
**Auditor:** AutoVAP (READ-ONLY — no code modified)

---

## 1. Controller Contract

**Controller file:** `includes/admin/class-bptodo-admin-panel.php` (`Bptodo_Admin_Panel`)

| Method | Present? | Notes |
|--------|----------|-------|
| `get_tabs()` | YES (static) | 5 tabs: overview, general, group, retention, shortcodes |
| `register()` | YES | Hooks: `admin_menu`, `admin_menu@999`, `admin_enqueue_scripts`, `in_admin_header@1` |
| `add_menu()` | YES | Creates hub if not registered; submenu under `wbcomplugins` with `manage_options` |
| `takeover_hub_landing()` | YES (Part 16) | Runs at priority 999; strips and re-adds hub render action |
| `register_settings()` | N/A — delegated | Settings registration stays in legacy `Bptodo_Admin` class (design intent) |
| `enqueue_assets()` | YES | Screen-scoped via `is_our_screen()`; loads `admin.css`, `admin.js`, `select2` |
| `is_our_screen()` | YES (private) | Matches plugin page + hub landing by regex |
| `suppress_foreign_notices()` | YES | Hooked `in_admin_header@1`; removes `admin_notices` + `all_admin_notices` |
| `render_hub()` | YES | Includes `hub.php` via `BPTODO_PLUGIN_PATH` |
| `render_page()` | YES | Routes active tab; legacy slug back-compat map present |

**Assessment:** Contract is COMPLETE. The split between `Bptodo_Admin_Panel` (UI chrome) and the retained `Bptodo_Admin` (settings service) is intentional per the BUILD-LOG and is correctly documented.

**Minor defect (Medium):** `Bptodo_Admin` is instantiated in `includes/class-bptodo.php:190` with 3 arguments (`plugin_name`, `plugin_prefix`, `version`) but the class constructor (`admin/class-bptodo-admin.php:48`) accepts 0 parameters. PHP silently ignores excess arguments, so there is no fatal, but the call site is wrong and will produce a deprecation in strict tools. The instance is valid and all required methods (`bptodo_register_general_settings`, `bptodo_register_shortcode_settings`, `bptodo_save_general_settings`, `bptodo_variables`) are confirmed present.

---

## 2. Path-Constant Resolution — PASS

- `BPTODO_PLUGIN_PATH` is defined in `bp-user-todo-list.php:36` as `plugin_dir_path( __FILE__ )` — resolves to the **plugin root**.
- `BPTODO_PLUGIN_URL` is defined in `bp-user-todo-list.php:40` as `plugin_dir_url( __FILE__ )` — also plugin root.
- `Bptodo_Admin_Panel::enqueue_assets()` uses `BPTODO_PLUGIN_URL . 'assets/css/admin.css'` and `'assets/js/admin.js'`. Both files exist at `assets/css/admin.css` and `assets/js/admin.js` (confirmed present on disk).
- `render_hub()` uses `BPTODO_PLUGIN_PATH . 'includes/admin/views/hub.php'` — wraps in `file_exists()` guard.
- `render_page()` uses `BPTODO_PLUGIN_PATH . 'includes/admin/views/' . $view . '.php'` — also `file_exists()` guarded.
- No `_FILE_` constant is defined inside a subdirectory; the known "ads plugin" bug does not exist here.

**No 404 asset risk. Path-constant check: PASS.**

---

## 3. Wrapper Structure / Gaps

**shell.php:** Present. Renders: page-header with title + version pill, `wp-header-end` marker (prevents WP core JS from re-parenting notices), sidebar nav built from `get_tabs()`, `settings_errors()` inside content column, includes active `$view_path` via `file_exists()` guard. PASS.

**hub.php:** Present. Auto-discovers plugins from `$GLOBALS['submenu']['wbcomplugins']`. Filters legacy wrapper helper slugs (wbcom-plugins-page, wbcom-themes-page, wbcom-support-page, wbcom-license-page) via filterable `wbcom_hub_wrapper_helper_slugs`. Empty-state handled. PASS.

### Tab ↔ View map

| Tab slug | View file | File exists? |
|----------|-----------|-------------|
| overview | `overview.php` | YES |
| general | `settings-general.php` | YES |
| group | `settings-group.php` | YES |
| retention | `settings-retention.php` | YES |
| shortcodes | `settings-shortcodes.php` | YES |

No dead nav links. No orphan views. PASS.

### Legacy wrapper removal

- `admin/wbcom/` directory: **DELETED** (confirmed absent — `admin/` now contains only `assets/`, `class-bptodo-admin.php`, `class-bptodo-cli-commands.php`, `class-bptodo-cpt.php`, `class-bptodo-feedback.php`, `widget/`).
- `Wbcom_Admin_Settings` class: no references remain in PHP files.
- `wbcom_admin_setting_header` shortcode: not registered anywhere (old file deleted, no re-registration).
- `wbcom-admin-setting-nonce` and `wbcom_addons_cards` AJAX action: fully removed.
- `includes/shared-admin/`: never existed in this plugin.

**Legacy wrapper: fully gone. PASS.**

---

## 4. Option Wiring — End-to-End

### Four option groups — distinct-key verification

Each tab writes to its own option key. No key is shared across tabs.

| Tab | Option key | Key format | Any other tab writes same key? |
|-----|-----------|------------|-------------------------------|
| General | `user_todo_list_settings` | underscore | NO |
| Group Todo | `group-todo-list-settings` | hyphen | NO |
| Data Retention | `data-retention-settings` | hyphen | NO |
| Shortcodes | `user-todo-list-shortcodes` | hyphen | NO |

**The migration agent's claim is confirmed: each tab owns a distinct key. No sentinel-merge is needed. Multi-tab data-loss guard: N/A.**

### Full option wiring table

| Option key | Registered? | Rendered? | Saved + sanitized? | Read back? | Verdict |
|-----------|-------------|-----------|-------------------|------------|---------|
| `user_todo_list_settings` | `register_setting('user-todo-list-settings', 'user-todo-list-settings')` — note: this registers the hyphen-keyed option for Settings API completeness but the actual save is via custom self-POST to the underscore key | Form in `settings-general.php` reads `$bptodo->*` properties which are loaded from `user_todo_list_settings` (underscore) | `bptodo_save_general_settings()` writes `update_option('user_todo_list_settings', $settings)` with inline sanitization; `current_user_can('manage_options')` + nonce present | `Bptodo_Globals::setup_globals()` reads `get_option('user_todo_list_settings')` and populates global `$bptodo` | PASS — with one field anomaly (see below) |
| `group-todo-list-settings` | `register_setting('group-todo-list-settings', 'group-todo-list-settings', 'bptodo_sanitize_group_settings')` | Form in `settings-group.php` using `settings_fields('group-todo-list-settings')`; fields named `group-todo-list-settings[*]` | Settings API via `options.php`; sanitizer `bptodo_sanitize_group_settings()` handles all 4 checkboxes correctly | Read by `get_option('group-todo-list-settings', array())` in `settings-group.php` (display) and elsewhere in the public class | PASS |
| `data-retention-settings` | `register_setting('data-retention-settings', 'data-retention-settings', 'bptodo_sanitize_retention_settings')` | Form in `settings-retention.php` using `settings_fields('data-retention-settings')`; all 8 fields rendered and read-back from `wp_parse_args($bptodo_retention_settings, $defaults)` | Settings API via `options.php`; sanitizer `bptodo_sanitize_retention_settings()` handles all 8 keys | Read by `Bptodo_Data_Retention` class; also displayed in `overview.php` | PASS |
| `user-todo-list-shortcodes` | `register_setting('user-todo-list-shortcodes', 'user-todo-list-shortcodes')` | Informational only — no form fields | Never written (by design) | Never read (by design) | PASS — intentional |

### Field anomaly: `hide_button`

The save handler `bptodo_save_general_settings()` (`admin/class-bptodo-admin.php:260-261`) saves `$settings['hide_button']` from `$_POST['bptodo_hide_button']`. The field `bptodo_hide_button` is **not rendered** in `settings-general.php` and is **not mapped** in `Bptodo_Globals`. It is effectively orphaned in the save handler: it can never be set via the UI and is never read back by any feature code. This is a dead-code orphan rather than a data-loss risk (existing saved values for this key are preserved but have no effect). Classified as **Low** severity.

### Security fix on General tab save — VERIFIED

`Bptodo_Admin::bptodo_save_general_settings()` (`admin/class-bptodo-admin.php:246-292`):

```php
if ( isset( $_POST['bptodo-save-settings'] ) && isset( $_POST['bptodo-general-settings-nonce'] ) && wp_verify_nonce( ... 'bptodo' ) ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
```

Both the nonce verification **and** the `current_user_can('manage_options')` capability check are present. The security fix requested in the pre-audit manifest is **confirmed applied**.

---

## 5. Hygiene Checks

### alert() / confirm() — PASS

- `admin/assets/js/bp-todo-list-admin.js`: Uses `window.BPTodo.modal.confirm()` → `window.bptodoConfirm()` → ARIA live region fallback. No native `confirm()` or `alert()`.
- `assets/js/admin.js`: No native `alert()`/`confirm()`. Exposes `window.bptodoToast()` and `window.bptodoConfirm()` promise modal.
- `assets/js/bptodo-front.js` line ~455: Uses `BPTodo.modal.confirm()` for the delete flow; falls through to `performRemoveTodo()` (server-nonce-gated) if helper absent. No native `confirm()`.
- `assets/js/bptodo-front.js` line ~1186: Uses `window.BPTodo.toast.error()` for invalid-data path. No native `alert()`.

The Rule 10 violations documented in the onboarding CLAUDE.md are **confirmed fixed**.

### CSS tokens

`assets/css/admin.css` defines a complete `:root` token set (`--bptodo-admin-accent`, `--bptodo-admin-white`, etc., lines 17-42). All structural color uses reference those tokens. 9 raw hex instances remain outside the `:root` block: `#fff` (lines 127, 173, 178, 576, 584, 609, 769, 1077) and `#92400e` (line 684). The `#fff` occurrences should be `var(--bptodo-admin-white)` and `#92400e` (amber-900, a one-off warn text) should be a named token. These are all **Polish** severity.

### Screen-scoped enqueue — PASS

`enqueue_assets()` calls `get_current_screen()` and gates on `is_our_screen()` before enqueueing anything. Assets load only on the plugin's page and the shared hub landing.

### Tap target / responsive

Not tested interactively (READ-ONLY audit). The CSS includes `bptodo-btn` with `padding: 10px 20px` and toggle switches (`wb-switch`) — these are consistent with the pre-existing design system. Pre-existing issues with 10 breakpoints and sub-40px targets (documented in CLAUDE.md) are outside the scope of this wrapper migration audit.

---

## 6. Member-Facing AJAX Handlers — Not Broken

All 8 member AJAX actions are registered as `wp_ajax_*` only (no `wp_ajax_nopriv_*` anywhere in the codebase). Each handler calls `check_ajax_referer()` or `wp_verify_nonce()` with the `bptodo-todo-nonce` (or `bptodo-export-todo` for export). The wrapper migration did not touch `includes/class-bptodo.php`'s `define_public_hooks()` registration block — all 8 AJAX add_actions are present and unmodified. Confirmed not broken.

---

## 7. Severity-Ranked Findings

| # | Severity | Finding | File:line | Suggested fix |
|---|----------|---------|-----------|---------------|
| 1 | Medium | `Bptodo_Admin` constructor takes 0 parameters but is called with 3 arguments (`plugin_name`, `plugin_prefix`, `version`). Harmless in PHP but semantically wrong; flagged by static analysis tools. | `includes/class-bptodo.php:190` | Remove the 3 arguments from the instantiation call: `new Bptodo_Admin()`. |
| 2 | Medium | `register_setting('user-todo-list-settings', 'user-todo-list-settings')` registers the hyphen-keyed option for Settings API. The actual General tab save writes to the underscore-keyed `user_todo_list_settings`. These are two distinct WP option keys. The hyphen key is registered but never written or read — it is a dead registration. No data-loss risk, but a confusing naming artifact. | `admin/class-bptodo-admin.php:180` | Document the intent in a code comment, or remove the `register_setting` call for the hyphen key if it is confirmed unused. |
| 3 | Low | `hide_button` key: saved by `bptodo_save_general_settings()` from `$_POST['bptodo_hide_button']` but the field is not rendered in `settings-general.php` and is not mapped in `Bptodo_Globals`. Any previously saved value is silently preserved but unmodifiable from the UI and has no consumer in plugin code. | `admin/class-bptodo-admin.php:260-261` | Either render the field in `settings-general.php` and map it in `Bptodo_Globals`, or remove the dead save branch. |
| 4 | Polish | 9 raw hex values in `assets/css/admin.css` (8 × `#fff`, 1 × `#92400e`) should use CSS tokens (`--bptodo-admin-white`, plus a new `--bptodo-admin-warn-text` token for the amber-900 warning text). | `assets/css/admin.css:127,173,178,576,584,609,684,769,1077` | Replace `#fff` with `var(--bptodo-admin-white)` and extract `#92400e` as a token. |

---

## 8. Overall Verdict

**SHIP-CLEAN — with two Medium items to tidy before next release.**

The wrapper migration is functionally correct:

- The card-panel controller (`Bptodo_Admin_Panel`) implements the full contract.
- All path constants resolve to the plugin root — no 404 asset risk.
- All 5 tabs have matching view files; no dead or orphan views.
- Each of the 4 option groups owns a distinct key — no multi-tab data-loss risk; no sentinel merge needed.
- The security fix (`current_user_can('manage_options')` in the General save handler) is confirmed present.
- Native `alert()`/`confirm()` are fully replaced with `BPTodo.modal` / `BPTodo.toast` helpers.
- The legacy `admin/wbcom/` wrapper is fully deleted with zero dangling references.
- All 8 member AJAX handlers remain nonce + login gated; none exposed to `wp_ajax_nopriv`.

The two Medium items (#1 wrong constructor arity, #2 dead `register_setting` for hyphen key) are static-analysis / hygiene issues — not runtime bugs. The Low item (#3 `hide_button` orphan) is a dead code path. All are safe to defer to the next PR; they do not block the current release.
