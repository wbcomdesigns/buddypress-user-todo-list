=== Todo for BuddyPress & BuddyBoss ===
Contributors: wbcomdesigns , vapvarun
Donate link: https://wbcomdesigns.com/contact/
Tags: buddypress, cpt, todo
Requires at least: 4.0.1
Tested up to: 6.7.0
Stable tag: 3.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

The BuddyPress Todo List plugin allows BuddyPress members to add tasks to their todo list and will enable them to 'edit,' 'delete,' and 'mark-complete' their mission. In addition, members can create a category for todo and add todo accordingly.

It also has a due date reminder feature that reminds the user of their due date and sends an email and BuddyPress notification to the user. And the user can export all todo tasks in a CSV format.

You can list all Todo on any page using a shortcode.

Todo for BuddyPress & BuddyBoss Features
  1- Add custom Profile Menu Label for todo.
  2- Allow or Disallow users to add todo categories.
  3- Enable or disable Send email option for users whose todo date has arrived.
  4- Enable or disable BP Notification whose todo date has arrived.
  5- Options for Task Edit, Task Delete, and Task Mark as Complete from the front end.
  6- Use a shortcode [bptodo_by_category category=”CATEGORY_ID”] to display Todo.

Easy-to-Use Backend Setting
  1- Customize the label of to do
  2- Admin Can allow users to add new categories
  3- Admin can enable BuddyPress notifications
  4- Admin can also enable the e-mail feature

Post Reviews Base upon Multiple Criteria
  1- Members can create a todo
  2- They can edit, delete, and mark complete the todo.
  3- Help members to remember things with notification and email features.
  4- Members can create todos based on the category.

BuddyPress ToDo List with Group Task
  1. Adding the task to the group
  2. Tasks will be added to group members todo lists
  3. Members can mark todos assigned to them from the group mark complete from the profile todo tab.

= Links =

[Wbcom Designs Site](https://wbcomdesigns.com/downloads/buddypress-user-todo-list/ "BuddyPress User ToDo List" )
*	[Demo]( https://demos.wbcomdesigns.com/wbcomplugins/ "BuddyPress User ToDo List Demo")
*	[Support](https://wbcomdesigns.com/helpdesk/article-categories/bp-user-todo-list/)
*	[Github Development Repo](https://github.com/wbcomdesigns/buddypress-user-todo-list/)

== Installation ==

1. Upload the entire buddypress-user-todo-list folder to the /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= Where do I get support?
We request you to use [Wbcom Designs](https://wbcomdesigns.com/contact/) for all the support questions. We love helping. Using Wbcom Designs allows us to assist you better and quicker.

= How can we add a task in our todo list? =

Just goto your member profile and you can see the Todo tab. Click on it and then you will see a child tab to add a task.

== Screenshots ==

The screenshots are present in the root of the plugin folder.
1. screenshot-1 - is the screen which shows the plugin settings at admin panel.
2. screenshot-2 - is the screen that shows member's todo list dashboard.
3. screenshot-3 - is the screen that shows member's all todo list.
4. screenshot-4 - is the screen which shows form to create a todo.

== Changelog ==

== Changelog ==
= 3.3.0 =
* Fixed feedback icon display issues.
* Added functionality to identify groups and profiles in user profiles.
* Added group admin and moderator roles in the group todo report.
* Added filters to exclude admins and moderators in group reports.
* Fixed reports page UI and group member tab UI.
* Fixed todo form and category display with Youzify.
* Fixed submenu tab display with Youzify.
* Fixed group todo submenu UI issues.
* Fixed reports sorting arrow issue with BuddyBoss theme.
* Fixed PHP warnings and fatal errors when BuddyPress is not active.
* Fixed console issues in member profiles and the todo list.
* Fixed errors related to map functionality and frontend checkbox display.
* Added report tab to group todo functionality.
* Added stats icon for the group todo report display.
* Automatically published posts for administrators using filters.
* Defaulted to select all users for the "create todo" option.
* Removed delete icons and single todo links from group todos.
* Optimized usermeta keys for email notifications.
* Fixed text domain issues and added translation support for distance units (KM, Miles).
* Fixed redirection issues during plugin activation on multisite setups.
* Removed unnecessary inline code and unused variables for better performance.
* Enhanced security with escaping, sanitization, and nonce verification.
* Improved email content formatting and notification settings.

= 3.2.1 =
- Compatibility check with WordPress 6.5.0

= 3.2.0 =
- Resolved PHP warnings and profile sidebar display issues. (#259)
- Addressed due date field malfunction in group settings. (#257)
- Corrected group Todo percentage tracking and description editing. (#254, #256)
- Improved compatibility with BuddyPress v12 functions. (#258)
- Updated top banner visuals and navigation links.
- Enhanced user notifications for group Todo creations. (#244)

= 3.1.1 =
* Fix: (#226)Fixed todo summary is not editable input is not saving
* Fix: (#229)Fixed header menu not clickable when plugin activate

= 3.1.0 =
* Fix: (#221) Fixed notices
* Fix: Profile todo UI with BB

= 3.0.0 =
* Fix: Rewamped the plugin

= 2.4.0 =
* Fix: (#174) Fixed quick view dispaly Issue
* Fix: (#162) Managed todo-list shortcode login form
* Fix: (#170) Hide groups todos tab for logout user
* Fix: (#168) Removed div tag of report is displaying on single todo page
* Fix: (#163) Fixed shortcode page message string is not changing according 
* Fix: (#160) Fixed Remove todo option display issue
* Fix: (#158) Plugin is deactivated if BuddyPress is not activated
* Fix: (#159) Fixed profile tab title missing after plugin activation issue
* Fix: (#156) Can't translate tasks/Todo's on front end(user's profile)
* Enhancement: Add WPML plugin support

= 2.3.0 =
* Fix: BB support
* Enhancement: Added Group Support
* Enhancement: Reports for Group Admin and Moderators

= 2.2.0 =
* Fix: Fixed Group id meta values with each task for groups

= 2.1.0 =
* Enhancement: Added support for BuddyPress Groups
* Fix: Added condition to deactivate plugin is BuddyPress class does not exist.
* Fix: (#57) Manage calendar UI
* Fix: Added Support for Learndash Dashboard plugin
* Fix: (#47) - Uncategorized” category slug does not change.
* Fix: (#48) - Could not translate some strings.
* Fix: (#54) - Add actions/filters in buddypress todo edit form.

= 2.0.1 =
* Fix - Compatibility with BuddyPress 4.3.0. #45
* Fix - Changed font-awesome version. #46

= 2.0.0 =
* Fix - Compatibility with BuddyPress 4.1.0 #41
* Enhancement- Improve Backend UI where you can manage all wbcom plugin's settings at one place. #43

= 1.0.6 =
* Fix - Fixed Translation issues.
* Fix - Compatibility with BuddyPress 3.2.0
* Enhancement - Added French translation files – credits to Jean Pierre Michaud

= 1.0.5 =
* Fix - slug issue in other language.

= 1.0.4 =
* Fix - BuddyPress 3.1.0 Compatible
* Fix - Added setting for todo label plural.

= 1.0.3 =
* Enhancement - Layout changes for task management
* Enhancement - Added Visual editor support for new task
* Enhancement - Added tags for each task to set priorities
* Fix - Conflict with BP reaction
* Fix - Dedicated support for Reign theme.

= 1.0.2 =
* Added Todo dashboard
* Added Todo priority
* Changed UI

= 1.0.1 =
Plugin with admin options and modified todo list view in front end.

= 1.0.0 =
Initial Release

