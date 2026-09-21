# Reminders and notifications

The plugin reminds members about due tasks through the BuddyPress email system and BuddyPress notifications.

## Due-date reminders

A scheduled job checks for incomplete tasks whose due date is today or already passed, and for each one it can send:

- An email reminder to the task owner, when Send Email is enabled.
- A BuddyPress notification to the task owner, when Send Notification is enabled.

Both toggles live in WB Plugins > Todo Lists > General. Members can also control their own email preference through their profile mail setting.

### How the schedule works

The reminder job runs on a custom WP-Cron schedule named `every_six_hours` (every 21600 seconds) under the `bptodo_todo_notification` event. Tasks are processed in batches of 100 to stay within memory limits. The interval and batch size are filterable (`bptodo_notification_cron_schedule_interval`, `bptodo_due_date_batch_size`).

Each task is only notified once for its due date, tracked by the `todo_last_day_notification_sent` and `todo_last_day_mail_sent` meta flags, so members do not get repeated reminders for the same task.

## Group assignment notifications

When a group task is assigned, each recipient gets a BuddyPress notification, and an assignment email if that template is active. See Features > Group tasks and reporting.

## Email templates

Emails are sent through the BuddyPress native email system, not raw `wp_mail`. Two email types are registered:

- Due-date reminder.
- Group assignment notification.

They are installed automatically on activation and re-checked on upgrade, so no manual setup is needed. You can edit the copy under Settings > BuddyPress > Emails. Email subject and body respect your custom todo label.

## Requirements

Notifications require the BuddyPress Notifications component to be active. If BuddyPress is inactive, no reminders are sent.
