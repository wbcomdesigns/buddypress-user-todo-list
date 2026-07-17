---
title: Categories
slug: categories
---

# Categories

To-dos can be organized with categories, backed by the `todo_category` taxonomy.

## Who can create categories

- Site admins (or any user with the `manage_categories` capability) can always create
  categories.
- Regular members can create categories only when **Member-created categories**
  (`allow_user_add_category`) is enabled in Settings → Todo List → General.

## Where members create them

On the [Add a to-do](add-a-todo) form, an inline "+ New" control next to the Category
field lets a member type a name and add it on the spot; it appears in the category list
immediately and is selected for the task being created.

## REST endpoint

`POST /wp-json/bptodo/v1/categories` creates a category (idempotent by name; creating an
existing name returns the existing term). See
[REST API reference](../developer-guide/rest-api) for the full request/response shape.
