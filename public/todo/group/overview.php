<?php
/**
 * Template for Group Overview - Shows all tasks with completion stats
 */

// Ensure BuddyPress and Groups component are active
if ( ! function_exists( 'bp_get_current_group_id' ) || ! function_exists( 'groups_is_user_admin' ) ) {
	return;
}

// Get current group details
$group_id = bp_get_current_group_id();
$group = groups_get_group($group_id);

// Check if user is logged in
if ( ! is_user_logged_in() ) {
	bp_core_add_message( __( 'You must be logged in to view group todos.', 'wb-todo' ), 'error' );
	bp_core_redirect( bptodo_get_group_url() );
	exit;
}

// Check if user is a group admin or moderator (or site admin)
$current_user_id = get_current_user_id();
$is_site_admin   = bptodo_is_site_admin();
$is_group_admin  = groups_is_user_admin( $current_user_id, $group_id );
$is_group_mod    = groups_is_user_mod( $current_user_id, $group_id );

if ( ! $is_group_admin && ! $is_group_mod && ! $is_site_admin ) {
	bp_core_add_message( __( 'You must be a group administrator or moderator to view group overview.', 'wb-todo' ), 'error' );
	bp_core_redirect( bptodo_get_group_url() );
	exit;
}

// Check if user is admin or mod
$can_modify = bp_group_is_admin() || bp_group_is_mod();

// Get group permalink for admin links (use the configured, sanitized todo slug).
global $bptodo;
$admin_link = bptodo_get_group_url( $group ) . trailingslashit( $bptodo->profile_menu_slug ?? 'todo' );

// Pagination for members.
$members_per_page = apply_filters( 'bptodo_group_overview_members_per_page', 15, $group_id );
$current_page     = isset( $_GET['overview_page'] ) ? absint( $_GET['overview_page'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination arg.
$current_page     = max( 1, $current_page );

// Group-wide totals via ONE aggregate query (no row fetch).
// Every group todo (primary + associated) carries todo_group_id, so this
// matches the old "primary + its associated todos" total exactly.
$group_counts           = bptodo_count_todos( array( 'group_id' => $group_id ) );
$group_total_tasks      = $group_counts['total'];
$group_completed_tasks  = $group_counts['completed'];

$group_completion_percentage = $group_total_tasks > 0 ? round( ( $group_completed_tasks / $group_total_tasks ) * 100 ) : 0;

// Task selector: bounded page of PRIMARY todos only (was posts_per_page => -1).
// Meta is batch-primed by the query, sorting happens in SQL.
$selector_limit  = apply_filters( 'bptodo_group_overview_task_selector_limit', 100, $group_id );
$selector_result = bptodo_get_todos_page(
    array(
        'group_id'     => $group_id,
        'primary_only' => true,
        'page'         => 1,
        'per_page'     => $selector_limit,
    )
);
$selector_tasks  = $selector_result['todos'];

// Check if specific task is selected
$selected_task_id = isset($_GET['task_id']) ? absint($_GET['task_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only view arg.
$selected_task = null;

if ($selected_task_id > 0) {
    $selected_task = get_post($selected_task_id);
    // Validate it's a task from this group
    if ($selected_task && get_post_meta($selected_task_id, 'todo_group_id', true) != $group_id) {
        $selected_task = null;
        $selected_task_id = 0;
    }
}

if ( $selected_task_id > 0 && $selected_task ) {
    /*
     * Selected-task view: bounded by the assignees of ONE task.
     * Batch-prime the primary + associated posts (posts, meta) in two
     * queries instead of get_post()/get_post_meta() per row.
     */
    $associated_todos = get_post_meta( $selected_task->ID, 'botodo_associated_todo', true );
    if ( empty( $associated_todos ) || ! is_array( $associated_todos ) ) {
        $associated_todos = array();
    }

    $task_post_ids = array_merge( array( $selected_task->ID ), array_map( 'absint', $associated_todos ) );
    _prime_post_caches( $task_post_ids, false, true );

    // Resolve assignee posts and authors from the primed cache.
    $assignee_posts = array();
    foreach ( $task_post_ids as $task_post_id ) {
        $task_post = get_post( $task_post_id );
        if ( $task_post && 'bp-todo' === $task_post->post_type ) {
            $assignee_posts[] = $task_post;
        }
    }

    // Keep only assignees who are still confirmed, unbanned group members.
    $assignee_ids = array_map( 'absint', wp_list_pluck( $assignee_posts, 'post_author' ) );
    $member_ids   = bptodo_filter_group_member_ids( $group_id, $assignee_ids );
    cache_users( $member_ids );

    $member_stats = array();
    foreach ( $assignee_posts as $task_post ) {
        $member_id = (int) $task_post->post_author;
        if ( ! in_array( $member_id, $member_ids, true ) ) {
            continue;
        }

        $member_user  = get_userdata( $member_id );
        $is_completed = ( 'complete' === get_post_meta( $task_post->ID, 'todo_status', true ) );

        $member_stats[] = array(
            'user_id'               => $member_id,
            'name'                  => $member_user ? $member_user->display_name : '',
            'total_tasks'           => 1,
            'completed_tasks'       => $is_completed ? 1 : 0,
            'pending_tasks'         => $is_completed ? 0 : 1,
            'completion_percentage' => $is_completed ? 100 : 0,
            'status'                => $is_completed ? 'complete' : 'incomplete',
            'completed_date'        => $is_completed ? get_post_meta( $task_post->ID, 'todo_completed_date', true ) : '',
        );
    }

    // Sort by completion percentage (lowest first - those who need attention)
    usort($member_stats, function($a, $b) {
        return $a['completion_percentage'] - $b['completion_percentage'];
    });

    /**
     * Filters the member stats before pagination (selected-task view).
     *
     * @since 3.5.0
     *
     * @param array $member_stats     Array of member statistics.
     * @param int   $group_id         The group ID.
     * @param int   $selected_task_id The selected task ID (0 for all tasks).
     */
    $member_stats = apply_filters( 'bptodo_group_overview_member_stats', $member_stats, $group_id, $selected_task_id );

    $total_members_with_tasks = count($member_stats);
    $completed_members = count(array_filter($member_stats, function($m) { return $m['status'] === 'complete'; }));
    $pending_members = $total_members_with_tasks - $completed_members;

    $total_pages = (int) ceil($total_members_with_tasks / $members_per_page);
    $offset = ($current_page - 1) * $members_per_page;
    $paginated_members = array_slice($member_stats, $offset, $members_per_page);
} else {
    /*
     * Overall view: per-member stats aggregated, ranked and paginated in
     * SQL (was: load ALL group todos, loop ALL group members and issue
     * get_post_meta()/get_post() per associated todo).
     */
    $member_stats_result = bptodo_get_group_member_task_stats( $group_id, $members_per_page, $current_page );

    /**
     * Filters the member stats for the current page (overall view).
     *
     * Since 3.6.0 member stats are aggregated and paginated in SQL, so
     * this filter receives the CURRENT PAGE of rows rather than the full
     * member list.
     *
     * @since 3.5.0
     * @since 3.6.0 Receives the current page of rows only.
     *
     * @param array $member_stats     Array of member statistics (current page).
     * @param int   $group_id         The group ID.
     * @param int   $selected_task_id The selected task ID (0 for all tasks).
     */
    $paginated_members = apply_filters( 'bptodo_group_overview_member_stats', $member_stats_result['members'], $group_id, $selected_task_id );

    $total_members_with_tasks = $member_stats_result['total_members'];
    $completed_members        = $member_stats_result['completed'];
    $pending_members          = $total_members_with_tasks - $completed_members;

    $total_pages = (int) ceil( $total_members_with_tasks / $members_per_page );
    $offset      = ( $current_page - 1 ) * $members_per_page;
}

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
            <?php if (!empty($selector_tasks)) : ?>
                <?php foreach ($selector_tasks as $task) : ?>
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
