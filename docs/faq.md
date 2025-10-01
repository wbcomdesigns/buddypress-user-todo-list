# Frequently Asked Questions (FAQ)

Quick answers to common questions about Todo for BuddyPress & BuddyBoss.

## 📋 General Questions

### What is this plugin?

Todo for BuddyPress & BuddyBoss is a complete task management system for your community site. Members can create personal todo lists, group admins can assign tasks to members, and everyone can track their progress.

### Do I need BuddyPress or BuddyBoss?

**Yes**, you need either:
- BuddyPress plugin (free), OR
- BuddyBoss Platform (premium)

The plugin won't activate without one of these.

### Is it free?

**Yes!** The core plugin is completely free and open source.

### Where can I get support?

- **Documentation:** You're reading it!
- **Support Forum:** [Wbcom Designs Helpdesk](https://wbcomdesigns.com/helpdesk/)
- **Bug Reports:** [GitHub Issues](https://github.com/wbcomdesigns/buddypress-user-todo-list/)

---

## 🔧 Installation & Setup

### Plugin won't activate

**Most common cause:** BuddyPress/BuddyBoss not installed

**Solution:**
1. Install BuddyPress OR BuddyBoss first
2. Activate BuddyPress/BuddyBoss
3. Then activate Todo plugin

### Where are the settings?

**Location:** `WordPress Admin > BuddyPress > Settings > Todo List`

Or for BuddyBoss: `Settings > BuddyBoss > Integrations > Todo List`

### Can I change "Todo" to something else?

**Yes!**
1. Go to plugin settings
2. Find "Profile Menu Label"
3. Change to: Tasks, Projects, Goals, etc.
4. Save settings

### How do I enable/disable features?

Go to settings page and toggle:
- Enable Todo for Members
- Allow Users to Add Categories
- Send Email Notifications
- Send BuddyPress Notifications

---

## 👥 User & Permissions

### Who can create todos?

By default: All user roles except Administrator

**To change:**
1. Go to settings
2. "User Roles" section
3. Check/uncheck roles
4. Save

### Can guests see todos?

**No.** Todos are private to:
- The member who created it
- Admins (via admin panel)
- Group members (for group todos)

### Why can't members see Todo tab?

**Possible reasons:**
1. Feature disabled in settings
2. User role not permitted
3. Plugin not activated
4. Cache needs clearing

### Can members delete their todos?

**Yes**, members can:
- Create todos
- Edit their todos
- Delete their todos
- Mark complete/incomplete

---

## 📧 Notifications & Emails

### Email notifications not working

**Check these:**

1. **Plugin setting:** Is "Send Email Notifications" ON?
2. **User setting:** Has user enabled email notifications?
3. **Email template:** Does it exist in `BuddyPress > Emails`?
4. **Server email:** Test WordPress email functionality
5. **Due date:** Emails only sent on due date (not before)

**Test email:**
1. Go to `Tools > Site Health`
2. Click "Info" tab
3. Test email delivery

### How to customize email template

1. Go to `BuddyPress > Emails`
2. Find "Todo Due Date Reminder"
3. Click Edit
4. Customize subject and content
5. Use tokens: `{{{bptodo.title}}}`, `{{{recipient.name}}}`
6. Save changes

### When are notifications sent?

**Email:** Morning of due date (once per todo)

**BuddyPress Notification:** When due date arrives

**Not sent:**
- Before due date
- After due date passed
- If already sent
- If user disabled notifications

### Can I disable notifications for specific users?

**Yes**, each user controls their own notifications in profile settings.

**Admin can:**
- Disable globally (all users)
- But can't disable for specific users

---

## 📝 Creating & Managing Todos

### Are due dates required?

**Depends on admin settings.**

If required:
- Can't save without due date
- Enforced on all forms

If optional:
- Can leave blank
- No reminders (need date for reminders)

### Can I set recurring todos?

**Not built-in**, but you can:
- Manually recreate tasks
- Use categories for recurring types
- Set due dates weekly/monthly

### How to organize todos?

**Use categories:**
- Work, Personal, Projects, etc.
- Admin or users can create (based on settings)
- Filter list by category

**Use priorities:**
- High, Medium, Low
- Visual indicators
- Sort by priority

### Can I assign todos to others?

**Only via groups:**
- Group admin/moderator creates group todo
- Assigns to group members
- Members see in their personal list
- Members mark complete

**No direct assignment** between individual members.

### Difference between personal and group todos?

**Personal todos:**
- Created by member for themselves
- Only they see it
- Full control (edit, delete)

**Group todos:**
- Created by group admin/mod
- Assigned to group members
- Members can only mark complete
- Admin maintains control

---

## 🔍 Display & Shortcodes

### How to show todos on other pages?

**Use shortcodes:**

```
[bptodo_list] - Current user's todos
[bptodo_by_category category="5"] - Category todos
```

See [Shortcodes Guide](shortcodes.md) for details.

### Can I embed in sidebar/widget?

**Yes!**

1. Go to `Appearance > Widgets`
2. Add "Custom HTML" or "Shortcode" widget
3. Enter shortcode: `[bptodo_list]`
4. Save

### Todos not showing in profile

**Check:**
1. Plugin activated?
2. User role has permission?
3. Feature enabled in settings?
4. Clear cache and reload

### Can I customize the display?

**Yes, with CSS:**

Add to theme or Customizer:
```css
.bptodo-item {
    /* Your custom styles */
}
```

See [Shortcodes Guide](shortcodes.md#styling-shortcodes) for examples.

---

## 👥 Groups

### How to enable group todos?

Settings > Group Settings > Enable Todo Tab in Groups: ON

### Who can create group todos?

- Group Administrators
- Group Moderators (if enabled in settings)

### Can members edit group todos?

**No.** Members can only:
- View group todos
- Mark them complete/incomplete

**Only group admin/mod can:**
- Create
- Edit
- Delete

### Where do group todos appear?

**Two places:**
1. Group's Todo tab (all members see)
2. Each member's personal todo list (tagged with group)

### How to track group progress?

Group admins can view:
- Completion statistics
- Individual member progress
- Overall group completion rate

Access via group's Todo tab > Reports

---

## 📊 Data & Export

### Can I export todos?

**Yes!**

1. Go to your todo list
2. Click "Export" button
3. Download CSV file

Contains: titles, dates, categories, status, etc.

### Can I import todos?

**Not built-in.** But you can:
- Manually create
- Use WP-CLI commands (developers)
- Database import (advanced)

### Where is data stored?

- **Todos:** WordPress custom post type `bp-todo`
- **Categories:** Custom taxonomy `bp_todo_category`
- **Settings:** WordPress options table
- **User preferences:** User meta table

### What happens to todos when user is deleted?

**Depends on WordPress settings:**

- Keep posts: Todos remain, author changed to deleted user
- Delete posts: Todos deleted with user

Configure in `Users > user > Delete`

### Can I backup todos?

**Yes, multiple ways:**

1. **Export feature** (per user)
2. **WordPress backup plugins** (full site)
3. **Database backup** (includes all todos)

---

## 🎨 Customization

### Can I change colors/styling?

**Yes!** Add custom CSS:

```css
/* Example: Change priority colors */
.bptodo-priority-high {
    color: #dc3232;
}
```

Add via:
- Theme customizer
- Child theme style.css
- Custom CSS plugin

### Can I add custom fields?

**Requires development:**

Use WordPress filters/actions to extend functionality. See plugin code for available hooks.

### Can I translate the plugin?

**Yes!** Plugin is translation-ready.

1. Use translation plugin (Loco Translate, WPML)
2. Or create `.po` file for your language
3. Submit translation to plugin authors

---

## 🔒 Security & Privacy

### Are todos private?

**Yes:**
- Members see only their own
- Group members see group todos
- Admins can view all (admin panel)

### GDPR compliance?

**Plugin is GDPR-friendly:**

- Users can view their data
- Users can export their data
- Users can delete their todos
- Admins can delete user data

### Can I hide from search engines?

**Already hidden:**
- Todos not indexed by search engines
- Require login to view
- No public pages

---

## 🐛 Troubleshooting

### Settings not saving

**Try:**
1. Disable other plugins temporarily
2. Switch to default theme
3. Check browser console for errors
4. Clear cache
5. Try different browser

### Todos disappeared

**Check:**
1. Not filtered (show "All" status)
2. Not in different category
3. Plugin still activated
4. Database okay (contact host)

### Dates showing wrong format

**Solution:**
Plugin uses WordPress date format.

Change at: `Settings > General > Date Format`

### Performance issues

**If slow:**
1. Limit todos displayed
2. Use caching plugin
3. Optimize database
4. Check server resources
5. Update WordPress/PHP

---

## 🚀 Advanced

### WP-CLI commands available?

**Yes!** See [WP-CLI Commands](wp-cli-commands.md)

Examples:
```bash
wp bptodo generate member-todos --count=10
wp bptodo generate group-todos --count=5
```

### Hooks/filters for developers?

**Yes!** Plugin includes:

- Action hooks for extensions
- Filters for customization
- Well-documented code

See plugin source code for available hooks.

### REST API support?

**Partial.** Uses WordPress Post REST API.

Endpoints available at: `/wp-json/wp/v2/bp-todo`

### Multisite compatible?

**Yes!** Works on multisite WordPress:

- Network activate: Available all sites
- Site activate: Per-site basis
- Independent settings per site

---

## 💬 Still Have Questions?

**Can't find your answer?**

1. Check other documentation pages
2. Visit [support forum](https://wbcomdesigns.com/helpdesk/)
3. Ask on [GitHub](https://github.com/wbcomdesigns/buddypress-user-todo-list/)
4. Contact [Wbcom Designs](https://wbcomdesigns.com/contact/)

---

**Back to:** [Documentation Home](README.md)
