# Personal task lists

Each member gets a private todo list on their own profile. Personal tasks are visible only to the member who created them (and to site admins through the WordPress admin).

## The profile Todo tab

When Enable Todo For Member is on, a Todo tab is added to the member's own profile at position 75, labeled with your configured plural label and a count of the member's open tasks. It has two sub-navigation items:

- List (default): tasks grouped into Past, Today, Tomorrow, and Future by due date.
- Add: the create form.

The tab appears only on the member's own profile. It is also added to the BuddyPress toolbar (My Account menu) with List and Add shortcuts.

## Who can create tasks

By default any logged-in member with the Todo tab can create tasks. If you set Select User Roles to Create Todos in General settings, only administrators and members in the listed roles see the Add sub-tab and can create tasks. The role check is enforced again on save, so it cannot be bypassed.

## Task fields

- Title.
- Description, saved as post content through a rich-text editor (`wp_kses_post` sanitized).
- Category, from the `todo_category` taxonomy.
- Due date.
- Priority: Critical, High, or Normal.

## Sorting

The list sorts incomplete tasks first, then by priority (Critical, High, Normal), then by due date (earliest first), then newest. Completed tasks always drop to the bottom.

## Completion

Completing a task sets its status to complete and records both a Unix timestamp (`todo_complete_time`) and a human-readable datetime (`todo_completed_date`). Undo clears both and reopens the task.

## Export

Members can export their own tasks to CSV from the Export link on the Todo tab. The export includes Task ID, Title, Summary, Due Date, and Status, and is capped at 1000 tasks (adjustable with the `bptodo_export_limit` filter).

## Task count cache

The open-task count shown on the tab is cached per user for 5 minutes and cleared when the member's tasks change, so large communities do not pay for a live count on every page load.

## Member Todo widget

The plugin registers a "Member Todo" widget (`BP_ToDo_Member_ToDo_Widget`) that lists the current member's tasks with priority indicators. Add it from Appearance > Widgets or the block widget editor.
