---
title: Overview
slug: overview
---

# Todo for BuddyPress & BuddyBoss

Todo for BuddyPress & BuddyBoss adds personal and group task management to your community.
Every member gets a private To-do tab on their profile. Group admins and moderators can
assign shared tasks to a BuddyPress or BuddyBoss group and track who has finished what.

The plugin requires BuddyPress or BuddyBoss. Tasks are stored as a private custom post type
(`bp-todo`) with post meta for status, priority, due date, and group assignment. No custom
database tables are used, so there is nothing to migrate between versions.

## What's new in 4.0

Version 4.0 is a major modernization. The frontend now renders with the WordPress
Interactivity API and a `bptodo/v1` REST API: no full-page reloads for adding, completing,
editing, deleting, filtering, searching, or paginating tasks. This is the **Modern** layout.

The previous jQuery + admin-ajax templates are kept as the **Legacy** layout, selectable at
any time from the plugin's General settings. See
[Modern vs. Legacy layout](modern-vs-legacy).

## Where to find things

- **Member profile** - a To-do tab lists the member's personal tasks.
- **Group page** - a Todos tab (visible when enabled) with Todos, Add, and Overview
  sub-tabs. See [Group to-dos](../features/group-todos).
- **Any page** - the `bptodo/my-todos` block, the `bptodo/group-todos` block, or the
  `[bptodo_list]` / `[bptodo_by_category]` shortcodes. See
  [Blocks](../developer-guide/blocks) and the legacy `docs/shortcodes.md` for shortcode
  attributes.
- **Admin** - Settings → Todo List (under the shared Wbcom Plugins menu). See
  [Admin settings](../settings/admin-settings).

To-dos are private data. There is no public single page, archive, or site-search entry
for a task; see [Privacy](../features/privacy).
