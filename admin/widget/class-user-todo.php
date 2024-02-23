<?php
/**
 * BuddyPress Member To Dos Widget.
 *
 * @package  bp-user-todo-list
 * @subpackage ToDosWidgets
 * @since 2.2.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Member To Dos Widget.
 *
 * @since 2.2.1
 */
class BP_ToDo_Member_ToDo_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {

		// Setup widget name & description.
		$name        = _x( 'Member To-Do', 'widget name', 'wb-todo' );
		$description = __( 'A dynamic list of ToDos of displayed member.', 'wb-todo' );

		// Call WP_Widget constructor.
		parent::__construct(
			false,
			$name,
			array(
				'description' => $description,
				'classname'   => 'widget_bp_todo_member_todo wb-todo widget',
			)
		);
	}

	/**
	 * Display the ToDo widget.
	 *
	 * @since 2.2.1
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	public function widget( $args, $instance ) {

		if ( ! is_user_logged_in() ) {
			return; // The todo is only shown for logged in users.
		}

		$title  = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$title  = $instance['link_title'] ? '<a href="' . get_post_type_archive_link( 'bp-todo' ) . '">' . $title . '</a>' : $title;
		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number ) {
			$number = 5;
		}
		$user_id = bp_loggedin_user_id();

		$r = new WP_Query(
			apply_filters(
				'widget_bp_todo_args',
				array(
					'post_type'           => 'bp-todo',
					'posts_per_page'      => $number,
					'no_found_rows'       => true,
					'post_status'         => 'publish',
					'ignore_sticky_posts' => true,
					'author'              => $user_id,
				),
				$instance
			)
		);

		if ( ! $r->have_posts() ) {
			return;
		}
		// Output before widget HTMl, title (and maybe content before & after it).
		echo $args['before_widget'] . $args['before_title'] . $title . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<div class="widget-bptodo-list-row">
			<ul>
			<?php foreach ( $r->posts as $recent_todo ) : ?>
				<?php
				$post_title   = get_the_title( $recent_todo->ID );
				$todo_priority = get_post_meta( $recent_todo->ID, 'todo_priority', true );
				$todo_due_date = get_post_meta( $recent_todo->ID, 'todo_due_date', true );
				if ( ! empty( $todo_priority ) ) {
					if ( 'critical' === $todo_priority ) {
						$priority_class = 'bptodo-priority-critical';
						$priority_text  = esc_html__( 'Critical', 'wb-todo' );
					} elseif ( 'high' === $todo_priority ) {
						$priority_class = 'bptodo-priority-high';
						$priority_text  = esc_html__( 'High', 'wb-todo' );
					} else {
						$priority_class = 'bptodo-priority-normal';
						$priority_text  = esc_html__( 'Normal', 'wb-todo' );
					}
				}
				$title        = ( ! empty( $post_title ) ) ? $post_title : __( '(no title)', 'wb-todo' );
				$aria_current = '';

				if ( get_queried_object_id() === $recent_todo->ID ) {
					$aria_current = ' aria-current="page"';
				}
				?>
			<li>
				<a href="<?php the_permalink( $recent_todo->ID ); ?>"<?php echo esc_attr( $aria_current ); ?>><?php echo esc_html( $title ); ?></a>
				<?php
					$show_date = '';
				if ( $show_date ) :
					?>
				<?php endif; ?>
				<div class="widget-bptodo-list-meta">
					<div class="bptodo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></div>
					<span class="post-date"><?php echo esc_html( $todo_due_date ); ?></span>
				</div>
			</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Update the Members widget options.
	 *
	 * @since 1.0.3
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']      = wp_strip_all_tags( $new_instance['title'] );
		$instance['number']     = (int) $new_instance['number'];
		$instance['link_title'] = ! empty( $new_instance['link_title'] );

		return $instance;
	}

	/**
	 * Output the Members widget options form.
	 *
	 * @since 1.0.3
	 *
	 * @param array $instance Widget instance settings.
	 * @return void
	 */
	public function form( $instance ) {

		$title      = isset( $instance['title'] ) ? wp_strip_all_tags( $instance['title'] ) : '';
		$number     = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$link_title = isset( $instance['link_title'] ) ? (bool) $instance['link_title'] : '';
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'wb-todo' ); ?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" style="width: 100%" />
			</label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'link_title' ) ); ?>">
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'link_title' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'link_title' ) ); ?>" value="1" <?php checked( $link_title ); ?> />
				<?php esc_html_e( 'Link widget title to archive page of To-Do', 'wb-todo' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>">
				<?php esc_html_e( 'Max members to show:', 'wb-todo' ); ?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" min="1" value="<?php echo esc_attr( $number ); ?>" style="width: 30%" />
			</label>
		</p>
		<?php
	}
}

add_action( 'widgets_init', 'wpdocs_register_widgets' );

/**
 * Register todo widget
 */
function wpdocs_register_widgets() {
	register_widget( 'BP_ToDo_Member_ToDo_Widget' );
}
