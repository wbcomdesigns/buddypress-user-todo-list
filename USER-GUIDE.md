# Todo for BuddyPress & BuddyBoss - User Guide

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Initial Setup](#initial-setup)
4. [General Settings](#general-settings)
5. [Personal Todo Management](#personal-todo-management)
6. [Group Todo Management](#group-todo-management)
7. [Group Overview & Reports](#group-overview--reports)
8. [Data Retention](#data-retention)
9. [Email Notifications](#email-notifications)
10. [Shortcodes](#shortcodes)
11. [Categories](#categories)
12. [FAQ](#faq)

---

## Introduction

The **Todo for BuddyPress & BuddyBoss** plugin allows BuddyPress and BuddyBoss members to create, manage, and track tasks directly within your community. Members can organize personal todos, collaborate on group tasks, and receive notifications for due dates.

### Key Features

- ✅ **Personal Todo Lists** - Members can create and manage their own tasks
- ✅ **Group Todo Assignment** - Assign tasks to all group members or selected individuals
- ✅ **Group Overview & Reports** - Track task completion progress across members
- ✅ **Read-Only Admin View** - Site admins can view tasks in groups they're not members of
- ✅ **Due Date Reminders** - Automatic email and BuddyPress notifications
- ✅ **Priority Levels** - Mark tasks as Critical, High, or Normal priority
- ✅ **Categories** - Organize todos by custom categories
- ✅ **Data Retention** - Automatic cleanup of old completed tasks
- ✅ **CSV Export** - Export todo lists in CSV format
- ✅ **Shortcodes** - Display todos anywhere on your site
- ✅ **RTL Support** - Full right-to-left language support
- ✅ **BuddyBoss & BuddyPress 12.0+ Compatible**

---

## Installation

### Automatic Installation

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins → Add New**
3. Search for "Todo for BuddyPress"
4. Click **Install Now** on the plugin
5. Click **Activate** once installation is complete

### Manual Installation

1. Download the plugin ZIP file
2. Navigate to **Plugins → Add New → Upload Plugin**
3. Choose the downloaded ZIP file
4. Click **Install Now**
5. Click **Activate Plugin**

### Requirements

- WordPress 4.0.1 or higher
- BuddyPress 2.5+ or BuddyBoss Platform
- PHP 7.0 or higher

---

## Initial Setup

After activation, configure your plugin settings:

1. Navigate to **WP Admin → User Todo List → Settings**
2. Configure the following tabs:
   - **General Settings** - Customize labels, permissions, and features
   - **Group Settings** - Enable group todo functionality
   - **Data Retention** - Configure automatic cleanup of old tasks
   - **Shortcode Settings** - Manage shortcode display options

---

## General Settings

### Profile Menu Label

**Location:** General Settings → Profile Menu Label

Customize how the todo menu appears in member profiles.

- **Menu Label (Singular):** Default "Todo" - appears in navigation
- **Menu Label (Plural):** Default "Todos" - used in descriptions
- **Menu Slug:** URL slug for the todo section (e.g., `example.com/members/username/todo/`)

**Example:**
```
Singular: Task
Plural: Tasks
Slug: tasks
Result: example.com/members/john/tasks/
```

### User Permissions

**Allow User to Add Categories:** Enable/disable members from creating new todo categories.

- **Yes** - Members see "+ Add Category" button when creating todos
- **No** - Only site admins can create categories via **User Todo List → Add New Category**

### Due Date Settings

**Due Date Field Requirement:**

- **Yes** - Members must select a due date to create a todo
- **No** - Due date is optional

### Email Notifications

**Send Email Before Due Date:**

- **Yes** - Members receive an email reminder before tasks are due
- **No** - No email reminders sent

**Note:** Members can individually disable their own email notifications from their profile settings.

### BuddyPress Notifications

**Send BuddyPress Notification:**

- **Yes** - Members receive BuddyPress notifications for due tasks
- **No** - No BuddyPress notifications sent

### Frontend Options

**Task Completion:** Allow members to mark tasks complete from the frontend
**Task Editing:** Allow members to edit their tasks from the frontend
**Task Deletion:** Allow members to delete their tasks from the frontend

---

## Personal Todo Management

### Creating a Personal Todo

1. Navigate to your member profile
2. Click the **Todo** tab
3. Click **Add** sub-tab
4. Fill in the following fields:
   - **Category** - Select from existing categories (required)
   - **Title** - Enter task name (required)
   - **Summary** - Add detailed description (optional, rich text editor)
   - **Due Date** - Select deadline using date picker
   - **Priority** - Choose Critical, High, or Normal

5. Click **Submit Todo**

### Managing Your Todos

**View All Todos:**
- Navigate to your profile → Todo → List
- Todos are organized by due date:
  - **Past** - Overdue tasks (red highlight)
  - **Today** - Tasks due today (yellow highlight)
  - **Tomorrow** - Tasks due tomorrow
  - **Future** - Tasks due later
  - **Completed** - Finished tasks (strikethrough)

**Mark as Complete:**
- Click the checkbox next to any task
- Task moves to "Completed" section automatically

**Edit a Todo:**
- Click the edit icon (pencil) next to the task
- Update fields and click **Update Todo**

**Delete a Todo:**
- Click the delete icon (trash) next to the task
- Confirm deletion in the popup

**View Single Todo:**
- Click the task title to view full details
- See summary, priority, due date, and completion status

### Exporting Todos

Export your todo list to CSV format:

1. Go to your profile → Todo → List
2. Click **Export CSV** button (top right)
3. CSV file downloads with all your tasks

---

## Group Todo Management

### Enabling Group Todos

**Admin Setup:**
1. Navigate to **User Todo List → Settings → Group Settings**
2. Enable **Add To-Do Tab in Group**
3. Configure group-specific settings

### Creating Group Todos

Group administrators and moderators can assign tasks to members:

1. Navigate to your group page
2. Click **Todo** tab
3. Click **Add** sub-tab
4. Fill in task details:
   - **Category** - Select category
   - **Title** - Task name
   - **Summary** - Task description
   - **Due Date** - Deadline
   - **Priority** - Critical, High, or Normal
   - **Group Members** - Select who to assign this task to:
     - **All Members** - Assign to everyone in the group
     - **Selected Members** - Choose specific members

5. Click **Submit Todo**

### How Group Todos Work

When you create a group todo:

1. **Primary Todo** is created (owned by you, the creator)
2. **Associated Todos** are created for each selected member
3. Each member sees their own copy in their profile todo list
4. Members can mark their individual copy as complete
5. The Group Overview shows everyone's progress

**Example:**
```
Task: "Review Meeting Notes"
Assigned to: All Members (5 people)
Result:
- 1 primary todo (creator's copy)
- 4 associated todos (one for each other member)
- Total: 5 individual tasks for tracking
```

### Viewing Group Todos

**List View:**
- Group → Todo → List
- Shows all tasks created for this group
- See who created each task and when

**Site Admin Read-Only Access:**
- Site administrators can view group todos even if they're not group members
- Tasks display in read-only mode:
  - Checkboxes are disabled
  - "Read-Only" badge appears
  - Edit/Delete buttons hidden
  - Only primary tasks shown (no duplicates)
- If admin is a group member, normal permissions apply

---

## Group Overview & Reports

### Accessing Group Overview

Navigate to: **Group → Todo → Overview**

The Group Overview page provides comprehensive task completion statistics and member progress tracking.

### Features

#### 1. Task Selector Dropdown

View progress for:
- **Overall Progress (All Tasks)** - Shows group-wide statistics
- **Specific Task** - Select individual task to see member completion

#### 2. Overall Completion Stats

- **Progress Bar** - Visual representation of completion percentage
- **Completion Details:**
  - For overall view: "X of Y tasks completed (Z%)"
  - For specific task: "X of Y members completed (Z%)"

#### 3. Member Status Grid

Displays each member's progress with:
- **Avatar** - Member profile picture
- **Name** - Links to member profile
- **Status Badge:**
  - ✅ **Completed** - Task/all tasks finished (green)
  - ⏰ **Pending** - Task(s) still incomplete (orange)
- **Progress Info:**
  - Overall view: "3/5 tasks (60%)"
  - Specific task: Completion date/time

#### 4. Member Filters

Filter members by status:
- **All** - Show everyone
- **Completed** - Show only those who finished
- **Pending** - Show those with incomplete tasks

#### 5. Pagination

Browse members 15 per page with:
- Previous/Next buttons
- Page numbers
- "Showing X-Y of Z members"

### Use Cases

**Project Managers:**
- Track which team members completed their assigned tasks
- Identify blockers (members with low completion rates)
- Monitor overall project progress

**Group Admins:**
- See at-a-glance who's keeping up with group activities
- Follow up with members who haven't completed critical tasks
- Export data for reports

---

## Data Retention

Automatically cleanup old completed tasks to keep your database optimized.

### Accessing Settings

Navigate to: **User Todo List → Settings → Data Retention Settings**

### Configuration Options

#### Enable Automatic Cleanup

- **Enable Group Todo Cleanup** - Auto-delete old group tasks
- **Enable Personal Todo Cleanup** - Auto-delete old personal tasks

#### Retention Period

Set how long to keep completed tasks:

- **30 days** - Remove after 1 month
- **60 days** - Remove after 2 months
- **90 days** - Remove after 3 months (recommended)
- **180 days** - Remove after 6 months
- **365 days** - Remove after 1 year
- **Never** - Keep forever (disable cleanup)

**Minimum:** 7 days (for safety)

#### Delete Method

- **Move to Trash** - Completed tasks move to WordPress trash (recoverable for 30 days)
- **Permanent Delete** - Immediately and permanently delete tasks (cannot be recovered)

### Manual Cleanup

Run cleanup immediately without waiting for scheduled task:

1. Go to Data Retention Settings
2. Click **Run Cleanup Now** button
3. Choose cleanup type:
   - **All Completed Tasks** - Clean both group and personal
   - **Group Tasks Only** - Clean only group todos
   - **Personal Tasks Only** - Clean only personal todos

4. Confirm action

### Cleanup Schedule

- Automatic cleanup runs **daily at midnight** (server time)
- Only affects **completed** tasks older than the retention period
- Incomplete tasks are never automatically deleted

### Email Notifications

After each automatic cleanup, site admins receive an email report with:
- Cleanup date and time
- Number of tasks deleted (group/personal/total)
- Retention settings used
- Link to manage settings

### Best Practices

1. **Start Conservative:** Begin with 90 days retention
2. **Monitor Storage:** Check database size regularly
3. **Use Trash First:** Start with "Move to Trash" before using permanent deletion
4. **Inform Members:** Let users know old completed tasks will be removed
5. **Regular Backups:** Always maintain database backups before permanent deletions

---

## Email Notifications

### Member Email Settings

Members can control their own email preferences:

1. Navigate to: **Profile → Settings → Notifications**
2. Find **Todo Notifications** section
3. Enable/Disable: "Send me email when my todo is due"

### Admin Email Settings

**Location:** User Todo List → Settings → General Settings

- **Send Email Before Due Date:** Master switch for all email notifications

### Email Triggers

Members receive emails when:
1. **Task Due Soon** - Automated reminder before due date
2. **Group Task Assigned** - When added to a group todo
3. **Data Cleanup Complete** - Admins only, after automatic cleanup

### Email Content

Emails include:
- Task title and description
- Due date and priority
- Category
- Link to view task
- Link to mark as complete

### Using BuddyPress Native Emails

The plugin uses BuddyPress's native email system:
- **Customize Templates:** WP Admin → Settings → BuddyPress → Emails
- **Find Templates:** Search for "todo" or "task"
- **Edit Content:** Modify subject, body, HTML/Plain text
- **Use Tokens:** `{{task.title}}`, `{{task.due_date}}`, etc.

---

## Shortcodes

Display todos anywhere on your WordPress site using shortcodes.

### Basic Shortcode

```
[bptodo_by_category]
```

Displays all todos for the current logged-in user.

### Filter by Category

```
[bptodo_by_category category="work"]
```

Displays only todos from the "work" category.

### Multiple Categories

```
[bptodo_by_category category="work,personal,urgent"]
```

Use comma-separated category slugs to show multiple categories.

### Shortcode Settings

**Admin Configuration:**
Navigate to: **User Todo List → Settings → Shortcode Settings**

- **Enable Shortcode on Archive Pages:** Allow shortcode on category archives
- **Customize CSS Classes:** Add custom styling to shortcode output

### Usage Examples

**Page/Post:**
Create a "My Tasks" page and add the shortcode to the content editor.

**Widget:**
Use the "Shortcode" widget to add todo list to sidebar.

**PHP Templates:**
```php
<?php echo do_shortcode('[bptodo_by_category category="work"]'); ?>
```

### Non-Logged-In Users

If visitor is not logged in, shortcode displays:
- Login form
- Message: "Please log in to view your todos"

---

## Categories

### Managing Categories

**Admin Access:**
Navigate to: **User Todo List → Add New Category**

### Creating Categories

1. Enter **Category Name** (e.g., "Work", "Personal", "Urgent")
2. Add **Slug** (auto-generated from name, or customize)
3. Add **Description** (optional)
4. Click **Add New Category**

### User-Created Categories

If enabled in General Settings, members can create categories when adding todos:

1. When creating a todo, click the **+ icon** next to Category dropdown
2. Enter new category name
3. Click **Add**
4. New category becomes available immediately

### Category Best Practices

**Recommended Categories:**
- Work
- Personal
- Urgent
- Later
- Ideas
- Shopping

**Naming Tips:**
- Keep names short (1-2 words)
- Use clear, descriptive terms
- Avoid special characters
- Consider color-coding with custom CSS

---

## FAQ

### General Questions

**Q: Can members see each other's personal todos?**
A: No, personal todos are private. Only the member who created it can see their personal tasks.

**Q: Can I change the "Todo" label to something else?**
A: Yes! Go to Settings → General Settings → Profile Menu Label and customize the singular/plural labels.

**Q: Are group todos visible to all group members?**
A: Yes, group admins and moderators can see all group todos in the Group → Todo → List page. Regular members see only their assigned tasks.

**Q: Can site admins view group todos?**
A: Yes, site admins can view all group todos in read-only mode, even if they're not group members.

### Group Todos

**Q: What happens if I delete a group todo?**
A: Deleting the primary todo will delete all associated member copies as well.

**Q: Can members edit group todos assigned to them?**
A: Members can mark their copy as complete, but cannot edit the title, description, or due date.

**Q: How do I remove a specific member from a group todo?**
A: Currently, you need to delete their associated todo copy. Better member management is planned for future releases.

### Data Retention

**Q: Will incomplete tasks be deleted?**
A: No, only completed tasks older than the retention period are deleted.

**Q: Can I recover deleted tasks?**
A: If you use "Move to Trash," tasks can be recovered from WordPress trash within 30 days. Permanent deletions cannot be recovered.

**Q: How do I disable automatic cleanup?**
A: Set Retention Period to "Never" in Data Retention Settings.

### Notifications

**Q: How do I stop receiving todo emails?**
A: Go to your Profile → Settings → Notifications and disable "Send me email when my todo is due."

**Q: Can I customize email templates?**
A: Yes, go to WP Admin → Settings → BuddyPress → Emails and edit the todo-related email templates.

### Shortcodes

**Q: Can I display another user's todos with shortcodes?**
A: No, shortcodes only display the current logged-in user's tasks for privacy reasons.

**Q: Can I customize the shortcode output design?**
A: Yes, use custom CSS targeting the `.bptodo-shortcode` class or modify the plugin's templates.

### Troubleshooting

**Q: Todos not appearing in my profile**
A: Ensure BuddyPress/BuddyBoss is active and you've cleared browser cache.

**Q: Group todo tab missing**
A: Check Settings → Group Settings and enable "Add To-Do Tab in Group."

**Q: Email notifications not working**
A: Verify your WordPress site can send emails. Test with a plugin like WP Mail SMTP.

**Q: Due date field not appearing**
A: Check browser JavaScript console for errors. Ensure jQuery UI datepicker is loaded.

---

## Support & Resources

### Need Help?

- **Support:** [https://wbcomdesigns.com/helpdesk/](https://wbcomdesigns.com/helpdesk/)
- **Documentation:** [https://wbcomdesigns.com/downloads/buddypress-user-todo-list/](https://wbcomdesigns.com/downloads/buddypress-user-todo-list/)
- **Demo:** [https://demos.wbcomdesigns.com/wbcomplugins/](https://demos.wbcomdesigns.com/wbcomplugins/)
- **GitHub:** [https://github.com/wbcomdesigns/buddypress-user-todo-list/](https://github.com/wbcomdesigns/buddypress-user-todo-list/)

### Contributing

We welcome contributions! Please submit issues and pull requests on GitHub.

### Rate & Review

If you find this plugin helpful, please leave a review on WordPress.org!

---

**Version:** 3.5.0
**Last Updated:** January 2025
**Author:** Wbcom Designs
