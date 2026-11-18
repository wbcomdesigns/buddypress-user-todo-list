# Todo for BuddyPress & BuddyBoss - Developer Guide

## Table of Contents

1. [Overview](#overview)
2. [Plugin Architecture](#plugin-architecture)
3. [Hooks Reference](#hooks-reference)
4. [Template System](#template-system)
5. [Database Structure](#database-structure)
6. [Helper Functions](#helper-functions)
7. [Email System](#email-system)
8. [Ajax Handlers](#ajax-handlers)
9. [Code Examples](#code-examples)
10. [Extending the Plugin](#extending-the-plugin)

---

## Overview

This developer guide provides technical documentation for extending and customizing the **Todo for BuddyPress & BuddyBoss** plugin.

### Plugin Information

- **Text Domain:** `wb-todo`
- **Prefix:** `bptodo_`
- **Post Type:** `bp-todo`
- **Taxonomy:** `todo_category`
- **Minimum PHP:** 7.0
- **BuddyPress:** 12.0+
- **BuddyBoss:** Compatible

### File Structure

```
buddypress-user-todo-list/
├── admin/                       # Admin-specific functionality
│   ├── assets/                  # Admin CSS/JS
│   ├── inc/                     # Admin includes
│   └── class-bptodo-admin.php   # Admin class
├── includes/                    # Core classes
│   ├── class-bptodo.php         # Main plugin class
│   ├── class-bptodo-emails.php  # Email handling
│   ├── class-bptodo-data-retention.php # Cleanup system
│   ├── bptodo-functions.php     # Helper functions
│   └── bptodo-ajax.php          # Ajax handlers
├── public/                      # Frontend functionality
│   ├── assets/                  # Frontend CSS/JS
│   ├── todo/                    # Template files
│   │   ├── member/              # Member profile templates
│   │   └── group/               # Group templates
│   └── class-bptodo-public.php  # Public class
├── languages/                   # Translation files
└── bp-user-todo-list.php        # Main plugin file
```

---

## Plugin Architecture

### Main Classes

#### 1. BPTODO (Main Plugin Class)

**File:** `includes/class-bptodo.php`

The main plugin class that handles initialization and dependency loading.

```php
// Access global instance
global $bptodo;

// Get plugin settings
$settings = $bptodo->profile_menu_label; // "Todo"
$slug = $bptodo->profile_menu_slug;      // "todo"
```

#### 2. BPTODO_Admin

**File:** `admin/class-bptodo-admin.php`

Handles all admin-specific functionality including settings pages and meta boxes.

#### 3. BPTODO_Public

**File:** `public/class-bptodo-public.php`

Manages frontend functionality, enqueues scripts/styles, and registers templates.

#### 4. BPTODO_Emails

**File:** `includes/class-bptodo-emails.php`

Handles email notifications using BuddyPress native email system.

#### 5. BPTODO_Data_Retention

**File:** `includes/class-bptodo-data-retention.php`

Manages automatic cleanup of old completed tasks.

### Initialization Flow

```
1. bp-user-todo-list.php (main file)
   ↓
2. BPTODO class loads
   ↓
3. Dependencies loaded (admin/public classes)
   ↓
4. Hooks registered
   ↓
5. Templates registered (BuddyPress)
   ↓
6. Plugin ready
```

---

## Hooks Reference

### Action Hooks

#### Plugin Lifecycle

##### `bptodo_loaded`

Fires after the plugin has fully loaded.

```php
/**
 * Fires after plugin is loaded.
 *
 * @since 1.0.0
 */
do_action( 'bptodo_loaded' );
```

**Example:**
```php
add_action( 'bptodo_loaded', 'my_custom_todo_init' );
function my_custom_todo_init() {
    // Your custom initialization code
}
```

#### Todo Operations

##### `bptodo_before_add_todo`

Fires before a todo is created.

```php
/**
 * Fires before a todo is added.
 *
 * @since 1.0.0
 *
 * @param array $todo_data The todo data being inserted.
 * @param int   $user_id   The user creating the todo.
 */
do_action( 'bptodo_before_add_todo', $todo_data, $user_id );
```

**Example:**
```php
add_action( 'bptodo_before_add_todo', 'log_todo_creation', 10, 2 );
function log_todo_creation( $todo_data, $user_id ) {
    error_log( "User $user_id creating todo: " . $todo_data['title'] );
}
```

##### `bptodo_after_add_todo`

Fires after a todo is successfully created.

```php
/**
 * Fires after a todo is added.
 *
 * @since 1.0.0
 *
 * @param int $todo_id The created todo ID.
 */
do_action( 'bptodo_after_add_todo', $todo_id );
```

**Example:**
```php
add_action( 'bptodo_after_add_todo', 'notify_admin_of_new_todo' );
function notify_admin_of_new_todo( $todo_id ) {
    $todo = get_post( $todo_id );
    wp_mail(
        get_option('admin_email'),
        'New Todo Created',
        "Todo: {$todo->post_title}"
    );
}
```

##### `bptodo_todo_updated`

Fires after a todo is updated.

```php
/**
 * Fires after todo is updated.
 *
 * @since 1.0.0
 *
 * @param int $todo_id The updated todo ID.
 */
do_action( 'bptodo_todo_updated', $todo_id );
```

##### `bptodo_todo_deleted`

Fires after a todo is deleted.

```php
/**
 * Fires after todo is deleted.
 *
 * @since 1.0.0
 *
 * @param int $todo_id The deleted todo ID.
 */
do_action( 'bptodo_todo_deleted', $todo_id );
```

##### `bptodo_todo_completed`

Fires when a todo is marked as complete.

```php
/**
 * Fires when todo is completed.
 *
 * @since 1.0.0
 *
 * @param int $todo_id The completed todo ID.
 * @param int $user_id The user who completed it.
 */
do_action( 'bptodo_todo_completed', $todo_id, $user_id );
```

**Example:**
```php
add_action( 'bptodo_todo_completed', 'award_points_on_completion', 10, 2 );
function award_points_on_completion( $todo_id, $user_id ) {
    // Integration with points system
    $priority = get_post_meta( $todo_id, 'todo_priority', true );
    $points = ( $priority === 'critical' ) ? 100 : 50;

    // Award points (pseudo code)
    award_user_points( $user_id, $points );
}
```

#### Group Todos

##### `bptodo_group_todo_submit`

Fires after a group todo is created and assigned to members.

```php
/**
 * Fires after group todo is submitted.
 *
 * @since 2.0.0
 *
 * @param array $member_ids     Array of member IDs assigned.
 * @param int   $primary_todo_id The primary todo ID.
 */
do_action( 'bptodo_group_todo_submit', $member_ids, $primary_todo_id );
```

**Example:**
```php
add_action( 'bptodo_group_todo_submit', 'log_group_assignment', 10, 2 );
function log_group_assignment( $member_ids, $primary_todo_id ) {
    $count = count( $member_ids );
    error_log( "Group todo $primary_todo_id assigned to $count members" );
}
```

##### `bp_group_todo_add_field_before_default_listing`

Add custom fields before default form fields in group todo add form.

```php
/**
 * Add fields before default listing.
 *
 * @since 2.0.0
 *
 * @param int    $user_id The current user ID.
 * @param string $form_post_link Form submission link.
 */
do_action( 'bp_group_todo_add_field_before_default_listing', $user_id, $form_post_link );
```

##### `bp_group_todo_add_field_after_default_listing`

Add custom fields after default form fields in group todo add form.

```php
/**
 * Add fields after default listing.
 *
 * @since 2.0.0
 *
 * @param int    $user_id The current user ID.
 * @param string $form_post_link Form submission link.
 */
do_action( 'bp_group_todo_add_field_after_default_listing', $user_id, $form_post_link );
```

**Example:**
```php
add_action( 'bp_group_todo_add_field_after_default_listing', 'add_custom_todo_field', 10, 2 );
function add_custom_todo_field( $user_id, $form_post_link ) {
    ?>
    <tr>
        <td><label><?php esc_html_e( 'Estimated Hours', 'my-plugin' ); ?></label></td>
        <td><input type="number" name="custom_hours" min="1" max="100" /></td>
    </tr>
    <?php
}

// Save the custom field
add_action( 'bptodo_after_add_todo', 'save_custom_todo_field' );
function save_custom_todo_field( $todo_id ) {
    if ( isset( $_POST['custom_hours'] ) ) {
        update_post_meta( $todo_id, 'custom_hours', absint( $_POST['custom_hours'] ) );
    }
}
```

#### Group Overview

##### `bptodo_group_overview_before_stats`

Fires before group overview statistics are calculated.

```php
/**
 * Fires before group overview stats are calculated.
 *
 * @since 3.5.0
 *
 * @param int    $group_id The group ID.
 * @param object $group    The group object.
 */
do_action( 'bptodo_group_overview_before_stats', $group_id, $group );
```

##### `bptodo_group_overview_start`

Fires at the start of group overview page output.

```php
/**
 * Fires at start of group overview page.
 *
 * @since 3.5.0
 *
 * @param int   $group_id         The group ID.
 * @param array $member_stats     Array of member statistics.
 * @param int   $selected_task_id The selected task ID.
 */
do_action( 'bptodo_group_overview_start', $group_id, $member_stats, $selected_task_id );
```

**Example:**
```php
add_action( 'bptodo_group_overview_start', 'add_export_button', 10, 3 );
function add_export_button( $group_id, $member_stats, $selected_task_id ) {
    ?>
    <a href="<?php echo wp_nonce_url( add_query_arg( 'export_overview', '1' ), 'export' ); ?>"
       class="button">
        Export Overview
    </a>
    <?php
}
```

##### `bptodo_group_overview_end`

Fires at the end of group overview page output.

```php
/**
 * Fires at end of group overview page.
 *
 * @since 3.5.0
 *
 * @param int   $group_id         The group ID.
 * @param array $member_stats     Array of member statistics.
 * @param int   $selected_task_id The selected task ID.
 */
do_action( 'bptodo_group_overview_end', $group_id, $member_stats, $selected_task_id );
```

#### Data Retention

##### `bptodo_before_cleanup_todos`

Fires before automatic cleanup runs.

```php
/**
 * Fires before cleanup runs.
 *
 * @since 3.5.0
 *
 * @param int    $retention_days Number of days for retention.
 * @param string $cleanup_type   Type: 'all', 'group', 'personal'.
 */
do_action( 'bptodo_before_cleanup_todos', $retention_days, $cleanup_type );
```

##### `bptodo_after_cleanup_todos`

Fires after automatic cleanup completes.

```php
/**
 * Fires after cleanup completes.
 *
 * @since 3.5.0
 *
 * @param array $deleted_tasks Array with 'group', 'personal', 'total' counts.
 */
do_action( 'bptodo_after_cleanup_todos', $deleted_tasks );
```

**Example:**
```php
add_action( 'bptodo_after_cleanup_todos', 'log_cleanup_results' );
function log_cleanup_results( $deleted_tasks ) {
    error_log( sprintf(
        'Todo Cleanup: %d total (%d group, %d personal)',
        $deleted_tasks['total'],
        $deleted_tasks['group'],
        $deleted_tasks['personal']
    ));
}
```

### Filter Hooks

#### Plugin Settings

##### `bptodo_default_settings`

Filter default plugin settings.

```php
/**
 * Filters default plugin settings.
 *
 * @since 1.0.0
 *
 * @param array $defaults Default settings array.
 */
$defaults = apply_filters( 'bptodo_default_settings', $defaults );
```

**Example:**
```php
add_filter( 'bptodo_default_settings', 'modify_default_settings' );
function modify_default_settings( $defaults ) {
    $defaults['profile_menu_label'] = 'Task';
    $defaults['req_duedate'] = 'yes';
    return $defaults;
}
```

#### Todo Query

##### `bptodo_query_args`

Filter todo query arguments.

```php
/**
 * Filters todo query args.
 *
 * @since 1.0.0
 *
 * @param array $args    Query arguments.
 * @param int   $user_id User ID being queried.
 */
$args = apply_filters( 'bptodo_query_args', $args, $user_id );
```

**Example:**
```php
add_filter( 'bptodo_query_args', 'exclude_archived_todos', 10, 2 );
function exclude_archived_todos( $args, $user_id ) {
    $args['meta_query'][] = array(
        'key'     => 'todo_archived',
        'compare' => 'NOT EXISTS',
    );
    return $args;
}
```

#### Submit Button

##### `bp_group_todo_change_submit_button`

Change whether to use custom submit button in group todo form.

```php
/**
 * Filter to change submit button.
 *
 * @since 2.0.0
 *
 * @param bool $change Whether to change button.
 */
$change = apply_filters( 'bp_group_todo_change_submit_button', false );
```

**Example:**
```php
add_filter( 'bp_group_todo_change_submit_button', '__return_true' );

add_action( 'bp_group_todo_change_submit_button_action', 'custom_submit_button' );
function custom_submit_button( $label ) {
    ?>
    <tr>
        <td></td>
        <td>
            <button type="submit" name="group_todo_create" class="btn btn-primary">
                <i class="fa fa-plus"></i> Create <?php echo esc_html( $label ); ?>
            </button>
        </td>
    </tr>
    <?php
}
```

#### Group Overview

##### `bptodo_group_overview_tasks_per_page`

Filter number of tasks per page in group overview.

```php
/**
 * Filters tasks per page in group overview.
 *
 * @since 3.5.0
 *
 * @param int $per_page Number of tasks per page.
 * @param int $group_id The group ID.
 */
$per_page = apply_filters( 'bptodo_group_overview_tasks_per_page', 15, $group_id );
```

##### `bptodo_group_overview_members_per_page`

Filter number of members per page in group overview.

```php
/**
 * Filters members per page in group overview.
 *
 * @since 3.5.0
 *
 * @param int $per_page Number of members per page.
 * @param int $group_id The group ID.
 */
$per_page = apply_filters( 'bptodo_group_overview_members_per_page', 15, $group_id );
```

**Example:**
```php
add_filter( 'bptodo_group_overview_members_per_page', 'increase_members_per_page', 10, 2 );
function increase_members_per_page( $per_page, $group_id ) {
    // Show more members for large groups
    $member_count = groups_get_total_member_count( $group_id );
    return ( $member_count > 50 ) ? 30 : 15;
}
```

##### `bptodo_group_overview_stats_args`

Filter arguments for getting group overview statistics.

```php
/**
 * Filters the arguments for getting group overview stats.
 *
 * @since 3.5.0
 *
 * @param array $stats_args The query arguments.
 * @param int   $group_id   The group ID.
 */
$stats_args = apply_filters( 'bptodo_group_overview_stats_args', $stats_args, $group_id );
```

##### `bptodo_group_overview_member_stats`

Filter member statistics before display.

```php
/**
 * Filters the member stats before pagination.
 *
 * @since 3.5.0
 *
 * @param array $member_stats     Array of member statistics.
 * @param int   $group_id         The group ID.
 * @param int   $selected_task_id The selected task ID (0 for all).
 */
$member_stats = apply_filters( 'bptodo_group_overview_member_stats', $member_stats, $group_id, $selected_task_id );
```

**Example:**
```php
add_filter( 'bptodo_group_overview_member_stats', 'add_custom_member_data', 10, 3 );
function add_custom_member_data( $member_stats, $group_id, $selected_task_id ) {
    foreach ( $member_stats as &$stat ) {
        // Add custom role badge
        if ( groups_is_user_admin( $stat['user_id'], $group_id ) ) {
            $stat['role'] = 'Admin';
        } elseif ( groups_is_user_mod( $stat['user_id'], $group_id ) ) {
            $stat['role'] = 'Moderator';
        } else {
            $stat['role'] = 'Member';
        }
    }
    return $member_stats;
}
```

#### Data Retention

##### `bptodo_retention_delete_query_args`

Filter query args for finding todos to delete.

```php
/**
 * Filters query args for deletion.
 *
 * @since 3.5.0
 *
 * @param array  $args          Query arguments.
 * @param string $cleanup_type  Type: 'group' or 'personal'.
 */
$args = apply_filters( 'bptodo_retention_delete_query_args', $args, $cleanup_type );
```

---

## Template System

### Template Hierarchy

The plugin uses BuddyPress template hierarchy. You can override templates by copying them to your theme:

**Plugin Templates:**
```
wp-content/plugins/buddypress-user-todo-list/public/todo/
├── member/
│   ├── member-todo-list.php
│   ├── member-todo-add.php
│   └── member-todo-edit.php
└── group/
    ├── list.php
    ├── add.php
    ├── edit.php
    └── overview.php
```

**Theme Override:**
```
wp-content/themes/your-theme/buddypress/todo/
├── member/
│   ├── member-todo-list.php
│   └── ...
└── group/
    └── ...
```

### Template Functions

#### Loading Templates

```php
// Load a specific template
bp_get_template_part( 'todo/member/member-todo-list' );

// Load with custom data
set_query_var( 'custom_data', $data );
bp_get_template_part( 'todo/member/member-todo-list' );
```

#### Template Tags

##### Get Todo Meta

```php
$status = get_post_meta( $todo_id, 'todo_status', true );
$due_date = get_post_meta( $todo_id, 'todo_due_date', true );
$priority = get_post_meta( $todo_id, 'todo_priority', true );
$group_id = get_post_meta( $todo_id, 'todo_group_id', true );
$primary_id = get_post_meta( $todo_id, 'todo_primary_id', true );
```

##### Display Todo

```php
// Get todo object
$todo = get_post( $todo_id );

// Display title
echo esc_html( $todo->post_title );

// Display content
echo wp_kses_post( $todo->post_content );

// Display due date formatted
$due_date = get_post_meta( $todo_id, 'todo_due_date', true );
echo esc_html( date_i18n( get_option('date_format'), strtotime($due_date) ) );
```

---

## Database Structure

### Post Type: `bp-todo`

**Structure:**
```sql
wp_posts
├── ID (Primary Key)
├── post_author (User ID who owns this todo)
├── post_title (Todo title)
├── post_content (Todo description/summary)
├── post_status (publish/draft/trash)
└── post_type ('bp-todo')
```

### Post Meta Fields

| Meta Key | Description | Values |
|----------|-------------|--------|
| `todo_status` | Completion status | `incomplete`, `complete` |
| `todo_due_date` | Due date | `YYYY-MM-DD` format |
| `todo_priority` | Priority level | `critical`, `high`, `normal` |
| `todo_group_id` | Associated group | Group ID or empty |
| `todo_primary_id` | Primary todo reference | Same as post ID (primary) or primary todo ID (associated) |
| `todo_creator_id` | Who created the task | User ID |
| `botodo_associated_todo` | Associated todos | Array of post IDs |
| `todo_completed_date` | When completed | `YYYY-MM-DD HH:MM:SS` |
| `todo_last_day_mail_sent` | Email sent flag | `yes`, `no` |
| `todo_last_day_notification_sent` | Notification sent flag | `yes`, `no` |

### Taxonomy: `todo_category`

Categories for organizing todos.

```php
// Get all categories
$categories = get_terms( array(
    'taxonomy'   => 'todo_category',
    'hide_empty' => false,
) );

// Get todo categories
$todo_categories = wp_get_object_terms( $todo_id, 'todo_category' );
```

### Primary vs Associated Todos

**Primary Todo:**
- Created by the task creator
- `todo_primary_id` equals its own post ID
- Contains `botodo_associated_todo` array with associated IDs

**Associated Todo:**
- Created for each assigned member
- `todo_primary_id` points to the primary todo ID
- Individual member owns and manages their copy

**Query Example:**
```php
// Get only primary todos
$args = array(
    'post_type'  => 'bp-todo',
    'meta_query' => array(
        array(
            'key'     => 'todo_primary_id',
            'value'   => 'wp_posts.ID',
            'compare' => '=',
        ),
    ),
);

// Or use this filter approach
$all_todos = get_posts( array( 'post_type' => 'bp-todo' ) );
$primary_todos = array_filter( $all_todos, function( $todo ) {
    $primary_id = get_post_meta( $todo->ID, 'todo_primary_id', true );
    return $primary_id == $todo->ID;
});
```

---

## Helper Functions

### URL Functions

#### `bptodo_get_member_url( $user_id_or_obj = 0 )`

Get member profile URL with BuddyPress 12.0+ compatibility.

```php
$user_id = 123;
$profile_url = bptodo_get_member_url( $user_id );
// Returns: https://example.com/members/username/
```

#### `bptodo_get_group_url( $group_id_or_obj = 0 )`

Get group URL with BuddyPress 12.0+ compatibility.

```php
$group_id = 456;
$group_url = bptodo_get_group_url( $group_id );
// Returns: https://example.com/groups/group-slug/
```

### Permission Functions

#### `bptodo_is_site_admin()`

Check if current user is site administrator.

```php
if ( bptodo_is_site_admin() ) {
    // User is site admin
}
```

#### `bptodo_is_user_assigned_to_task( $task_id, $user_id )`

Check if user is assigned to a specific task.

```php
$task_id = 789;
$user_id = 123;

if ( bptodo_is_user_assigned_to_task( $task_id, $user_id ) ) {
    // User can view/edit this task
}
```

### Data Functions

#### Creating a Todo Programmatically

```php
function create_custom_todo( $user_id, $title, $description, $due_date, $priority = 'normal' ) {
    $args = array(
        'post_type'    => 'bp-todo',
        'post_status'  => 'publish',
        'post_title'   => sanitize_text_field( $title ),
        'post_content' => wp_kses_post( $description ),
        'post_author'  => absint( $user_id ),
    );

    $todo_id = wp_insert_post( $args );

    if ( $todo_id && ! is_wp_error( $todo_id ) ) {
        update_post_meta( $todo_id, 'todo_status', 'incomplete' );
        update_post_meta( $todo_id, 'todo_due_date', sanitize_text_field( $due_date ) );
        update_post_meta( $todo_id, 'todo_priority', sanitize_text_field( $priority ) );
        update_post_meta( $todo_id, 'todo_primary_id', $todo_id ); // Self-reference for primary
        update_post_meta( $todo_id, 'todo_creator_id', absint( $user_id ) );

        do_action( 'bptodo_after_add_todo', $todo_id );

        return $todo_id;
    }

    return false;
}

// Usage
$todo_id = create_custom_todo(
    123,
    'Complete project',
    'Finish the website redesign',
    '2025-02-15',
    'high'
);
```

#### Completing a Todo Programmatically

```php
function complete_todo( $todo_id ) {
    update_post_meta( $todo_id, 'todo_status', 'complete' );
    update_post_meta( $todo_id, 'todo_completed_date', current_time( 'mysql' ) );

    $todo = get_post( $todo_id );
    do_action( 'bptodo_todo_completed', $todo_id, $todo->post_author );

    return true;
}

// Usage
complete_todo( 789 );
```

#### Getting User's Todos

```php
function get_user_todos( $user_id, $status = '' ) {
    $args = array(
        'post_type'      => 'bp-todo',
        'post_status'    => 'publish',
        'author'         => $user_id,
        'posts_per_page' => -1,
    );

    if ( ! empty( $status ) ) {
        $args['meta_query'] = array(
            array(
                'key'     => 'todo_status',
                'value'   => $status,
                'compare' => '=',
            ),
        );
    }

    return get_posts( $args );
}

// Usage
$incomplete_todos = get_user_todos( 123, 'incomplete' );
$all_todos = get_user_todos( 123 );
```

---

## Email System

### BuddyPress Native Emails

The plugin uses BuddyPress native email system. Email templates are stored in the database and can be customized via **WP Admin → Settings → BuddyPress → Emails**.

### Sending Custom Todo Emails

```php
function send_custom_todo_email( $user_id, $todo_id ) {
    $user = get_user_by( 'id', $user_id );
    $todo = get_post( $todo_id );

    if ( ! $user || ! $todo ) {
        return false;
    }

    // Prepare email args
    $args = array(
        'tokens' => array(
            'task.title'    => $todo->post_title,
            'task.content'  => wp_strip_all_tags( $todo->post_content ),
            'task.due_date' => get_post_meta( $todo_id, 'todo_due_date', true ),
            'user.name'     => $user->display_name,
            'task.url'      => get_permalink( $todo_id ),
        ),
    );

    // Send using BuddyPress
    bp_send_email( 'bptodo-custom-email', $user->user_email, $args );

    return true;
}
```

### Registering Custom Email Template

```php
add_action( 'bp_core_install_emails', 'register_custom_todo_email' );
function register_custom_todo_email() {
    $defaults = array(
        'post_status'  => 'publish',
        'post_type'    => bp_get_email_post_type(),
    );

    $emails = array(
        'bptodo-custom-email' => array(
            'post_title'   => __( 'Custom Todo Email', 'wb-todo' ),
            'post_content' => __( 'Hi {{user.name}}, your task "{{task.title}}" needs attention!', 'wb-todo' ),
            'post_excerpt' => __( 'Custom todo reminder email.', 'wb-todo' ),
        ),
    );

    foreach ( $emails as $id => $email ) {
        $post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
        if ( ! $post_id ) {
            continue;
        }

        $tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );
        foreach ( $tt_ids as $tt_id ) {
            $term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
            wp_update_term( (int) $term->term_id, bp_get_email_tax_type(), array(
                'description' => 'Custom todo email notification',
            ) );
        }
    }
}
```

---

## Ajax Handlers

### Existing Ajax Actions

#### Complete/Incomplete Todo

**Action:** `bptodo_mark_complete` / `bptodo_mark_incomplete`

**File:** `includes/bptodo-ajax.php`

```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'bptodo_mark_complete',
        todo_id: 123,
        nonce: bptodo_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Todo completed!');
        }
    }
});
```

#### Add Category

**Action:** `bptodo_add_category`

```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'bptodo_add_category',
        category_name: 'New Category',
        nonce: bptodo_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Category added:', response.data);
        }
    }
});
```

### Creating Custom Ajax Handler

```php
// Register ajax action
add_action( 'wp_ajax_bptodo_custom_action', 'handle_custom_todo_action' );

function handle_custom_todo_action() {
    // Verify nonce
    check_ajax_referer( 'bptodo-ajax-nonce', 'nonce' );

    // Get data
    $todo_id = isset( $_POST['todo_id'] ) ? absint( $_POST['todo_id'] ) : 0;

    if ( ! $todo_id ) {
        wp_send_json_error( array( 'message' => 'Invalid todo ID' ) );
    }

    // Check permissions
    $todo = get_post( $todo_id );
    if ( ! $todo || $todo->post_author != get_current_user_id() ) {
        wp_send_json_error( array( 'message' => 'Permission denied' ) );
    }

    // Do something
    $result = update_post_meta( $todo_id, 'custom_field', 'custom_value' );

    // Return response
    if ( $result ) {
        wp_send_json_success( array( 'message' => 'Updated successfully' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Update failed' ) );
    }
}

// Enqueue the handler
add_action( 'wp_enqueue_scripts', 'enqueue_custom_todo_ajax' );
function enqueue_custom_todo_ajax() {
    wp_localize_script( 'bptodo-front', 'custom_ajax', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'bptodo-ajax-nonce' ),
    ) );
}
```

**JavaScript:**
```javascript
jQuery.ajax({
    url: custom_ajax.ajaxurl,
    type: 'POST',
    data: {
        action: 'bptodo_custom_action',
        todo_id: 123,
        nonce: custom_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data.message);
        }
    }
});
```

---

## Code Examples

### Example 1: Add Custom Priority Level

```php
// Add "Urgent" priority option
add_filter( 'bptodo_priority_options', 'add_urgent_priority' );
function add_urgent_priority( $priorities ) {
    $priorities['urgent'] = __( 'Urgent', 'my-plugin' );
    return $priorities;
}

// Add CSS for urgent priority
add_action( 'wp_head', 'urgent_priority_css' );
function urgent_priority_css() {
    ?>
    <style>
        .bptodo-priority-urgent {
            background: #ff0000;
            color: #fff;
            font-weight: bold;
        }
    </style>
    <?php
}
```

### Example 2: Restrict Todos by User Role

```php
add_action( 'bptodo_before_add_todo', 'restrict_todo_creation', 10, 2 );
function restrict_todo_creation( $todo_data, $user_id ) {
    $user = get_user_by( 'id', $user_id );

    // Only allow members and above
    if ( ! in_array( 'subscriber', $user->roles ) && ! in_array( 'member', $user->roles ) ) {
        wp_die( __( 'You do not have permission to create todos.', 'my-plugin' ) );
    }
}
```

### Example 3: Auto-Assign Tags Based on Keywords

```php
add_action( 'bptodo_after_add_todo', 'auto_tag_todos' );
function auto_tag_todos( $todo_id ) {
    $todo = get_post( $todo_id );
    $content = strtolower( $todo->post_title . ' ' . $todo->post_content );

    // Define keyword => category mappings
    $mappings = array(
        'meeting' => 'Meetings',
        'call'    => 'Calls',
        'email'   => 'Emails',
        'code'    => 'Development',
    );

    foreach ( $mappings as $keyword => $category ) {
        if ( strpos( $content, $keyword ) !== false ) {
            // Get or create category
            $term = get_term_by( 'name', $category, 'todo_category' );
            if ( ! $term ) {
                $term = wp_insert_term( $category, 'todo_category' );
                $term = get_term( $term['term_id'], 'todo_category' );
            }

            // Assign category
            wp_set_object_terms( $todo_id, $term->term_id, 'todo_category', true );
        }
    }
}
```

### Example 4: Gamification - Award Points

```php
add_action( 'bptodo_todo_completed', 'award_completion_points', 10, 2 );
function award_completion_points( $todo_id, $user_id ) {
    $priority = get_post_meta( $todo_id, 'todo_priority', true );

    // Points based on priority
    $points_map = array(
        'critical' => 100,
        'high'     => 50,
        'normal'   => 25,
    );

    $points = isset( $points_map[ $priority ] ) ? $points_map[ $priority ] : 25;

    // Update user meta
    $total_points = (int) get_user_meta( $user_id, 'todo_points', true );
    $total_points += $points;
    update_user_meta( $user_id, 'todo_points', $total_points );

    // Achievement: First 10 tasks
    $completed_count = count( get_user_todos( $user_id, 'complete' ) );
    if ( $completed_count === 10 ) {
        // Award achievement
        update_user_meta( $user_id, 'achievement_first_10', true );
    }
}

// Display points in profile
add_action( 'bp_before_member_header_meta', 'display_todo_points' );
function display_todo_points() {
    if ( ! bp_is_my_profile() ) {
        return;
    }

    $points = get_user_meta( bp_displayed_user_id(), 'todo_points', true );
    ?>
    <div class="todo-points">
        <strong><?php esc_html_e( 'Task Points:', 'my-plugin' ); ?></strong>
        <span class="points-value"><?php echo absint( $points ); ?></span>
    </div>
    <?php
}
```

### Example 5: Slack Integration

```php
add_action( 'bptodo_group_todo_submit', 'notify_slack_on_group_todo', 10, 2 );
function notify_slack_on_group_todo( $member_ids, $primary_todo_id ) {
    $todo = get_post( $primary_todo_id );
    $group_id = get_post_meta( $primary_todo_id, 'todo_group_id', true );
    $group = groups_get_group( $group_id );

    $message = sprintf(
        'New task "%s" assigned to %d members in group "%s"',
        $todo->post_title,
        count( $member_ids ),
        $group->name
    );

    // Send to Slack
    $webhook_url = get_option( 'my_slack_webhook_url' );
    if ( $webhook_url ) {
        wp_remote_post( $webhook_url, array(
            'body' => json_encode( array( 'text' => $message ) ),
            'headers' => array( 'Content-Type' => 'application/json' ),
        ) );
    }
}
```

### Example 6: CSV Export Enhancement

```php
add_action( 'init', 'handle_custom_csv_export' );
function handle_custom_csv_export() {
    if ( ! isset( $_GET['bptodo_export_csv'] ) || ! is_user_logged_in() ) {
        return;
    }

    check_admin_referer( 'bptodo_export_csv' );

    $user_id = get_current_user_id();
    $todos = get_user_todos( $user_id );

    // Set headers for CSV download
    header( 'Content-Type: text/csv' );
    header( 'Content-Disposition: attachment; filename="my-todos-' . date('Y-m-d') . '.csv"' );

    $output = fopen( 'php://output', 'w' );

    // Header row with custom columns
    fputcsv( $output, array(
        'Title',
        'Description',
        'Due Date',
        'Priority',
        'Status',
        'Category',
        'Created Date'
    ) );

    foreach ( $todos as $todo ) {
        $categories = wp_get_object_terms( $todo->ID, 'todo_category', array( 'fields' => 'names' ) );

        fputcsv( $output, array(
            $todo->post_title,
            wp_strip_all_tags( $todo->post_content ),
            get_post_meta( $todo->ID, 'todo_due_date', true ),
            get_post_meta( $todo->ID, 'todo_priority', true ),
            get_post_meta( $todo->ID, 'todo_status', true ),
            implode( ', ', $categories ),
            $todo->post_date,
        ) );
    }

    fclose( $output );
    exit;
}
```

---

## Extending the Plugin

### Creating an Addon Plugin

**Structure:**
```
my-todo-addon/
├── my-todo-addon.php
├── includes/
│   └── class-addon.php
└── readme.txt
```

**my-todo-addon.php:**
```php
<?php
/**
 * Plugin Name: My Todo Addon
 * Description: Extends Todo for BuddyPress
 * Version: 1.0.0
 * Requires Plugins: buddypress-user-todo-list
 */

// Check if parent plugin is active
add_action( 'plugins_loaded', 'my_todo_addon_init' );
function my_todo_addon_init() {
    if ( ! class_exists( 'BPTODO' ) ) {
        add_action( 'admin_notices', 'my_todo_addon_missing_notice' );
        return;
    }

    // Load addon
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-addon.php';
    My_Todo_Addon::get_instance();
}

function my_todo_addon_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e( 'My Todo Addon requires Todo for BuddyPress to be installed and active.', 'my-todo-addon' ); ?></p>
    </div>
    <?php
}
```

**includes/class-addon.php:**
```php
<?php
class My_Todo_Addon {
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->hooks();
    }

    private function hooks() {
        // Add custom features
        add_action( 'bptodo_after_add_todo', array( $this, 'on_todo_created' ) );
        add_filter( 'bptodo_query_args', array( $this, 'modify_query' ), 10, 2 );

        // Add custom admin page
        add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
    }

    public function on_todo_created( $todo_id ) {
        // Custom logic when todo is created
        update_post_meta( $todo_id, '_addon_timestamp', time() );
    }

    public function modify_query( $args, $user_id ) {
        // Modify todo query
        return $args;
    }

    public function add_admin_page() {
        add_submenu_page(
            'user-todo-list-settings',
            __( 'Addon Settings', 'my-todo-addon' ),
            __( 'Addon', 'my-todo-addon' ),
            'manage_options',
            'my-todo-addon',
            array( $this, 'render_admin_page' )
        );
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'My Todo Addon Settings', 'my-todo-addon' ); ?></h1>
            <!-- Settings form -->
        </div>
        <?php
    }
}
```

### Best Practices

1. **Use Hooks:** Always use hooks instead of modifying core files
2. **Check Dependencies:** Verify parent plugin is active
3. **Prefix Everything:** Use unique prefixes for functions, classes, variables
4. **Sanitize & Escape:** Always sanitize input and escape output
5. **Translations:** Make all strings translatable
6. **Documentation:** Comment your code thoroughly
7. **Testing:** Test with BuddyPress, BuddyBoss, and various themes
8. **Performance:** Use caching when querying multiple todos
9. **Backwards Compatibility:** Don't break existing functionality
10. **Follow WordPress Coding Standards**

---

## Support

For more information and support:

- **Documentation:** [https://wbcomdesigns.com/downloads/buddypress-user-todo-list/](https://wbcomdesigns.com/downloads/buddypress-user-todo-list/)
- **GitHub:** [https://github.com/wbcomdesigns/buddypress-user-todo-list/](https://github.com/wbcomdesigns/buddypress-user-todo-list/)
- **Support:** [https://wbcomdesigns.com/helpdesk/](https://wbcomdesigns.com/helpdesk/)

---

**Version:** 3.5.0
**Last Updated:** January 2025
**Author:** Wbcom Designs
