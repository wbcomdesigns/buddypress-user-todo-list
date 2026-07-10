---
title: Blocks
slug: blocks
---

# Blocks

Two dynamic, server-rendered blocks ship with 4.0, both `apiVersion: 3` and
Interactivity-powered (`supports.interactivity: true`). Registered on `init` via
`register_block_type()` from each block's `block.json`.

## `bptodo/my-todos`

A personal to-do list for the logged-in viewer. Renders through the same
`Bptodo_Renderer::render()` used by the member profile tab and the `[bptodo_list]`
shortcode, so behavior is identical everywhere.

**Attributes:**

| Attribute | Type | Default |
|---|---|---|
| `perPage` | number | 10 |
| `status` | string | `all` |
| `priority` | string | `all` |
| `showAdd` | boolean | true |
| `showStats` | boolean | true |

Logged-out visitors see a "Please log in" message instead of the list.

## `bptodo/group-todos`

Shared to-dos for a BuddyPress/BuddyBoss group. Only registered when BuddyPress/BuddyBoss
is active.

**Attributes:**

| Attribute | Type | Default |
|---|---|---|
| `groupId` | number | 0 (falls back to the current group when placed on a group page) |
| `perPage` | number | 10 |
| `showAdd` | boolean | true |
| `showStats` | boolean | true |

If no group can be resolved (no `groupId` attribute and not on a group page), the block
shows a prompt to select a group instead of rendering an empty list.

## How rendering works

Each block's `render.php` is a thin wrapper: it reads `$attributes`, maps them to
`Bptodo_Renderer::render()` args, and echoes the result. Because the block declares
`supports.interactivity`, WordPress processes the `data-wp-*` directives in that markup
during the same render pass, so the first paint is already interactive with no extra
client-side hydration step.

The editor side (`index.js`) registers the block type and previews it with
`ServerSideRender`, so the editor preview always matches the frontend output.
