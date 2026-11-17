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
	 * Upper bound on rows, so a large "number" setting cannot load an unbounded list.
	 */
	const MAX_ITEMS = 20;

	/**
	 * Constructor method.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {

		// Setup widget name & description.
		$name        = _x( 'Member Todo', 'widget name', 'wb-todo' );
		$description = __( 'The logged-in member\'s own todos. Hidden from visitors who are not logged in.', 'wb-todo' );

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
	 * The site owner's plural "Todo" label (e.g. Tasks), as the rest of the plugin uses it.
	 *
	 * @return string
	 */
	protected static function plural_label() {
		global $bptodo;
		return ( is_object( $bptodo ) && ! empty( $bptodo->profile_menu_label_plural ) ) ? $bptodo->profile_menu_label_plural : __( 'Todos', 'wb-todo' );
	}

	/**
	 * Title used until the site owner saves one.
	 *
	 * @return string
	 */
	protected static function default_title() {
		/* translators: %s: plural todo label. */
		return sprintf( __( 'My %s', 'wb-todo' ), self::plural_label() );
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
			return; // Todos are private: a visitor has no list of their own to show.
		}

		wp_enqueue_style( 'bptodo-widget' );

		$plural = self::plural_label();
		$title  = isset( $instance['title'] ) ? $instance['title'] : self::default_title();
		$title  = apply_filters( 'widget_title', $title, $instance, $this->id_base );
		$title  = ( '' !== $title && ! empty( $instance['link_title'] ) && function_exists( 'bptodo_get_standalone_todos_url' ) ) ? '<a href="' . esc_url( bptodo_get_standalone_todos_url() ) . '">' . $title . '</a>' : $title;
		$number = ( ! empty( $instance['number'] ) ) ? min( absint( $instance['number'] ), self::MAX_ITEMS ) : 5;
		// The WordPress current user owns the list: bp_loggedin_user_id() can still be 0 here (e.g. widget preview over REST).
		$user_id = get_current_user_id();

		$r = new WP_Query(
			apply_filters(
				'bptodo_widget_args',
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

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( '' !== $title ) {
			echo $args['before_title'] . $title . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( ! $r->have_posts() ) {
			/* translators: %s: plural todo label as the site owner set it (e.g. Tasks). */
			echo '<p class="bptodo-widget-empty">' . esc_html( sprintf( __( 'You have no %s yet.', 'wb-todo' ), $plural ) ) . '</p>';
			echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}
		?>
		<ul class="bptodo-widget-list">
			<?php
			foreach ( $r->posts as $recent_todo ) :
				$todo     = new Bptodo_Todo( $recent_todo );
				$priority = $todo->priority();
				$due      = $todo->due_human();
				$name     = get_the_title( $recent_todo );
				?>
			<li class="bptodo-widget-item">
				<a class="bptodo-widget-link" href="<?php echo esc_url( bptodo_get_todo_view_url( $recent_todo->ID ) ); ?>">
					<span class="bptodo-widget-title"><?php echo esc_html( '' !== $name ? $name : __( '(no title)', 'wb-todo' ) ); ?></span>
					<span class="bptodo-widget-meta">
						<span class="bptodo-widget-priority bptodo-priority-<?php echo esc_attr( $priority ); ?>"><?php echo esc_html( Bptodo_Todo::priority_label( $priority ) ); ?></span>
						<?php if ( '' !== $due['text'] ) : ?>
							<span class="bptodo-widget-due"><?php echo esc_html( $due['text'] ); ?></span>
						<?php endif; ?>
					</span>
				</a>
			</li>
			<?php endforeach; ?>
		</ul>
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

		$instance['title']      = wp_strip_all_tags( isset( $new_instance['title'] ) ? $new_instance['title'] : '' );
		$instance['number']     = isset( $new_instance['number'] ) ? min( absint( $new_instance['number'] ), self::MAX_ITEMS ) : 5;
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

		$title      = isset( $instance['title'] ) ? wp_strip_all_tags( $instance['title'] ) : self::default_title();
		$number     = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$link_title = isset( $instance['link_title'] ) ? (bool) $instance['link_title'] : '';
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'wb-todo' ); ?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'link_title' ) ); ?>">
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'link_title' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'link_title' ) ); ?>" value="1" <?php checked( $link_title ); ?> />
				<?php esc_html_e( 'Link widget title to archive page of Todo', 'wb-todo' ); ?>
			</label>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>">
				<?php esc_html_e( 'Number of todos to show:', 'wb-todo' ); ?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'number' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'number' ) ); ?>" type="number" min="1" max="<?php echo esc_attr( self::MAX_ITEMS ); ?>" value="<?php echo esc_attr( $number ); ?>" />
			</label>
		</p>
		<?php
	}
}

add_action( 'widgets_init', 'bptodo_register_widgets' );

/**
 * Register todo widget
 */
function bptodo_register_widgets() {
	register_widget( 'BP_ToDo_Member_ToDo_Widget' );
}

add_action( 'wp_enqueue_scripts', 'bptodo_register_widget_style' );

/**
 * Register the widget stylesheet; enqueue it in the head when the widget is placed.
 *
 * The widget also enqueues it on render, for placements is_active_widget() cannot see.
 */
function bptodo_register_widget_style() {
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	if ( ! wp_style_is( 'bptodo-design-system', 'registered' ) ) {
		wp_register_style( 'bptodo-design-system', BPTODO_PLUGIN_URL . 'assets/css/bptodo-design-system' . $suffix . '.css', array(), bptodo_asset_ver( 'assets/css/bptodo-design-system' . $suffix . '.css' ) );
	}
	wp_register_style( 'bptodo-widget', BPTODO_PLUGIN_URL . 'assets/css/bptodo-widget' . $suffix . '.css', array( 'bptodo-design-system' ), bptodo_asset_ver( 'assets/css/bptodo-widget' . $suffix . '.css' ) );

	if ( is_user_logged_in() && is_active_widget( false, false, 'bp_todo_member_todo_widget', true ) ) {
		wp_enqueue_style( 'bptodo-widget' );
	}
}
