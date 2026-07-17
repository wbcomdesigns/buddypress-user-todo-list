---
title: REST API reference
slug: rest-api
---

# REST API reference

Base URL: `/wp-json/bptodo/v1/`

All routes require a logged-in user. Authenticate with cookies plus the `X-WP-Nonce`
header using a `wp_rest` nonce (the standard WordPress REST auth pattern; the modern
frontend gets its nonce from the Interactivity state automatically). Every route has an
explicit permission callback; no route trusts a client-sent user or ownership value.

## Todo object shape

Every route that returns a todo uses the same shape (`Bptodo_Todo::prepare_for_response()`):

```json
{
  "id": 123,
  "title": "Finish the report",
  "description": "Include Q3 numbers",
  "status": "incomplete",
  "is_complete": false,
  "priority": "high",
  "priority_label": "High",
  "due_date": "2026-07-15",
  "due_human": { "text": "5 days left", "state": "upcoming" },
  "category": { "id": 4, "slug": "work", "name": "Work" },
  "group_id": 0,
  "author": { "id": 7, "name": "Jane Doe", "avatar": "https://.../avatar.jpg" },
  "is_owner": true,
  "can_edit": true,
  "can_delete": true,
  "permalink": "https://example.com/members/jane/todo/"
}
```

`permalink` always points to the owner's To-do tab (or the group's tab for a group
todo) - to-dos have no public single URL. See [Privacy](../features/privacy).

## Routes

### `GET /todos`

List the current user's to-dos (or another scope, if permitted).

- **Params:** `status` (`all`|`complete`|`incomplete`, default `all`), `priority`
  (`all`|`normal`|`high`|`critical`), `category` (slug), `search`, `per_page` (1-100,
  default 10), `page` (default 1), `orderby` (`smart`|`due_date`|`date`|`title`|`priority`),
  `order` (`ASC`|`DESC`), `user_id` (omit for self; a user ID; or `all`).
- **Permission:** logged in. `user_id` other than your own, or `all`, requires
  `manage_options` (checked via `Bptodo_Permissions::can_view_scope()`).
- **Returns:** `{ items[], total, total_pages, page, per_page }`. Also sets
  `X-WP-Total` / `X-WP-TotalPages` headers.

### `POST /todos`

Create a personal to-do.

- **Params:** `title` (required), `description`, `priority`, `due_date`, `category`.
- **Permission:** logged in and allowed to create tasks (`Bptodo_Permissions::can_create()`,
  honors the "Roles allowed to create tasks" setting).
- **Errors:** `400 bptodo_due_required` if the **Require due date** setting is on and
  `due_date` is empty.
- **Returns:** the created todo object.

### `GET /todos/{id}`

- **Permission:** owner, a user with `edit_others_posts`, or a manager of the todo's group.
- **Returns:** the todo object, or `404 bptodo_rest_not_found`.

### `PATCH /todos/{id}`

Update a to-do.

- **Params:** `title`, `description`, `priority`, `due_date`, `category` (only fields
  present in the request are changed).
- **Permission:** same as `GET /todos/{id}`.
- **Returns:** the updated todo object.

### `DELETE /todos/{id}`

- **Permission:** same as `GET /todos/{id}`.
- **Returns:** `{ "deleted": true, "id": 123 }`.

### `POST /todos/{id}/complete`

Mark complete.

- **Permission:** owner, `edit_others_posts`, or group manager.
- **Returns:** the updated todo object (`status: "complete"`).

### `DELETE /todos/{id}/complete`

Undo completion (same permission). Returns the updated todo object
(`status: "incomplete"`).

### `GET /todos/stats`

- **Params:** `user_id` (optional), `group_id` (optional).
- **Permission:** logged in.
- **Returns:** `{ total, completed, pending }`.

### `GET /categories`

- **Permission:** logged in.
- **Returns:** `[{ id, slug, name, count }]`.

### `POST /categories`

- **Params:** `name` (required).
- **Permission:** logged in and allowed to add categories (`manage_categories`
  capability, or the **Member-created categories** setting). See
  [Categories](../features/categories).
- **Returns:** `{ id, slug, name }`.

### `GET /groups/{group_id}/todos`

- **Params:** same as `GET /todos` (minus `user_id`).
- **Permission:** confirmed, unbanned group member, or a group admin/moderator.
- **Returns:** same paginated shape as `GET /todos`.

### `POST /groups/{group_id}/todos`

Create a group to-do, optionally assigning it.

- **Params:** `title` (required), `description`, `priority`, `due_date`, `category`,
  `assignees` (array of user IDs, or `["all"]`/omitted to assign the whole group).
- **Permission:** any confirmed group member can create an unassigned task; assigning
  specific `assignees` requires a group admin or moderator
  (`Bptodo_Permissions::can_create_group_todo()`).
- **Errors:** `400 bptodo_due_required` (same rule as `POST /todos`).
- **Returns:** the created (primary) todo object; member copies fan out internally using
  the same code path as the legacy assignment flow.

## Errors

Errors are standard `WP_Error` responses with an HTTP status:

| Code | Status | Meaning |
|---|---|---|
| `bptodo_rest_unauthorized` | 401 | Not logged in. |
| `bptodo_rest_forbidden` | 403 | Logged in, but not permitted. |
| `bptodo_rest_not_found` | 404 | Todo does not exist. |
| `bptodo_due_required` | 400 | Due date missing while required. |
| `bptodo_cannot_add_category` | 403 | Not permitted to add categories. |
| `bptodo_empty_category` | 400 | Category name was empty. |
