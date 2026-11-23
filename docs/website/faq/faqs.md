# Frequently asked questions

## Do I need BuddyPress or BuddyBoss?

No. Add the My Todos block or the `[bptodo_list]` shortcode to any page and each logged-in user gets a private list. BuddyPress or BuddyBoss adds the To-do profile tab and the group Todos tab.

## Where are the settings?

Under Settings, Todo List, General tab. See [Admin settings](../settings/admin-settings).

## Can I rename "Todo" to something else?

Yes. Set the Todo Label (singular) and Todos Label (plural) settings, for example Task and Tasks. The profile tab, empty state and buttons use them.

## Who can create to-dos?

Every member by default. Restrict creation to specific roles with the Roles allowed to create tasks setting.

## Are to-dos public?

No. A to-do has no public page, archive or search entry. See [Privacy](../features/privacy).

## Who can assign a group task to members?

Group admins, and moderators when the group settings allow it. Members see the tasks assigned to them on the group Todos tab and can tick them complete.

## Why does the assign picker not list every member of a large group?

It shows the first 100 members A to Z so big groups load quickly. Leave every box unchecked to assign the task to the whole group, or raise the number with the `bptodo_assignee_picker_limit` filter.

## Can I switch back to the older layout?

Yes. Set Frontend layout to Classic in the General settings. See [Modern vs. Legacy layout](../getting-started/modern-vs-legacy).

## Is there a REST API?

Yes. The `bptodo/v1` namespace covers to-dos, completion, stats, categories and group to-dos. See [REST API reference](../developer-guide/rest-api).

## Is the plugin translated?

German, Spanish, French, Italian and Portuguese (Brazil) are included, and translations load from WordPress.org language packs.
