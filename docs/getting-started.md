# Getting Started with Todo for BuddyPress & BuddyBoss

This guide will help you install and configure the Todo plugin for your BuddyPress or BuddyBoss community.

## 📋 Requirements

Before installing, make sure you have:

- ✅ WordPress 6.5 or higher
- ✅ BuddyPress 12.0+ or BuddyBoss Platform (optional, for profile tabs, group to-dos and email reminders)
- ✅ PHP 8.0 or higher
- ✅ MySQL 5.6 or higher

## 🔧 Installation

### Method 1: WordPress Admin Panel (Recommended for Non-Technical Users)

1. **Login to WordPress Admin**
   - Go to `wp-admin` on your site
   - Navigate to `Plugins > Add New`

2. **Upload Plugin**
   - Click the `Upload Plugin` button
   - Choose the plugin ZIP file
   - Click `Install Now`

3. **Activate Plugin**
   - After installation, click `Activate Plugin`
   - You'll see a success message

### Method 2: FTP Upload (For Advanced Users)

1. **Extract ZIP File**
   - Unzip the plugin package on your computer
   - You'll see a folder named `buddypress-user-todo-list`

2. **Upload via FTP**
   - Connect to your server using FTP client
   - Navigate to `/wp-content/plugins/`
   - Upload the `buddypress-user-todo-list` folder

3. **Activate in WordPress**
   - Go to `Plugins` in WordPress admin
   - Find "Todo for BuddyPress & BuddyBoss"
   - Click `Activate`

## ⚙️ Initial Setup

### Step 1: Access Settings

After activation:
1. Go to `BuddyPress > Settings` in WordPress admin
2. Look for the "Todo List" tab
3. Click to open settings page

### Step 2: Configure Basic Settings

**Profile Menu Label** (Important!)
- Default: "Todo"
- This label appears in member profiles
- Example: Change to "Tasks", "My List", or "Projects"
- Users will see this in their navigation menu

**Profile Menu Label (Plural)**
- Default: "Todos"
- Used when showing multiple items
- Keep it consistent with singular label

### Step 3: Enable Features

**✓ Enable Todo for Members**
- Toggle ON to allow members to create todos
- Toggle OFF to disable the feature

**✓ Allow Users to Add Categories**
- Toggle ON to let members create custom categories
- Toggle OFF to restrict to admin-created categories only

**✓ Send Email Notifications**
- Toggle ON to send reminder emails for due dates
- Uses BuddyPress Email system (customizable)

**✓ Send BuddyPress Notifications**
- Toggle ON to show in-site notifications
- Members get notifications on due dates

### Step 4: Select User Roles

Choose which user roles can create todos:
- ☑️ Subscriber
- ☑️ Contributor
- ☑️ Author
- ☑️ Editor
- ⬜ Administrator (excluded by default)

**Tip:** Administrators can always manage todos regardless of this setting.

### Step 5: Save Settings

- Click the `Save Settings` button
- Wait for confirmation message
- Your settings are now active!

## 🎨 First Look for Members

After setup, members will see:

1. **New Profile Tab**
   - Visit any member profile
   - Look for the new "Todo" tab (or your custom label)
   - Click to view their todo list

2. **Todo Dashboard**
   - Shows incomplete and completed tasks
   - Quick stats (total, pending, completed)
   - Add new todo button

3. **Navigation**
   - View All Todos
   - Add New Todo
   - Categories (if enabled)

## 🧪 Test the Installation

### Quick Test Checklist

1. **Visit a Member Profile**
   ```
   Go to: yoursite.com/members/username/
   Check: Can you see the Todo tab?
   ```

2. **Create a Test Todo**
   ```
   Click: Add New Todo
   Fill: Title, Description, Due Date
   Submit: Save Todo
   Check: Does it appear in the list?
   ```

3. **Test Notifications** (Optional)
   ```
   Create: A todo with today's due date
   Wait: A few minutes
   Check: Did you receive email/notification?
   ```

4. **Test Group Todos** (If using groups)
   ```
   Go to: Any group you manage
   Look for: Todo tab
   Create: A group task
   Check: Can members see it?
   ```

## 🎓 Next Steps

Now that installation is complete:

1. **Customize Settings**
   - Review [Admin Settings Guide](admin-settings.md)
   - Adjust notifications and permissions

2. **Learn Member Features**
   - Read [Member Guide](member-guide.md)
   - Share with your community

3. **Explore Group Todos**
   - Check [Group Todos Guide](group-todos.md)
   - Set up team collaboration

4. **Add Shortcodes**
   - See [Shortcodes Guide](shortcodes.md)
   - Display todos on pages

## ⚠️ Common Installation Issues

### "Plugin could not be activated"

**Cause:** BuddyPress/BuddyBoss not installed

**Solution:**
1. Install BuddyPress or BuddyBoss Platform first
2. Activate BuddyPress/BuddyBoss
3. Then activate Todo plugin

### "Email notifications not working"

**Cause:** BuddyPress Email not configured

**Solution:**
1. Go to `BuddyPress > Emails`
2. Look for "Todo Due Date Reminder" email
3. Customize template if needed
4. Test email delivery with a test todo

### "Todo tab not showing"

**Cause:** Cache or permalink issue

**Solution:**
1. Go to `Settings > Permalinks`
2. Click "Save Changes" (no need to change anything)
3. Clear browser cache
4. Clear site cache (if using cache plugin)
5. Refresh member profile page

### "Permission denied for users"

**Cause:** User role not selected in settings

**Solution:**
1. Go to Todo settings
2. Check "User Roles" section
3. Enable required roles
4. Save settings

## 🆘 Still Need Help?

- 📖 Check [Troubleshooting Guide](troubleshooting.md)
- 💬 Visit [Support Forum](https://wbcomdesigns.com/helpdesk/)
- 🐛 Report bugs on [GitHub](https://github.com/wbcomdesigns/buddypress-user-todo-list/)

---

**Next:** [Admin Settings Guide](admin-settings.md) →
