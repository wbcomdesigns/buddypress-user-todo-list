# What this plugin does

BuddyPress User Todo List adds task management to a BuddyPress or BuddyBoss community. Every member gets a private todo list in their profile, and group admins can assign shared tasks to group members.

## What members can do

- Create personal tasks with a title, a rich-text description, a due date, a category, and a priority (Critical, High, Normal).
- Mark tasks complete and undo completion. Completion is timestamped.
- See tasks grouped by due date (Past, Today, Tomorrow, Future), with incomplete tasks sorted ahead of completed ones.
- Export their own tasks to CSV.
- Receive an email and a BuddyPress notification when a task is due.

## What group admins can do

- Turn on a Todo tab inside each group.
- Assign a task to all members or to selected members. Each assigned member gets their own copy to complete independently.
- View a Group Overview report with per-member completion progress.

## What site admins can do

- Customize the todo label and menu slug, choose which roles may create tasks, and toggle email and notification reminders.
- Configure automatic cleanup of old tasks to keep the database lean.
- Manage all tasks from the standard WordPress admin under the Todo Items post type.

## How tasks are stored

Tasks are stored as a custom post type named `bp-todo`, categorized with a `todo_category` taxonomy. Task details (due date, status, priority, group ID, completion time) are saved as post meta. There is no separate custom database table.

## Requirements

- WordPress 6.5 or higher.
- PHP 7.4 or higher.
- BuddyPress 12.0 or higher, or BuddyBoss Platform, installed and active.
- MySQL 5.7 or higher.
