# Feature Audit — Todo for BuddyPress & BuddyBoss

> Source of truth: [`audit/manifest.json`](manifest.json). v3.5.1, branch 3.6.0, text domain `wb-todo`.
> Storage model: CPT `bp-todo` (one post per task) + taxonomy `todo_category`; all task data in postmeta. **No custom DB tables.**

## 1. Frontend surfaces (member + group)

| Surface | Where | Roles | Gating setting |
|---|---|---|---|
| Member profile "Todo" tab | BuddyPress profile component; slug from `profile_menu_slug` (filter `bptodo_profile_menu_slug`) | logged-in member | `user_todo_list_settings.enable_todo_member`, `bptodo_user_roles[]` |
| Group "Todo" tab | `Group_Extension_Todo` (BP_Group_Extension), `public/class-bptodo-groups-extension-tab.php` | group members/mods | `group-todo-list-settings.enable_todo_tab_group` + `mod_enable`/`list_enable`/`view_enable` |
| Member widget | `BP_ToDo_Member_ToDo_Widget` (`admin/widget/class-user-todo.php`) | any | widget placement |
| Shortcode views | `[bptodo_by_category]`, `[bptodo_list]` | per template | n/a |

Templates: `inc/templates/{archive,single}-bp-todo.php`, `public/todo/{member,group,partials}/*.php`, `public/todo/bptodo-*-template.php`.

## 2. AJAX handlers (admin-ajax, all logged-in)

| Action | Handler | Nonce | Purpose |
|---|---|---|---|
| `bptodo_export_my_tasks` | `Bptodo_Public::bptodo_export_my_tasks` | `bptodo-export-todo` | Export own todos (PDF/print) |
| `bptodo_remove_todo` | `Bptodo_Public::bptodo_remove_todo` | `bptodo-todo-nonce` | Delete task |
| `bptodo_complete_todo` | `Bptodo_Public::bptodo_complete_todo` | `bptodo-todo-nonce` | Complete task |
| `bptodo_undo_complete_todo` | `Bptodo_Public::bptodo_undo_complete_todo` | `bptodo-todo-nonce` | Un-complete task |
| `bptodo_add_todo_category_front` | `Bptodo_Public::bptodo_add_todo_category_front` | `bptodo-todo-nonce` | Add category (gated by `allow_user_add_category`) |
| `bptodo_edit_form_popup` | `Bptodo_Public::bptodo_edit_form_popup` | `bptodo-todo-nonce` | Return edit form |
| `bptodo_update_form_popup` | `Bptodo_Public::bptodo_update_form_popup` | `bptodo-todo-nonce` | Save edits |
| `bptodo_get_todo_card_html` | `Bptodo_Public::bptodo_get_todo_card_html` | `bptodo-todo-nonce` | Refresh card HTML |
| `get_group_todo_report` | `Bptodo_Public::bptodo_display_group_todo_report` | `bptodo-todo-nonce` | Group member report |
| `bptodo_network_deactivate` | `bptodo_handle_network_deactivation` | `bptodo_network_nonce` | Network deactivate (advanced multisite only) |
| `wbcom_addons_cards` | `Wbcom_Admin_Settings::wbcom_addons_cards_links` | `wbcom_admin_setting_nonce` | Shared wbcom admin card markup |

No `wp_ajax_nopriv_*` registrations — every endpoint requires login.

## 3. REST endpoints
_None._ Plugin uses admin-ajax exclusively.

## 4. Admin pages & settings

Menu: top-level **`wbcomplugins`** ("WB Plugins") shared across Wbcom plugins, `manage_options`, registered on `admin_menu` by `Bptodo_Admin::bptodo_add_menu_page`.

| Page slug | Label | Registered by |
|---|---|---|
| `wbcomplugins` | WB Plugins (top) / General | `Bptodo_Admin` (admin.php:223-224) |
| `user-todo-list-settings` | Todo Lists (primary settings) | `Bptodo_Admin` (admin.php:226) |
| `wbcom-plugins-page` | Our Plugins | `Wbcom_Admin_Settings` (admin_menu@999) |
| `wbcom-themes-page` | Our Themes | `Wbcom_Admin_Settings` |
| `wbcom-support-page` | Support | `Wbcom_Admin_Settings` |

Settings page is the **OLD wbcom wrapper** + legacy Settings API tabs (Welcome / General / Group Todo / Data Retention / Shortcodes) rendered via `do_settings_sections($tab)`.

Settings option keys:
- `user_todo_list_settings` — profile_menu_label(_plural), hide_button, enable_todo_member, allow_user_add_category, send_notification, send_mail, req_duedate, bptodo_user_roles[]
- `group-todo-list-settings` — enable_todo_tab_group, mod_enable, list_enable, view_enable
- `data-retention-settings` — enable_retention, enable_group_retention, enable_personal_retention, permanent_delete, email_notification, cleanup_type, retention_days, notification_email
- `user-todo-list-shortcodes` — informational reference tab
- `bptodo_version`, `bptodo_cleanup_log` — internal

## 5. Shortcodes
`[bptodo_by_category category="..."]`, `[bptodo_list]`, `[wbcom_admin_setting_header]` (admin header only).

## 6. Content types
- CPT `bp-todo` (private), `admin/class-bptodo-cpt.php:80`
- Taxonomy `todo_category` (hierarchical), `admin/class-bptodo-cpt.php:113`
- Key postmeta: todo_due_date, todo_status, todo_priority, todo_group_id, todo_user_id, todo_creator_id, todo_complete_time, todo_completed_date, todo_category; usermeta: todo_mail_setting

## 7. JS modules
`bptodo-view.js` (modern layout, @wordpress/interactivity), `bptodo-front.js` (legacy layout), `bptodo-modern.js` (toast/modal/loading), select2, datatables; admin: `assets/js/admin.js` (card panel), `admin/assets/js/bp-todo-list-admin.js` (network admin only). No icon font (core Dashicons). `tempust`, `pdfobject` and `selectize` were removed as dead code.

## 8. CSS modules
Design system + components + front (with .min and RTL variants), plus jquery-ui, select2, datatables. NOTE: 10 distinct breakpoints (target 3) and several sub-40px tap targets — see wppqa baseline.

## 9. Email templates
BuddyPress email integration via `Bptodo_Emails` — due-date reminder + assignment notification (installed in upgrade routine).

## 10. Cron jobs
| Hook | Interval | Handler |
|---|---|---|
| `bptodo_daily_cleanup` | daily | `Bptodo_Data_Retention::run_cleanup` |
| `bptodo_todo_notification` | every_six_hours (custom) | `Bptodo_Public::bptodo_manage_todo_due_date` |

## 11. DB tables
_None._ Postmeta/usermeta only.

## 12. Integrations
- **BuddyPress / BuddyBoss** — required; group extension, profile tab, BP emails, BP settings notifications tab.
- **LearnDash** — `ld_dashboard` shortcode detection in asset enqueue (`admin/class-bptodo-admin.php:104`).
- Multisite — BP_ROOT_BLOG subsite gating; optional advanced multisite (off by default).

## 13. Capabilities
- `manage_options` — all admin pages + settings save.
- `upload_files` — dynamically granted to subscribers on todo pages via `user_has_cap` filter.
- `manage_network_plugins` — network deactivate (advanced multisite only).

## 14. WP-CLI
`wp bptodo generate member-todos`, `wp bptodo generate group-todos`, `wp bptodo cleanup`.
