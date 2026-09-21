# Automatic data retention

Over time, completed and abandoned tasks build up in the database. The data retention feature cleans up old tasks automatically on a daily schedule.

## Enabling it

Go to WB Plugins > Todo Lists > Data Retention. Auto-cleanup is off by default. Turn on Enable Auto-Cleanup and set the options below.

## Options

- Task Scope: include Group Tasks, Personal Tasks, or both. Both are on by default.
- Tasks to Clean Up: Only Completed Tasks, All Tasks (completed and pending), or Only Pending Tasks.
- Retention Period (Days): tasks older than this many days are cleaned up. Default 90, minimum 7. Values below 7 are raised to 7.
- Permanent Delete: when on, tasks are permanently deleted. When off, tasks are moved to trash and are recoverable there.
- Email Notifications: send a summary email after each cleanup run.
- Notification Email(s): one or more comma-separated addresses to receive the summary. Defaults to the site admin email.

## How the schedule works

Cleanup runs once daily on the `bptodo_daily_cleanup` WP-Cron event. The event is only scheduled while Auto-Cleanup is enabled, and it is rescheduled when you change the retention settings. The Data Retention screen shows the next scheduled run time.

## What "old" means

A task's age is measured from its post date against the retention cutoff. The scope and cleanup-type options decide which tasks in that window are removed (for example, only completed personal tasks older than 90 days).

## Safe by default

Because the feature ships disabled, no tasks are removed until you explicitly turn it on. Keep Permanent Delete off if you want a recovery window through the trash.
