# Admin Settings Guide

Complete guide to configuring Todo for BuddyPress & BuddyBoss plugin settings.

## 📍 Accessing Settings

**Location:** `WordPress Admin > BuddyPress > Settings > Todo List`

Or: `WordPress Admin > Settings > BuddyBoss > Integrations > Todo List` (for BuddyBoss)

## ⚙️ Settings Overview

The settings page is divided into several sections:

1. [Profile Menu Labels](#profile-menu-labels)
2. [Feature Toggles](#feature-toggles)
3. [User Roles](#user-roles)
4. [Group Settings](#group-settings)
5. [Email Configuration](#email-configuration)

---

## 🏷️ Profile Menu Labels

### Profile Menu Label (Singular)

**What it does:** Sets the label that appears in member profile navigation

**Default:** `Todo`

**Examples:**
- `Task` - For professional teams
- `Project` - For project management
- `Goal` - For personal development
- `Assignment` - For educational sites
- `Mission` - For gamified communities

**Where it appears:**
- Member profile navigation tab
- Breadcrumbs
- Page titles
- Notification messages
- Email subjects

**Best practices:**
- Keep it short (1-2 words)
- Use familiar terms for your audience
- Be consistent with your site's language
- Consider your community type

### Profile Menu Label (Plural)

**What it does:** Plural form of the menu label

**Default:** `Todos`

**Examples:**
- `Tasks`
- `Projects`
- `Goals`
- `Assignments`
- `Missions`

**Where it appears:**
- "All Todos" page title
- Count displays ("5 Todos completed")
- Export file names
- Reports

---

## 🎛️ Feature Toggles

### Enable Todo for Members

**Toggle:** ON / OFF

**Default:** ON

**When ON:**
- Members can access todo functionality
- Todo tab appears in profiles
- Members can create/manage todos

**When OFF:**
- Todo tab hidden from members
- Existing todos preserved but not accessible
- Only admins can access via admin panel

**Use case for OFF:**
- Temporarily disable during maintenance
- Restrict feature to specific periods
- Testing or development

### Allow Users to Add Categories

**Toggle:** ON / OFF

**Default:** ON

**When ON:**
- Members can create custom categories
- "Add Category" button visible in todo form
- Members organize tasks their way

**When OFF:**
- Only admin-created categories available
- Members can only select existing categories
- Ensures consistency across site

**Recommended settings:**
- **Small communities:** ON (more flexibility)
- **Large organizations:** OFF (maintain structure)
- **Educational sites:** OFF (standardized categories)

### Send Email Notifications

**Toggle:** ON / OFF

**Default:** ON

**When ON:**
- Users receive email when todo is due today
- Uses BuddyPress Email system
- Members can control their own email preference

**When OFF:**
- No emails sent (BuddyPress notifications still work if enabled)
- Reduces server email load
- Good for sites with external task management

**Email details:**
- Sent once per todo on due date
- Includes todo title and link
- Uses customizable BuddyPress email template
- Can be edited at `BuddyPress > Emails > Todo Due Date Reminder`

**User control:**
Each member can enable/disable their own email notifications in their profile settings.

### Send BuddyPress Notifications

**Toggle:** ON / OFF

**Default:** ON

**When ON:**
- In-site notifications appear in notification center
- Bell icon shows unread count
- Click to view todo

**When OFF:**
- No in-site notifications
- Reduces notification noise
- Email still works if enabled

**Notification details:**
- Appears in BuddyPress notification dropdown
- Links directly to the todo
- Sent on due date only
- Marks as read when clicked

---

## 👥 User Roles

### User Roles Who Can Create Todos

**Purpose:** Control which user roles have access to todo features

**Default:** All roles except Administrator

**Available roles:**
- ☑️ Subscriber
- ☑️ Contributor
- ☑️ Author
- ☑️ Editor
- ⬜ Administrator

**How it works:**
- Check roles that should have access
- Only checked roles can create todos
- Unchecked roles won't see todo tab
- Existing todos remain if role is disabled

**Common configurations:**

**Open Community:**
```
☑️ Subscriber (everyone can use todos)
☑️ Contributor
☑️ Author
☑️ Editor
```

**Members Only:**
```
⬜ Subscriber (guests can't create)
☑️ Contributor
☑️ Author
☑️ Editor
```

**Staff Only:**
```
⬜ Subscriber
⬜ Contributor
☑️ Author (only staff and above)
☑️ Editor
```

**Note:** Administrators can always manage todos via admin panel regardless of this setting.

---

## 👥 Group Settings

### Enable Todo Tab in Groups

**Toggle:** ON / OFF

**Default:** ON

**When ON:**
- Groups show todo tab
- Group admins/moderators can create tasks
- Tasks assigned to group members
- Progress tracking available

**When OFF:**
- No group todos
- Only personal todos work
- Good for member-focused communities

### Group Admin/Moderator Permissions

**Create Tasks:** ON / OFF
- Moderators can create group tasks

**Edit Tasks:** ON / OFF
- Moderators can edit existing tasks

**Delete Tasks:** ON / OFF
- Moderators can remove tasks

**View Reports:** ON / OFF
- Moderators can access completion reports

---

## 📧 Email Configuration

### Customize Email Template

The plugin uses BuddyPress Email system for flexibility and consistency.

**Steps to customize:**

1. **Access Email Editor**
   ```
   WordPress Admin > BuddyPress > Emails
   ```

2. **Find Template**
   ```
   Look for: "Todo Due Date Reminder"
   Click: Edit
   ```

3. **Customize Content**
   - Edit subject line
   - Modify email body
   - Add your branding
   - Change colors/styling

4. **Available Tokens**
   ```
   {{{recipient.name}}}     - User's display name
   {{{site.name}}}          - Your site name
   {{{bptodo.title}}}       - Todo title
   {{{bptodo.url}}}         - Link to todo
   ```

5. **Example Subject**
   ```
   [{{{site.name}}}] Reminder: "{{{bptodo.title}}}" is due today
   ```

6. **Example Body**
   ```
   Hi {{{recipient.name}}},

   This is a friendly reminder that your task "{{{bptodo.title}}}"
   is due today.

   View and complete it here: {{{bptodo.url}}}

   Stay productive!
   {{{site.name}}} Team
   ```

### Test Email Notifications

**Method 1: Create Test Todo**
1. Create a todo with today's due date
2. Wait a few minutes
3. Check your email inbox
4. Verify content and formatting

**Method 2: Use WP-CLI** (Advanced)
```bash
wp eval "
require_once 'wp-content/plugins/buddypress-user-todo-list/includes/class-bptodo-emails.php';
Bptodo_Emails::send_due_date_email(TODO_ID, USER_ID, 'yes');
"
```

### Email Not Sending?

**Check these:**

1. **BuddyPress Email Settings**
   - Go to `BuddyPress > Emails`
   - Verify template exists
   - Check it's published (not draft)

2. **User Settings**
   - User must have email notifications enabled
   - Check in their profile settings

3. **Server Email**
   - Test WordPress email: `Tools > Site Health > Email Test`
   - Configure SMTP plugin if needed (recommended)

4. **Plugin Settings**
   - Verify "Send Email Notifications" is ON
   - Check user role has access

---

## 💾 Saving Settings

**Important:** Always click "Save Settings" button after making changes!

**What happens when you save:**
- Settings stored in database
- Changes apply immediately
- No page refresh needed for members
- May need to clear cache

**After saving:**
- Look for success message
- Test the changes
- Clear site cache if using cache plugin

---

## 🎯 Recommended Configurations

### For Small Communities (< 100 members)

```
✓ Enable Todo for Members: ON
✓ Allow Users to Add Categories: ON
✓ Send Email Notifications: ON
✓ Send BuddyPress Notifications: ON
✓ User Roles: All roles enabled
✓ Group Todos: ON
```

**Why:** Maximum flexibility, high engagement

### For Large Communities (> 1000 members)

```
✓ Enable Todo for Members: ON
✓ Allow Users to Add Categories: OFF
✓ Send Email Notifications: ON (with user control)
✓ Send BuddyPress Notifications: OFF (reduce load)
✓ User Roles: Contributor and above
✓ Group Todos: ON
```

**Why:** Maintain structure, reduce noise, scale better

### For Educational Platforms

```
✓ Enable Todo for Members: ON
✓ Allow Users to Add Categories: OFF
✓ Send Email Notifications: ON
✓ Send BuddyPress Notifications: ON
✓ User Roles: Student, Teacher
✓ Group Todos: ON (for class projects)
```

**Why:** Standardized categories, clear communication

### For Professional Teams

```
✓ Enable Todo for Members: ON
✓ Allow Users to Add Categories: ON
✓ Send Email Notifications: ON
✓ Send BuddyPress Notifications: ON
✓ User Roles: All team members
✓ Group Todos: ON (for projects)
```

**Why:** Flexibility, real-time updates, collaboration

---

## 🔒 Security & Privacy

### Data Storage

- Todos stored as WordPress custom posts
- Categories stored as custom taxonomies
- User preferences in user meta
- All data respects WordPress permissions

### Privacy Considerations

- Members can only see their own todos
- Group todos visible to group members only
- Admins can view all todos in admin panel
- No todos publicly accessible unless member profile is public

### GDPR Compliance

**User data stored:**
- Todo titles and descriptions
- Due dates and priorities
- Email notification preferences
- Categories created

**User rights:**
- Can view all their data
- Can export todos (CSV)
- Can delete todos
- Data removed when user deleted (if configured)

---

## 🆘 Settings Troubleshooting

### Settings not saving

**Solution:**
1. Check browser console for JavaScript errors
2. Disable other plugins temporarily
3. Clear browser cache
4. Try different browser

### Changes not appearing

**Solution:**
1. Clear site cache
2. Clear browser cache
3. Check user permissions
4. Verify settings saved successfully

### Email template missing

**Solution:**
1. Deactivate and reactivate plugin
2. Email template auto-creates on activation
3. Check `BuddyPress > Emails`
4. Look for "Todo Due Date Reminder"

---

**Next:** [Member Guide](member-guide.md) →
