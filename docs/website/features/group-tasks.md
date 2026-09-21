# Group tasks and reporting

Group admins and moderators can run shared task lists inside BuddyPress groups. Each assigned task is copied to every recipient's personal list so they can complete it independently, while admins track overall progress.

## Enabling group todos

In wp-admin, go to WB Plugins > Todo Lists > Group Todo and turn on Enable Todo For Group. This tab also controls moderator permissions:

- Prevent Group Moderators from Modifying Todo List (`mod_enable`).
- Allow Group Moderators to add Todos in group (`list_enable`).
- Allow Group Moderators to View Todo List Report (`view_enable`).

## Group tabs

When group todos are enabled, a Todo section is added to each group's navigation with up to three sub-tabs:

- List: visible to group members.
- Add: shown to group admins, and to moderators when they are allowed to add tasks.
- Group Overview: shown to group admins, and to moderators when they are allowed to view reports.

## Assigning a task

On the Add tab, a group admin (or a permitted moderator) creates a task and chooses recipients:

- All members, or
- Selected members.

Each recipient gets their own copy of the task. The copies are linked to a primary task so that editing or deleting the primary keeps the member copies in sync. Group context is stored on each copy as the `todo_group_id` meta value.

## Notifications and emails on assignment

When a group task is assigned, each recipient receives a BuddyPress notification and, if the assignment email template is active, an assignment email naming the task, the group, and who assigned it.

## Group Overview report

The Overview tab shows real-time completion progress:

- Overall group progress as a percentage.
- A per-member grid with avatars and status.
- Filters by completion status.
- Pagination for large groups.

Members shown and page size can be tuned with the `bptodo_group_overview_member_stats` and `bptodo_group_overview_members_per_page` filters. The group list and reports use DataTables, whose assets load only on group pages.

## Site admin oversight

Site administrators have read-only access to group tasks even in groups they are not members of, so they can audit progress without changing anything.
