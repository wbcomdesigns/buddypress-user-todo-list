---
title: Extension seams
slug: extension-seams
---

# Extension seams

4.0 introduced a domain layer under `includes/domain/` that is the single source of truth
for reading and writing to-dos. Legacy AJAX handlers, the REST API, and the blocks all
delegate to it, so business logic lives in exactly one place.

## Domain layer

| Class | Responsibility |
|---|---|
| `Bptodo_Todo_Repository` | All CPT/postmeta CRUD, querying, stats, and group fan-out. Nothing else in the plugin touches `get_post_meta()`/`update_post_meta()` directly for a todo's own storage. |
| `Bptodo_Permissions` | Every capability decision: `can_view()`, `can_edit()`, `can_delete()`, `can_complete()`, `can_create()`, `can_create_group_todo()`, `can_view_scope()`, `is_group_manager()`. |
| `Bptodo_Categories` | Category listing, creation, and the `allow_user_add_category` gate. |
| `Bptodo_Todo` | A read-oriented DTO around one `bp-todo` post. `prepare_for_response()` produces the one canonical shape used by both REST JSON and the SSR templates. |

Because REST, legacy AJAX, and the blocks all call the same repository and permission
methods, there is no risk of the two frontends drifting out of sync on what a member is
allowed to do.

## Filters

| Filter | Fires in | Purpose |
|---|---|---|
| `bptodo_layout_mode` | `bptodo_layout_mode( $mode, $context )` | Override Modern/Classic per site or per surface (`member`, `group`, `shortcode`). See [Modern vs. Legacy layout](../getting-started/modern-vs-legacy). |
| `bptodo_exclude_modrator_view` | `bptodo_list_group_modrator()` | Whether group moderators can add/list group to-dos. |
| `bptodo_exclude_modrator_edit` | `bptodo_report_view_enabled()` | Whether group moderators can view the group Overview report. |
| `alter_bptodo_if_moderator_modification_enabled` | `bptodo_if_moderator_modification_enabled( $group_id, $current_user )` | Whether a moderator may modify group to-dos. |
| `bptodo_assignee_picker_limit` | Group Add form | Members listed in the assign picker (default 100, A to Z). |
| `bptodo_group_overview_members_per_page` | Group Overview template | Members shown per Overview page (default 15). |
| `bptodo_group_overview_task_selector_limit` | Group Overview template | Max primary tasks loaded for the task selector (default 100). |

## Storage contract

Meta keys are constants on `Bptodo_Todo` (`META_STATUS`, `META_PRIORITY`, `META_DUE_DATE`,
`META_GROUP_ID`, and related keys) and are unchanged from pre-4.0 versions: `todo_status`
(`'complete'`/`'incomplete'`), `todo_priority`, `todo_due_date`, `todo_group_id`,
`todo_primary_id`. Any code reading these directly (rather than through the repository)
still works, but new code should go through `Bptodo_Todo_Repository` and
`Bptodo_Permissions` instead of re-implementing checks inline.

## Legacy compatibility

Legacy AJAX handlers, shortcodes, and BuddyPress tab templates remain fully registered
and functional; they call the same domain layer as REST and the blocks. Existing
integrations that hook the legacy `wp_ajax_bptodo_*` actions, or the filters above,
continue to work unchanged under either layout mode.
