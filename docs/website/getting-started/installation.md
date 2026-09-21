# Install and activate

## Before you start

BuddyPress 12.0 or higher, or BuddyBoss Platform, must be installed and active first. If BuddyPress is not active, this plugin deactivates itself and shows an admin notice, because the profile and group tabs depend on it.

## Install from the WordPress admin

1. Go to Plugins > Add New.
2. Search for "Todo for BuddyPress".
3. Click Install Now, then Activate.

## Install by upload

1. Download the plugin ZIP.
2. Go to Plugins > Add New > Upload Plugin.
3. Choose the ZIP, click Install Now, then Activate Plugin.

## After activation

On a single site, activating the plugin redirects you to its settings page. You can return any time at WB Plugins > Todo Lists (page slug `user-todo-list-settings`).

Recommended first steps:

1. Open WB Plugins > Todo Lists > General and confirm Enable Todo For Member is on.
2. Set the singular and plural labels and choose which user roles may create tasks.
3. Decide whether Send Email and Send Notification reminders should be on.
4. If you run groups, open the Group Todo tab and enable Todo for groups.

## Multisite

The plugin is intended to run on the BuddyPress root blog.

- If `BP_ROOT_BLOG` is defined, the plugin stays active only on that subsite and deactivates elsewhere with a notice.
- If BuddyPress is network activated, the plugin loads on the network's BuddyPress site.
- Advanced network-wide activation handling is available but off by default. It is gated behind the `BPTODO_ENABLE_ADVANCED_MULTISITE` constant.
