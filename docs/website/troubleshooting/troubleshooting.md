# Troubleshooting

## The plugin deactivated itself

The plugin requires BuddyPress or BuddyBoss. If neither is active, or on multisite if you activated it on a subsite that is not the BuddyPress root blog, the plugin deactivates and shows an admin notice. Activate BuddyPress or BuddyBoss on the correct site, then reactivate.

## The Todo tab does not appear on profiles

- Confirm Enable Todo For Member is on in WB Plugins > Todo Lists > General.
- The tab shows only on the member's own profile, not when viewing someone else.
- If you set Select User Roles to Create Todos, members outside those roles still see the list tab but not the Add tab. Administrators always have access.

## The Todo section is missing from groups

Enable Todo For Group in the Group Todo settings tab. The Add and Group Overview sub-tabs appear for group admins, and for moderators only when you allow moderators to add tasks or view reports.

## Reminder emails or notifications are not arriving

- Check that Send Email and Send Notification are enabled in General settings.
- Notifications need the BuddyPress Notifications component active.
- Reminders are driven by WP-Cron on a six-hour schedule. On low-traffic sites WP-Cron may not fire on time; a real system cron calling `wp cron event run --due-now` makes delivery reliable.
- Each task is reminded once for its due date, so you will not see repeat reminders for the same task.
- Emails are sent through the BuddyPress email system. If BuddyPress email delivery is broken site-wide, fix that first (an SMTP plugin usually resolves it).

## Email templates look empty or missing

Templates install automatically on activation and re-check on upgrade. If they are missing, deactivate and reactivate the plugin to trigger reinstallation. You can edit them under Settings > BuddyPress > Emails.

## Data retention did not remove old tasks

- Auto-cleanup is off by default; enable it in the Data Retention tab.
- Cleanup runs on the daily `bptodo_daily_cleanup` cron and only while auto-cleanup is enabled. The Data Retention screen shows the next scheduled run.
- Tasks must be older than the retention period (minimum 7 days, default 90) and match the chosen scope and cleanup type.
- With Permanent Delete off, tasks go to trash rather than being erased, so check the trash if a task seems gone.

## The category shortcode shows a notice instead of tasks

`[bptodo_by_category]` needs a valid numeric category term ID in the `category` attribute. A slug or name will not work. Find the ID under Todo Items > Categories. The shortcode also only shows tasks for the logged-in member; logged-out visitors see a different template.

## Group and report tables look unstyled

The group list and reports use DataTables assets that load only on group pages. If a caching or optimization plugin strips those assets, exclude the plugin's `datatables` CSS and JS from concatenation or deferral.

## Counts look stale

The profile task count is cached per member for five minutes and cleared when tasks change. A brief delay after a change is expected. An aggressive object cache can extend it; flushing the cache refreshes the count.
