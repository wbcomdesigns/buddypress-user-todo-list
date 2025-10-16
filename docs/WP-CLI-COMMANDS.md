# WP-CLI Commands Guide

This plugin includes WP-CLI commands to help you generate demo data for testing the BuddyPress User Todo List plugin.

## Overview

The WP-CLI commands allow you to:
- Generate demo member todos for testing
- Generate demo group todos for testing
- Clean up demo data when you're done

All demo data is tagged with `_bptodo_demo_data` meta for easy identification and cleanup.

## Available Commands

### 1. Generate Member Todos

Generate demo todos for BuddyPress members.

```bash
wp bptodo generate member-todos [--users=<ids>] [--count=<number>] [--clean]
```

#### Options

- `--users=<ids>` - Comma-separated user IDs. If not provided, automatically selects up to 3 users.
- `--count=<number>` - Number of todos to create per user. Default: 5
- `--clean` - Remove existing demo data before generating new todos

#### Examples

```bash
# Generate 5 todos for auto-selected users
wp bptodo generate member-todos

# Generate 10 todos for specific users
wp bptodo generate member-todos --users=2,24,21 --count=10

# Clean existing demo data and generate 3 new todos per user
wp bptodo generate member-todos --clean --count=3

# Generate todos for a single user
wp bptodo generate member-todos --users=2 --count=5
```

### 2. Generate Group Todos

Generate demo todos for BuddyPress groups.

```bash
wp bptodo generate group-todos [--groups=<ids>] [--count=<number>] [--clean]
```

#### Options

- `--groups=<ids>` - Comma-separated group IDs. If not provided, automatically selects up to 3 groups.
- `--count=<number>` - Number of todos to create per group. Default: 5
- `--clean` - Remove existing demo data before generating new todos

#### Examples

```bash
# Generate 5 todos for auto-selected groups
wp bptodo generate group-todos

# Generate 10 todos for specific groups
wp bptodo generate group-todos --groups=1,2,3 --count=10

# Clean existing demo data and generate 3 new todos per group
wp bptodo generate group-todos --clean --count=3

# Generate todos for a single group
wp bptodo generate group-todos --groups=1 --count=8
```

### 3. Cleanup Demo Data

Remove demo todos from the database.

```bash
wp bptodo cleanup [--all]
```

#### Options

- `--all` - Delete ALL bp-todo posts, not just demo data (use with caution!)

#### Examples

```bash
# Remove only demo data (todos tagged with _bptodo_demo_data)
wp bptodo cleanup

# Remove ALL todos (requires confirmation)
wp bptodo cleanup --all
```

## Demo Task Templates

All generated todos use realistic task templates from `admin/demo-tasks.json`. The JSON file includes 44+ diverse tasks covering:

- **Critical Priority**: Security vulnerabilities, hotfixes, database issues, payment failures
- **High Priority**: Reports, documentation, code reviews, client presentations
- **Normal Priority**: Refactoring, research, optimization, feature development
- **Completed Tasks**: Past successful tasks for realistic testing

### Task Template Structure

Each task in the JSON file includes:

```json
{
  "title": "Task title",
  "description": "Detailed task description",
  "priority": "critical|high|normal",
  "due_date": "{{today}}|{{tomorrow}}|{{week}}|{{past}}|{{2weeks}}|{{3weeks}}",
  "status": "incomplete|complete"
}
```

### Date Placeholders

The following date placeholders are automatically converted:

- `{{today}}` - Current date
- `{{tomorrow}}` - Tomorrow's date
- `{{week}}` - 7 days from now
- `{{2weeks}}` - 14 days from now
- `{{3weeks}}` - 21 days from now
- `{{past}}` - 3 days ago

### Customizing Task Templates

You can customize the demo tasks by editing `admin/demo-tasks.json`:

1. Add new tasks to the JSON array
2. Modify existing task titles, descriptions, or priorities
3. Use date placeholders for dynamic due dates
4. Set status to `complete` for completed tasks

Example custom task:

```json
{
  "title": "Custom testing task",
  "description": "This is a custom task for testing purposes",
  "priority": "high",
  "due_date": "{{tomorrow}}",
  "status": "incomplete"
}
```

## Workflow Examples

### Testing Member Todo Features

```bash
# Generate diverse test data for 3 users
wp bptodo generate member-todos --users=2,24,21 --count=10

# Test the sorting, filtering, and display
# Visit: http://yoursite.com/members/username/todo/

# Clean up when done
wp bptodo cleanup
```

### Testing Group Todo Features

```bash
# Generate test data for specific groups
wp bptodo generate group-todos --groups=1,2 --count=8

# Test group todo functionality
# Visit: http://yoursite.com/groups/groupname/todo/

# Clean up demo data
wp bptodo cleanup
```

### Complete Testing Setup

```bash
# Clean slate - remove old demo data
wp bptodo cleanup

# Generate member todos
wp bptodo generate member-todos --count=15

# Generate group todos
wp bptodo generate group-todos --count=10

# Run your tests...

# Clean up everything when done
wp bptodo cleanup
```

### Reset and Start Fresh

```bash
# Clean and regenerate in one command
wp bptodo generate member-todos --clean --count=5
wp bptodo generate group-todos --clean --count=5
```

## Technical Details

### How Demo Data is Tagged

All generated todos include a meta field:
```php
update_post_meta( $todo_id, '_bptodo_demo_data', '1' );
```

This allows the cleanup command to identify and remove only demo data, leaving real user data intact.

### Group Todo Assignment

For group todos, the system:
1. Identifies the group creator or first admin
2. Creates the todo assigned to that user
3. Associates it with the group via `todo_group_id` meta

### Task Selection

Tasks are randomly selected from the JSON file for each todo generation, providing diverse and realistic test scenarios.

## Best Practices

1. **Always use --clean** when you want fresh test data to avoid duplicates
2. **Test with realistic counts** - Don't generate 100 todos per user, use realistic numbers (5-15)
3. **Customize the JSON** - Edit `admin/demo-tasks.json` to match your testing scenarios
4. **Use specific IDs** - When testing specific users/groups, always specify IDs with `--users` or `--groups`
5. **Clean up regularly** - Run `wp bptodo cleanup` after testing to keep your database clean

## Troubleshooting

### Command not found
Ensure you're running the command from the WordPress root directory, or use `--path`:
```bash
wp bptodo generate member-todos --path=/path/to/wordpress
```

### No users/groups found
If auto-detection fails, explicitly specify IDs:
```bash
wp bptodo generate member-todos --users=1,2,3
```

### BuddyPress not active
The group-todos command requires BuddyPress Groups component to be active. Enable it in BuddyPress settings.

## Support

For issues or questions:
- GitHub: https://github.com/wbcomdesigns/buddypress-user-todo-list
- Support: https://wbcomdesigns.com/contact/

## Files

- **Command Class**: `admin/class-bptodo-cli-commands.php`
- **Task Templates**: `admin/demo-tasks.json`
- **Registration**: `bp-user-todo-list.php` (lines 283-291)
