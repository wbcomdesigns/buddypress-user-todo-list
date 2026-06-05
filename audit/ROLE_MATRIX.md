# Role Matrix — Todo for BuddyPress & BuddyBoss

Legend: C=Create R=Read U=Update D=Delete  -=No access. "own" = only the actor's own records.

| Feature | Admin | Editor/Author | Subscriber/Member | Group Moderator | Logged-out |
|---|---|---|---|---|---|
| Admin settings pages (`wbcomplugins`, `user-todo-list-settings`) | CRUD | - | - | - | - |
| Member todo (own) | CRUD | CRUD (own) | CRUD (own)* | CRUD (own) | - |
| Member todo categories | CRUD | C/R (own)** | C/R (own)** | C/R (own)** | - |
| Export own tasks | R | R | R | R | - |
| Group todo (create/list) | CRUD | per group role | per group role*** | CRUD | - |
| Group todo report | R | R (if enabled) | R (if enabled) | R | - |
| Upload attachment on todo page | CRUD | CRUD | granted via user_has_cap**** | CRUD | - |
| Network deactivate (advanced MS) | super-admin only | - | - | - | - |
| WP-CLI (generate/cleanup) | shell/root | - | - | - | - |

Notes:
- `*` Member access to the todo tab is gated by `user_todo_list_settings.enable_todo_member` and the `bptodo_user_roles[]` allow-list.
- `**` Frontend category creation is gated by `user_todo_list_settings.allow_user_add_category = yes`.
- `***` Group todo permissions follow `group-todo-list-settings` (`mod_enable`, `list_enable`, `view_enable`) and the actor's BuddyPress group role.
- `****` `upload_files` is dynamically granted to subscribers ONLY while on a BuddyPress todo page (`user_has_cap` filter, bp-user-todo-list.php:161); not a persistent role change.
- All AJAX endpoints require login (no `wp_ajax_nopriv`). Logged-out users get no write access.
