<?php
/**
 * Admin page shell: page header, sidebar nav, body slot.
 *
 * Receives from Bptodo_Admin_Panel::render_page():
 *
 * @var array  $bptodo_tabs Tab registry keyed by slug.
 * @var string $active      Active tab slug.
 * @var string $page_url    Base URL (admin.php?page=user-todo-list-settings).
 * @var string $view        View slug (e.g. 'overview', 'settings-general').
 * @var string $view_path   Absolute path to the active partial.
 *
 * Each settings tab view owns its own <form> (General uses the custom
 * self-POST handler; Group + Data Retention use the Settings API via
 * options.php). The shell therefore does NOT wrap a shared form — it
 * just provides the chrome and includes the active partial.
 *
 * @package bp-user-todo-list
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

$bptodo_version = defined( 'BPTODO_VERSION' ) ? BPTODO_VERSION : '';
?>
<div class="wrap bptodo-admin">

	<header class="bptodo-page-header">
		<div class="bptodo-page-header__title">
			<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
			<div>
				<h1><?php esc_html_e( 'Todo for BuddyPress & BuddyBoss', 'wb-todo' ); ?></h1>
				<p class="bptodo-page-header__subtitle"><?php esc_html_e( 'Personal and group to-do lists on every member profile, with reminders, categories, and role-based access.', 'wb-todo' ); ?></p>
			</div>
		</div>
		<div class="bptodo-page-header__actions">
			<?php if ( $bptodo_version ) : ?>
				<span class="bptodo-version-pill">v<?php echo esc_html( $bptodo_version ); ?></span>
			<?php endif; ?>
		</div>
	</header>

	<?php
	/*
	 * Without this marker, core's common.js re-parents every .notice to sit
	 * right after the first <h1> it finds, which slots the "Settings saved"
	 * banner between our title and its subtitle instead of below the whole
	 * header. See migration playbook Part 10.2.
	 */
	?>
	<hr class="wp-header-end">

	<div class="bptodo-settings-layout">

		<aside class="bptodo-settings-sidebar">
			<div class="bptodo-settings-sidebar-brand">
				<span class="bptodo-settings-brand-icon" aria-hidden="true">
					<span class="dashicons dashicons-list-view"></span>
				</span>
				<div class="bptodo-settings-brand-text">
					<p class="bptodo-settings-brand-name"><?php esc_html_e( 'Todo Lists', 'wb-todo' ); ?></p>
					<p class="bptodo-settings-brand-sub"><?php esc_html_e( 'Plugin', 'wb-todo' ); ?></p>
				</div>
			</div>
			<nav class="bptodo-settings-sidebar-nav" aria-label="<?php esc_attr_e( 'Todo Lists navigation', 'wb-todo' ); ?>">
				<?php
				$bptodo_printed_groups = array();
				$bptodo_group_labels   = array(
					'settings' => esc_html__( 'Settings', 'wb-todo' ),
				);
				foreach ( $bptodo_tabs as $bptodo_slug => $bptodo_tab ) {
					$bptodo_group = isset( $bptodo_tab['group'] ) ? $bptodo_tab['group'] : 'main';
					if ( 'main' !== $bptodo_group && ! in_array( $bptodo_group, $bptodo_printed_groups, true ) ) {
						echo '<div class="bptodo-snav-divider" role="separator"></div>';
						if ( isset( $bptodo_group_labels[ $bptodo_group ] ) ) {
							echo '<p class="bptodo-snav-section-label">' . esc_html( $bptodo_group_labels[ $bptodo_group ] ) . '</p>';
						}
						$bptodo_printed_groups[] = $bptodo_group;
					}
					$bptodo_classes  = 'bptodo-snav-link';
					$bptodo_classes .= $active === $bptodo_slug ? ' bptodo-snav-link--active' : '';
					echo '<a href="' . esc_url( $page_url . '&tab=' . $bptodo_slug ) . '" class="' . esc_attr( $bptodo_classes ) . '">';
					echo '<span class="dashicons ' . esc_attr( $bptodo_tab['icon'] ) . '" aria-hidden="true"></span>';
					echo esc_html( $bptodo_tab['label'] );
					echo '</a>';
				}
				?>

				<div class="bptodo-snav-divider" role="separator"></div>
				<p class="bptodo-snav-section-label"><?php esc_html_e( 'Resources', 'wb-todo' ); ?></p>
				<a href="https://docs.wbcomdesigns.com/docs/bp-user-todo-list/" class="bptodo-snav-link" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-book" aria-hidden="true"></span>
					<?php esc_html_e( 'Documentation', 'wb-todo' ); ?>
					<span class="dashicons dashicons-external bptodo-snav-link__ext" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'wb-todo' ); ?></span>
				</a>
			</nav>
		</aside>

		<div class="bptodo-settings-main">
			<?php
			/*
			 * Render settings notices inside the content column so the banner
			 * aligns with the panel chrome rather than spanning the full admin
			 * width above the bounded layout.
			 */
			settings_errors();
			?>
			<?php
			if ( file_exists( $view_path ) ) {
				include $view_path;
			}
			?>
		</div>

	</div>
</div>
