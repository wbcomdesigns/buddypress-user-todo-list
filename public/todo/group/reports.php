<?php
$args = array(
    'post_type'      => 'bp-todo',
    'author'         => get_current_user_id(),
    'post_status'    => 'publish',
    'posts_per_page' => -1,
);
$todo_query = new WP_Query($args);

// Initialize counters
$total_count = 0;
$complete_count = 0;
?>
<table class="bp-todo-report">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Todo Name', 'wb-todo' ); ?></th>
            <th><?php esc_html_e( 'Status', 'wb-todo' ); ?></th>
            <th><?php esc_html_e( 'Time', 'wb-todo' ); ?></th>
        </tr>
    </thead>
    <tbody>
<?php
if ($todo_query->have_posts()) {
    while ($todo_query->have_posts()) {
        $todo_query->the_post();
        $total_count++;

        // Get the todo status and complete time
        $todo_status = get_post_meta(get_the_ID(), 'todo_status', true);
        $complete_time = get_post_meta(get_the_ID(), 'todo_complete_time', true);

        // Convert the complete time to a human-readable format
        if ($todo_status === 'complete' && $complete_time) {
            $time_ago = human_time_diff($complete_time, current_time('timestamp')) . ' ago';
            $complete_count++;
        } else {
            $time_ago = '-'; // No time for incomplete tasks
        }
        ?>
        <tr>
            <td><?php echo esc_html( get_the_title() ); ?></td>
            <td><?php echo esc_html( ucfirst($todo_status) ); ?></td>
            <td><?php echo esc_html( $time_ago ); ?></td>
        </tr>
        <?php
    }
}

// Reset post data to avoid conflicts
wp_reset_postdata();
?>
</tbody>
</table>
<?php

// Display the counts using sprintf
echo '<div class="bptodo-all-todo">';
echo sprintf( esc_html__( 'Total Todo: %d', 'wb-todo' ) ,  esc_html( $total_count ) );
echo '</div>';
echo '<div class="bptodo-complete-todo">';
echo sprintf( esc_html__( 'Complete Todo: %d', 'wb-todo' ) , esc_html( $complete_count ) );
echo '</div>';
?>
