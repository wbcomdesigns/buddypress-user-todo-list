<?php

/**
 * Exit if accessed directly.
 *
 * @package bp-user-todo-list
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filter to get only primary tasks (where todo_primary_id equals the post's own ID).
 * This performs the filtering at database level instead of loading all tasks into memory.
 *
 * @param string   $where The WHERE clause of the query.
 * @param WP_Query $query The WP_Query instance.
 * @return string Modified WHERE clause.
 */
function bptodo_filter_primary_tasks_only( $where, $query ) {
    global $wpdb;

    // Only apply to our specific query (bp-todo post type)
    if ( isset( $query->query_vars['post_type'] ) && 'bp-todo' === $query->query_vars['post_type'] ) {
        // Add condition: todo_primary_id meta value must equal the post's own ID
        // This filters for primary tasks only (self-referencing todo_primary_id)
        $where .= $wpdb->prepare(
            " AND {$wpdb->posts}.ID IN (
                SELECT pm.post_id FROM {$wpdb->postmeta} pm
                WHERE pm.meta_key = %s AND pm.meta_value = pm.post_id
            )",
            'todo_primary_id'
        );
    }

    return $where;
}

global $bptodo;
global $bp;

$profile_menu_slug         = $bptodo->profile_menu_slug;
$profile_menu_label        = $bptodo->profile_menu_label;
$profile_menu_label_plural = $bptodo->profile_menu_label_plural;

$groups_link = bptodo_get_group_url( $bp->groups->current_group );
$admin_link  = trailingslashit( $groups_link . $profile_menu_slug );

$group_members = groups_get_group_members(
    array(
        'group_id'            => $bp->groups->current_group->id,
        'exclude_admins_mods' => false,
    )
);

$group_members_ids = array();
if (isset($group_members['members'])) {
    foreach ($group_members['members'] as $member) {
        $group_members_ids[] = $member->ID;
    }
}

$current_user = get_current_user_id();
$cu_index     = array_search($current_user, $group_members_ids);
if ($cu_index !== false) {
    unset($group_members_ids[$cu_index]);
}

$class      = 'todo-completed';
$group_id   = $bp->groups->current_group->id ? $bp->groups->current_group->id : 0;

// Check if user is site admin and whether they're a group member
$is_site_admin = bptodo_is_site_admin();
$is_group_member = groups_is_user_member( get_current_user_id(), $group_id );

// Site admin who is NOT a group member gets read-only view
// Site admin who IS a group member follows normal member permissions
$is_admin_non_member = $is_site_admin && ! $is_group_member;

$can_modify = (function_exists('groups_is_user_admin') && groups_is_user_admin(get_current_user_id(), $group_id)) || $is_admin_non_member;
$can_views = ((function_exists('groups_is_user_admin') && groups_is_user_admin(get_current_user_id(), $group_id)) || (function_exists('groups_is_user_mod') && groups_is_user_mod(get_current_user_id(), $group_id))) || $is_admin_non_member;

if (function_exists('groups_is_user_mod') && groups_is_user_mod(get_current_user_id(), $group_id)) {
    $mod_can_modify = bptodo_if_moderator_modification_enabled($group_id, $current_user);
    if ($mod_can_modify) {
        $can_modify = true;
    }
    $mod_can_views = bptodo_report_view_enabled($group_id, $current_user);
    if ($mod_can_views) {
        $can_views = true;
    }
}

// List of Todo Items with limited posts per page for better performance.
// Site admins see all PRIMARY tasks, regular users see only their assigned todos

$args = array(
    'post_type'      => 'bp-todo',
    'post_status'    => 'publish',
    'posts_per_page' => 100,
    'paged'          => 1,
);

// Query logic:
// - Admin who is NOT a group member: Get only PRIMARY tasks (filtered at database level)
// - Admin who IS a group member OR regular user: Get only their own tasks
if ( $is_admin_non_member ) {
    // Admin non-member: Get only PRIMARY tasks in group (filtered at database level)
    // Primary tasks have todo_primary_id equal to their own post ID (self-reference)
    $args['meta_query'] = array(
        array(
            'key'     => 'todo_group_id',
            'value'   => $group_id,
            'compare' => '=',
            'type'    => 'NUMERIC',
        ),
    );

    // Add filter to only get primary tasks (where todo_primary_id = post ID)
    add_filter( 'posts_where', 'bptodo_filter_primary_tasks_only', 10, 2 );
} else {
    // Regular member or admin who is a group member: Get only their own tasks
    $args['author'] = get_current_user_id();
    $args['meta_query'] = array(
        array(
            'key'     => 'todo_group_id',
            'value'   => $group_id,
            'compare' => '=',
            'type'    => 'NUMERIC',
        ),
    );
}

$all_todo_count     = 0;
$all_completed_todo = 0;
$all_remaining_todo = 0;
$completed_todo_ids = array();
$todo_list          = array();
$all_todos          = array(); // Store all todos for intelligent sorting

// Loop through all pages to get all todos.
do {
    $todos = get_posts($args);

    // Collect all todos first for sorting
    $all_todos = array_merge($all_todos, $todos);

    $args['paged']++;
} while (count($todos) === $args['posts_per_page']); // Continue fetching until no more posts are returned.

// Remove the filter after query completes (only if it was added)
if ( $is_admin_non_member ) {
    remove_filter( 'posts_where', 'bptodo_filter_primary_tasks_only', 10 );
}
// Note: Primary task filtering is now done at database level via bptodo_filter_primary_tasks_only()
// No need to filter in PHP anymore - significant performance improvement for large datasets

// Intelligent sorting: Priority (Critical > High > Normal) + Due Date (earliest first) + Status (incomplete first)
usort( $all_todos, function( $a, $b ) {
	$a_status   = get_post_meta( $a->ID, 'todo_status', true );
	$b_status   = get_post_meta( $b->ID, 'todo_status', true );
	$a_priority = get_post_meta( $a->ID, 'todo_priority', true );
	$b_priority = get_post_meta( $b->ID, 'todo_priority', true );
	$a_due_date = get_post_meta( $a->ID, 'todo_due_date', true );
	$b_due_date = get_post_meta( $b->ID, 'todo_due_date', true );

	// 1. Completed tasks always go to bottom
	if ( $a_status === 'complete' && $b_status !== 'complete' ) {
		return 1;
	}
	if ( $a_status !== 'complete' && $b_status === 'complete' ) {
		return -1;
	}

	// 2. Sort by priority (Critical=3, High=2, Normal=1, Empty=0)
	$priority_map = array( 'critical' => 3, 'high' => 2, 'normal' => 1 );
	$a_priority_val = isset( $priority_map[ $a_priority ] ) ? $priority_map[ $a_priority ] : 0;
	$b_priority_val = isset( $priority_map[ $b_priority ] ) ? $priority_map[ $b_priority ] : 0;

	if ( $a_priority_val !== $b_priority_val ) {
		return $b_priority_val - $a_priority_val; // Higher priority first
	}

	// 3. Sort by due date (earliest first, empty dates last)
	if ( empty( $a_due_date ) && ! empty( $b_due_date ) ) {
		return 1;
	}
	if ( ! empty( $a_due_date ) && empty( $b_due_date ) ) {
		return -1;
	}
	if ( ! empty( $a_due_date ) && ! empty( $b_due_date ) ) {
		$a_timestamp = strtotime( $a_due_date );
		$b_timestamp = strtotime( $b_due_date );
		// Handle invalid dates gracefully
		if ( false === $a_timestamp || false === $b_timestamp ) {
			return 0;
		}
		return $a_timestamp - $b_timestamp;
	}

	// 4. If all else equal, sort by post date (newest first)
	return strtotime( $b->post_date ) - strtotime( $a->post_date );
} );

// Now process the sorted todos
foreach ($all_todos as $todo) {
        $curr_date   = date_create(gmdate('Y-m-d'));
        $due_date    = date_create(get_post_meta($todo->ID, 'todo_due_date', true));
        $todo_status = get_post_meta($todo->ID, 'todo_status', true);
        $diff        = date_diff($curr_date, $due_date);
        $diff_days   = (int) $diff->format('%R%a');

        if ($diff_days < 0) {
            $todo_list['past'][] = $todo->ID;
        } elseif (0 === $diff_days) {
            $todo_list['today'][] = $todo->ID;
            if ('complete' !== $todo_status) {
                $all_remaining_todo++;
            }
        } elseif (1 === $diff_days) {
            $todo_list['tomorrow'][] = $todo->ID;
            if ('complete' !== $todo_status) {
                $all_remaining_todo++;
            }
        } else {
            $todo_list['future'][] = $todo->ID;
            if ('complete' !== $todo_status) {
                $all_remaining_todo++;
            }
        }

        if ('complete' === $todo_status) {
            $all_completed_todo++;
            $completed_todo_ids[] = $todo->ID;
        }

        $all_todo_count++;
}

$avg_rating = $all_todo_count > 0 ? ($all_completed_todo * 100) / $all_todo_count : 0;

// Calculate group-wide statistics for group overview mode
// Removed unused group-overview statistics calculation (leftover from dual-view mode)
// These stats are calculated in overview.php instead

$bp_template_option = bp_get_option('_bp_theme_package_id');

// Check if todo list is actually empty (no todos in any category)
$has_todos = false;
if (!empty($todo_list)) {
    foreach ($todo_list as $category_todos) {
        if (!empty($category_todos)) {
            $has_todos = true;
            break;
        }
    }
}

if (!$has_todos) {
    if ('nouveau' === $bp_template_option) { ?>
        <div id="message" class="bptodo-notice info bp-feedback bp-messages bp-template-notice">
            <span class="bp-icon" aria-hidden="true"></span>
        <?php } else { ?>
            <div id="message" class="info">
            <?php } ?>
            <p>
                <?php /* Translators: Display the plural label name */ ?>
                <?php echo sprintf(esc_html__('Sorry, no %1$s found.', 'wb-todo'), esc_html($profile_menu_label)); ?>
            </p>
            </div>
        <?php } else { ?>

            <!-- Show the successful message when todo is added -->
            <?php
            if (isset($_POST['todo_create']) && isset($_POST['save_new_todo_data_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['save_new_todo_data_nonce'])), 'wp-bp-todo')) {
                if ('nouveau' === $bp_template_option) {
            ?>
                    <div id="message" class="bptodo-notice info bp-feedback bp-messages bp-template-notice">
                        <span class="bp-icon" aria-hidden="true"></span>
                    <?php } else { ?>
                        <div id="message" class="info">
                        <?php } ?>
                        <p>
                            <?php /* Translators: Display the plural label name */ ?>
                            <?php echo sprintf(esc_html__('%1$s added successfully !', 'wb-todo'), esc_html($profile_menu_label)); ?>
                        </p>
                        </div>
                    <?php } ?>

                    <!-- Show the successful message when todo is updated -->
                    <?php
                    if (isset($_POST['todo_update'])) {
                        if ('nouveau' === $bp_template_option) {
                    ?>
                            <div id="message" class="bptodo-notice info bp-feedback bp-messages bp-template-notice">
                                <span class="bp-icon" aria-hidden="true"></span>
                            <?php } else { ?>
                                <div id="message" class="info">
                                <?php } ?>
                                <p>
                                    <?php /* Translators: Display the plural label name */ ?>
                                    <?php echo sprintf(esc_html__('%1$s updated successfully !', 'wb-todo'), esc_html($profile_menu_label)); ?>
                                </p>
                                </div>
                            <?php } ?>

                            <section class="bptodo-adming-setting">
                                <div class="bptodo-admin-settings-block">
                                    <div class="bptodo-progress-section">
                                        <!-- Personal Progress -->
                                        <h5><?php printf( esc_html__( '%s Progress', 'wb-todo' ), esc_html( $profile_menu_label ) ); ?></h5>
                                        <div class="task-progress-task-count-wrap">
                                            <div class="task-progress-task-count">
                                                <span class="task_breaker-total-tasks"><?php echo esc_html($all_todo_count); ?></span>
                                                <?php
                                                if ($all_todo_count <= 1) {
                                                    echo esc_html($profile_menu_label);
                                                } else {
                                                    echo esc_html($profile_menu_label_plural);
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <div class="bptodo-light-grey">
                                            <span><b><?php echo esc_html(round($avg_rating, 2)) . '% '; ?></b><?php esc_html_e('Completed', 'wb-todo'); ?></span>
                                            <div class="bptodo-color" style="height:24px;width:<?php echo esc_attr($avg_rating); ?>%">
                                            </div>
                                        </div>
                                    </div>

                                    <div id="bptodo-tabs">

                                        <?php do_action('bptodo_add_extra_tab_content_before_defaults', $profile_menu_label); ?>

                                        <div id="bptodo-todos">
                                            <div id="bptodo-task-tabs">
                                                <div class="bptodo-task-tabs-head">
                                                    <ul class="bptodo-task-tabs-btn">
                                                        <li class="all-bp-todo-list-tab">
                                                            <a href="#bptodo-all">
                                                                <span class="bptodo-btn-icon"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/all-list.svg" /></span>
                                                                <span><?php /* translators: %s: */ echo sprintf(esc_html__('All %1$s', 'wb-todo'), esc_html($profile_menu_label)); ?></span>
                                                                <span class="bpdoto-count-tab bp_all_todo_count"><?php echo esc_html($all_todo_count); ?></span>
                                                            </a>
                                                        </li>
                                                        <li class="all-bp-todo-list-tab-completed">
                                                            <a href="#bptodo-completed">
                                                                <span class="bptodo-btn-icon"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/check-complete.svg" /></span>
                                                                <span><?php esc_html_e('Completed', 'wb-todo'); ?></span>
                                                                <span class="bpdoto-count-tab bp_completed_todo_count"><?php echo esc_html($all_completed_todo); ?></span>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>

                                                <div id="bptodo-all">
                                                    <div class="todo-panel">
                                                        <div class="todo-detail">
                                                            <div class="bp-todo-reminder">
                                                                <div class="todo-header">
                                                                    <ul class="todo-header-head">
                                                                        <li class="bptodo-check"></li>
                                                                        <li class="bptodo-priority"><?php esc_html_e('Priority', 'wb-todo'); ?></li>
                                                                        <li class="bptodo-date"><?php esc_html_e('Due Date', 'wb-todo'); ?></li>
                                                                        <li class="bptodo-cat"><?php esc_html_e('Category', 'wb-todo'); ?></li>
                                                                        <li class="bptodo-task"><?php echo esc_html( $profile_menu_label ); ?></li>
                                                                        <?php if ($can_modify) { ?>
                                                                            <li class="bptodo-actions"><?php esc_html_e('Actions', 'wb-todo'); ?></li>
                                                                        <?php  } ?>
                                                                    </ul>
                                                                </div>
                                                                <div class="todo-header-list">
                                                                    <!-- PAST TASKS -->
                                                                    <?php
                                                                    if (!empty($todo_list['past'])) {
                                                                        foreach ($todo_list['past'] as $tid) {
                                                                            $todo          = get_post($tid);
                                                                            $todo_title    = $todo->post_title;
                                                                            $todo_edit_url = $admin_link . '/add?args=' . $tid;
                                                                            $todo_view_url = get_permalink($tid);

                                                                            // Admin non-members have read-only access to all tasks
                                                                            // Members (including admins who are members) can edit their assigned tasks
                                                                            $is_readonly = $is_admin_non_member;
                                                                            $todo_status    = get_post_meta($todo->ID, 'todo_status', true);
                                                                            $todo_priority  = get_post_meta($todo->ID, 'todo_priority', true);
                                                                            $due_date_str   = $due_date_td_class = '';
                                                                            $curr_date      = date_create(gmdate('Y-m-d'));
                                                                            $due_date       = date_create(get_post_meta($todo->ID, 'todo_due_date', true));
                                                                            $diff           = date_diff($curr_date, $due_date);
                                                                            $diff_days      = (int) $diff->format('%R%a');
                                                                            $priority_class = '';
                                                                            $todo_cats = get_terms(array(
                                                                                'taxonomy'   => 'todo_category',
                                                                                'orderby'    => 'name',
                                                                                'hide_empty' => false,
                                                                            ));

                                                                            $todo_cat    = wp_get_object_terms($tid, 'todo_category');
                                                                            $todo_cat_id = !empty($todo_cat) && is_array($todo_cat) ? $todo_cat[0]->term_id : 0;
                                                                            $disabled       = '';
                                                                            if ($diff_days < 0) {
                                                                                $due_date_str      = esc_html__('Expired!', 'wb-todo');
                                                                                $due_date_td_class = 'bptodo-expired';
                                                                                $disabled          = 'disabled';
                                                                            } elseif (0 === $diff_days) {
                                                                                $due_date_str      = esc_html__('Today is the last day to complete. Hurry Up!', 'wb-todo');
                                                                                $due_date_td_class = 'bptodo-expires-today';
                                                                            } else {
                                                                                $day_string = abs($diff_days) === 1 ? __('day', 'wb-todo') : __('days', 'wb-todo');
						                                                        /* translators: %1$d */
                                                                                $due_date_str = sprintf(esc_html__('%1$d %2$s left', 'wb-todo'), abs($diff_days), $day_string);
                                                                            }

                                                                            $bptodo_row_complete = '';
                                                                            if ('complete' === $todo_status) {
                                                                                $due_date_str      = esc_html__('Completed', 'wb-todo');
                                                                                $due_date_td_class = '';
                                                                                $bptodo_row_complete = 'completed';
                                                                            }

                                                                            if (!empty($todo_priority)) {
                                                                                if ('critical' === $todo_priority) {
                                                                                    $priority_class = 'bptodo-priority-critical';
                                                                                    $priority_text  = esc_html__('Critical', 'wb-todo');
                                                                                } elseif ('high' === $todo_priority) {
                                                                                    $priority_class = 'bptodo-priority-high';
                                                                                    $priority_text  = esc_html__('High', 'wb-todo');
                                                                                } else {
                                                                                    $priority_class = 'bptodo-priority-normal';
                                                                                    $priority_text  = esc_html__('Normal', 'wb-todo');
                                                                                }
                                                                            }
                                                                            ?>
                                                                            <ul id="bptodo-row-<?php echo esc_attr($tid); ?>" class="todo-header-list-row <?php echo esc_attr($bptodo_row_complete . ' ' . $due_date_td_class); ?>">
                                                                                <?php
                                                                                $checked = '';
                                                                                $checked_class = 'todo-uncomplete';
                                                                                if ('complete' === $todo_status) {
                                                                                    $checked = 'checked';
                                                                                    $checked_class = 'todo-complete';
                                                                                }
                                                                                ?>
					                                                            <?php	/* translators: %s */ ?>
                                                                                <li id="bptodo-complete-li-<?php echo esc_attr($tid); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr($checked_class); ?> <?php echo $is_readonly ? 'bptodo-readonly' : ''; ?>" data-tid="<?php echo esc_attr($tid); ?>" title="<?php echo sprintf(esc_html__('Complete: %s', 'wb-todo'), esc_attr($todo_title)); ?>">
                                                                                    <label><input type="checkbox" class="option-input checkbox" <?php echo $checked ? 'checked="checked"' : ''; ?> <?php echo $disabled ? 'disabled="disabled"' : ''; ?> <?php echo $is_readonly ? 'disabled="disabled"' : ''; ?> /></label>
                                                                                </li>
                                                                                <li class="bptodo-priority"><span class="<?php echo esc_attr($priority_class); ?>"><?php echo esc_html($priority_text); ?></span></li>
                                                                                <li class="bptodo-date"><span>Due Date</span><?php echo esc_html($due_date_str); ?></li>
                                                                                <li class="bptodo-cat">
                                                                                    <span class="bptodo-cat-mobile-title">Category</span>
                                                                                    <span class="bptodo-cat-text">
                                                                                        <?php
                                                                                        foreach ($todo_cats as $todo_cat) {
                                                                                            if ($todo_cat_id == $todo_cat->term_id) {
                                                                                                echo esc_html($todo_cat->name);
                                                                                            }
                                                                                        }
                                                                                        ?>
                                                                                    </span>
                                                                                </li>
                                                                                <li class="bptodo-task">
                                                                                    <?php echo esc_html($todo_title); ?>
                                                                                    <?php if ($is_readonly) { ?>
                                                                                        <span class="bptodo-readonly-badge" title="<?php esc_attr_e('This task is not assigned to you', 'wb-todo'); ?>">
                                                                                            <span class="dashicons dashicons-lock"></span> <?php esc_html_e('Read-Only', 'wb-todo'); ?>
                                                                                        </span>
                                                                                    <?php } ?>
                                                                                </li>
                                                                                <li class="bp-to-do-actions">
                                                                                    <ul class="bp-to-do-actions-list">
                                                                                        <?php  /* translators: %s */ ?>
                                                                                        <li><a href="<?php echo esc_url($todo_view_url); ?>" title="<?php echo sprintf(esc_html__('View: %s', 'wb-todo'), esc_attr($todo_title)); ?>" target="_blank"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/eye.svg" /></a></li>
                                                                                        <?php if ($can_modify && !$is_readonly) { ?>
                                                                                            <?php  /* translators: %s */ ?>
                                                                                            <li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr($tid); ?>" title="<?php echo sprintf(esc_html__('Remove: %s', 'wb-todo'), esc_attr($todo_title)); ?>"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/trash-alt.svg" /></a></li>
                                                                                            <?php  /* translators: %s */ ?>
                                                                                            <li><button data-tid="<?php echo esc_attr($tid); ?>" class="trigger" data-modal-trigger="trigger" data-type='group' title="<?php echo sprintf(esc_html__('Edit: %s', 'wb-todo'), esc_attr($todo_title)); ?>"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/edit.svg" /></button></li>
                                                                                        <?php } ?>
                                                                                    </ul>
                                                                                </li>
                                                                            </ul>
                                                                        <?php } ?>
                                                                    <?php } ?>
                                                                    <!-- TASKS FOR TODAY -->
                                                                    <?php if (!empty($todo_list['today'])) { ?>
                                                                        <?php foreach ($todo_list['today'] as $tid) { ?>
                                                                            <?php
                                                                            $todo          = get_post($tid);
                                                                            $todo_title    = $todo->post_title;
                                                                            $todo_edit_url = $admin_link . '/add?args=' . $tid;

                                                                            // Admin non-members have read-only access to all tasks
                                                                            // Members (including admins who are members) can edit their assigned tasks
                                                                            $is_readonly = $is_admin_non_member;
                                                                            $todo_view_url = get_permalink($tid);
                                                                            $todo_status   = get_post_meta($todo->ID, 'todo_status', true);
                                                                            $todo_priority = get_post_meta($todo->ID, 'todo_priority', true);
                                                                            $due_date_str  = $due_date_td_class  = '';
                                                                            $curr_date     = date_create(gmdate('Y-m-d'));
                                                                            $due_date      = date_create(get_post_meta($todo->ID, 'todo_due_date', true));
                                                                            $diff          = date_diff($curr_date, $due_date);
                                                                            $diff_days     = (int) $diff->format('%R%a');
                                                                            $priority_class = '';
                                                                            $todo_cats = get_terms(array(
                                                                                'taxonomy'   => 'todo_category',
                                                                                'orderby'    => 'name',
                                                                                'hide_empty' => false,
                                                                            ));

                                                                            $todo_cat    = wp_get_object_terms($tid, 'todo_category');
                                                                            $todo_cat_id = !empty($todo_cat) && is_array($todo_cat) ? $todo_cat[0]->term_id : 0;
                                                                            $disabled = '';
                                                                            if ($diff_days < 0) {
                                                                                $due_date_str      = esc_html__('Expired!', 'wb-todo');
                                                                                $due_date_td_class = 'bptodo-expired';
                                                                                $disabled          = 'disabled';
                                                                            } elseif (0 === $diff_days) {
                                                                                $due_date_str      = esc_html__('Today is the last day to complete. Hurry Up!', 'wb-todo');
                                                                                $due_date_td_class = 'bptodo-expires-today';
                                                                            } else {
                                                                                $day_string = abs($diff_days) === 1 ? __('day', 'wb-todo') : __('days', 'wb-todo');
                                                                                 /* translators: %1$d */
                                                                                $due_date_str = sprintf(esc_html__('%1$d %2$s left', 'wb-todo'), abs($diff_days), $day_string);
                                                                            }

                                                                            $bptodo_row_complete = '';
                                                                            if ('complete' === $todo_status) {
                                                                                $due_date_str      = esc_html__('Completed', 'wb-todo');
                                                                                $due_date_td_class = '';
                                                                                $bptodo_row_complete = 'completed';
                                                                            }

                                                                            if (!empty($todo_priority)) {
                                                                                if ('critical' === $todo_priority) {
                                                                                    $priority_class = 'bptodo-priority-critical';
                                                                                    $priority_text  = esc_html__('Critical', 'wb-todo');
                                                                                } elseif ('high' === $todo_priority) {
                                                                                    $priority_class = 'bptodo-priority-high';
                                                                                    $priority_text  = esc_html__('High', 'wb-todo');
                                                                                } else {
                                                                                    $priority_class = 'bptodo-priority-normal';
                                                                                    $priority_text  = esc_html__('Normal', 'wb-todo');
                                                                                }
                                                                            }
                                                                            ?>
                                                                            <ul id="bptodo-row-<?php echo esc_attr($tid); ?>" class="todo-header-list-row <?php echo esc_attr($bptodo_row_complete . ' ' . $due_date_td_class); ?>">
                                                                                <?php
                                                                                $checked = '';
                                                                                $checked_class = 'todo-uncomplete';
                                                                                if ('complete' === $todo_status) {
                                                                                    $checked = 'checked';
                                                                                    $checked_class = 'todo-complete';
                                                                                }
                                                                                ?>
                                                                                <?php  /* translators: %s */ ?>
                                                                                <li id="bptodo-complete-li-<?php echo esc_attr($tid); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr($checked_class); ?> <?php echo $is_readonly ? 'bptodo-readonly' : ''; ?>" data-tid="<?php echo esc_attr($tid); ?>" title="<?php echo sprintf(esc_html__('Complete: %s', 'wb-todo'), esc_attr($todo_title)); ?>">
                                                                                    <label><input type="checkbox" class="option-input checkbox" <?php echo $checked ? 'checked="checked"' : ''; ?> <?php echo $disabled ? 'disabled="disabled"' : ''; ?> <?php echo $is_readonly ? 'disabled="disabled"' : ''; ?> /></label>
                                                                                </li>
                                                                                <li class="bptodo-priority"><span class="<?php echo esc_attr($priority_class); ?>"><?php echo esc_html($priority_text); ?></span></li>
                                                                                <li class="bptodo-date"><span>Due Date</span><?php echo esc_html($due_date_str); ?></li>
                                                                                <li class="bptodo-cat">
                                                                                    <span class="bptodo-cat-mobile-title">Category</span>
                                                                                    <span class="bptodo-cat-text">
                                                                                        <?php
                                                                                        foreach ($todo_cats as $todo_cat) {
                                                                                            if ($todo_cat_id == $todo_cat->term_id) {
                                                                                                echo esc_html($todo_cat->name);
                                                                                            }
                                                                                        }
                                                                                        ?>
                                                                                    </span>
                                                                                </li>
                                                                                <li class="bptodo-task">
                                                                                    <?php echo esc_html($todo_title); ?>
                                                                                    <?php if ($is_readonly) { ?>
                                                                                        <span class="bptodo-readonly-badge" title="<?php esc_attr_e('This task is not assigned to you', 'wb-todo'); ?>">
                                                                                            <span class="dashicons dashicons-lock"></span> <?php esc_html_e('Read-Only', 'wb-todo'); ?>
                                                                                        </span>
                                                                                    <?php } ?>
                                                                                </li>
                                                                                <li class="bp-to-do-actions">
                                                                                    <ul class="bp-to-do-actions-list">
                                                                                        <?php  /* translators: %s */ ?>
                                                                                        <li><a href="<?php echo esc_url($todo_view_url); ?>" title="<?php echo sprintf(esc_html__('View: %s', 'wb-todo'), esc_attr($todo_title)); ?>" target="_blank"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/eye.svg" /></a></li>
                                                                                        <?php if ($can_modify && !$is_readonly) { ?>
                                                                                            <?php  /* translators: %s */ ?>
                                                                                            <li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr($tid); ?>" title="<?php echo sprintf(esc_html__('Remove: %s', 'wb-todo'), esc_attr($todo_title)); ?>"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/trash-alt.svg" /></a></li>
                                                                                            <?php  /* translators: %s */ ?>
                                                                                            <li><button data-tid="<?php echo esc_attr($tid); ?>" class="trigger" data-modal-trigger="trigger" data-type='group' title="<?php echo sprintf(esc_html__('Edit: %s', 'wb-todo'), esc_attr($todo_title)); ?>"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/edit.svg" /></button></li>
                                                                                        <?php } ?>
                                                                                    </ul>
                                                                                </li>
                                                                            </ul>
                                                                        <?php } ?>
                                                                    <?php } ?>
                                                                    <!-- TASKS FOR TOMORROW -->
                                                                    <?php if (!empty($todo_list['tomorrow'])) { ?>
                                                                        <?php foreach ($todo_list['tomorrow'] as $tid) { ?>
                                                                            <?php
                                                                            $todo          = get_post($tid);
                                                                            $todo_title    = $todo->post_title;
                                                                            $todo_edit_url = $admin_link . '/add?args=' . $tid;

                                                                            // Admin non-members have read-only access to all tasks
                                                                            // Members (including admins who are members) can edit their assigned tasks
                                                                            $is_readonly = $is_admin_non_member;
                                                                            $todo_view_url = get_permalink($tid);
                                                                            $todo_status   = get_post_meta($todo->ID, 'todo_status', true);
                                                                            $todo_priority = get_post_meta($todo->ID, 'todo_priority', true);
                                                                            $due_date_str  = $due_date_td_class = '';
                                                                            $curr_date     = date_create(gmdate('Y-m-d'));
                                                                            $due_date      = date_create(get_post_meta($todo->ID, 'todo_due_date', true));
                                                                            $diff          = date_diff($curr_date, $due_date);
                                                                            $diff_days     = (int) $diff->format('%R%a');
                                                                            $priority_class = '';
                                                                            $todo_cats = get_terms(array(
                                                                                'taxonomy'   => 'todo_category',
                                                                                'orderby'    => 'name',
                                                                                'hide_empty' => false,
                                                                            ));

                                                                            $todo_cat    = wp_get_object_terms($tid, 'todo_category');
                                                                            $todo_cat_id = !empty($todo_cat) && is_array($todo_cat) ? $todo_cat[0]->term_id : 0;
                                                                             $disabled          = '';
                                                                            if ($diff_days < 0) {
                                                                                $due_date_str      = esc_html__('Expired!', 'wb-todo');
                                                                                $due_date_td_class = 'bptodo-expired';
                                                                                 $disabled          = 'disabled';
                                                                            } elseif (0 === $diff_days) {
                                                                                $due_date_str      = esc_html__('Today is the last day to complete. Hurry Up!', 'wb-todo');
                                                                                $due_date_td_class = 'bptodo-expires-today';
                                                                            } else {
                                                                                $day_string = abs($diff_days) === 1 ? __('day', 'wb-todo') : __('days', 'wb-todo');
                                                                                 /* translators: %1$d */
                                                                                $due_date_str = sprintf(esc_html__('%1$d %2$s left', 'wb-todo'), abs($diff_days), $day_string);
                                                                            }

                                                                            $bptodo_row_complete = '';
                                                                            if ('complete' === $todo_status) {
                                                                                $due_date_str      = esc_html__('Completed', 'wb-todo');
                                                                                $due_date_td_class = '';
                                                                                $bptodo_row_complete = 'completed';
                                                                            }

                                                                            if (!empty($todo_priority)) {
                                                                                if ('critical' === $todo_priority) {
                                                                                    $priority_class = 'bptodo-priority-critical';
                                                                                    $priority_text  = esc_html__('Critical', 'wb-todo');
                                                                                } elseif ('high' === $todo_priority) {
                                                                                    $priority_class = 'bptodo-priority-high';
                                                                                    $priority_text  = esc_html__('High', 'wb-todo');
                                                                                } else {
                                                                                    $priority_class = 'bptodo-priority-normal';
                                                                                    $priority_text  = esc_html__('Normal', 'wb-todo');
                                                                                }
                                                                            }
                                                                            ?>
                                                                            <ul id="bptodo-row-<?php echo esc_attr($tid); ?>" class="todo-header-list-row <?php echo esc_attr($bptodo_row_complete . ' ' . $due_date_td_class); ?>">
                                                                                <?php
                                                                                $checked = '';
                                                                                $checked_class = 'todo-uncomplete';
                                                                                if ('complete' === $todo_status) {
                                                                                    $checked = 'checked';
                                                                                    $checked_class = 'todo-complete';
                                                                                }
                                                                                ?>
                                                                                <?php  /* translators: %s */ ?>
                                                                              <li id="bptodo-complete-li-<?php echo esc_attr($tid); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr($checked_class); ?> <?php echo $is_readonly ? 'bptodo-readonly' : ''; ?>" data-tid="<?php echo esc_attr($tid); ?>" title="<?php echo sprintf(esc_html__('Complete: %s', 'wb-todo'), esc_attr($todo_title)); ?>">
                                                                                    <label><input type="checkbox" class="option-input checkbox" <?php echo $checked ? 'checked="checked"' : ''; ?> <?php echo $disabled ? 'disabled="disabled"' : ''; ?> <?php echo $is_readonly ? 'disabled="disabled"' : ''; ?> /></label>
                                                                                </li>
                                                                                <li class="bptodo-priority"><span class="<?php echo esc_attr($priority_class); ?>"><?php echo esc_html($priority_text); ?></span></li>
                                                                                <li class="bptodo-date"><span>Due Date</span><?php echo esc_html($due_date_str); ?></li>
                                                                                <li class="bptodo-cat">
                                                                                    <span class="bptodo-cat-mobile-title">Category</span>
                                                                                    <span class="bptodo-cat-text">
                                                                                        <?php
                                                                                        foreach ($todo_cats as $todo_cat) {
                                                                                            if ($todo_cat_id == $todo_cat->term_id) {
                                                                                                echo esc_html($todo_cat->name);
                                                                                            }
                                                                                        }
                                                                                        ?>
                                                                                    </span>
                                                                                </li>
                                                                                <li class="bptodo-task">
                                                                                    <?php echo esc_html($todo_title); ?>
                                                                                    <?php if ($is_readonly) { ?>
                                                                                        <span class="bptodo-readonly-badge" title="<?php esc_attr_e('This task is not assigned to you', 'wb-todo'); ?>">
                                                                                            <span class="dashicons dashicons-lock"></span> <?php esc_html_e('Read-Only', 'wb-todo'); ?>
                                                                                        </span>
                                                                                    <?php } ?>
                                                                                </li>
                                                                                <li class="bp-to-do-actions">
                                                                                    <ul class="bp-to-do-actions-list">
                                                                                        <?php  /* translators: %s */ ?>
                                                                                        <li><a href="<?php echo esc_url($todo_view_url); ?>" title="<?php echo sprintf(esc_html__('View: %s', 'wb-todo'), esc_attr($todo_title)); ?>" target="_blank"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/eye.svg" /></a></li>
                                                                                        <?php if ($can_modify && !$is_readonly) { ?>
                                                                                            <?php  /* translators: %s */ ?>
                                                                                            <li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr($tid); ?>" title="<?php echo sprintf(esc_html__('Remove: %s', 'wb-todo'), esc_attr($todo_title)); ?>"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/trash-alt.svg" /></a></li>
                                                                                            <?php  /* translators: %s */ ?>
                                                                                            <li><button data-tid="<?php echo esc_attr($tid); ?>" class="trigger" data-modal-trigger="trigger" data-type='group' title="<?php echo sprintf(esc_html__('Edit: %s', 'wb-todo'), esc_attr($todo_title)); ?>"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/edit.svg" /></button></li>
                                                                                        <?php } ?>
                                                                                    </ul>
                                                                                </li>
                                                                            </ul>
                                                                        <?php } ?>
                                                                    <?php } ?>
                                                                    <!-- TASKS FOR FUTURE -->
                                                                    <?php if (!empty($todo_list['future'])) { ?>
                                                                        <?php foreach ($todo_list['future'] as $tid) { ?>
                                                                            <?php
                                                                            // Admin non-members have read-only access to all tasks
                                                                            // Members (including admins who are members) can edit their assigned tasks
                                                                            $is_readonly = $is_admin_non_member;
                                                                            $todo          = get_post($tid);
                                                                            $todo_title    = $todo->post_title;
                                                                            $todo_edit_url = $admin_link . '/add?args=' . $tid;
                                                                            $todo_view_url = get_permalink($tid);
                                                                            $todo_status   = get_post_meta($todo->ID, 'todo_status', true);
                                                                            $todo_priority = get_post_meta($todo->ID, 'todo_priority', true);
                                                                            $due_date_str  = $due_date_td_class = '';
                                                                            $curr_date     = date_create(gmdate('Y-m-d'));
                                                                            $due_date      = date_create(get_post_meta($todo->ID, 'todo_due_date', true));
                                                                            $diff          = date_diff($curr_date, $due_date);
                                                                            $diff_days     = (int) $diff->format('%R%a');
                                                                            $priority_class = '';
                                                                            $todo_cats = get_terms(array(
                                                                                'taxonomy'   => 'todo_category',
                                                                                'orderby'    => 'name',
                                                                                'hide_empty' => false,
                                                                            ));

                                                                            $todo_cat    = wp_get_object_terms($tid, 'todo_category');
                                                                            $todo_cat_id = !empty($todo_cat) && is_array($todo_cat) ? $todo_cat[0]->term_id : 0;
                                                                            $disabled = '';
                                                                            if ($diff_days < 0) {
                                                                                $due_date_str      = esc_html__('Expired!', 'wb-todo');
                                                                                $due_date_td_class = 'bptodo-expired';
                                                                                $disabled = 'disabled';
                                                                            } elseif (0 === $diff_days) {
                                                                                $due_date_str      = esc_html__('Today is the last day to complete. Hurry Up!', 'wb-todo');
                                                                                $due_date_td_class = 'bptodo-expires-today';
                                                                            } else {
                                                                                $day_string = abs($diff_days) === 1 ? __('day', 'wb-todo') : __('days', 'wb-todo');
                                                                                 /* translators: %1$d */
                                                                                $due_date_str = sprintf(esc_html__('%1$d %2$s left', 'wb-todo'), abs($diff_days), $day_string);
                                                                            }

                                                                            $bptodo_row_complete = '';
                                                                            if ('complete' === $todo_status) {
                                                                                $due_date_str      = esc_html__('Completed', 'wb-todo');
                                                                                $due_date_td_class = '';
                                                                                $bptodo_row_complete = 'completed';
                                                                            }

                                                                            if (!empty($todo_priority)) {
                                                                                if ('critical' === $todo_priority) {
                                                                                    $priority_class = 'bptodo-priority-critical';
                                                                                    $priority_text  = esc_html__('Critical', 'wb-todo');
                                                                                } elseif ('high' === $todo_priority) {
                                                                                    $priority_class = 'bptodo-priority-high';
                                                                                    $priority_text  = esc_html__('High', 'wb-todo');
                                                                                } else {
                                                                                    $priority_class = 'bptodo-priority-normal';
                                                                                    $priority_text  = esc_html__('Normal', 'wb-todo');
                                                                                }
                                                                            }
                                                                            ?>
                                                                            <ul id="bptodo-row-<?php echo esc_attr($tid); ?>" class="todo-header-list-row <?php echo esc_attr($bptodo_row_complete . ' ' . $due_date_td_class); ?>">
                                                                                <?php
                                                                                $checked = '';
                                                                                $checked_class = 'todo-uncomplete';
                                                                                if ('complete' === $todo_status) {
                                                                                    $checked = 'checked';
                                                                                    $checked_class = 'todo-complete';
                                                                                }
                                                                                ?>
                                                                                 <?php  /* translators: %s */ ?>
                                                                                <li id="bptodo-complete-li-<?php echo esc_attr($tid); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr($checked_class); ?> <?php echo $is_readonly ? 'bptodo-readonly' : ''; ?>" data-tid="<?php echo esc_attr($tid); ?>" title="<?php echo sprintf(esc_html__('Complete: %s', 'wb-todo'), esc_attr($todo_title)); ?>">
                                                                                    <label><input type="checkbox" class="option-input checkbox" <?php echo $checked ? 'checked="checked"' : ''; ?> <?php echo $disabled ? 'disabled="disabled"' : ''; ?> <?php echo $is_readonly ? 'disabled="disabled"' : ''; ?> /></label>
                                                                                </li>
                                                                                <li class="bptodo-priority"><span class="<?php echo esc_attr($priority_class); ?>"><?php echo esc_html($priority_text); ?></span></li>
                                                                                <li class="bptodo-date"><span>Due Date</span><?php echo esc_html($due_date_str); ?></li>
                                                                                <li class="bptodo-cat">
                                                                                    <span class="bptodo-cat-mobile-title">Category</span>
                                                                                    <span class="bptodo-cat-text">
                                                                                        <?php
                                                                                        foreach ($todo_cats as $todo_cat) {
                                                                                            if ($todo_cat_id == $todo_cat->term_id) {
                                                                                                echo esc_html($todo_cat->name);
                                                                                            }
                                                                                        }
                                                                                        ?>
                                                                                    </span>
                                                                                </li>
                                                                                <li class="bptodo-task">
                                                                                    <?php echo esc_html($todo_title); ?>
                                                                                    <?php if ($is_readonly) { ?>
                                                                                        <span class="bptodo-readonly-badge" title="<?php esc_attr_e('This task is not assigned to you', 'wb-todo'); ?>">
                                                                                            <span class="dashicons dashicons-lock"></span> <?php esc_html_e('Read-Only', 'wb-todo'); ?>
                                                                                        </span>
                                                                                    <?php } ?>
                                                                                </li>
                                                                                <li class="bp-to-do-actions">
                                                                                    <ul class="bp-to-do-actions-list">
                                                                                        <?php  /* translators: %s */ ?>
                                                                                        <li><a href="<?php echo esc_url($todo_view_url); ?>" title="<?php echo sprintf(esc_html__('View: %s', 'wb-todo'), esc_attr($todo_title)); ?>" target="_blank"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/eye.svg" /></a></li>
                                                                                        <?php if ($can_modify && !$is_readonly) { ?>
                                                                                            <?php  /* translators: %s */ ?>
                                                                                            <li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr($tid); ?>" title="<?php echo sprintf(esc_html__('Remove: %s', 'wb-todo'), esc_attr($todo_title)); ?>"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/trash-alt.svg" /></a></li>
                                                                                            <?php  /* translators: %s */ ?>
                                                                                            <li><button data-tid="<?php echo esc_attr($tid); ?>" class="trigger" data-modal-trigger="trigger" data-type='group' title="<?php echo sprintf(esc_html__('Edit: %s', 'wb-todo'), esc_attr($todo_title)); ?>"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/edit.svg" /></button></li>
                                                                                        <?php } ?>
                                                                                    </ul>
                                                                                </li>
                                                                            </ul>
                                                                        <?php } ?>
                                                                    <?php } ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="bptodo-completed">
                                                    <div id="bptodo-all">
                                                        <div class="todo-panel">
                                                            <div class="todo-detail">
                                                                <div class="bp-todo-reminder">
                                                                    <div class="todo-header">
                                                                        <ul class="todo-header-head">
                                                                            <li class="bptodo-check"></li>
                                                                            <li class="bptodo-priority"><?php esc_html_e('Priority', 'wb-todo'); ?></li>
                                                                            <li class="bptodo-date"><?php esc_html_e('Due Date', 'wb-todo'); ?></li>
                                                                            <li class="bptodo-cat"><?php esc_html_e('Category', 'wb-todo'); ?></li>
                                                                            <li class="bptodo-task"><?php echo esc_html( $profile_menu_label ); ?></li>
                                                                            <?php if ($can_modify) { ?>
                                                                                <li class="bptodo-actions"><?php esc_html_e('Actions', 'wb-todo'); ?></li>
                                                                            <?php  } ?>
                                                                        </ul>
                                                                    </div>
                                                                    <div class="todo-header-list">
                                                                        <?php if (!empty($completed_todo_ids)) { ?>
                                                                            <?php
                                                                            foreach ($completed_todo_ids as $tid) {
                                                                                $todo          = get_post($tid);
                                                                                $todo_title    = $todo->post_title;
                                                                                $todo_edit_url = $admin_link . '/add?args=' . $tid;
                                                                                $todo_view_url = get_permalink($tid);

                                                                                // Admin non-members have read-only access to all tasks
                                                                                // Members (including admins who are members) can edit their assigned tasks
                                                                                $is_readonly = $is_admin_non_member;
                                                                                $todo_status   = get_post_meta($todo->ID, 'todo_status', true);
                                                                                $todo_priority = get_post_meta($todo->ID, 'todo_priority', true);
                                                                                $due_date_str  = $due_date_td_class = '';
                                                                                $curr_date     = date_create(gmdate('Y-m-d'));
                                                                                $due_date      = date_create(get_post_meta($todo->ID, 'todo_due_date', true));
                                                                                $diff          = date_diff($curr_date, $due_date);
                                                                                $diff_days     = (int) $diff->format('%R%a');
                                                                                $priority_class = '';
                                                                                $todo_cats = get_terms(array(
                                                                                    'taxonomy'   => 'todo_category',
                                                                                    'orderby'    => 'name',
                                                                                    'hide_empty' => false,
                                                                                ));

                                                                                $todo_cat    = wp_get_object_terms($tid, 'todo_category');
                                                                                $todo_cat_id = !empty($todo_cat) && is_array($todo_cat) ? $todo_cat[0]->term_id : 0;
                                                                                $disabled = '';
                                                                                if ($diff_days < 0) {
                                                                                    $due_date_str      = esc_html__('Expired!', 'wb-todo');
                                                                                    $due_date_td_class = 'bptodo-expired';
                                                                                    $disabled = 'disabled';
                                                                                } elseif (0 === $diff_days) {
                                                                                    $due_date_str      = esc_html__('Today is the last day to complete. Hurry Up!', 'wb-todo');
                                                                                    $due_date_td_class = 'bptodo-expires-today';
                                                                                } else {
                                                                                    $day_string = abs($diff_days) === 1 ? __('day', 'wb-todo') : __('days', 'wb-todo');
                                                                                     /* translators: %s */
                                                                                    $due_date_str = sprintf(esc_html__('%1$d %2$s left', 'wb-todo'), abs($diff_days), $day_string);
                                                                                }

                                                                                $bptodo_row_complete = '';
                                                                                if ('complete' === $todo_status) {
                                                                                    $due_date_str      = esc_html__('Completed', 'wb-todo');
                                                                                    $due_date_td_class = '';
                                                                                    $bptodo_row_complete = 'completed';
                                                                                }

                                                                                if (!empty($todo_priority)) {
                                                                                    if ('critical' === $todo_priority) {
                                                                                        $priority_class = 'bptodo-priority-critical';
                                                                                        $priority_text  = esc_html__('Critical', 'wb-todo');
                                                                                    } elseif ('high' === $todo_priority) {
                                                                                        $priority_class = 'bptodo-priority-high';
                                                                                        $priority_text  = esc_html__('High', 'wb-todo');
                                                                                    } else {
                                                                                        $priority_class = 'bptodo-priority-normal';
                                                                                        $priority_text  = esc_html__('Normal', 'wb-todo');
                                                                                    }
                                                                                }
                                                                                ?>
                                                                                <ul id="bptodo-row-<?php echo esc_attr($tid); ?>" class="todo-header-list-row <?php echo esc_attr($bptodo_row_complete . ' ' . $due_date_td_class); ?>">
                                                                                    <?php
                                                                                    $checked = '';
                                                                                    $checked_class = 'todo-uncomplete';
                                                                                    if ('complete' === $todo_status) {
                                                                                        $checked = 'checked';
                                                                                        $checked_class = 'todo-complete';
                                                                                    }
                                                                                    ?>
                                                                                     <?php  /* translators: %s */ ?>
                                                                                    <li id="bptodo-complete-li-<?php echo esc_attr($tid); ?>" class="bptodo-complete-todo bptodo-check <?php echo esc_attr($checked_class); ?> <?php echo $is_readonly ? 'bptodo-readonly' : ''; ?>" data-tid="<?php echo esc_attr($tid); ?>" title="<?php echo sprintf(esc_html__('Complete: %s', 'wb-todo'), esc_attr($todo_title)); ?>">
                                                                                        <label><input type="checkbox" class="option-input checkbox" <?php echo $checked ? 'checked="checked"' : ''; ?> <?php echo $disabled ? 'disabled="disabled"' : ''; ?> <?php echo $is_readonly ? 'disabled="disabled"' : ''; ?> /></label>
                                                                                    </li>
                                                                                    <li class="bptodo-priority"><span class="<?php echo esc_attr($priority_class); ?>"><?php echo esc_html($priority_text); ?></span></li>
                                                                                    <li class="bptodo-date"><span>Due Date</span><?php echo esc_html($due_date_str); ?></li>
                                                                                    <li class="bptodo-cat">
                                                                                        <span class="bptodo-cat-mobile-title">Category</span>
                                                                                        <span class="bptodo-cat-text">
                                                                                            <?php
                                                                                            foreach ($todo_cats as $todo_cat) {
                                                                                                if ($todo_cat_id == $todo_cat->term_id) {
                                                                                                    echo esc_html($todo_cat->name);
                                                                                                }
                                                                                            }
                                                                                            ?>
                                                                                        </span>
                                                                                    </li>
                                                                                    <li class="bptodo-task"><?php echo esc_html($todo_title); ?></li>
                                                                                    <li class="bp-to-do-actions">
                                                                                        <ul class="bp-to-do-actions-list">
                                                                                            <?php  /* translators: %s */ ?>
                                                                                            <li><a href="<?php echo esc_url($todo_view_url); ?>" title="<?php echo sprintf(esc_html__('View: %s', 'wb-todo'), esc_attr($todo_title)); ?>" target="_blank"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/eye.svg" /></a></li>
                                                                                            <?php if ($can_modify) { ?>
                                                                                                <?php  /* translators: %s */ ?>
                                                                                                <li><a href="javascript:void(0);" class="bptodo-remove-todo" data-tid="<?php echo esc_attr($tid); ?>" title="<?php echo sprintf(esc_html__('Remove: %s', 'wb-todo'), esc_attr($todo_title)); ?>"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/trash-alt.svg" /></a></li>
                                                                                                <?php  /* translators: %s */ ?>
                                                                                                <li><button data-tid="<?php echo esc_attr($tid); ?>" class="trigger" data-modal-trigger="trigger" data-type='group' title="<?php echo sprintf(esc_html__('Edit: %s', 'wb-todo'), esc_attr($todo_title)); ?>"><img src="<?php echo esc_attr(BPTODO_PLUGIN_URL); ?>assets/css/images/edit.svg" /></button></li>
                                                                                            <?php } ?>
                                                                                        </ul>
                                                                                    </li>
                                                                                </ul>
                                                                            <?php } ?>
                                                                        <?php } else { ?>
                                                                            <div class="info bp-feedback">
                                                                                <span class="bp-icon" aria-hidden="true"></span>
                                                                                <p class="text"><?php echo esc_html__('Completed Todo Not Found', 'wb-todo'); ?></p>
                                                                            </div>
                                                                        <?php } ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php do_action('bptodo_add_extra_tab_content_after_defaults', $profile_menu_label); ?>
                                    </div>
                            </section>
                        <?php
                    }
                        ?>
