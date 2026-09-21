# Data model

Tasks are stored with core WordPress objects, not a custom table. This makes them queryable with `WP_Query`, `get_posts`, and the standard admin.

## Post type

`bp-todo` is a custom post type registered with:

- `supports`: title, editor, author, thumbnail.
- `has_archive`: true, `public`: true.
- `create_posts` capability disabled in the admin (tasks are created from the front end), with `map_meta_cap` on.

The task title is the post title, and the description is the post content.

## Taxonomy

`todo_category` is a hierarchical taxonomy attached to `bp-todo`. A default `Uncategorized` term is created on first load if no terms exist.

## Post meta

| Meta key | Values / meaning |
| --- | --- |
| `todo_status` | `incomplete` or `complete`. |
| `todo_due_date` | Due date string (`Y-m-d`). |
| `todo_priority` | `critical`, `high`, or `normal`. |
| `todo_complete_time` | Unix timestamp when completed. |
| `todo_completed_date` | MySQL datetime when completed. |
| `todo_user_id` | User ID recorded at completion. |
| `todo_group_id` | Group ID for group tasks; absent or empty for personal tasks. |
| `todo_primary_id` | On a member copy of a group task, the ID of the primary task. |
| `botodo_associated_todo` | On the primary group task, an array of the member copy IDs. |
| `todo_last_day_notification_sent` | `yes` / `no` guard so a due-date notification is sent once. |
| `todo_last_day_mail_sent` | `yes` / `no` guard for the due-date email. |

User meta `todo_mail_setting` holds a member's own email preference used by the reminder job.

## Options

| Option | Contents |
| --- | --- |
| `user_todo_list_settings` | General settings (labels, enable member tab, category permission, email/notification toggles, allowed roles, required due date). |
| `group-todo-list-settings` | Group tab enable and moderator permissions. |
| `data-retention-settings` | Auto-cleanup scope, type, period, delete method, and email options. |
| `bptodo_version` | Installed plugin version, used to run upgrade routines. |

## Group task copies

A group assignment creates one primary `bp-todo` and one copy per recipient. Copies carry `todo_primary_id` back to the primary, and the primary carries `botodo_associated_todo` (the array of copy IDs). Editing or deleting the primary cascades to the copies so the set stays consistent.

## REST API

The plugin does not register any custom REST routes. Tasks are ordinary posts, so they are reachable through WordPress core post handling where that is enabled, but there is no dedicated todo REST API.
