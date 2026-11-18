<?php
/**
 * Discover partial: ecosystem cross-promotion (read-only display view).
 *
 * Rendered inside page-layout.php. This is a pure presentation view - product
 * cards linking out to other free Wbcom Designs tools. No forms, no
 * settings, no options, no AJAX.
 *
 * @package bp-user-todo-list
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

$bptodo_ecosystem_img = BPTODO_PLUGIN_URL . 'assets/images/ecosystem/';

/*
 * Each product: brand mark (shipped in assets/images/ecosystem/), name, a
 * short admin-specific blurb (worded differently from readme.txt), and the
 * wbcomdesigns.com download URL.
 */
$bptodo_ecosystem = array(
	array(
		'name' => 'BuddyX',
		'logo' => 'buddyx.png',
		'icon' => 'admin-appearance',
		'desc' => __( 'A free, fast community theme for BuddyPress, BuddyBoss and PeepSo with a modern layout and dark mode.', 'bp-user-to-do-list' ),
		'url'  => 'https://wbcomdesigns.com/downloads/buddyx-theme/',
	),
	array(
		'name' => 'BuddyNext',
		'logo' => 'buddynext.svg',
		'icon' => 'groups',
		'desc' => __( 'The full community stack: activity feeds, member spaces, profiles, and private messaging on WordPress.', 'bp-user-to-do-list' ),
		'url'  => 'https://wbcomdesigns.com/downloads/buddynext/',
	),
	array(
		'name' => 'Jetonomy',
		'logo' => 'jetonomy.svg',
		'icon' => 'format-chat',
		'desc' => __( 'Self-moderating forums and Q&A boards that stay fast well beyond 100,000 topics.', 'bp-user-to-do-list' ),
		'url'  => 'https://wbcomdesigns.com/downloads/jetonomy/',
	),
	array(
		'name' => 'Mediaverse',
		'logo' => 'mediaverse.svg',
		'icon' => 'format-gallery',
		'desc' => __( 'A photo and video hub with albums, reactions, following, and private chat.', 'bp-user-to-do-list' ),
		'url'  => 'https://wbcomdesigns.com/downloads/mediaverse/',
	),
	array(
		'name' => 'Eventonomy',
		'logo' => 'eventonomy.svg',
		'icon' => 'calendar-alt',
		'desc' => __( 'Run community events with RSVPs, calendars, and front-end submissions.', 'bp-user-to-do-list' ),
		'url'  => 'https://wbcomdesigns.com/downloads/eventonomy/',
	),
	array(
		'name' => 'WB Gamification',
		'logo' => 'wb-gamification.svg',
		'icon' => 'awards',
		'desc' => __( 'Reward members with points, badges, and leaderboards to keep engagement high.', 'bp-user-to-do-list' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wordpress-gamification-plugin/',
	),
	array(
		'name' => 'Listora',
		'logo' => 'listora.svg',
		'icon' => 'list-view',
		'desc' => __( 'Searchable directories with ten listing types, ratings, maps, and front-end submissions.', 'bp-user-to-do-list' ),
		'url'  => 'https://wbcomdesigns.com/downloads/listora/',
	),
	array(
		'name' => 'WP Career Board',
		'logo' => 'wp-career-board.svg',
		'icon' => 'businessman',
		'desc' => __( 'Add a job board with front-end listings, applications, and employer profiles.', 'bp-user-to-do-list' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wp-career-board/',
	),
	array(
		'name' => 'Learnomy',
		'logo' => 'learnomy.svg',
		'icon' => 'welcome-learn-more',
		'desc' => __( 'Create, sell, and auto-grade online courses, then hand out certificates automatically.', 'bp-user-to-do-list' ),
		'url'  => 'https://wbcomdesigns.com/downloads/learnomy/',
	),
	array(
		'name' => 'WP Sell Services',
		'logo' => 'wp-sell-services.svg',
		'icon' => 'cart',
		'desc' => __( 'A free service marketplace with vendor dashboards, an 11-status order flow, and Stripe or PayPal built in.', 'bp-user-to-do-list' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wp-sell-services/',
	),
);

$bptodo_ecosystem_dir = defined( 'BPTODO_PLUGIN_PATH' ) ? BPTODO_PLUGIN_PATH . 'assets/images/ecosystem/' : '';
?>

<div class="bptodo-card">
	<div class="bptodo-card__head">
		<p class="bptodo-card__title"><?php esc_html_e( 'More Free Tools from Wbcom Designs', 'bp-user-to-do-list' ); ?></p>
		<p class="bptodo-card__desc"><?php esc_html_e( 'Todo Lists keeps your members and teams on task. These free tools from Wbcom Designs give you the rest of the community around it: the theme and network itself, forums, media, events, gamification, directories, jobs, courses, and services.', 'bp-user-to-do-list' ); ?></p>
	</div>
	<div class="bptodo-card__body">
		<div class="bptodo-discover-grid">
			<?php foreach ( $bptodo_ecosystem as $bptodo_product ) : ?>
				<div class="bptodo-discover-card">
					<span class="bptodo-discover-card__logo" aria-hidden="true">
						<?php if ( $bptodo_ecosystem_dir && file_exists( $bptodo_ecosystem_dir . $bptodo_product['logo'] ) ) : ?>
							<img src="<?php echo esc_url( $bptodo_ecosystem_img . $bptodo_product['logo'] ); ?>" alt="<?php echo esc_attr( $bptodo_product['name'] ); ?>" width="52" height="52" loading="lazy" />
						<?php else : ?>
							<span class="dashicons dashicons-<?php echo esc_attr( isset( $bptodo_product['icon'] ) ? $bptodo_product['icon'] : 'admin-plugins' ); ?>"></span>
						<?php endif; ?>
					</span>
					<h3 class="bptodo-discover-card__title"><?php echo esc_html( $bptodo_product['name'] ); ?></h3>
					<p class="bptodo-discover-card__desc"><?php echo esc_html( $bptodo_product['desc'] ); ?></p>
					<a class="bptodo-btn bptodo-btn-secondary bptodo-discover-card__cta" href="<?php echo esc_url( $bptodo_product['url'] ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'Get it free', 'bp-user-to-do-list' ); ?>
						<span class="dashicons dashicons-external" aria-hidden="true"></span>
						<span class="screen-reader-text">
							<?php
							/* translators: %s: product name. */
							echo esc_html( sprintf( __( '%s (opens in a new tab)', 'bp-user-to-do-list' ), $bptodo_product['name'] ) );
							?>
						</span>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
