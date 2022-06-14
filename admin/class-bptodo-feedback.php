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
			$this->name = $args['name'];

			$this->date_option  = $this->slug . '_activation_date';
			$this->nobug_option = $this->slug . '_no_bug';

			if ( isset( $args['time_limit'] ) ) {
				$this->time_limit = $args['time_limit'];
			} else {
				$this->time_limit = WEEK_IN_SECONDS;
			}

			// Add actions.
			add_action( 'admin_init', array( $this, 'check_installation_date' ) );
			add_action( 'admin_init', array( $this, 'set_no_bug' ), 5 );
		}

		/**
		 * Seconds to words.
		 *
		 * @param string $seconds Seconds in time.
		 */
		public function seconds_to_words( $seconds ) {

			// Get the years.
			$years = ( intval( $seconds ) / YEAR_IN_SECONDS ) % 100;
			if ( $years > 1 ) {
				/* translators: Number of years */
				return sprintf( __( '%s years', 'wb-todo' ), $years );
			} elseif ( $years > 0 ) {
				return __( 'a year', 'wb-todo' );
			}

			// Get the weeks.
			$weeks = ( intval( $seconds ) / WEEK_IN_SECONDS ) % 52;
			if ( $weeks > 1 ) {
				/* translators: Number of weeks */
				return sprintf( __( '%s weeks', 'wb-todo' ), $weeks );
			} elseif ( $weeks > 0 ) {
				return __( 'a week', 'wb-todo' );
			}

			// Get the days.
			$days = ( intval( $seconds ) / DAY_IN_SECONDS ) % 7;
			if ( $days > 1 ) {
				/* translators: Number of days */
				return sprintf( __( '%s days', 'wb-todo' ), $days );
			} elseif ( $days > 0 ) {
				return __( 'a day', 'wb-todo' );
			}

			// Get the hours.
			$hours = ( intval( $seconds ) / HOUR_IN_SECONDS ) % 24;
			if ( $hours > 1 ) {
				/* translators: Number of hours */
				return sprintf( __( '%s hours', 'wb-todo' ), $hours );
			} elseif ( $hours > 0 ) {
				return __( 'an hour', 'wb-todo' );
			}

			// Get the minutes.
			$minutes = ( intval( $seconds ) / MINUTE_IN_SECONDS ) % 60;
			if ( $minutes > 1 ) {
				/* translators: Number of minutes */
				return sprintf( __( '%s minutes', 'wb-todo' ), $minutes );
			} elseif ( $minutes > 0 ) {
				return __( 'a minute', 'wb-todo' );
			}

			// Get the seconds.
			$seconds = intval( $seconds ) % 60;
			if ( $seconds > 1 ) {
				/* translators: Number of seconds */
				return sprintf( __( '%s seconds', 'wb-todo' ), $seconds );
			} elseif ( $seconds > 0 ) {
				return __( 'a second', 'wb-todo' );
			}
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
				$no_bug_url = wp_nonce_url( admin_url( '?' . $this->nobug_option . '=true' ), 'wb-todo-feedback-nounce' );
				$time       = $this->seconds_to_words( time() - get_site_option( $this->date_option ) );
				?>

<style>
.notice.wb-todo-notice {
	border-left-color: #008ec2 !important;
	padding: 20px;
}

.rtl .notice.wb-todo-notice {
	border-right-color: #008ec2 !important;
}

.notice.notice.wb-todo-notice .wb-todo-notice-inner {
	display: table;
	width: 100%;
}

.notice.wb-todo-notice .wb-todo-notice-inner .wb-todo-notice-icon,
.notice.wb-todo-notice .wb-todo-notice-inner .wb-todo-notice-content,
.notice.wb-todo-notice .wb-todo-notice-inner .wb-todo-install-now {
	display: table-cell;
	vertical-align: middle;
}

.notice.wb-todo-notice .wb-todo-notice-icon {
	color: #509ed2;
	font-size: 50px;
	width: 60px;
}

.notice.wb-todo-notice .wb-todo-notice-icon img {
	width: 64px;
}

.notice.wb-todo-notice .wb-todo-notice-content {
	padding: 0 40px 0 20px;
}

.notice.wb-todo-notice p {
	padding: 0;
	margin: 0;
}

.notice.wb-todo-notice h3 {
	margin: 0 0 5px;
}

.notice.wb-todo-notice .wb-todo-install-now {
	text-align: center;
}

.notice.wb-todo-notice .wb-todo-install-now .wb-todo-install-button {
	padding: 6px 50px;
	height: auto;
	line-height: 20px;
}

.notice.wb-todo-notice a.no-thanks {
	display: block;
	margin-top: 10px;
	color: #72777c;
	text-decoration: none;
}

.notice.wb-todo-notice a.no-thanks:hover {
	color: #444;
}

@media (max-width: 767px) {

	.notice.notice.wb-todo-notice .wb-todo-notice-inner {
		display: block;
	}

	.notice.wb-todo-notice {
		padding: 20px !important;
	}

	.notice.wb-todo-noticee .wb-todo-notice-inner {
		display: block;
	}

	.notice.wb-todo-notice .wb-todo-notice-inner .wb-todo-notice-content {
		display: block;
		padding: 0;
	}

	.notice.wb-todo-notice .wb-todo-notice-inner .wb-todo-notice-icon {
		display: none;
	}

	.notice.wb-todo-notice .wb-todo-notice-inner .wb-todo-install-now {
		margin-top: 20px;
		display: block;
		text-align: left;
	}

	.notice.wb-todo-notice .wb-todo-notice-inner .no-thanks {
		display: inline-block;
		margin-left: 15px;
	}
}
</style>
			<div class="notice updated wb-todo-notice">
				<div class="wb-todo-notice-inner">
					<div class="wb-todo-notice-icon">
						<img src="<?php echo esc_url( BPTODO_PLUGIN_URL . '/admin/wbcom/assets/imgs/bp-to-do.jpeg' ); ?>" alt="<?php echo esc_attr__( 'BuddyPress User To-Do List', 'wb-todo' ); ?>" />
					</div>
					<div class="wb-todo-notice-content">
						<h3><?php echo esc_html__( 'Are you enjoying BuddyPress User To-Do List?', 'wb-todo' ); ?></h3>
						<p>
							<?php /* translators: 1. Name */ ?>
							<?php printf( esc_html__( 'We hope you\'re enjoying %1$s! Could you please do us a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'wb-todo' ), esc_html( $this->name ) ); ?>
						</p>
					</div>
					<div class="wb-todo-install-now">
						<?php printf( '<a href="%1$s" class="button button-primary wb-todo-install-button" target="_blank">%2$s</a>', esc_url( 'https://wordpress.org/support/plugin/bp-user-to-do-list/reviews/#new-post' ), esc_html__( 'Leave a Review', 'wb-todo' ) ); ?>
						<a href="<?php echo esc_url( $no_bug_url ); ?>" class="no-thanks"><?php echo esc_html__( 'No thanks / I already have', 'wb-todo' ); ?></a>
					</div>
				</div>
			</div>
				<?php
			}
		}

		/**
		 * Set the plugin to no longer bug users if user asks not to be.
		 */
		public function set_no_bug() {

			// Bail out if not on correct page.
			if ( ! isset( $_GET['_wpnonce'] ) || ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wb-todo-feedback-nounce' ) || ! is_admin() || ! isset( $_GET[ $this->nobug_option ] ) || ! current_user_can( 'manage_options' ) ) ) {
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
		'name'       => __( 'BuddyPress User To-Do List', 'wb-todo' ),
		'time_limit' => WEEK_IN_SECONDS,
	)
);
