<?php
/**
 * WB Plugins hub — landing dashboard at ?page=wbcomplugins.
 *
 * Lists every Wbcom plugin that has registered a submenu under the shared
 * wbcomplugins parent. No manual registration needed: peer plugins appear
 * automatically once their admin page is in the WordPress menu table.
 *
 * Legacy wbcom-wrapper plugins register four boilerplate helper pages (Our
 * Plugins, Our Themes, Support, License) under this hub. Those are not real
 * plugins and must not appear as cards — we filter them out via
 * wbcom_hub_wrapper_helper_slugs. See migration playbook Part 16.
 *
 * @package bp-user-todo-list
 * @since   3.6.0
 */

defined( 'ABSPATH' ) || exit;

$bptodo_submenu_entries = isset( $GLOBALS['submenu']['wbcomplugins'] ) && is_array( $GLOBALS['submenu']['wbcomplugins'] )
	? $GLOBALS['submenu']['wbcomplugins']
	: array();

// Slugs of the legacy wbcom-wrapper boilerplate helper pages. Every
// un-migrated Wbcom plugin still registers these shared pages under the hub.
// They are not plugins, so they must not appear as cards on the dashboard —
// they would read like duplicate tiles. Filterable so Pro modules or future
// wrapper slugs can be added.
$bptodo_wrapper_helper_slugs = apply_filters(
	'wbcom_hub_wrapper_helper_slugs',
	array(
		'wbcom-plugins-page',
		'wbcom-themes-page',
		'wbcom-support-page',
		'wbcom-license-page',
	)
);

$bptodo_plugins = array();
foreach ( $bptodo_submenu_entries as $bptodo_entry ) {
	$bptodo_slug = isset( $bptodo_entry[2] ) ? (string) $bptodo_entry[2] : '';
	if ( '' === $bptodo_slug || 'wbcomplugins' === $bptodo_slug ) {
		continue;
	}
	if ( in_array( $bptodo_slug, $bptodo_wrapper_helper_slugs, true ) ) {
		continue;
	}
	$bptodo_plugins[] = array(
		'slug'       => $bptodo_slug,
		'menu_title' => isset( $bptodo_entry[0] ) ? wp_strip_all_tags( (string) $bptodo_entry[0] ) : $bptodo_slug,
		'page_title' => isset( $bptodo_entry[3] ) ? wp_strip_all_tags( (string) $bptodo_entry[3] ) : '',
		'url'        => admin_url( 'admin.php?page=' . rawurlencode( $bptodo_slug ) ),
	);
}

$bptodo_plugin_count = count( $bptodo_plugins );
?>
<div class="wrap bptodo-admin">
	<header class="bptodo-page-header">
		<div class="bptodo-page-header__title">
			<span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
			<div>
				<h1><?php esc_html_e( 'WB Plugins', 'wb-todo' ); ?></h1>
				<p class="bptodo-page-header__subtitle">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: active Wbcom plugin count */
							_n(
								'%d Wbcom plugin active on this site.',
								'%d Wbcom plugins active on this site.',
								$bptodo_plugin_count,
								'wb-todo'
							),
							$bptodo_plugin_count
						)
					);
					?>
				</p>
			</div>
		</div>
	</header>

	<?php if ( 0 === $bptodo_plugin_count ) : ?>
		<div class="bptodo-empty-state">
			<span class="bptodo-empty-state__icon" aria-hidden="true">
				<span class="dashicons dashicons-lightbulb"></span>
			</span>
			<p class="bptodo-empty-state__title"><?php esc_html_e( 'No Wbcom plugins attached to this hub yet', 'wb-todo' ); ?></p>
			<p class="bptodo-empty-state__desc">
				<?php esc_html_e( 'Activate one or more Wbcom plugins and they will appear here automatically.', 'wb-todo' ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="bptodo-hub-grid">
			<?php foreach ( $bptodo_plugins as $bptodo_p ) : ?>
				<a href="<?php echo esc_url( $bptodo_p['url'] ); ?>" class="bptodo-hub-card">
					<span class="bptodo-hub-card__icon" aria-hidden="true">
						<span class="dashicons dashicons-admin-plugins"></span>
					</span>
					<span class="bptodo-hub-card__title"><?php echo esc_html( $bptodo_p['menu_title'] ); ?></span>
					<?php if ( ! empty( $bptodo_p['page_title'] ) && $bptodo_p['page_title'] !== $bptodo_p['menu_title'] ) : ?>
						<span class="bptodo-hub-card__subtitle"><?php echo esc_html( $bptodo_p['page_title'] ); ?></span>
					<?php endif; ?>
					<span class="bptodo-hub-card__cta">
						<?php esc_html_e( 'Open settings', 'wb-todo' ); ?>
						<span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="bptodo-card bptodo-card--spaced">
		<div class="bptodo-card__head">
			<p class="bptodo-card__title"><?php esc_html_e( 'About WB Plugins', 'wb-todo' ); ?></p>
		</div>
		<div class="bptodo-card__body">
			<p class="bptodo-card__lead">
				<?php esc_html_e( 'This hub is the single entry point for every Wbcom Designs plugin installed on your site. Each plugin lives on its own page under this menu and keeps its own settings, license, and data.', 'wb-todo' ); ?>
			</p>
			<p>
				<a href="https://wbcomdesigns.com/" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Visit wbcomdesigns.com for more plugins and themes', 'wb-todo' ); ?>
				</a>
			</p>
		</div>
	</div>
</div>
