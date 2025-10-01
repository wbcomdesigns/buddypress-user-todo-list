# Shortcodes Guide

Display todos anywhere on your WordPress site using shortcodes. Perfect for dashboards, landing pages, and custom layouts.

## 📝 Available Shortcodes

1. [Todo by Category](#todo-by-category) - Display todos from specific category
2. [User Todo List](#user-todo-list) - Show current user's todos
3. [All Todos](#all-todos) - Display all site todos (admin only)

---

## 🎯 Todo by Category

Display todos from a specific category anywhere on your site.

### Basic Usage

```
[bptodo_by_category category="5"]
```

### Parameters

| Parameter | Required | Default | Description |
|-----------|----------|---------|-------------|
| `category` | Yes | - | Category ID to display |
| `user_id` | No | Current user | Specific user's todos |
| `limit` | No | -1 (all) | Number of todos to show |
| `status` | No | all | Filter by status |

### Examples

**Show category 5 todos:**
```
[bptodo_by_category category="5"]
```

**Show only 10 todos:**
```
[bptodo_by_category category="5" limit="10"]
```

**Show specific user's todos:**
```
[bptodo_by_category category="5" user_id="15"]
```

**Show only incomplete todos:**
```
[bptodo_by_category category="5" status="incomplete"]
```

**Show only completed todos:**
```
[bptodo_by_category category="5" status="completed"]
```

### Finding Category IDs

**Method 1: Admin Panel**
1. Go to `WordPress Admin > Todos > Categories`
2. Hover over category name
3. Look at URL: `?tag_ID=5` (5 is the ID)

**Method 2: Edit Category**
1. Edit any todo
2. Look at category dropdown
3. Inspect element to see option value

**Method 3: WP-CLI**
```bash
wp term list bp_todo_category --fields=term_id,name
```

---

## 👤 User Todo List

Display current logged-in user's todos.

### Basic Usage

```
[bptodo_list]
```

### Parameters

| Parameter | Required | Default | Description |
|-----------|----------|---------|-------------|
| `user_id` | No | Current user | Show specific user's todos |
| `category` | No | all | Filter by category |
| `status` | No | all | Filter by status |
| `show_add` | No | true | Show "Add New" button |
| `show_export` | No | true | Show export button |

### Examples

**Basic list:**
```
[bptodo_list]
```

**Specific user:**
```
[bptodo_list user_id="25"]
```

**Filter by category:**
```
[bptodo_list category="5"]
```

**Show only incomplete:**
```
[bptodo_list status="incomplete"]
```

**Hide action buttons:**
```
[bptodo_list show_add="false" show_export="false"]
```

**Read-only view:**
```
[bptodo_list show_add="false" show_export="false" user_id="10"]
```

---

## 📊 All Todos (Admin)

Display all todos from all users. **Admin only!**

### Basic Usage

```
[bptodo_all]
```

### Parameters

| Parameter | Required | Default | Description |
|-----------|----------|---------|-------------|
| `limit` | No | 50 | Number to show |
| `orderby` | No | date | Sort field |
| `order` | No | DESC | Sort direction |
| `status` | No | all | Filter by status |

### Examples

**Recent 50 todos:**
```
[bptodo_all]
```

**Show 100 todos:**
```
[bptodo_all limit="100"]
```

**Sort by title:**
```
[bptodo_all orderby="title" order="ASC"]
```

**Only incomplete:**
```
[bptodo_all status="incomplete"]
```

---

## 🎨 Styling Shortcodes

### Custom CSS

Add custom styling in your theme or Customizer:

```css
/* Todo list container */
.bptodo-shortcode-list {
    background: #f5f5f5;
    padding: 20px;
    border-radius: 8px;
}

/* Individual todo item */
.bptodo-item {
    background: white;
    padding: 15px;
    margin-bottom: 10px;
    border-left: 3px solid #0073aa;
}

/* Completed todos */
.bptodo-item.completed {
    opacity: 0.6;
    border-left-color: #46b450;
}

/* High priority */
.bptodo-item.priority-high {
    border-left-color: #dc3232;
}

/* Due date display */
.bptodo-due-date {
    color: #666;
    font-size: 0.9em;
}

/* Overdue items */
.bptodo-item.overdue {
    background: #fff5f5;
    border-left-color: #dc3232;
}
```

### Theme Integration

**Add to page template:**

```php
<?php
// In your theme template file
if ( shortcode_exists( 'bptodo_list' ) ) {
    echo do_shortcode( '[bptodo_list]' );
}
?>
```

---

## 📍 Use Cases & Examples

### Personal Dashboard Page

Create a custom page with user's todos:

```
<h2>My Tasks</h2>
[bptodo_list status="incomplete" show_export="false"]

<h2>Completed Tasks</h2>
[bptodo_list status="completed" limit="10"]
```

### Category-Specific Landing Pages

**Work Tasks Page:**
```
<h1>Work Tasks</h1>
<p>Your professional todo list</p>
[bptodo_by_category category="3"]
```

**Personal Goals Page:**
```
<h1>Personal Goals</h1>
<p>Track your personal development</p>
[bptodo_by_category category="5"]
```

### Team Overview Page

**For team leaders:**
```
<h2>Team Member Tasks</h2>

<h3>John's Tasks</h3>
[bptodo_list user_id="15" status="incomplete" show_add="false"]

<h3>Sarah's Tasks</h3>
[bptodo_list user_id="22" status="incomplete" show_add="false"]
```

### Public Task Board

**Show completed work:**
```
<h2>Recently Completed Projects</h2>
[bptodo_all status="completed" limit="20" orderby="date" order="DESC"]
```

### Widget Areas

**Add to sidebar:**

```php
// In functions.php or custom plugin
add_action( 'widgets_init', function() {
    register_sidebar( array(
        'name' => 'Todo Sidebar',
        'id' => 'todo-sidebar',
        'before_widget' => '<div class="widget">',
        'after_widget' => '</div>',
    ) );
} );

// In sidebar.php
if ( is_user_logged_in() ) {
    echo do_shortcode( '[bptodo_list limit="5"]' );
}
```

---

## 🔒 Access Control

### Login Requirements

Shortcodes respect WordPress login status:

```php
// Wrap shortcode with login check
if ( is_user_logged_in() ) {
    echo do_shortcode( '[bptodo_list]' );
} else {
    echo '<p>Please <a href="/wp-login.php">login</a> to view your todos.</p>';
}
```

### Role-Based Display

```php
// Show only to subscribers
if ( current_user_can( 'subscriber' ) ) {
    echo do_shortcode( '[bptodo_list]' );
}

// Show different views by role
if ( current_user_can( 'administrator' ) ) {
    echo do_shortcode( '[bptodo_all]' );
} else {
    echo do_shortcode( '[bptodo_list]' );
}
```

---

## 🎛️ Advanced Customization

### Custom Shortcode

Create your own shortcode wrapper:

```php
// Add to functions.php
function my_custom_todo_shortcode( $atts ) {
    if ( ! is_user_logged_in() ) {
        return '<p>Please login to view todos.</p>';
    }

    // Get current user's incomplete todos from category 5
    $atts = shortcode_atts( array(
        'category' => '5',
        'status' => 'incomplete',
        'user_id' => get_current_user_id()
    ), $atts );

    return do_shortcode( sprintf(
        '[bptodo_by_category category="%s" status="%s" user_id="%s"]',
        $atts['category'],
        $atts['status'],
        $atts['user_id']
    ) );
}
add_shortcode( 'my_todos', 'my_custom_todo_shortcode' );

// Usage: [my_todos]
```

### Conditional Display

```php
// Show different todos based on day
function daily_todo_shortcode() {
    $day = date( 'w' ); // 0-6, Sunday-Saturday

    if ( $day == 1 ) {
        // Monday - Work tasks
        return do_shortcode( '[bptodo_by_category category="3"]' );
    } elseif ( $day == 6 || $day == 0 ) {
        // Weekend - Personal tasks
        return do_shortcode( '[bptodo_by_category category="5"]' );
    } else {
        // Weekdays - All tasks
        return do_shortcode( '[bptodo_list]' );
    }
}
add_shortcode( 'daily_todos', 'daily_todo_shortcode' );
```

---

## 🐛 Troubleshooting

### Shortcode displays as text

**Problem:** `[bptodo_list]` shows literally on page

**Causes:**
- Plugin not activated
- Typed in wrong field (not content editor)
- Theme/page builder conflicts

**Solutions:**
1. Verify plugin is active
2. Use Text editor, not visual
3. For page builders, use shortcode widget/element

### No todos showing

**Check:**
1. User has todos created
2. Category ID is correct
3. User is logged in (if required)
4. Permissions are correct
5. Filter status matches

### Styling issues

**Solutions:**
1. Add custom CSS (see styling section)
2. Check theme CSS conflicts
3. Use browser inspector to identify issues
4. Wrap in custom div with class:
   ```
   <div class="my-todo-wrapper">
   [bptodo_list]
   </div>
   ```

### Performance with large lists

**If slow loading:**
1. Use `limit` parameter
2. Add pagination (custom development)
3. Filter by status
4. Use caching plugin
5. Consider AJAX loading

---

## 📚 Complete Examples

### Member Dashboard

```html
<div class="todo-dashboard">
    <div class="todo-stats">
        <!-- Custom stats widget here -->
    </div>

    <div class="todo-urgent">
        <h3>🔥 Urgent Tasks</h3>
        [bptodo_list status="incomplete" limit="5"]
    </div>

    <div class="todo-recent">
        <h3>✅ Recently Completed</h3>
        [bptodo_list status="completed" limit="5"]
    </div>
</div>
```

### Project Page

```html
<h1>Project Alpha Tasks</h1>
<p>All tasks related to Project Alpha</p>

<h2>🎯 Active Tasks</h2>
[bptodo_by_category category="8" status="incomplete"]

<h2>✅ Completed Tasks</h2>
[bptodo_by_category category="8" status="completed" limit="20"]
```

---

**Next:** [WP-CLI Commands](wp-cli-commands.md) →
