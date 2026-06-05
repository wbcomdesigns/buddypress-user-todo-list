# Code Flows — Todo for BuddyPress & BuddyBoss

> Source: [`audit/manifest.json`](manifest.json). v3.5.1.

## Bootstrap
```
bp-user-todo-list.php
  plugins_loaded → bptodo_load_textdomain, bptodo_check_plugin_update (runs upgrade routine + email install)
  plugins_loaded@20 → bptodo_load_bp_integrations (Bptodo_Emails, Bptodo_Data_Retention)
  bp_loaded → run_wp_bptodo_list (new Bptodo_Globals → $bptodo; load Group_Extension_Todo)
  (top level) bptodo_run() → new Bptodo_List()->run()
       └─ Bptodo_List_Loader collects + dispatches all add_action/add_filter/add_shortcode
  if WP_CLI → register 3 bptodo commands
```

## Flow A — Member creates a todo (frontend)
```
Profile Todo tab → public/todo/member/member-todo-add.php (form, nonce wp-bp-todo)
  → POST handled in Bptodo_Public (public.php:850, todo_create branch)
  → role check (bptodo_user_roles) → wp_insert_post(post_type=bp-todo) + postmeta
  → redirect / list refresh
```
Key files: `public/class-bptodo-public.php`, `public/todo/member/*`, `public/todo/partials/todo-card.php`.

## Flow B — Complete / undo / delete (AJAX)
```
todo-card.php button → assets/js/bptodo-front.js
  → admin-ajax: bptodo_complete_todo | bptodo_undo_complete_todo | bptodo_remove_todo
     (nonce bptodo-todo-nonce, localized as todo_ajax_object.ajax_nonce)
  → Bptodo_Public handler → update postmeta (todo_status / todo_complete_time) or wp_delete_post
  → bptodo_get_todo_card_html returns fresh card markup → JS swaps DOM
  → toast via bptodo-modern.js
```
AJAX chain: bptodo-front.js → wp_ajax_bptodo_* → Bptodo_Public.

## Flow C — Export own tasks (AJAX → PDF)
```
list_todo_tab_function_to_show_title() prints export link + nonce bptodo-export-todo
  → #export_my_tasks (bptodo-front.js) → bptodo_export_my_tasks
  → Bptodo_Public::bptodo_export_my_tasks (limit via filter bptodo_export_limit;
     data via filter bptodo_modify_task_export_data) → pdfobject.js renders
```

## Flow D — Group todo + report
```
Group tab (Group_Extension_Todo) → public/todo/group/{add,list,edit,overview,reports}.php
  → create POST (public.php:702, group_todo_create, nonce wp-bp-todo) → fires bptodo_group_todo_submit
  → report: get_group_todo_report AJAX (nonce bptodo-todo-nonce)
     → Bptodo_Public::bptodo_display_group_todo_report
        (gated by bptodo_enable_report_view / alter_bptodo_if_moderator_modification_enabled)
```

## Flow E — Admin settings save
```
admin.php?page=user-todo-list-settings&tab=...
  → Bptodo_Admin::bptodo_admin_options_page → do_settings_sections($tab)
  → General tab POST → bptodo_save_general_settings (admin.php:441)
     nonce bptodo-general-settings-nonce ('bptodo') → update_option('user_todo_list_settings')
     → rebuild $bptodo → transient bptodo_settings_saved → redirect
  → Group/Retention tabs use Settings API (register_setting + sanitize callbacks)
```
HARDENING: add inline current_user_can('manage_options') in bptodo_save_general_settings.

## Flow F — Cron
```
init → Bptodo_Public::setup_cron_event → schedule bptodo_todo_notification (every_six_hours)
  → bptodo_manage_todo_due_date → due-date reminders (batch via bptodo_due_date_batch_size)
Bptodo_Data_Retention → schedule bptodo_daily_cleanup (daily)
  → run_cleanup → delete/trash old todos per data-retention-settings → log bptodo_cleanup_log
```

## Key files
| File | Role |
|---|---|
| `bp-user-todo-list.php` | Bootstrap, constants, multisite gating, CLI, upgrade |
| `includes/class-bptodo.php` | Orchestrator; wires all hooks/AJAX/shortcodes |
| `includes/class-bptodo-loader.php` | Hook collector/dispatcher |
| `inc/class-bptodo-globals.php` | `$bptodo` settings object |
| `admin/class-bptodo-admin.php` | Admin pages + settings + save handlers |
| `admin/wbcom/wbcom-admin-settings.php` | OLD shared wbcom admin wrapper |
| `admin/class-bptodo-cpt.php` | CPT + taxonomy |
| `public/class-bptodo-public.php` | Frontend render + all member/group AJAX |
| `public/class-bptodo-groups-extension-tab.php` | BP group extension |
| `includes/class-bptodo-emails.php` | BP email integration |
| `includes/class-bptodo-data-retention.php` | Retention cron |

## Required settings / dependencies
- BuddyPress (or BuddyBoss) active.
- `user_todo_list_settings.enable_todo_member = yes` for member tab.
- `group-todo-list-settings.enable_todo_tab_group = yes` for group tab.
