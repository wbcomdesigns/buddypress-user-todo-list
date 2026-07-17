---
title: Todo for BuddyPress & BuddyBoss - Documentation
slug: bptodo-docs-index
---

# Todo for BuddyPress & BuddyBoss

Personal and group to-do lists for BuddyPress and BuddyBoss communities. Version 4.0
adds a modern, no-reload frontend (blocks + REST + the WordPress Interactivity API)
while keeping the classic layout available as an instant fallback.

## Getting started

- [Overview](getting-started/overview) - what the plugin does and where to find it
- [Modern vs. Legacy layout](getting-started/modern-vs-legacy) - the 4.0 layout toggle,
  auto-fallback, and rollback story

## Features

- [Modern personal to-do list](features/modern-todo-list) - progress summary, quick-add,
  inline complete/edit/delete, search, filters, pagination
- [Task details and notes](features/task-details) - the Details toggle and inline note
- [Add a to-do](features/add-a-todo) - the full add form and the Require due date rule
- [Categories](features/categories) - member-created categories and the inline creator
- [Group to-dos](features/group-todos) - the Todos / Add / Overview sub-tabs and member
  assignment
- [Privacy](features/privacy) - why to-dos have no public URL and how "view" links work

## Settings

- [Admin settings](settings/admin-settings) - labels, member access, reminders, and the
  Frontend layout setting

## Developer guide

- [REST API reference](developer-guide/rest-api) - the `bptodo/v1` namespace, every
  route, and the todo response shape
- [Blocks](developer-guide/blocks) - `bptodo/my-todos` and `bptodo/group-todos`
- [Extension seams](developer-guide/extension-seams) - the domain layer and the filters
  available for customization

## Also see

The plugin root `docs/` folder has the pre-4.0 user guides (admin settings walkthrough,
member guide, group todos guide, email/notifications, shortcodes, WP-CLI commands, FAQ,
and troubleshooting). This `docs/website/` tree documents the 4.0 modernization
specifically; it does not replace those guides.
