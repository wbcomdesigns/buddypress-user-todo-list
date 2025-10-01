<?php
/**
 * BuddyPress Email Integration for Todo Plugin
 *
 * @package bp-user-todo-list
 * @since 3.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class to handle BuddyPress email integration
 */
class Bptodo_Emails {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'bp_init', array( $this, 'register_email_types' ) );
	}

	/**
	 * Register custom BP email types
	 *
	 * @since 3.5.0
	 */
	public function register_email_types() {
		// Register the todo due date email type
		bp_register_email_type(
			'bptodo-due-date-reminder',
			array(
				/* translators: Email type description in admin */
				'description' => __( 'A user receives this email when their todo item is due today.', 'wb-todo' ),
			)
		);
	}

	/**
	 * Install BuddyPress email templates
	 *
	 * @since 3.5.0
	 */
	public static function install_emails() {
		// Check if email already exists
		$args = array(
			'post_type'      => bp_get_email_post_type(),
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'tax_query'      => array(
				array(
					'taxonomy' => bp_get_email_tax_type(),
					'field'    => 'slug',
					'terms'    => 'bptodo-due-date-reminder',
				),
			),
		);

		$email = get_posts( $args );

		// Email already exists, skip installation
		if ( ! empty( $email ) ) {
			return;
		}

		global $bptodo;
		if ( empty( $bptodo ) ) {
			$bptodo = new Bptodo_Globals();
		}
		$todo_label = ! empty( $bptodo->profile_menu_label ) ? $bptodo->profile_menu_label : 'Todo';

		// Create the email post
		$post_id = wp_insert_post(
			array(
				'post_title'   => sprintf(
					/* translators: %s: Todo label */
					__( '[{{{site.name}}}] %s Due Date Reminder', 'wb-todo' ),
					$todo_label
				),
				'post_content' => sprintf(
					/* translators: 1: Todo label, 2: Todo title */
					__( 'Hi {{{recipient.name}}},

This is a reminder that your %1$s "{{{bptodo.title}}}" is due today.

Please complete it as soon as possible to stay on track with your tasks.

Task details: {{{bptodo.url}}}

Thank you!', 'wb-todo' ),
					$todo_label
				),
				'post_excerpt' => sprintf(
					/* translators: 1: Site name, 2: Todo label, 3: Todo title */
					__( '{{{site.name}}}: %s "{{{bptodo.title}}}" Due Today', 'wb-todo' ),
					$todo_label
				),
				'post_status'  => 'publish',
				'post_type'    => bp_get_email_post_type(),
			)
		);

		// Failed to create post
		if ( ! $post_id ) {
			return;
		}

		// Add email type taxonomy term
		$tt_ids = wp_set_object_terms( $post_id, 'bptodo-due-date-reminder', bp_get_email_tax_type() );

		// Failed to associate with taxonomy
		if ( is_wp_error( $tt_ids ) ) {
			wp_delete_post( $post_id, true );
			return;
		}

		// Add email tokens
		$tokens = array(
			'bptodo.title' => __( 'The title of the todo item.', 'wb-todo' ),
			'bptodo.url'   => __( 'URL to view the todo item.', 'wb-todo' ),
		);

		update_post_meta( $post_id, 'tokens', $tokens );
	}

	/**
	 * Send todo due date email using BP Email
	 *
	 * @since 3.5.0
	 * @param int    $todo_id    The todo post ID
	 * @param int    $author_id  The todo author user ID
	 * @param string $mail_settings User's mail preference
	 * @return bool Whether the email was sent successfully
	 */
	public static function send_due_date_email( $todo_id, $author_id, $mail_settings ) {
		// Check global mail setting
		$global_mail_settings = get_option( 'user_todo_list_settings', array() );

		if ( empty( $global_mail_settings['send_mail'] ) || 'yes' !== $mail_settings ) {
			return false;
		}

		// Check if email already sent
		$email_sent = get_post_meta( $todo_id, 'todo_last_day_mail_sent', true );
		if ( 'yes' === $email_sent ) {
			return false;
		}

		// Get todo details
		$todo = get_post( $todo_id );
		if ( ! $todo ) {
			return false;
		}

		$todo_title = $todo->post_title;
		$todo_url   = get_permalink( $todo_id );

		// Prepare email tokens
		$tokens = array(
			'bptodo.title' => $todo_title,
			'bptodo.url'   => $todo_url,
		);

		// Send the email
		$sent = bp_send_email(
			'bptodo-due-date-reminder',
			$author_id,
			array(
				'tokens' => $tokens,
			)
		);

		// Mark as sent if successful
		if ( ! is_wp_error( $sent ) ) {
			update_post_meta( $todo_id, 'todo_last_day_mail_sent', 'yes' );
			return true;
		}

		return false;
	}
}

// Initialize the email integration
new Bptodo_Emails();
