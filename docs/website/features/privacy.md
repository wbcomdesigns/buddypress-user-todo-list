---
title: Privacy
slug: privacy
---

# Privacy

To-dos are private, permission-scoped data. There is no public page for a task.

## No public URL, archive, or search entry

The `bp-todo` post type and the `todo_category` taxonomy are registered as private:
not publicly queryable, no archive, excluded from site search, and no rewrite rules.
A guessable URL like `/todo/{slug}/` or the `/todo/` archive returns a 404, and tasks
never appear in WordPress search results.

`show_ui` stays enabled so site admins can still manage tasks from wp-admin.

## Where viewing happens instead

Every legitimate way to see a to-do is permission-checked at the point of display:

- The member's To-do tab (owner, or an admin/group manager per the same rules used
  everywhere else).
- The group's Todos tab, for group tasks.
- The `bptodo/my-todos` / `bptodo/group-todos` blocks and the `[bptodo_list]` /
  `[bptodo_by_category]` shortcodes.
- The `bptodo/v1` REST API, gated by the same permission checks as the UI.

## "View to-do" links

Reminder emails, BuddyPress notifications, the REST API's `permalink` field, and the
member dashboard widget all link to the task through `bptodo_get_todo_view_url()`, which
resolves to the owner's member To-do tab, or the group's Todos tab for a group task, never
to a raw CPT permalink.
