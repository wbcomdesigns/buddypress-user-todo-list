# Admin settings

All settings live under WB Plugins > Todo Lists in wp-admin (page slug `user-todo-list-settings`). The screen has four tabs: Welcome, General, Group Todo, and Data Retention.

## General

Stored in the `user_todo_list_settings` option.

| Setting | What it does |
| --- | --- |
| Todo Label (Singular) | Singular label used across the UI. Default "Todo". |
| Todos Label (Plural) | Plural label and the profile tab name. Default "Todos". The tab URL slug is derived from the singular label. |
| Enable Todo For Member | Adds the Todo tab to member profiles. |
| Allow User to Add Category | Lets members create categories inline from the task form. |
| Send Notification | Sends a BuddyPress notification when a task is due. |
| Send Email | Sends an email reminder when a task is due. |
| Select User Roles to Create Todos | Restricts task creation to the chosen roles. Administrators always retain access. Leave empty to allow all members. |
| Require Due Date? | Makes the due date mandatory when creating a task. |

## Group Todo

Stored in the `group-todo-list-settings` option.

| Setting | What it does |
| --- | --- |
| Enable Todo For Group | Adds the Todo section to group navigation. |
| Prevent Group Moderators from Modifying Todo List | Blocks moderators from changing the list. |
| Allow Group Moderators to add Todos in group | Lets moderators use the Add tab. |
| Allow Group Moderators to View Todo List Report | Lets moderators open Group Overview. |

Group admins always have full access. These toggles only widen or narrow what moderators can do.

## Data Retention

Stored in the `data-retention-settings` option. See Features > Automatic data retention for the full option list. In short: enable auto-cleanup, pick the scope and cleanup type, set a retention period (default 90 days, minimum 7), choose trash or permanent delete, and optionally email a summary after each run.

## Managing tasks in wp-admin

Tasks are a custom post type. You can browse and manage them under Todo Items, and manage the Todo Category taxonomy there as well. New categories can be created in the admin regardless of the member category setting.
