# bptodo_by_category

The plugin registers one shortcode, `[bptodo_by_category]`. It shows the current logged-in member's own tasks in a single category, grouped by due date.

## Usage

```
[bptodo_by_category category="5"]
```

## Attributes

| Attribute | Required | Description |
| --- | --- | --- |
| `category` | Yes | The numeric term ID of a Todo Category. Not a slug and not a name. |

`category` is the only attribute. There are no `limit`, `user_id`, `status`, `orderby`, `show_add`, or `show_export` attributes.

## Finding a category ID

In wp-admin go to Todo Items > Categories, hover a category, and read the `tag_ID` value in the edit link. Use that number as the `category` value.

## What it displays

- Only the tasks that belong to the currently logged-in member (the shortcode uses the current user as the author).
- Tasks in the given category and its child categories.
- Grouped into Past, Today, Tomorrow, and Future by due date, sorted with completed tasks last.
- Up to 10 tasks.

Each row includes the built-in complete, undo, edit, remove, and view actions, so the shortcode is interactive for the member viewing it.

## Behavior notes

- Logged-out visitors see a separate template instead of tasks.
- If `category` is missing or is not a valid category ID, the shortcode shows a notice asking for a valid category ID rather than rendering a list.
- Because output is per logged-in user, the same shortcode on a shared page shows each member their own tasks.
