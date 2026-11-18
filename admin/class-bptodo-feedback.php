<?php
/**
 * Plugin review class.
 * Prompts users to give a review of the plugin on WordPress.org after a period of usage.
 *
 * Heavily based on code by Rhys Wynne
 * https://winwar.co.uk/2014/10/ask-wordpress-plugin-reviews-week/
 *
 * @package Bp_Job_Manager
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BuddyPress_TO_DO_Feedback' ) ) :

	/**
	 * The feedback.
	 */
	class BuddyPress_TO_DO_Feedback {

		/**
		 * Slug.
		 *
		 * @var string $slug
		 */
		private $slug;

		/**
		 * Name.
		 *
		 * @var string $name
		 */
		private $name;

		/**
		 * Time limit.
		 *
		 * @var string $time_limit
		 */
		private $time_limit;

		/**
		 * No Bug Option.
		 *
		 * @var string $nobug_option
		 */
		public $nobug_option;

		/**
		 * Activation Date Option.
		 *
		 * @var string $date_option
		 */
		public $date_option;

		/**
		 * Class constructor.
		 *
		 * @param string $args Arguments.
		 */
		public function __construct( $args ) {
			$this->slug = $args['slug'];

			$this->date_option  = $this->slug . '_activation_date';
			$this->nobug_option = $this->slug . '_no_bug';

			$this->time_limit = isset( $args['time_limit'] ) ? $args['time_limit'] : WEEK_IN_SECONDS;

			// Add actions.
			add_action( 'admin_init', array( $this, 'check_installation_date' ) );
			add_action( 'admin_init', array( $this, 'set_no_bug' ), 5 );
		}

		/**
		 * Check date on admin initiation and add to admin notice if it was more than the time limit.
		 */
		public function check_installation_date() {
			if ( ! get_site_option( $this->nobug_option ) || false === get_site_option( $this->nobug_option ) ) {
				add_site_option( $this->date_option, time() );

				// Retrieve the activation date.
				$install_date = get_site_option( $this->date_option );

				// If difference between install date and now is greater than time limit, then display notice.
				if ( ( time() - $install_date ) > $this->time_limit ) {
					add_action( 'admin_notices', array( $this, 'display_admin_notice' ) );
				}
			}
		}

		/**
		 * Display the admin notice.
		 */
		public function display_admin_notice() {
			$screen = get_current_screen();

			if ( isset( $screen->base ) && 'plugins' === $screen->base ) {
				$no_bug_url = wp_nonce_url( admin_url( '?' . $this->nobug_option . '=true' ), 'wb-todo-feedback-nonce' );
				?>
				<div class="notice notice-info wb-todo-notice">
					<?php $this->name = 'Todo for BuddyPress & BuddyBoss'; ?>
					<h3><?php echo esc_html__( 'Are you enjoying Todo for BuddyPress & BuddyBoss?', 'bp-user-to-do-list' ); ?></h3>
					<p>
						<?php /* translators: 1. Name */ ?>
						<?php printf( esc_html__( 'We hope you\'re enjoying %1$s! Could you please do us a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'bp-user-to-do-list' ), esc_html( $this->name ) ); ?>
					</p>
					<p>
						<?php printf( '<a href="%1$s" class="button button-primary" target="_blank" rel="noopener noreferrer">%2$s</a>', esc_url( 'https://wordpress.org/support/plugin/bp-user-to-do-list/reviews/#new-post' ), esc_html__( 'Leave a Review', 'bp-user-to-do-list' ) ); ?>
						<a href="<?php echo esc_url( $no_bug_url ); ?>" class="button button-link"><?php echo esc_html__( 'No thanks / I already have', 'bp-user-to-do-list' ); ?></a>
					</p>
				</div>
				<?php
			}
		}

		/**
		 * Set the plugin to no longer bug users if user asks not to be.
		 */
		public function set_no_bug() {

			// Bail out if not on correct page.
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wb-todo-feedback-nonce' ) || ! is_admin() || ! isset( $_GET[ $this->nobug_option ] ) || ! current_user_can( 'manage_options' ) ) {
				return;
			}

			add_site_option( $this->nobug_option, true );
		}
	}
endif;

/*
* Instantiate the BuddyPress_TO_DO_Feedback class.
*/
new BuddyPress_TO_DO_Feedback(
	array(
		'slug'       => 'bp_to_do',
		'time_limit' => WEEK_IN_SECONDS,
	)
);
