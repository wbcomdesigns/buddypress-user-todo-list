# Developer Guide

The developer documentation lives in [`docs/website/developer-guide/`](website/developer-guide/):

- [Extension seams](website/developer-guide/extension-seams.md) - domain layer, actions, filters, storage contract.
- [REST API](website/developer-guide/rest-api.md) - `bptodo/v1` routes, permissions and errors.
- [Blocks](website/developer-guide/blocks.md) - the My Todos and Group Todos blocks.

Shortcodes: [shortcodes.md](shortcodes.md). WP-CLI: [WP-CLI-COMMANDS.md](WP-CLI-COMMANDS.md).

The previous long-form guide described hooks that the plugin never fired (`bptodo_loaded`,
`bptodo_after_add_todo`, `bptodo_before_cleanup_todos` and others), so it was retired in 4.0.1.
