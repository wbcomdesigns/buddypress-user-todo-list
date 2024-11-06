<?php
$group_id = bp_get_current_group_id();
$args = array(
    'post_type'      => 'bp-todo',
    'post_status'    => 'publish',
    'author'         => get_current_user_id(),
    'meta_key'       => 'todo_group_id',
    'meta_value'     => $group_id,
    'paged'          => 1, // Use pagination to handle large numbers of posts.
    'orderby'        => 'date',
    'order'          => 'DESC',
);
$todo_query = get_posts($args);
?> 
<div class="todo-reports-wrapper">
    <div class="select-todo-option">
        <label><?php esc_html_e( 'Select Todo', 'wb-todo' ); ?>:</label>
        <select id="todo-select">
            <?php if($todo_query) { 
                foreach ( $todo_query as $todo ) : ?>
                <option value="<?php echo esc_attr( $todo->ID ); ?>">
                    <?php echo esc_html( get_the_title( $todo->ID ) ); ?>
                </option>
            <?php endforeach; } else{
                echo '<option value="">'. esc_html__( 'No Todos found', 'wb-todo' ). '</option>';  // Display message if no todos found.
            }?>
        </select>
    </div>
<table id="bp-todo-table" class="bp-todo-report" data-group="<?php echo esc_attr( bp_get_current_group_id() ); ?>">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Members', 'wb-todo' ); ?></th>
            <th><?php esc_html_e( 'Status', 'wb-todo' ); ?></th>
            <th><?php esc_html_e( 'Time', 'wb-todo' ); ?></th>
        </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
            <tr>
                <th><?php esc_html_e( 'Members', 'wb-todo' ); ?></th>
                <th><?php esc_html_e( 'Status', 'wb-todo' ); ?></th>
                <th><?php esc_html_e( 'Time', 'wb-todo' ); ?></th>
            </tr>
        </tfoot>
</table>
</div>