# WP-CLI commands

The plugin registers WP-CLI commands for generating demo tasks and cleaning them up. They are available only when WP-CLI is running and BuddyPress is active.

## Generate member tasks

```
wp bptodo generate member-todos
wp bptodo generate member-todos --users=2,24,21 --count=10
wp bptodo generate member-todos --clean --count=3
```

- `--users=<ids>`: comma-separated user IDs. Omit to use a default set.
- `--count=<number>`: tasks per user. Default 5.
- `--clean`: remove existing demo data before generating.

## Generate group tasks

```
wp bptodo generate group-todos
wp bptodo generate group-todos --groups=1,2,3 --count=10
wp bptodo generate group-todos --clean --count=3
```

- `--groups=<ids>`: comma-separated group IDs. Omit to use a default set.
- `--count=<number>`: tasks per group. Default 5.
- `--clean`: remove existing demo data before generating.

## Clean up demo tasks

```
wp bptodo cleanup
wp bptodo cleanup --all
```

- No flag: removes only demo tasks (posts flagged with the `_bptodo_demo_data` meta by the generators).
- `--all`: permanently deletes every `bp-todo` task, not just demo data. Both modes prompt for confirmation first. Use `--all` with great care on a production database.

## Notes

These commands create real `bp-todo` posts, so generated tasks appear in member and group lists exactly like manually created ones. They are intended for demo sites and local testing, not production seeding.
