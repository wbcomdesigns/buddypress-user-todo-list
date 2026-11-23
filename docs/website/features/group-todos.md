---
title: Group to-dos
slug: group-todos
---

# Group to-dos

BuddyPress and BuddyBoss groups get a Todos tab with three sub-tabs: **Todos**, **Add**,
and **Overview**. The tab only appears when group todos are enabled in
[Admin settings](../settings/admin-settings) and not disabled for that specific group.

## Todos

The default sub-tab lists group tasks using the same modern list described
in [Modern personal to-do list](modern-todo-list) (or the legacy list in Classic mode).
What you see depends on your role:

- Group admins, site admins and moderators (unless the list is locked for moderators) see
  the group's tasks and their count, and can edit, complete or delete them.
- Other members, and moderators while the list is locked, see the tasks assigned to them,
  can tick them complete, and the count and stats match that list.

Each assigned member gets their own copy of the task. The copy lives on the group's Todos
tab; it does not appear in the member's personal to-do list. Editing the group's task
updates the title, details, due date, priority and category on every copy, and each
member keeps their own completed state. A member who deletes their copy removes only
their own; deleting the group's task removes every copy.

## Add

Visible to group admins and site admins, and to moderators when **Moderators can add
tasks** is on and **Lock list for moderators** is off. The Add sub-tab is the same form as
[Add a to-do](add-a-todo), plus an **Assign to members** section:

![Assign to members](../images/group-assign.webp)

- Check specific members to assign the task only to them (a chip-style picker with
  member avatars).
- Leave every member unchecked to assign the task to everyone in the group.
- The picker lists the first 100 members of the group A to Z. Members beyond that are still
  reached by leaving every box unchecked. Developers can change the number with the
  `bptodo_assignee_picker_limit` filter.

Only the people who can see the Add sub-tab can create group tasks, with or without
specific assignees. Ordinary group members cannot create group tasks. The same rule
applies to the REST API and the Classic layout.

## Overview

Visible to group admins, and to moderators when the "moderators can view report" setting
allows it. Overview shows per-member completion progress: total tasks, completed,
pending, and a completion percentage per member, ranked so members needing attention
surface first.

![Group overview](../images/group-overview.webp)

Filter the member list by **All**, **Completed**, or **Pending** to focus on who still has
outstanding work.
