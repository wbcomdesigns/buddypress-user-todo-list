# Shortcodes Guide

Display todos anywhere on your WordPress site using shortcodes. Perfect for dashboards, landing pages, and custom layouts.

## 📝 Available Shortcodes

1. [User Todo List](#user-todo-list) — `[bptodo_list]` — the primary shortcode with filtering, sorting, and pagination
2. [Todo by Category](#todo-by-category) — `[bptodo_by_category]` — legacy category-only shortcode, kept for backward compatibility

> **Looking for `[bptodo_all]`?** There is no separate "all todos" shortcode. Use `[bptodo_list user_id="all"]` (admin only) — it renders every user's todos with the same filtering and pagination as the standard list.

---

## 👤 User Todo List

`[bptodo_list]` is the main shortcode. It displays the current user's todos by default, with full filtering, sorting, and pagination. Site admins can widen it to any user or to everyone.

### Basic Usage

```
[bptodo_list]
```

### Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `user_id` | `0` | User to show tasks for. `0` = current user, a numeric ID for a specific user, or `all` for everyone (**admin only**). |
| `category` | `""` | Filter by category **slug or ID**. |
| `status` | `all` | Filter by status: `all`, `complete`, or `incomplete`. |
| `priority` | `all` | Filter by priority: `all`, `critical`, `high`, or `normal`. |
| `per_page` | `10` | Number of items per page. |
| `orderby` | `due_date` | Sort field: `due_date`, `date`, `title`, or `priority`. |
| `order` | `ASC` | Sort direction: `ASC` or `DESC`. |
| `show_add` | `no` | Show the "Add New" button: `yes` or `no`. |

> Visitors who are not logged in see the plugin's logged-out notice, not the list.

### Examples

**Current user's todos:**
```
[bptodo_list]
```

**All incomplete todos across the site (admin only):**
```
[bptodo_list user_id="all" status="incomplete"]
```

**A specific user's todos, read-only (no add button):**
```
[bptodo_list user_id="15" show_add="no"]
```

**High-priority work tasks, 20 per page:**
```
[bptodo_list category="work" priority="high" per_page="20"]
```

**Completed tasks, newest first:**
```
[bptodo_list status="complete" orderby="date" order="DESC"]
```

**With the add-new button visible:**
```
[bptodo_list show_add="yes"]
```

### Finding Category IDs / Slugs

**Method 1: Admin Panel**
1. Go to `WordPress Admin > Todos > Categories`.
2. Hover over a category name.
3. The URL shows the ID: `?tag_ID=5` (5 is the ID). The slug is in the "Slug" column.

**Method 2: WP-CLI**
```bash
wp term list todo_category --fields=term_id,slug,name
```

Both an ID (`category="5"`) and a slug (`category="work"`) are accepted by `[bptodo_list]`.

---

## 🏷️ Todo by Category

`[bptodo_by_category]` is a **legacy** shortcode kept for backward compatibility. It displays todos from a single category and accepts **only** the `category` parameter. Use the category **ID** (`category="5"`): the Classic layout looks the category up by ID only. A slug works in the Modern layout.

> For any filtering beyond a single category (status, priority, user, sorting, pagination), use `[bptodo_list]` instead — e.g. `[bptodo_list category="5" status="incomplete"]`.

### Usage

```
[bptodo_by_category category="5"]
```

### Parameters

| Parameter | Required | Default | Description |
|-----------|----------|---------|-------------|
| `category` | Yes | — | Category ID (or slug) to display |

Logged-out visitors see the plugin's logged-out notice.

---

## 🎨 Styling Shortcodes

### Custom CSS

The shortcode markup is token-driven and inherits the plugin's design system. To layer your own styles, target the plugin containers:

```css
/* List container */
.bptodo-shortcode-list {
    background: #f5f5f5;
    padding: 20px;
    border-radius: 8px;
}

/* Individual todo card */
.bptodo-card {
    margin-bottom: 10px;
}
```

### Theme Integration

**Render from a template file:**

```php
<?php
if ( shortcode_exists( 'bptodo_list' ) ) {
    echo do_shortcode( '[bptodo_list]' );
}
?>
```

---

## 📍 Use Cases & Examples

### Personal Dashboard Page

```
<h2>My Open Tasks</h2>
[bptodo_list status="incomplete"]

<h2>Recently Completed</h2>
[bptodo_list status="complete" per_page="10" orderby="date" order="DESC"]
```

### Category Landing Page

```
<h1>Work Tasks</h1>
[bptodo_list category="work" status="incomplete"]
```

### Team Overview (admin)

```
<h2>Everyone's Open Tasks</h2>
[bptodo_list user_id="all" status="incomplete" per_page="25"]
```

### Read-only View of One Member

```
[bptodo_list user_id="22" status="incomplete" show_add="no"]
```

---

## 🔒 Access Control

Shortcodes respect WordPress login status. Logged-out visitors see the plugin's built-in notice.

- `user_id="all"` and viewing **another** user's todos both require the current user to be a site admin (`manage_options`). Non-admins are silently scoped back to their own todos.

```php
// Show an admin-wide view to admins, a personal view to everyone else.
if ( current_user_can( 'manage_options' ) ) {
    echo do_shortcode( '[bptodo_list user_id="all"]' );
} else {
    echo do_shortcode( '[bptodo_list]' );
}
```

---

## 🎛️ Advanced Customization

### Custom Shortcode Wrapper

```php
// Add to functions.php — a pre-filtered wrapper around [bptodo_list].
function my_custom_todo_shortcode( $atts ) {
    if ( ! is_user_logged_in() ) {
        return '<p>Please log in to view todos.</p>';
    }

    $atts = shortcode_atts(
        array(
            'category' => 'work',
            'status'   => 'incomplete',
        ),
        $atts,
        'my_todos'
    );

    return do_shortcode( sprintf(
        '[bptodo_list category="%s" status="%s"]',
        esc_attr( $atts['category'] ),
        esc_attr( $atts['status'] )
    ) );
}
add_shortcode( 'my_todos', 'my_custom_todo_shortcode' );

// Usage: [my_todos]  or  [my_todos category="5"]
```

---

## 🐛 Troubleshooting

### Shortcode displays as text

**Problem:** `[bptodo_list]` shows literally on the page.

**Causes & fixes:**
- Plugin not activated → activate it.
- Typed into the wrong field → use the content editor / a Shortcode block, not a title field.
- Page-builder conflict → use the builder's dedicated shortcode widget/element.

### No todos showing

**Check:**
1. The target user actually has todos.
2. The `category` ID/slug is correct.
3. The viewer is logged in.
4. The `status` / `priority` filters aren't excluding everything.
5. For `user_id="all"` or another user's ID, the viewer is an admin.

### Performance with large lists

`[bptodo_list]` paginates server-side via `per_page`, so large datasets stay fast. Lower `per_page` for lighter pages; combine with `status` / `category` to narrow the set.

---

**Next:** [WP-CLI Commands](wp-cli-commands.md) →
