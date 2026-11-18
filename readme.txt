=== Todo for BuddyPress & BuddyBoss ===
Contributors: wbcomdesigns, vapvarun
Donate link: https://wbcomdesigns.com/contact/
Tags: buddypress, todo list, task management, group tasks, productivity
Requires at least: 6.5
Tested up to: 7.0
Stable tag: 4.0.1
Requires PHP: 8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wb-todo
Domain Path: /languages

A modern to-do and task manager for WordPress. Every logged-in user gets a private task list they manage without page reloads. BuddyPress and BuddyBoss are optional and add profile tabs and group to-dos.

== Description ==

**Todo for BuddyPress & BuddyBoss** is a task manager for any WordPress site. Add the "My Todos" block or the [bptodo_list] shortcode to a page and every logged-in user gets their own private to-do list: add, complete, edit, filter, search and paginate, all through a fast no-reload interface built on the WordPress Interactivity API and REST.

BuddyPress or BuddyBoss is optional, on single sites and on multisite alike. When either is active, the plugin also adds a To-do tab on member profiles and group to-dos with member assignment and progress reporting. When neither is active, the personal task list still works as a standalone app. Email reminders need BuddyPress or BuddyBoss active; data retention does not.

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
* Due dates, and an optional "require a due date" rule.
* Email reminders before a task is due (these use the BuddyPress email system, so they need BuddyPress or BuddyBoss active).

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
* Optional cleanup of old completed tasks on a schedule, with configurable retention periods and trash-or-delete. Works with or without BuddyPress.

**Developer-friendly**
* REST API (bptodo/v1) for todos, completion, stats, categories and group todos.
* "My Todos" and "Group Todos" dynamic blocks.
* WP-CLI commands to seed demo tasks and run cleanup.
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

* **[Documentation and Support](https://docs.wbcomdesigns.com/)** - Setup walkthrough, settings reference, and usage guides
* The Settings > Shortcodes tab documents every shortcode and parameter inside the plugin itself

= Translations =

* English (default)
* Ready for translation in your language with included POT file
* RTL language support included

= Links =

* [Plugin Homepage](https://wbcomdesigns.com/downloads/buddypress-user-to-do-list/)
* [Documentation](https://docs.wbcomdesigns.com/)
* [Support](https://wbcomdesigns.com/support/)
* [Request Features](https://wbcomdesigns.com/contact/)

= Compatibility =

* WordPress 6.5 and higher
* PHP 8.0 and higher
* BuddyPress 12.0+ or BuddyBoss Platform - optional, for profile tabs, group to-dos and email reminders.
* Works with the block editor and classic editor
* Tested with popular themes including BuddyX, Reign and Youzify

= What's New in 4.0.0 =

Version 4.0 turns the plugin into a modern to-do app for any WordPress site. The list is rebuilt on the WordPress Interactivity API and a REST API for a fast, no-reload experience, and it now works without BuddyPress: add the "My Todos" block or the [bptodo_list] shortcode to a page and every logged-in user gets a private task list. To-dos are private by design, with no public page or archive. BuddyPress and BuddyBoss remain fully supported and add profile tabs and group to-dos on top. Your data is unchanged; a one-click Classic layout is available if you need the older templates.

== More Free Tools from Wbcom Designs ==

Task lists keep your members and teams organized, but a productive community needs more than tracking who is doing what. These other free tools from Wbcom Designs fill in the space your members work in, from the theme and social network itself to forums, media, events, gamification, directories, jobs, and courses.

* **[BuddyX](https://wbcomdesigns.com/downloads/buddyx-theme/)** - A free, fast community theme for BuddyPress, BuddyBoss and PeepSo with a modern layout and dark mode.
* **[BuddyNext](https://wbcomdesigns.com/downloads/buddynext/)** - Stand up a complete WordPress community with activity streams, member spaces, profiles, direct messaging, and built-in moderation.
* **[Jetonomy](https://wbcomdesigns.com/downloads/jetonomy/)** - Add forums, question-and-answer boards, and idea spaces that stay tidy through trust-based auto-moderation even past 100,000 topics.
* **[Mediaverse](https://wbcomdesigns.com/downloads/mediaverse/)** - Let members build photo and video albums, react, follow each other, and message privately while AI moderation keeps things clean.
* **[Eventonomy](https://wbcomdesigns.com/downloads/eventonomy/)** - Run community events with RSVPs, calendars, and front-end submissions.
* **[WB Gamification](https://wbcomdesigns.com/downloads/wordpress-gamification-plugin/)** - Reward members with points, badges, and leaderboards to keep engagement high.
* **[Listora](https://wbcomdesigns.com/downloads/listora/)** - Publish searchable directories across ten listing types with reviews, maps, and member-submitted entries from the front end.
* **[WP Career Board](https://wbcomdesigns.com/downloads/wp-career-board/)** - Add a job board with front-end listings, applications, and employer profiles.
* **[Learnomy](https://wbcomdesigns.com/downloads/learnomy/)** - Build and sell online courses, auto-grade quizzes, collect payments, and award certificates when learners finish.

== Installation ==

= Automatic Installation (Recommended) =

1. Log in to your WordPress admin dashboard
2. Navigate to Plugins > Add New
3. Search for "Todo for BuddyPress"
4. Click "Install Now" on the Todo for BuddyPress & BuddyBoss plugin
5. After installation, click "Activate"
6. Go to WB Plugins > Todo Lists to configure the plugin
7. Customize labels, permissions, and notification settings
8. Add the "My Todos" block or the [bptodo_list] shortcode to a page so users can manage their tasks. With BuddyPress or BuddyBoss active, a To-do tab also appears on member profiles automatically

= Manual Installation =

1. Download the plugin ZIP file from WordPress.org
2. Log in to your WordPress admin dashboard
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the downloaded ZIP file and click "Install Now"
5. After installation, click "Activate Plugin"
6. Configure settings at WB Plugins > Todo Lists

= Post-Installation Setup =

After activating the plugin, we recommend:

1. Visit **WB Plugins > Todo Lists > General** to customize todo labels
2. Enable or disable email notifications based on your community needs
3. Configure group settings under the **Group Todo** tab if you want group task management
4. Create initial categories at **Todo Items > Categories**
5. Add the "My Todos" block or the [bptodo_list] shortcode to a page, then test by creating a task
6. See the [documentation](https://docs.wbcomdesigns.com/) for detailed instructions

= Requirements =

* WordPress 6.5 or higher
* PHP 8.0 or higher
* MySQL 5.7 or higher (8.0+ recommended)
* BuddyPress 12.0+ or BuddyBoss Platform - optional, and needed for profile tabs, group to-dos and email reminders.

== Frequently Asked Questions ==

= Is BuddyPress required for this plugin? =

Not on a single site. Add the "My Todos" block or the [bptodo_list] shortcode to a page and every logged-in user gets their own private to-do list. BuddyPress and BuddyBoss are optional: when either is active, the plugin also adds a To-do tab on member profiles and group to-dos.

One thing to know. Email reminders run through BuddyPress, so they need BuddyPress or BuddyBoss active. Everything else, including automated data retention, works standalone on single sites and on multisite.

= How do I use it without BuddyPress? =

Create a page (for example "My Tasks") and add the "My Todos" block in the block editor, or use the shortcode [bptodo_list show_add="yes"]. Any logged-in user who opens that page can add, complete, edit and organize their own tasks. In Settings you can point the "Todos page" option at that page so reminder links resolve correctly.

= Does this work with BuddyBoss Platform? =

Yes. When BuddyBoss Platform is active, the plugin adds the To-do profile tab and group to-dos, and has been tested for seamless integration.

= Can members see each other's personal todos? =

No. Personal todos are completely private. Only the member who created the todo can view and manage their personal tasks. However, group todos are visible to group administrators and moderators for tracking purposes.

= How do group todos work? =

When a group admin or moderator creates a group todo, they can assign it to all members or selected individuals. Each assigned member receives their own copy of the task in their personal todo list, which they can mark complete independently. The group overview page shows everyone's progress.

= Can I customize the "Todo" label? =

Yes. Navigate to WB Plugins > Todo Lists > General. You can customize both the singular label (e.g., "Task") and plural label (e.g., "Tasks"), as well as the URL slug for the todo section.

= How do email notifications work? =

The plugin uses BuddyPress's native email system, so email reminders need BuddyPress or BuddyBoss active. Members receive automatic reminders before their tasks are due. Members can control their own notification preferences from their profile settings, and site admins can customize email templates from Settings > BuddyPress > Emails.

= Can members create their own categories? =

This is configurable by site admins. In the General Settings, you can choose to allow or disallow members from creating their own todo categories. If disabled, only site admins can create categories.

= Will old completed tasks slow down my database? =

The plugin includes an automatic data retention feature that cleans up old completed tasks. You can configure retention periods and choose whether to trash or permanently delete old tasks. It runs on a scheduled job and works with or without BuddyPress. You can also run it on demand with WP-CLI: wp bptodo cleanup.

= Can site admins view all group todos? =

Yes! Site administrators have read-only access to all group tasks, even in groups they're not members of. This provides oversight without interfering with group operations. Tasks are displayed with a read-only badge and cannot be modified by non-member admins.

= Does this plugin work with multisite? =

Yes, on any site in the network, with or without BuddyPress. Personal task lists work everywhere.

The group to-dos and the member profile tab follow BuddyPress: they appear on the site where BuddyPress serves its profiles and groups, which is the site set as BP_ROOT_BLOG (or every site, if you run BuddyPress with BP_ENABLE_MULTIBLOG). On other sites in the network you get the personal task list, and a notice explains that the group and profile features live on the BuddyPress site.

= Can I display todos on other pages? =

Yes. Add the "My Todos" block in the block editor, or use the [bptodo_list] shortcode on any page or post. Add show_add="yes" to include the quick-add field, and filter with attributes like [bptodo_list category="work" priority="high"]. There is also a [bptodo_by_category category="work"] shortcode for a single category, and a "Group Todos" block. The Settings > Shortcodes tab lists every parameter.

= Is the plugin translation ready? =

Yes! The plugin is fully translation ready with an included POT file. It also includes RTL (right-to-left) language support for Arabic, Hebrew, and other RTL languages. French translation is already included.

= Where can I get support? =

We offer comprehensive support through multiple channels:
* [Documentation](https://docs.wbcomdesigns.com/) - Free guides for every Wbcom plugin
* [Support](https://wbcomdesigns.com/support/) - Get help from our team
* [Contact us](https://wbcomdesigns.com/contact/) - Report bugs and request features

= Can I customize the plugin? =

Yes. The plugin fires a broad set of action and filter hooks for developers, and ships a REST API (bptodo/v1) plus a shared domain layer you can build against. Classic-layout templates can be overridden by copying them into a todo/ directory in your theme.

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

= 4.0.1 =

Group members can complete the group tasks assigned to them again, and the group Add and Add Media screens are fixed in dark mode.* Fix      - In the Classic layout, adding a category shows the reason when it is refused, and a new category is selected in the add form.
* Fix      - The Member-created categories setting now lets members add categories when it is turned on.
* Fix      - On BuddyBoss, group todo notifications and emails open the group's todo tab instead of a missing page.
* Fix      - Keyboard focus stays on the delete question and returns to Delete when you choose No.
* Fix      - Overdue, Delete and completed-count text is readable in light mode.
* Dev      - Renamed the admin page layout view file so security filters on some hosts no longer block the plugin upload.

= 4.0.0 =

Major update: the todo list now uses blocks + REST for a faster, richer experience and works without BuddyPress. Your data is unchanged. If a theme or plugin conflict appears, switch to the Classic layout in the plugin settings.

== Changelog ==

= 4.0.1 =

* Improve  - The group "Assign to members" picker lists the first 100 members A to Z instead of every member, so large groups load quickly. Filter `bptodo_assignee_picker_limit` changes the number.
* Improve  - Group and notification features load only when the BuddyPress Groups or Notifications component is turned on.
* Fix      - Members assigned a group task get their on-site notification again.
* Fix      - The search field on the todo list is a normal height on phones.
* Fix      - Plugin settings fit the screen on phones with no sideways scrolling.
* Fix      - Each task checkbox is announced with the task title.
* Fix      - Task checkboxes, Edit, Delete and member picker chips have a 40px touch target.
* Fix      - Edit button text and the "days left" text are easier to read in dark mode.
* Fix      - Edit moves keyboard focus to the title field, and after Delete focus moves to the next task.
* Fix      - Toast messages are announced politely instead of as alerts.
* Fix      - Group members now see and complete the tasks assigned to them on the group Todos tab; it listed the organiser's copy, so ticking a task failed with a permission error.
* Fix      - The group Todos tab count and stats match what each viewer sees: managers see the group's tasks, members see their own assignments.
* Fix      - The "Assign to Members" picker on the classic group Add form no longer breaks out of its box or fails to open.
* Fix      - Export on the classic My Todos screen is now a real, keyboard-reachable button.
* Fix      - The Add Media window is readable in dark mode: details panel text, fields, links and the selection bar.
* Fix      - Dropdown options in the members picker are readable in dark mode.
* Security - Todo stats for another member are only returned to site administrators.

= 4.0.0 =

Major release: a modern, block-first, REST-backed to-do experience that works on any WordPress site, with or without BuddyPress. Your existing data is unchanged.

* New      - Works on any WordPress site: add the "My Todos" block or the [bptodo_list] shortcode and every logged-in user gets a private to-do list, no BuddyPress required.
* New      - "My Todos" and "Group Todos" blocks built on the WordPress Interactivity API: add, complete, edit, filter and paginate with no page reloads.
* New      - REST API (bptodo/v1) for todos, completion, stats, categories and group todos.
* New      - Inline quick-add, inline edit of title, details, priority and due date, and an expandable details view on the list.
* New      - Members can create categories inline from the add form when the setting allows it.
* New      - A "Todos page" setting so reminder and view links resolve to your block or shortcode page when BuddyPress is not active.
* New      - Added German, Spanish, French, Italian and Portuguese (Brazil) translations.
* Improve  - Rebuilt on a single domain layer (repository plus permissions) shared by the REST API, blocks and the classic UI.
* Improve  - Modern layout is the default; a Classic toggle keeps the older templates, and the plugin auto-falls back to Classic on WordPress below 6.5.
* Improve  - Filtering, search and pagination run server-side and cover every task, not just the current page.
* Improve  - The Todo label and Require due date settings now apply to the modern layout too.
* Improve  - Group add uses the modern form with member assignment; the group sub-tab is now Todos, Add and Overview.
* Improve  - Frontend assets load only on to-do screens and pages that use a to-do block or shortcode.
* Fix      - BuddyPress is optional: the plugin shows a dismissible notice instead of deactivating when it is absent.
* Fix      - Search on the modern list now filters correctly.
* Fix      - Bundled translations could never load because the Domain Path header was missing. WordPress 6.7+ needs it to locate the translation files.
* Fix      - The group overview status labels, the category date groupings and the task status column had no usable translatable source, so they always rendered in English.
* Fix      - The text domain loaded too early, which logs a notice on WordPress 6.7 and newer. It now loads on init.
* Security - To-dos are private: no public single page, archive or site-search entry, and all viewing goes through permission-checked screens.
* Security - Creating a group to-do requires a group manager, and writing a to-do can no longer create arbitrary categories.
* Dev      - New bptodo/v1 REST API, my-todos and group-todos blocks, the bptodo_layout_mode and bptodo_standalone_todos_url filters, and bptodo_todo_created lifecycle action.

= 3.6.0 =
* Improve  - Rebuilt the admin settings screen on the modern Wbcom card-panel UI (sidebar navigation, Overview dashboard, cleaner forms). Same settings, same option keys.
* Improve  - Replaced browser alert/confirm dialogs with accessible toast and modal notifications across the admin and member screens.
* Improve  - Load the frontend CSS and JavaScript only on todo screens and pages that use a todo shortcode, instead of on every page.
* Fix      - Member list filters and search no longer clear the whole list when applied.
* Fix      - Fixed a PHP 8 fatal error in the list loading placeholder.
* Security - Added an explicit capability check to the General settings save handler.
* Dev      - Added admin/demo-tasks.json so the wp bptodo generate member-todos command creates varied sample data.

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

