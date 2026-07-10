=== Todo for BuddyPress & BuddyBoss ===
Contributors: wbcomdesigns, vapvarun
Donate link: https://wbcomdesigns.com/contact/
Tags: buddypress, todo list, task management, group tasks, productivity
Requires at least: 6.5
Tested up to: 6.8.1
Stable tag: 4.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wb-todo
Domain Path: /languages

A modern to-do and task manager for WordPress. Every logged-in user gets a private task list they manage without page reloads. BuddyPress and BuddyBoss are optional and add profile tabs and group to-dos.

== Description ==

**Todo for BuddyPress & BuddyBoss** is a task manager for any WordPress site. Add the "My Todos" block or the [bptodo_list] shortcode to a page and every logged-in user gets their own private to-do list: add, complete, edit, filter, search and paginate, all through a fast no-reload interface built on the WordPress Interactivity API and REST.

BuddyPress or BuddyBoss is completely optional. When either is active, the plugin also adds a To-do tab on member profiles and group to-dos with member assignment and progress reporting. When neither is active, everything else still works as a standalone personal task app.

= What you get =

**A modern personal to-do list (no BuddyPress needed)**
* Place it anywhere with the "My Todos" block or the [bptodo_list] shortcode.
* Inline quick-add: type a task and press Enter.
* Complete, undo, edit (title, details, priority, due date) and delete right in the list, with no page reloads.
* Expandable task details, search, and filters for status, priority and category.
* Server-side filtering, search and pagination that cover every task, not just the current page.
* Private by design: to-dos have no public page, no archive, and never appear in site search.

**Categories, priorities and reminders**
* Organize tasks with custom categories; optionally let members create their own.
* Priority levels (Critical, High, Normal) with clear visual indicators.
* Due dates with optional email reminders, and an optional "require a due date" rule.

**With BuddyPress or BuddyBoss (optional)**
* A To-do tab on every member profile.
* Group to-dos: assign a task to specific members or the whole group, each member gets their own copy.
* Group overview: per-member completion progress with All, Completed and Pending filters.
* BuddyPress notifications and email reminders when a due date arrives.
* Site admins get read-only oversight of group tasks.

**Modern by default, classic on demand**
* The modern block/REST interface is the default. A one-click Classic layout keeps the older templates if you hit a theme or plugin conflict.
* The plugin auto-falls back to Classic on WordPress below 6.5.
* Switching layout never changes your data.

**Automated data retention**
* Optional cleanup of old completed tasks on a schedule, with configurable retention periods and trash-or-delete.

**Developer-friendly**
* REST API (bptodo/v1) for todos, completion, stats, categories and group todos.
* "My Todos" and "Group Todos" dynamic blocks.
* A single domain layer (repository + permissions) shared by the REST API, blocks and the classic UI.
* Action and filter hooks, translation ready with a POT file, and RTL support.

= Perfect For =

* Any WordPress site that needs a simple, private per-user task list
* Membership sites and online courses - assignments and checklists
* Teams and intranets - shared goals and accountability
* BuddyPress and BuddyBoss communities - personal and group task management

= Premium Support =

Our dedicated support team is ready to help you get the most out of this plugin. Whether you need help with setup, customization, or troubleshooting, we're here to assist.

= Documentation =

Comprehensive guides included in the plugin directory:

* **USER-GUIDE.md** - Complete walkthrough of all features, settings, and usage instructions
* **DEVELOPER-GUIDE.md** - Technical documentation with hooks, filters, database structure, and code examples

= Live Demo =

See the plugin in action before installing. Visit our demo site to explore all features with sample data.

= Translations =

* English (default)
* French - Credits to Jean Pierre Michaud
* Ready for translation in your language with included POT file
* RTL language support included

= Links =

* [Plugin Homepage](https://wbcomdesigns.com/downloads/buddypress-user-todo-list/)
* [Live Demo](https://demos.wbcomdesigns.com/wbcomplugins/)
* [Documentation & Support](https://wbcomdesigns.com/helpdesk/article-categories/bp-user-todo-list/)
* [GitHub Repository](https://github.com/wbcomdesigns/buddypress-user-todo-list/)
* [Request Features](https://wbcomdesigns.com/contact/)

= Compatibility =

* BuddyPress 12.0 and higher (backwards compatible with older versions)
* BuddyBoss Platform (all versions)
* WordPress 6.5 and higher
* PHP 7.4 and higher (8.0+ recommended)
* Tested with popular themes: BuddyX, Reign, Youzify, and more
* Full support for both classic and block editor

= What's New in 3.5.0 =

This major update brings enterprise-level features including group overview reporting, automated data retention, enhanced performance with caching, and full BuddyPress 12.0+ compatibility while maintaining backwards compatibility with older versions and BuddyBoss.

== More Free Tools from Wbcom Designs ==

Task lists keep your members and teams organized, but a productive community needs more than tracking who is doing what. These other free plugins from Wbcom Designs fill in the space your members work in, from the social network itself to forums, media sharing, directories, and courses.

* **[BuddyNext](https://wbcomdesigns.com/downloads/buddynext/)** - Stand up a complete WordPress community with activity streams, member spaces, profiles, direct messaging, and built-in moderation.
* **[Jetonomy](https://wbcomdesigns.com/downloads/jetonomy/)** - Add forums, question-and-answer boards, and idea spaces that stay tidy through trust-based auto-moderation even past 100,000 topics.
* **[Mediaverse](https://wbcomdesigns.com/downloads/mediaverse/)** - Let members build photo and video albums, react, follow each other, and message privately while AI moderation keeps things clean.
* **[Listora](https://wbcomdesigns.com/downloads/listora/)** - Publish searchable directories across ten listing types with reviews, maps, and member-submitted entries from the front end.
* **[Learnomy](https://wbcomdesigns.com/downloads/learnomy/)** - Build and sell online courses, auto-grade quizzes, collect payments, and award certificates when learners finish.

== Installation ==

= Automatic Installation (Recommended) =

1. Log in to your WordPress admin dashboard
2. Navigate to Plugins > Add New
3. Search for "Todo for BuddyPress"
4. Click "Install Now" on the Todo for BuddyPress & BuddyBoss plugin
5. After installation, click "Activate"
6. Go to User Todo List > Settings to configure the plugin
7. Customize labels, permissions, and notification settings
8. Done! Your members can now access the Todo tab in their profiles

= Manual Installation =

1. Download the plugin ZIP file from WordPress.org
2. Log in to your WordPress admin dashboard
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the downloaded ZIP file and click "Install Now"
5. After installation, click "Activate Plugin"
6. Configure settings at User Todo List > Settings

= Post-Installation Setup =

After activating the plugin, we recommend:

1. Visit **User Todo List > Settings > General Settings** to customize todo labels
2. Enable or disable email notifications based on your community needs
3. Configure group settings if you want to enable group task management
4. Create initial categories at **User Todo List > Add New Category**
5. Test the functionality by creating a personal todo from your profile
6. Review the USER-GUIDE.md file included in the plugin directory for detailed instructions

= Requirements =

* BuddyPress 12.0+ or BuddyBoss Platform installed and activated
* WordPress 6.5 or higher
* PHP 7.4 or higher
* MySQL 5.7 or higher (8.0+ recommended)

== Frequently Asked Questions ==

= Is BuddyPress required for this plugin? =

Yes, this plugin requires either BuddyPress 12.0 or higher, or BuddyBoss Platform to be installed and activated. It's specifically designed to integrate with BuddyPress/BuddyBoss member profiles and groups.

= Does this work with BuddyBoss Platform? =

Absolutely! The plugin is fully compatible with BuddyBoss Platform and has been tested extensively to ensure seamless integration with all BuddyBoss features.

= Can members see each other's personal todos? =

No. Personal todos are completely private. Only the member who created the todo can view and manage their personal tasks. However, group todos are visible to group administrators and moderators for tracking purposes.

= How do group todos work? =

When a group admin or moderator creates a group todo, they can assign it to all members or selected individuals. Each assigned member receives their own copy of the task in their personal todo list, which they can mark complete independently. The group overview page shows everyone's progress.

= Can I customize the "Todo" label? =

Yes! Navigate to User Todo List > Settings > General Settings. You can customize both the singular label (e.g., "Task") and plural label (e.g., "Tasks"), as well as the URL slug for the todo section.

= How do email notifications work? =

The plugin uses BuddyPress's native email system. Members receive automatic reminders before their tasks are due. Members can control their own notification preferences from their profile settings, and site admins can customize email templates from Settings > BuddyPress > Emails.

= Can members create their own categories? =

This is configurable by site admins. In the General Settings, you can choose to allow or disallow members from creating their own todo categories. If disabled, only site admins can create categories.

= Will old completed tasks slow down my database? =

No! The plugin includes an automatic data retention feature that cleans up old completed tasks. You can configure retention periods and choose whether to trash or permanently delete old tasks. This keeps your database optimized.

= Can site admins view all group todos? =

Yes! Site administrators have read-only access to all group tasks, even in groups they're not members of. This provides oversight without interfering with group operations. Tasks are displayed with a read-only badge and cannot be modified by non-member admins.

= Can I export todo lists? =

Yes, members can export their personal todo lists to CSV format from their profile. This is useful for offline tracking, reporting, or importing into other systems.

= Does this plugin work with multisite? =

Yes, the plugin supports WordPress multisite installations. You can activate it network-wide or on individual sites within your network.

= Can I display todos on other pages? =

Yes! Use the shortcode [bptodo_by_category] to display todos on any page or post. You can filter by category using [bptodo_by_category category="work"]. See the USER-GUIDE.md for more shortcode options.

= Is the plugin translation ready? =

Yes! The plugin is fully translation ready with an included POT file. It also includes RTL (right-to-left) language support for Arabic, Hebrew, and other RTL languages. French translation is already included.

= Where can I get support? =

We offer comprehensive support through multiple channels:
* [Support Portal](https://wbcomdesigns.com/helpdesk/article-categories/bp-user-todo-list/) - Submit tickets and browse knowledge base
* [Documentation](https://wbcomdesigns.com/downloads/buddypress-user-todo-list/) - Detailed guides and tutorials
* [GitHub](https://github.com/wbcomdesigns/buddypress-user-todo-list/) - Report bugs and request features
* USER-GUIDE.md and DEVELOPER-GUIDE.md files included in the plugin directory

= Can I customize the plugin? =

Yes! The plugin includes 15+ action hooks and 10+ filter hooks for developers to extend functionality. Complete documentation is provided in the DEVELOPER-GUIDE.md file. You can also override templates by copying them to your theme's buddypress/todo/ directory.

= Will this work with my theme? =

The plugin has been tested with popular BuddyPress and BuddyBoss themes including BuddyX, Reign, Youzify, and others. It follows BuddyPress template standards, so it should work with any properly coded BuddyPress-compatible theme.

= How often is the plugin updated? =

We regularly update the plugin to ensure compatibility with the latest versions of WordPress, BuddyPress, and BuddyBoss. Security patches are released promptly when needed.

== Screenshots ==

1. Modern to-do list - the personal list with a progress summary, inline quick-add, search and filters, and priority and due-date indicators. Complete, edit, filter and paginate with no page reloads.
2. Add a to-do - the full add form with description, category (including an inline "+ New" category creator), priority and due date.
3. Task details and inline editing - expand a task to read its details, and edit the title, details, priority and due date right in the list.
4. Group task assignment - assign a group task to specific members (chips with avatars) or leave everyone selected to assign the whole group.
5. Group overview - per-member completion progress with a task selector and All / Completed / Pending filters.
6. Dark mode - the modern list adapts automatically to the theme's dark mode.
7. Admin settings - switch between the Modern and Classic layout, and control labels, roles, member-created categories and due-date reminders.

== Upgrade Notice ==

= 4.0.0 =
Major update: the todo list now uses blocks + REST for a faster, richer experience and works without BuddyPress. Your data is unchanged. If a theme or plugin conflict appears, switch to the Classic layout in the plugin settings.

== Changelog ==

= 4.0.0 =

Major release: a modern, REST-backed, block-first todo experience that also works without BuddyPress. Existing data is unchanged.

* New      - Works standalone: personal todos for any logged-in WordPress user, with no BuddyPress required.
* New      - "My Todos" and "Group Todos" blocks (WordPress Interactivity API) - add, complete, filter and paginate with no page reloads.
* New      - REST API (bptodo/v1) for todos, completion, stats, categories and group todos.
* New      - Inline quick-add on the modern list.
* Improve  - Rebuilt on a single domain layer (repository + permissions) shared by the REST API, blocks and the classic UI.
* Improve  - Modern layout is the default; a Legacy toggle keeps the classic templates, and the plugin auto-falls back to Legacy on WordPress below 6.5.
* Improve  - Filtering and search now run server-side and cover every page, not just the current one.
* Improve  - Frontend assets load only on todo screens and pages that use a todo block or shortcode.
* Fix      - BuddyPress is no longer required; the plugin shows a dismissible notice instead of deactivating.
* Dev      - New hooks: the bptodo_layout_mode filter and bptodo_todo_created/updated/completed/deleted actions.


= 3.6.0 =
* Improve  - Rebuilt the admin settings screen on the modern Wbcom card-panel UI (sidebar navigation, Overview dashboard, cleaner forms). Same settings, same option keys.
* Improve  - Replaced browser alert/confirm dialogs with accessible toast and modal notifications across the admin and member screens.
* Improve  - Load the frontend CSS and JavaScript only on todo screens and pages that use a todo shortcode, instead of on every page.
* Fix      - Member list filters and search no longer clear the whole list when applied.
* Fix      - Fixed a PHP 8 fatal error in the list loading placeholder.
* Security - Added an explicit capability check to the General settings save handler.
* Dev      - Added admin/demo-tasks.json so the wp bptodo generate command creates varied demo data.

= 3.5.1 =
* Improvement: Enhanced task selector UI for better user experience
* Improvement: Improved UI layout in group and user profiles when sidebar is present

= 3.5.0 =
* New: Site administrators can now view all group tasks across the entire site with read-only access for better oversight
* New: Automated data retention system keeps your database clean by removing old completed tasks based on your preferences
* New: Group overview reporting with real-time progress tracking and member completion statistics
* New: WP-CLI command support for developers to generate test data and manage tasks from command line
* New: Comprehensive user and developer documentation included (USER-GUIDE.md and DEVELOPER-GUIDE.md)
* Enhancement: Complete email system overhaul - now uses BuddyPress native email system for better reliability
* Enhancement: Email templates now automatically respect your custom todo labels (e.g., "Task" instead of "Todo")
* Enhancement: Automatic email template installation on plugin activation - no manual setup required
* Enhancement: Smart task sorting - incomplete tasks appear first sorted by due date, completed tasks move to bottom automatically
* Enhancement: Implemented smart caching system for faster page loads (5-minute cache for todo counts)
* Enhancement: Added 15+ action hooks and 10+ filter hooks for extensibility
* Improved: Full compatibility with both BuddyPress and BuddyBoss Platform email systems
* Improved: Email reinstallation mechanism helps troubleshoot notification issues quickly
* Improved: Enhanced notifications when group tasks are assigned to members
* Improved: Category selection interface with better visual feedback
* Improved: Better empty state messages - shows "no todos found" when appropriate instead of blank screens
* Improved: Added direct links to view individual group todos from overview page
* Improved: DataTables assets now only load on group pages where needed, reducing file loading
* Improved: Optimized database queries for group todos - pages load significantly faster
* Improved: Email system uses efficient caching to reduce server load
* Improved: Automatic cache clearing ensures you always see up-to-date information
* Improved: Compatibility with popular themes including BuddyX, Reign, and Youzify
* Improved: Better multisite support with network-wide activation handling
* Fixed: Cron schedule registration timing issue that prevented scheduled tasks from running properly
* Fixed: Shortcode complete and undo buttons now work correctly across all themes
* Fixed: Dropdown text being cut off when adding todos on certain themes
* Fixed: Todo edit popup now properly updates content when you make changes
* Fixed: Access control checks ensure only authorized users can view group todo pages
* Fixed: Admin settings checkboxes now properly save both checked and unchecked states
* Fixed: Due date validation now correctly enforces required field settings across all forms
* Fixed: User role restrictions for creating todos now work as expected
* Fixed: Category functionality fully restored and improved
* Fixed: Group tab visibility settings now respect admin preferences correctly
* Fixed: Group todo filtering now shows only tasks for the current group
* Fixed: Permission checks improved throughout the plugin for better security
* Fixed: Shortcodes can now be used multiple times on the same page without conflicts
* Compatibility: Full BuddyPress 12.0+ compatibility while maintaining backwards compatibility
* Compatibility: WordPress.org localization compliance - ready for official translations
* Compatibility: Updated minimum requirements - WordPress 6.5+, PHP 7.4+ for better security

= 3.4.0 =
* Added To-Do tab navigation icons and UI improvements.
* Fixed: Network-wide plugin handling support.
* Fixed: Setting save issue with BuddyBoss platform.
* Fixed: Notification issue when all to-dos are deleted.
* Fixed: Email notification issues.
* Fixed: Task duplication when assigned to group admin/moderator.
* Fixed: Fatal error and incorrect timestamp issue with BuddyBoss.
* Fixed: Bulk plugin activate redirection bug.
* Fixed: JavaScript-related warnings and removed unnecessary console logs.
* Fixed: Group To-Do completion time issue.
* Fixed: Early text domain loading issue.
* Improved: Translated DataTable strings.
* Improved: Overall text strings and UI feedback messages.
* Cleanup: Removed unused files and optimized codebase.

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

