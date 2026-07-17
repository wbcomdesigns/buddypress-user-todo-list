<?php
/**
 * Discover partial: ecosystem cross-promotion (read-only display view).
 *
 * Rendered inside shell.php. This is a pure presentation view - product
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
		'name' => __( 'BuddyX', 'wb-todo' ),
		'logo' => 'buddyx.png',
		'icon' => 'admin-appearance',
		'desc' => __( 'A free, fast community theme for BuddyPress, BuddyBoss and PeepSo with a modern layout and dark mode.', 'wb-todo' ),
		'url'  => 'https://wbcomdesigns.com/downloads/buddyx-theme/',
	),
	array(
		'name' => __( 'BuddyNext', 'wb-todo' ),
		'logo' => 'buddynext.svg',
		'icon' => 'groups',
		'desc' => __( 'The full community stack: activity feeds, member spaces, profiles, and private messaging on WordPress.', 'wb-todo' ),
		'url'  => 'https://wbcomdesigns.com/downloads/buddynext/',
	),
	array(
		'name' => __( 'Jetonomy', 'wb-todo' ),
		'logo' => 'jetonomy.svg',
		'icon' => 'format-chat',
		'desc' => __( 'Self-moderating forums and Q&A boards that stay fast well beyond 100,000 topics.', 'wb-todo' ),
		'url'  => 'https://wbcomdesigns.com/downloads/jetonomy/',
	),
	array(
		'name' => __( 'Mediaverse', 'wb-todo' ),
		'logo' => 'mediaverse.svg',
		'icon' => 'format-gallery',
		'desc' => __( 'A photo and video hub with albums, reactions, following, and private chat.', 'wb-todo' ),
		'url'  => 'https://wbcomdesigns.com/downloads/mediaverse/',
	),
	array(
		'name' => __( 'Eventonomy', 'wb-todo' ),
		'logo' => 'eventonomy.svg',
		'icon' => 'calendar-alt',
		'desc' => __( 'Run community events with RSVPs, calendars, and front-end submissions.', 'wb-todo' ),
		'url'  => 'https://wbcomdesigns.com/downloads/eventonomy/',
	),
	array(
		'name' => __( 'WB Gamification', 'wb-todo' ),
		'logo' => 'wb-gamification.svg',
		'icon' => 'awards',
		'desc' => __( 'Reward members with points, badges, and leaderboards to keep engagement high.', 'wb-todo' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wordpress-gamification-plugin/',
	),
	array(
		'name' => __( 'Listora', 'wb-todo' ),
		'logo' => 'listora.svg',
		'icon' => 'list-view',
		'desc' => __( 'Searchable directories with ten listing types, ratings, maps, and front-end submissions.', 'wb-todo' ),
		'url'  => 'https://wbcomdesigns.com/downloads/listora/',
	),
	array(
		'name' => __( 'WP Career Board', 'wb-todo' ),
		'logo' => 'wp-career-board.svg',
		'icon' => 'businessman',
		'desc' => __( 'Add a job board with front-end listings, applications, and employer profiles.', 'wb-todo' ),
		'url'  => 'https://wbcomdesigns.com/downloads/wp-career-board/',
	),
	array(
		'name' => __( 'Learnomy', 'wb-todo' ),
		'logo' => 'learnomy.svg',
		'icon' => 'welcome-learn-more',
		'desc' => __( 'Create, sell, and auto-grade online courses, then hand out certificates automatically.', 'wb-todo' ),
		'url'  => 'https://wbcomdesigns.com/downloads/learnomy/',
	),
);

$bptodo_ecosystem_dir = defined( 'BPTODO_PLUGIN_PATH' ) ? BPTODO_PLUGIN_PATH . 'assets/images/ecosystem/' : '';
?>

<div class="bptodo-card">
	<div class="bptodo-card__head">
		<p class="bptodo-card__title"><?php esc_html_e( 'More Free Tools from Wbcom Designs', 'wb-todo' ); ?></p>
		<p class="bptodo-card__desc"><?php esc_html_e( 'Todo Lists keeps your members and teams on task. These free tools from Wbcom Designs give you the rest of the community around it: the theme and network itself, forums, media, events, gamification, directories, jobs, and courses.', 'wb-todo' ); ?></p>
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
						<?php esc_html_e( 'Get it free', 'wb-todo' ); ?>
						<span class="dashicons dashicons-external" aria-hidden="true"></span>
						<span class="screen-reader-text">
							<?php
							/* translators: %s: product name. */
							echo esc_html( sprintf( __( '%s (opens in a new tab)', 'wb-todo' ), $bptodo_product['name'] ) );
							?>
						</span>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
