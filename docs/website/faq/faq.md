# Frequently asked questions

## Is BuddyPress required?

Yes. The plugin needs BuddyPress 12.0 or higher, or BuddyBoss Platform, installed and active. Without it, the plugin deactivates itself, because the profile and group tabs are built on BuddyPress navigation.

## Does it work with BuddyBoss Platform?

Yes. It integrates with BuddyBoss profiles, groups, and the BuddyBoss email system.

## Can members see each other's personal tasks?

No. Personal tasks are private to the member who created them. Only site administrators can view them through the WordPress admin. Group tasks are visible to group admins and moderators for tracking.

## How do group tasks work?

A group admin (or a permitted moderator) creates a task and assigns it to all members or selected members. Each recipient gets their own copy in their personal list and completes it independently. The Group Overview report shows everyone's progress.

## Can I rename "Todo"?

Yes. In WB Plugins > Todo Lists > General, set the singular and plural labels (for example "Task" and "Tasks"). The profile tab URL slug is derived from the singular label.

## Can members create their own categories?

Only if you enable Allow User to Add Category in General settings. When it is off, categories are managed by admins under Todo Items > Categories.

## How do reminders work?

A cron job that runs every six hours finds tasks due today or overdue and sends the owner an email and a BuddyPress notification, according to the Send Email and Send Notification settings. Each task is reminded once per due date.

## Can I export tasks?

Yes. Members can export their own tasks to CSV (Task ID, Title, Summary, Due Date, Status) from the Export link on their Todo tab. Exports are capped at 1000 tasks by default.

## Will old completed tasks bloat the database?

Only if you let them. Enable Data Retention to clean up old tasks daily by scope, type, and age. It is off by default, so nothing is removed until you turn it on.

## Can site admins view all group tasks?

Yes. Administrators have read-only access to group tasks even in groups they do not belong to.

## Does it have a REST API?

No dedicated REST API. Tasks are stored as the `bp-todo` post type, so they are reachable through WordPress core post handling where enabled, but the plugin registers no custom todo REST routes.

## Which shortcode is available?

One: `[bptodo_by_category category="<term ID>"]`. It lists the current member's tasks in that category. See Shortcodes for details.

## Does it support multisite?

Yes, on the BuddyPress root blog. See Getting Started > Install and activate for how root-blog and network activation are handled.

## Is it translation ready?

Yes. The text domain is `wb-todo`, a POT file is included, RTL is supported, and a French translation ships with the plugin.
