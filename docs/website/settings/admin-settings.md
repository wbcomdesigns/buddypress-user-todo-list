---
title: Admin settings
slug: admin-settings
---

# Admin settings

Settings live under **Settings → Todo List** (in the shared Wbcom Plugins admin menu),
General tab. They are stored in the `user_todo_list_settings` option.

## Labels

| Setting | Description |
|---|---|
| Todo Label (Singular) | The word used for one task (default "Todo"). Drives the modern list count, quick-add placeholder, empty state, and the Add form's Create button. |
| Todos Label (Plural) | The word used for multiple tasks (default "Todos"). Also renames the member/group profile tab. |

## Member access

| Setting | Default | Description |
|---|---|---|
| Enable for members | On | Adds a To-do tab to every member profile. |
| Member-created categories | On | Lets members create their own to-do categories (an inline "+ New" control on the Add form). Admins can always add categories. |
| Roles allowed to create tasks | Everyone | Restrict task creation to specific roles. Leave empty to allow every member. |

## Reminders

| Setting | Default | Description |
|---|---|---|
| Send notification | On | Sends a BuddyPress notification when a task's due date arrives. Needs the BuddyPress Notifications component to be active. |
| Send email | On | Sends an email reminder when a task's due date arrives. Members can turn their own reminder emails off in their profile notification settings. |
| Require due date | Off | Makes the due date mandatory when creating a task, enforced on both the form and the REST API. |

Defaults are what a fresh install starts with. Updating the plugin keeps the settings you
already saved.

Reminders are checked every six hours. Each incomplete task gets one notification and one
email on or after its due date (not before it). Changing a task's due date re-arms its
reminder for the new date.

## Todos page

| Setting | Description |
|---|---|
| Todos page | The page that holds the My Todos block or the `[bptodo_list]` shortcode. Reminder and view links use it when BuddyPress is not active. Not needed when BuddyPress profile tabs are in use. |

## Frontend layout

![Frontend layout setting](../images/admin-frontend-layout.webp)

| Setting | Default | Description |
|---|---|---|
| Frontend layout | Modern | Modern uses blocks and the REST API for a faster, no-reload list. Classic uses the legacy templates. See [Modern vs. Legacy layout](../getting-started/modern-vs-legacy). |

## Group settings

Group-level toggles (group-todo-list-settings option, on the Group tab, shown when the BuddyPress Groups component is active):

| Setting | Default | Description |
|---|---|---|
| Group to-dos | On | Adds the Todos tab to every group (a group admin can still turn it off for one group). |
| Lock list for moderators | Off | When on, moderators cannot add, edit, complete or delete the group's tasks; they see and complete only the tasks assigned to them. Group admins are not affected. |
| Moderators can add tasks | On | Lets moderators create group tasks (while the list is not locked). |
| Moderators can view report | On | Lets moderators open the group Overview. |

These rules are enforced on the server for the REST API, the Classic layout and the
Modern layout alike. See [Group to-dos](../features/group-todos).
