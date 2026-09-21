# Hooks and filters

These are the extension points verified in the plugin source. Names are exact.

## Filters

| Filter | Purpose |
| --- | --- |
| `wbbptodo_slug` | Change the profile/group URL slug derived from the todo label. |
| `bptodo_export_limit` | Max number of tasks included in a member CSV export (default 1000). |
| `bptodo_due_date_batch_size` | Batch size for the due-date reminder job (default 100). |
| `bptodo_notification_cron_schedule_interval` | Interval in seconds for the reminder cron (default 21600, every 6 hours). |
| `bptodo_member_list_limit` | Limit for the member task list query. |
| `bptodo_completed_list_limit` | Limit for the completed task list. |
| `bptodo_change_todo_edit_button` | Filter the member edit-button markup. |
| `bptodo_change_submit_button` | Filter the member submit-button markup. |
| `bptodo_group_task_limit` | Limit for group task queries. |
| `bptodo_group_edit_limit` | Limit for the group edit view. |
| `bptodo_group_overview_member_stats` | Adjust the member stats shown in Group Overview. |
| `bptodo_group_overview_members_per_page` | Page size for the Group Overview member grid. |
| `bptodo_enable_report_view` | Control report-view availability. |
| `bptodo_admin_mod_gorup_report_view` | Control admin/moderator group report view. |
| `alter_bptodo_if_moderator_modification_enabled` | Alter whether moderators may modify the list. |
| `bp_group_todo_change_todo_edit_button` | Filter the group edit-button markup. |
| `bp_group_todo_change_submit_button` | Filter the group submit-button markup. |
| `bp_todo_locate_template` | Filter the located template path. |

## Actions

| Action | Fires |
| --- | --- |
| `bptodo_add_field_before_default_listing` | Before the default fields in the member add form. |
| `bptodo_add_field_after_default_listing` | After the default fields in the member add form. |
| `bptodo_add_field_before_edit_default_listing` | Before default fields in the member edit form. |
| `bptodo_add_field_after_edit_default_listing` | After default fields in the member edit form. |
| `bptodo_change_submit_button_action` | Around the member submit button. |
| `bptodo_change_todo_edit_button_action` | Around the member edit button. |
| `bptodo_add_extra_tab_content_before_defaults` | Before default tab content. |
| `bptodo_add_extra_tab_content_after_defaults` | After default tab content. |
| `bptodo_group_todo_submit` | On group task submission. |
| `bp_group_todo_add_field_before_default_listing` | Before default fields in the group add form. |
| `bp_group_todo_add_field_after_default_listing` | After default fields in the group add form. |
| `bp_group_todo_add_field_before_edit_default_listing` | Before default fields in the group edit form. |
| `bp_group_todo_add_field_after_edit_default_listing` | After default fields in the group edit form. |
| `bp_group_todo_change_submit_button_action` | Around the group submit button. |
| `bp_group_todo_change_todo_edit_button_action` | Around the group edit button. |

## Scheduled events

| Cron hook | Schedule | Callback |
| --- | --- | --- |
| `bptodo_todo_notification` | `every_six_hours` (custom, 6 hours) | Sends due-date emails and notifications for tasks due today or overdue. |
| `bptodo_daily_cleanup` | `daily` | Runs data-retention cleanup; scheduled only while auto-cleanup is enabled. |

## AJAX actions

All handlers are registered on `wp_ajax_` only, so they require a logged-in user. Each checks a nonce and an ownership or group-admin permission before acting.

| Action | Nonce | Effect |
| --- | --- | --- |
| `bptodo_complete_todo` | `bptodo-todo-nonce` | Mark a task complete. |
| `bptodo_undo_complete_todo` | `bptodo-todo-nonce` | Reopen a task. |
| `bptodo_remove_todo` | `bptodo-todo-nonce` | Delete a task (and its group copies). |
| `bptodo_edit_form_popup` | `bptodo-todo-nonce` | Load the edit popup. |
| `bptodo_update_form_popup` | `bptodo-todo-nonce` | Save the edit popup. |
| `bptodo_add_todo_category_front` | `bptodo-add-todo-category` | Create a category from the front end. |
| `bptodo_export_my_tasks` | `bptodo-export-todo` | Export the member's tasks to CSV. |

## Templates

Front-end templates live under `public/todo/` (member, group, and category templates) and `inc/templates/` (archive and single). BuddyPress group templates are registered through the plugin's template stack, and the located path is filterable via `bp_todo_locate_template`.
