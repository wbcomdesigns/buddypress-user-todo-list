# Troubleshooting

## The To-do tab is missing from member profiles

Turn on Enable for members in [Admin settings](../settings/admin-settings), and check that the member's role is allowed to create tasks.

## The group Todos tab is missing

The BuddyPress Groups component must be active, group to-dos must be enabled in the Group settings, and the group must not have to-dos disabled in its own settings.

## No due-date notification arrives

Send notification needs the BuddyPress Notifications component to be active. For email, turn on Send email and confirm the site can send mail. Reminders fire when the due date arrives, in the site's timezone.

## Reminder links go to a missing page without BuddyPress

Set the Todos page setting to the page that holds the My Todos block or the `[bptodo_list]` shortcode.

## A member cannot tick an assigned group task

Members complete tasks from the group Todos tab, where they see the tasks assigned to them. If the task is missing, ask a group admin to confirm the member was included when it was assigned.

## The list looks broken or does not update

Another plugin or the theme may conflict with the Modern layout. Set Frontend layout to Classic in the General settings. Sites on WordPress below 6.5 use Classic automatically. See [Modern vs. Legacy layout](../getting-started/modern-vs-legacy).

## Dates show in the wrong format

Dates follow the WordPress date format and timezone under Settings, General.
