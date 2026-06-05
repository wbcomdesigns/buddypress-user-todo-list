# wppqa Baseline — Todo for BuddyPress & BuddyBoss

- Plugin: buddypress-user-todo-list (v3.5.1, branch 3.6.0)
- Date: 2026-06-05
- Tools run: `wppqa_check_plugin_dev_rules`, `wppqa_check_rest_js_contract`, `wppqa_check_wiring_completeness`

## Per-check result

| Check | Passed | Failed | Skipped | Verdict |
|---|---|---|---|---|
| plugin_dev_rules | 0 | 23 | 0 | Mixed — see triage |
| rest_js_contract | 0 | 0 | 1 | Skipped (no REST endpoints) |
| wiring_completeness | 0 | 0 | 1 | Skipped (templates/ not present in expected shape) |

> Release-readiness gate: plugin_dev_rules failed > 0 → NOT release-ready until the real findings below are addressed (alert/confirm + the 2 cap-check hardening notes). The bulk of the 23 are triaged false positives or low-severity CSS warnings.

## plugin_dev_rules — 23 errors, triaged

### Real (fix before release)
1. **Browser `alert()` / `confirm()` (Rule 10)** — 7 sites:
   - `admin/assets/js/bp-todo-list-admin.js:41` (confirm), `:51,:54,:58` (alert)
   - `assets/js/bptodo-front.js:454,:460` (confirm), `:1186` (alert)
   Replace with the modern toast/modal already shipped in `assets/js/bptodo-modern.js`.
2. **Nonce-no-cap on a genuine admin handler** — `admin/class-bptodo-admin.php:441` (`bptodo_save_general_settings`). Page is `manage_options`-gated, but add an inline `current_user_can('manage_options')` for defense in depth.
3. **Nonce-no-cap on shared wbcom AJAX** — `admin/wbcom/wbcom-admin-settings.php:40` (`wbcom_addons_cards`). Add a cap check.

### False positives (per environment note: nonce-no-cap on member self-service + lifecycle hooks)
- `public/class-bptodo-public.php:702,850,1262,1313,1498,1731` — logged-in member self-service handlers (todo CRUD / export / group report) on the member's own data. Correctly nonce-protected. NO `wp_ajax_nopriv` registrations exist, so login is required. wppqa's heuristic does not recognize member-scoped (non-admin) endpoints.
- `includes/class-bptodo-activator.php:60,64,166,175` and `includes/class-bptodo-deactivator.php:58,62,97,106` — run only inside `register_activation_hook` / `register_deactivation_hook`; WordPress already gates these with the `activate_plugins` capability.

### Low-severity warnings (defer)
- 10 distinct CSS breakpoints (frontend-responsive Rule 1: target 3).
- Multiple `<40px` tap-target buttons across `bptodo-front*.css` / `bptodo-components*.css`.

## rest_js_contract — skipped
No `register_rest_route` calls in the plugin; the plugin uses admin-ajax exclusively. No envelope-mismatch risk.

## wiring_completeness — skipped
The checker scans `templates/`; this plugin renders via `inc/templates/`, `public/todo/`, and Settings API tab partials, so the heuristic skipped. Manual review: all 4 settings groups (general, group, retention, shortcodes) are read by `Bptodo_Globals` / `Bptodo_Data_Retention` / templates — no obviously orphaned settings found.
