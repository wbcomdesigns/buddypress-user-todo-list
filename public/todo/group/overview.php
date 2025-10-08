<?php
/**
 * Template for Group Overview - Shows all tasks with completion stats
 */

// Get current group details
$group_id = bp_get_current_group_id();
$group = groups_get_group($group_id);

// Check if user is admin or mod
$can_modify = bp_group_is_admin() || bp_group_is_mod();

// Get group permalink for admin links
$admin_link = bptodo_get_group_url( $group ) . 'todo/';

// Pagination
$tasks_per_page = 15; // Show 15 tasks per page
$current_page = isset($_GET['overview_page']) ? absint($_GET['overview_page']) : 1;
$current_page = max(1, $current_page); // Ensure at least page 1

// First, get ALL todos to calculate group-wide stats
$stats_args = array(
    'post_type'      => 'bp-todo',
    'post_status'    => 'publish',
    'posts_per_page' => -1, // Get all
    'meta_query'     => array(
        'relation' => 'AND',
        array(
            'key'     => 'todo_group_id',
            'value'   => $group_id,
            'compare' => '=',
            'type'    => 'NUMERIC',
        ),
    ),
);

$all_todos_for_stats = get_posts($stats_args);

// Filter to only include primary todos
$all_todos_for_stats = array_filter($all_todos_for_stats, function($todo) {
    $primary_id = get_post_meta($todo->ID, 'todo_primary_id', true);
    return !empty($primary_id) && $todo->ID == $primary_id;
});

$group_total_tasks = 0;
$group_completed_tasks = 0;

// Intelligent sorting: Priority (Critical > High > Normal) + Due Date (earliest first)
usort($all_todos_for_stats, function($a, $b) {
    $a_priority = get_post_meta($a->ID, 'todo_priority', true);
    $b_priority = get_post_meta($b->ID, 'todo_priority', true);
    $a_due_date = get_post_meta($a->ID, 'todo_due_date', true);
    $b_due_date = get_post_meta($b->ID, 'todo_due_date', true);

    // Priority levels
    $priority_order = array('critical' => 1, 'high' => 2, 'normal' => 3);
    $a_priority_val = isset($priority_order[$a_priority]) ? $priority_order[$a_priority] : 3;
    $b_priority_val = isset($priority_order[$b_priority]) ? $priority_order[$b_priority] : 3;

    if ($a_priority_val !== $b_priority_val) {
        return $a_priority_val - $b_priority_val;
    }

    // Then by due date
    if ($a_due_date && $b_due_date) {
        return strtotime($a_due_date) - strtotime($b_due_date);
    }
    return $a_due_date ? -1 : 1;
});

// Calculate group-wide statistics from ALL todos
foreach ($all_todos_for_stats as $todo) {
    $associated_todos = get_post_meta($todo->ID, 'botodo_associated_todo', true);
    if (empty($associated_todos)) {
        $associated_todos = array();
    }

    $task_total = 1 + count($associated_todos); // Primary + associated
    $task_completed = 0;

    // Check primary
    if (get_post_meta($todo->ID, 'todo_status', true) === 'complete') {
        $task_completed++;
    }

    // Check associated
    foreach ($associated_todos as $assoc_id) {
        if (get_post_meta($assoc_id, 'todo_status', true) === 'complete') {
            $task_completed++;
        }
    }

    $group_total_tasks += $task_total;
    $group_completed_tasks += $task_completed;
}

$group_completion_percentage = $group_total_tasks > 0 ? round(($group_completed_tasks / $group_total_tasks) * 100) : 0;

// Check if specific task is selected
$selected_task_id = isset($_GET['task_id']) ? absint($_GET['task_id']) : 0;
$selected_task = null;

if ($selected_task_id > 0) {
    $selected_task = get_post($selected_task_id);
    // Validate it's a task from this group
    if ($selected_task && get_post_meta($selected_task_id, 'todo_group_id', true) != $group_id) {
        $selected_task = null;
        $selected_task_id = 0;
    }
}

// Get all group members and their task completion status
$group_members = groups_get_group_members(array(
    'group_id' => $group_id,
    'exclude_admins_mods' => false,
));

$member_stats = array();

if (!empty($group_members['members'])) {
    foreach ($group_members['members'] as $member) {
        $member_id = $member->ID;
        $total_assigned = 0;
        $completed = 0;
        $completed_date = '';

        if ($selected_task_id > 0 && $selected_task) {
            // Check only the selected task for this member
            $todo = $selected_task;

            // Check if this is the primary todo (member is creator)
            if ($todo->post_author == $member_id) {
                $total_assigned = 1;
                if (get_post_meta($todo->ID, 'todo_status', true) === 'complete') {
                    $completed = 1;
                    $completed_date = get_post_meta($todo->ID, 'todo_completed_date', true);
                }
            } else {
                // Check associated todos
                $associated_todos = get_post_meta($todo->ID, 'botodo_associated_todo', true);
                if (!empty($associated_todos)) {
                    foreach ($associated_todos as $assoc_id) {
                        $assoc_post = get_post($assoc_id);
                        if ($assoc_post && $assoc_post->post_author == $member_id) {
                            $total_assigned = 1;
                            if (get_post_meta($assoc_id, 'todo_status', true) === 'complete') {
                                $completed = 1;
                                $completed_date = get_post_meta($assoc_id, 'todo_completed_date', true);
                            }
                        }
                    }
                }
            }
        } else {
            // Check all tasks for this member (overall view)
            foreach ($all_todos_for_stats as $todo) {
                // Check if this is the primary todo (member is creator)
                if ($todo->post_author == $member_id) {
                    $total_assigned++;
                    if (get_post_meta($todo->ID, 'todo_status', true) === 'complete') {
                        $completed++;
                    }
                } else {
                    // Check associated todos
                    $associated_todos = get_post_meta($todo->ID, 'botodo_associated_todo', true);
                    if (!empty($associated_todos)) {
                        foreach ($associated_todos as $assoc_id) {
                            $assoc_post = get_post($assoc_id);
                            if ($assoc_post && $assoc_post->post_author == $member_id) {
                                $total_assigned++;
                                if (get_post_meta($assoc_id, 'todo_status', true) === 'complete') {
                                    $completed++;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($total_assigned > 0) {
            $completion_percentage = round(($completed / $total_assigned) * 100);
            $member_stats[] = array(
                'user_id' => $member_id,
                'name' => $member->display_name,
                'total_tasks' => $total_assigned,
                'completed_tasks' => $completed,
                'pending_tasks' => $total_assigned - $completed,
                'completion_percentage' => $completion_percentage,
                'status' => ($completed === $total_assigned) ? 'complete' : 'incomplete',
                'completed_date' => $completed_date,
            );
        }
    }
}

// Sort by completion percentage (lowest first - those who need attention)
usort($member_stats, function($a, $b) {
    return $a['completion_percentage'] - $b['completion_percentage'];
});

/**
 * Filters the member stats before pagination.
 *
 * @since 3.5.0
 *
 * @param array $member_stats     Array of member statistics.
 * @param int   $group_id         The group ID.
 * @param int   $selected_task_id The selected task ID (0 for all tasks).
 */
$member_stats = apply_filters( 'bptodo_group_overview_member_stats', $member_stats, $group_id, $selected_task_id );

// Pagination for members
$members_per_page = apply_filters( 'bptodo_group_overview_members_per_page', 15, $group_id );
$current_page = isset($_GET['overview_page']) ? absint($_GET['overview_page']) : 1;
$current_page = max(1, $current_page);

$total_members_with_tasks = count($member_stats);
$completed_members = count(array_filter($member_stats, function($m) { return $m['status'] === 'complete'; }));
$pending_members = $total_members_with_tasks - $completed_members;

$total_pages = ceil($total_members_with_tasks / $members_per_page);
$offset = ($current_page - 1) * $members_per_page;
$paginated_members = array_slice($member_stats, $offset, $members_per_page);

$base_url = $admin_link . 'overview/';
?>

<div id="bptodo-group-overview-page" class="bptodo-group-report-wrapper">

    <!-- Task Selector -->
    <div class="bptodo-task-selector">
        <label for="task-selector"><?php esc_html_e('View Progress For:', 'wb-todo'); ?></label>
        <select id="task-selector" class="bptodo-task-dropdown">
            <option value="<?php echo esc_url($base_url); ?>" <?php selected($selected_task_id, 0); ?>>
                <?php esc_html_e('Overall Progress (All Tasks)', 'wb-todo'); ?>
            </option>
            <?php if (!empty($all_todos_for_stats)) : ?>
                <?php foreach ($all_todos_for_stats as $task) : ?>
                    <option value="<?php echo esc_url(add_query_arg('task_id', $task->ID, $base_url)); ?>" <?php selected($selected_task_id, $task->ID); ?>>
                        <?php echo esc_html($task->post_title); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <!-- Group Overview Header -->
    <div class="bptodo-report-header">
        <h3 class="bptodo-report-title">
            <?php
            if ($selected_task && $selected_task_id > 0) {
                printf(esc_html__('Task Progress: %s', 'wb-todo'), esc_html($selected_task->post_title));
            } else {
                esc_html_e('Group Task Progress', 'wb-todo');
            }
            ?>
        </h3>
    </div>

    <!-- Overall Completion Stats -->
    <div class="bptodo-report-overview">
        <div class="bptodo-completion-summary">
            <?php
            if ($selected_task_id > 0) {
                // Show stats for selected task
                $task_completion_percentage = $total_members_with_tasks > 0 ? round(($completed_members / $total_members_with_tasks) * 100) : 0;
                ?>
                <span class="bptodo-completion-percentage"><?php echo esc_html($task_completion_percentage); ?>%</span>
                <span class="bptodo-completion-details">
                    <?php echo esc_html($completed_members); ?>
                    <?php esc_html_e('of', 'wb-todo'); ?>
                    <?php echo esc_html($total_members_with_tasks); ?>
                    <?php esc_html_e('members completed', 'wb-todo'); ?>
                </span>
            <?php } else { ?>
                <span class="bptodo-completion-percentage"><?php echo esc_html($group_completion_percentage); ?>%</span>
                <span class="bptodo-completion-details">
                    <?php echo esc_html($group_completed_tasks); ?>
                    <?php esc_html_e('of', 'wb-todo'); ?>
                    <?php echo esc_html($group_total_tasks); ?>
                    <?php esc_html_e('tasks completed', 'wb-todo'); ?>
                </span>
            <?php } ?>
        </div>
        <div class="bptodo-progress-bar">
            <?php
            $progress_percentage = $selected_task_id > 0 ? $task_completion_percentage : $group_completion_percentage;
            ?>
            <div class="bptodo-progress-fill" style="width: <?php echo esc_attr($progress_percentage); ?>%;"></div>
        </div>
    </div>

    <!-- Member Progress List -->
    <div class="bptodo-member-progress-list">
        <div class="bptodo-member-list-header">
            <h4 class="bptodo-member-list-title"><?php esc_html_e('Member Status', 'wb-todo'); ?></h4>
            <div class="bptodo-member-filters">
                <button class="bptodo-filter-btn active" data-filter="all">
                    <?php esc_html_e('All', 'wb-todo'); ?>
                    <span class="bptodo-filter-count"><?php echo esc_html($total_members_with_tasks); ?></span>
                </button>
                <button class="bptodo-filter-btn" data-filter="completed">
                    <?php esc_html_e('Completed', 'wb-todo'); ?>
                    <span class="bptodo-filter-count"><?php echo esc_html($completed_members); ?></span>
                </button>
                <button class="bptodo-filter-btn" data-filter="pending">
                    <?php esc_html_e('Pending', 'wb-todo'); ?>
                    <span class="bptodo-filter-count"><?php echo esc_html($pending_members); ?></span>
                </button>
            </div>
        </div>

        <!-- Member Grid -->
        <div class="bptodo-member-grid">
            <?php if (!empty($paginated_members)) : ?>
                <?php foreach ($paginated_members as $member) : ?>
                    <?php
                    $status_class = $member['status'] === 'complete' ? 'status-complete' : 'status-incomplete';
                    ?>
                    <div class="bptodo-member-item <?php echo esc_attr($status_class); ?>">
                        <?php echo get_avatar($member['user_id'], 40, '', '', array('class' => 'bptodo-member-avatar')); ?>
                        <div class="bptodo-member-info">
                            <a href="<?php echo bptodo_get_member_url($member['user_id']); ?>" class="bptodo-member-name">
                                <?php echo esc_html(ucfirst($member['name'])); ?>
                            </a>
                            <span class="bptodo-member-status">
                                <?php if ($member['status'] === 'complete') : ?>
                                    <span class="bptodo-status-badge bptodo-status-done">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php esc_html_e($selected_task_id > 0 ? 'Completed' : 'All Complete', 'wb-todo'); ?>
                                    </span>
                                    <?php if ($selected_task_id > 0 && !empty($member['completed_date'])) : ?>
                                        <span class="bptodo-member-time">
                                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($member['completed_date']))); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span class="bptodo-status-badge bptodo-status-pending">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php esc_html_e($selected_task_id > 0 ? 'Pending' : 'Incomplete', 'wb-todo'); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($selected_task_id == 0) : ?>
                                    <span class="bptodo-member-time">
                                        <?php echo esc_html($member['completed_tasks']); ?> / <?php echo esc_html($member['total_tasks']); ?> <?php esc_html_e('tasks', 'wb-todo'); ?>
                                        (<?php echo esc_html($member['completion_percentage']); ?>%)
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="bptodo-no-tasks">
                    <p><?php esc_html_e('No members with assigned tasks found.', 'wb-todo'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1) : ?>
        <div class="bptodo-pagination">
            <div class="bptodo-pagination-info">
                <?php
                $start = $offset + 1;
                $end = min($offset + $members_per_page, $total_members_with_tasks);
                printf(
                    esc_html__('Showing %1$d-%2$d of %3$d members', 'wb-todo'),
                    $start,
                    $end,
                    $total_members_with_tasks
                );
                ?>
            </div>
            <div class="bptodo-pagination-links">
                <?php if ($current_page > 1) : ?>
                    <a href="<?php echo esc_url(add_query_arg('overview_page', $current_page - 1, $base_url)); ?>" class="bptodo-page-btn bptodo-prev-btn">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                        <?php esc_html_e('Previous', 'wb-todo'); ?>
                    </a>
                <?php endif; ?>

                <?php
                // Show page numbers
                $range = 2;
                $start_page = max(1, $current_page - $range);
                $end_page = min($total_pages, $current_page + $range);

                if ($start_page > 1) {
                    echo '<a href="' . esc_url(add_query_arg('overview_page', 1, $base_url)) . '" class="bptodo-page-num">1</a>';
                    if ($start_page > 2) {
                        echo '<span class="bptodo-page-dots">...</span>';
                    }
                }

                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active_class = ($i === $current_page) ? 'active' : '';
                    echo '<a href="' . esc_url(add_query_arg('overview_page', $i, $base_url)) . '" class="bptodo-page-num ' . esc_attr($active_class) . '">' . $i . '</a>';
                }

                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<span class="bptodo-page-dots">...</span>';
                    }
                    echo '<a href="' . esc_url(add_query_arg('overview_page', $total_pages, $base_url)) . '" class="bptodo-page-num">' . $total_pages . '</a>';
                }
                ?>

                <?php if ($current_page < $total_pages) : ?>
                    <a href="<?php echo esc_url(add_query_arg('overview_page', $current_page + 1, $base_url)); ?>" class="bptodo-page-btn bptodo-next-btn">
                        <?php esc_html_e('Next', 'wb-todo'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
