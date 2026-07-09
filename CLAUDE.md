# Plugin: Todo for BuddyPress & BuddyBoss

> **READ FIRST:** [`audit/manifest.json`](audit/manifest.json) is the canonical inventory — 0 REST endpoints, 10 AJAX handlers, 6 admin pages, 6 settings groups, 3 shortcodes, 1 CPT + 1 taxonomy, 0 custom tables, 2 cron hooks, 3 WP-CLI commands, 41 hooks fired. Use it before grepping. See also [`audit/manifest.summary.json`](audit/manifest.summary.json), [`audit/FEATURE_AUDIT.md`](audit/FEATURE_AUDIT.md), [`audit/CODE_FLOWS.md`](audit/CODE_FLOWS.md), [`audit/ROLE_MATRIX.md`](audit/ROLE_MATRIX.md), and the QA baseline at [`audit/wppqa-baseline-2026-06-05/SUMMARY.md`](audit/wppqa-baseline-2026-06-05/SUMMARY.md). Refresh via `/wp-plugin-onboard --refresh` after non-trivial changes.

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
| 2026-07-09 | perf | Frontend assets now load only where a todo template renders (member/group Todo tab via slug in URI, or a page with a todo shortcode); removed the `isset($post->post_type)` catch-all that loaded CSS/JS on nearly every singular page. Added missing `bptodo_list` to the shortcode gate. Verified: 5 assets on todo tab + `[bptodo_list]` page, 0 on Shop | admin/class-bptodo-admin.php |
| 2026-07-09 | cleanup | Removed dead edit-modal (unreachable since the modern revamp redirects edits to the full-page /add form): `wp_footer` handler + `bptodo_add_div_edit_form()` + `bptodo_edit_form_popup()` AJAX loader + JS `initEditPopup` `.trigger` opener/`initModalEvents`; consolidated the duplicated add-category toggle handlers into one document-delegated handler (also fixes the `add-cat-icon` swap on the full-page form). AJAX handlers 11 to 10 | includes/class-bptodo.php, public/class-bptodo-public.php, assets/js/bptodo-front.js(+min), audit/manifest*.json |
| 2026-07-09 | docs | Reconciled docs/shortcodes.md to the code: dropped the never-implemented `[bptodo_all]` (use `[bptodo_list user_id="all"]`), documented `[bptodo_by_category]` as category-only, corrected `[bptodo_list]` params (per_page/show_add, not limit/show_export; added priority/orderby/order). Manifest `bptodo_list` attrs filled in | docs/shortcodes.md, audit/manifest.json |
| 2026-07-09 | feature | Added admin/demo-tasks.json (46 varied tasks using the loader's date tokens) so `wp bptodo generate` produces diverse demo data instead of the 12 hardcoded fallback | admin/demo-tasks.json |
| 2026-07-03 | perf | Big-site readiness: shared server-side pagination helpers (bptodo_get_todos_page, bptodo_count_todos, bptodo_get_group_member_task_stats, bptodo_filter_group_member_ids) + link pager partial; group Overview stats via aggregate SQL (was posts_per_page -1 + N+1 meta); group list real LIMIT/OFFSET pagination (was do-while fetch-all); member My Tasks real pagination (was silent 500 cap, bptodo_member_list_limit removed) | includes/bptodo-functions.php, public/todo/partials/pagination.php, public/todo/group/{overview,list}.php, public/todo/member/member-todo-list.php |
| 2026-07-03 | bug-fix | Fired dead filters bptodo_exclude_modrator_view / bptodo_exclude_modrator_edit inside their setting-reader functions (were registered but never applied) | includes/bptodo-functions.php |
| 2026-07-03 | a11y | aria-labels on 12 wb-switch admin toggles + legacy edit-popup fields; scoped the site-wide a:focus/button:focus outline kill to plugin containers; :focus-visible rings for inputs/selects/search/filter/task-dropdown/option-input; rebuilt min+RTL | includes/admin/views/settings-{general,group,retention}.php, public/todo/group/edit.php, public/todo/member/member-todo-edit.php, public/todo/partials/todo-form-fields.php, assets/css/* |
| 2026-07-03 | bug-fix | Sanitize profile_menu_slug at source (multi-word labels broke group tab/subnav/assets); slug-derived URLs fixed | inc/class-bptodo-globals.php, public/class-bptodo-groups-extension-tab.php, public/todo/group/{add,overview}.php |
| 2026-07-03 | bug-fix | Dark mode: BuddyX --bx-color-* token cascade, [data-bx-mode] dark selectors (BuddyX 5.1+/Reign 8), retokenized group overview/report CSS, datepicker + core/vendor UI (wp_editor, wplink, media modal, Select2) token compat | assets/css/bptodo-design-system*.css, assets/css/bptodo-front*.css |
| 2026-07-03 | bug-fix | Confirm modal now carries bptodo-wrapper class (Cancel/Delete buttons were unstyled); Clear filters button uses ghost component; docs URL corrected | assets/js/bptodo-modern*.js, public/todo/group/list.php, public/todo/member/member-todo-list.php, includes/admin/views/shell.php |
| 2026-06-05 | onboard | Generated audit/ manifest + reports + graph; wrote READ-FIRST CLAUDE.md | audit/*, CLAUDE.md |
