# Plugin: Todo for BuddyPress & BuddyBoss

> **READ FIRST:** [`audit/manifest.json`](audit/manifest.json) is the canonical inventory — 0 REST endpoints, 11 AJAX handlers, 6 admin pages, 6 settings groups, 3 shortcodes, 1 CPT + 1 taxonomy, 0 custom tables, 2 cron hooks, 3 WP-CLI commands, 41 hooks fired. Use it before grepping. See also [`audit/manifest.summary.json`](audit/manifest.summary.json), [`audit/FEATURE_AUDIT.md`](audit/FEATURE_AUDIT.md), [`audit/CODE_FLOWS.md`](audit/CODE_FLOWS.md), [`audit/ROLE_MATRIX.md`](audit/ROLE_MATRIX.md), and the QA baseline at [`audit/wppqa-baseline-2026-06-05/SUMMARY.md`](audit/wppqa-baseline-2026-06-05/SUMMARY.md). Refresh via `/wp-plugin-onboard --refresh` after non-trivial changes.

> **DEV CONVENTIONS:** Follow the **`/wp-plugin-development`** skill for ALL code changes — the 16 critical admin rules, Part 6 Admin UI (card-based pattern), 3-layer design tokens, escaping/security, and dev hygiene (no em-dash in i18n, Lucide icons, no inline SVG, WPCS). This plugin currently uses the **OLD** admin wrapper and the legacy tab Settings API; any new admin UI should move toward the modern pattern in that skill, not extend the old wrapper.

## Quick reference
- **Main file**: `bp-user-todo-list.php`
- **Version**: `3.5.1`  (dev branch `3.6.0`)
- **Namespace**: none — prefixed procedural functions + `Bptodo_*` classes
- **Text domain**: `wb-todo`
- **Prefixes**: functions `bptodo_*`, options `user_todo_list_settings` / `group-todo-list-settings` / `data-retention-settings`, CPT `bp-todo`, taxonomy `todo_category`, AJAX `bptodo_*`
- **Extends**: BuddyPress / BuddyBoss (required dependency, not a Free/Pro pair)

## Admin UI wrapper — OLD
- Wrapper file: `admin/wbcom/wbcom-admin-settings.php` (`class Wbcom_Admin_Settings`) — registers the shared `wbcomplugins` menu + Our Plugins / Our Themes / Support on `admin_menu@999`.
- No INTERMEDIATE (`includes/shared-admin/class-wbcom-shared-dashboard.php`) and no NEW (`includes/admin/views/shell.php`) wrapper present.
- Primary settings page: slug `user-todo-list-settings`, parent `wbcomplugins`, registered by `Bptodo_Admin::bptodo_add_menu_page` (hook `admin_menu`). Tab UI via Settings API `do_settings_sections($tab)`.

## Key entry points
- Bootstrap: `bp-user-todo-list.php`
- Orchestrator + hook wiring: `includes/class-bptodo.php` (+ `includes/class-bptodo-loader.php`)
- Settings object: `inc/class-bptodo-globals.php` (`$bptodo`)
- Admin: `admin/class-bptodo-admin.php`
- Frontend + all AJAX: `public/class-bptodo-public.php`
- CPT/taxonomy: `admin/class-bptodo-cpt.php`
- Group extension: `public/class-bptodo-groups-extension-tab.php`
- Cron/retention: `includes/class-bptodo-data-retention.php`; emails: `includes/class-bptodo-emails.php`

## Important patterns
- **Storage is CPT + postmeta** (no custom tables). Tasks = `bp-todo` posts; status/due-date/priority/group in postmeta.
- **All AJAX is admin-ajax, logged-in only** (no `wp_ajax_nopriv`). Member self-service nonce is `bptodo-todo-nonce` (localized as `todo_ajax_object.ajax_nonce`).
- **Release build** via `grunt` (`gruntfile.js`). Distribution zip lives in `dist/`.
- Settings are read through `Bptodo_Globals`; after admin save the global `$bptodo` is rebuilt in-place.

## Onboarding findings (top, see wppqa SUMMARY)
1. Rule 10 violations: `alert()`/`confirm()` in `admin/assets/js/bp-todo-list-admin.js` (41,51,54,58) and `assets/js/bptodo-front.js` (454,460,1186) — replace with the toast/modal in `assets/js/bptodo-modern.js`.
2. `Bptodo_Admin::bptodo_save_general_settings` (admin.php:441) lacks an inline `current_user_can('manage_options')` (relies on menu gating) — add for defense in depth. Same for `wbcom_addons_cards` (wbcom-admin-settings.php:40).
3. Frontend CSS drift: 10 breakpoints (target 3) and several sub-40px tap targets — align with the `/wp-plugin-development` responsive contract.

## Recent changes
| Date | Type | Description | Files |
|---|---|---|---|
| 2026-06-05 | onboard | Generated audit/ manifest + reports + graph; wrote READ-FIRST CLAUDE.md | audit/*, CLAUDE.md |
