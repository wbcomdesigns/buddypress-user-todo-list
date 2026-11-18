<?php
/**
 * Server-Side Pagination Partial
 *
 * Link-based pager for server-paginated todo lists (member and group
 * lists). Uses the design-system pagination component classes so it
 * matches the rest of the plugin UI.
 *
 * @package bp-user-todo-list
 * @since 3.6.0
 *
 * Expected variables:
 * @var array $pager {
 *     @type string $param       Query arg that carries the page number (e.g. 'todo_page').
 *     @type int    $current     Current page (1-based).
 *     @type int    $total_pages Total number of pages.
 *     @type string $label       Accessible label for the <nav> element.
 * }
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bptodo_pager_param   = isset( $pager['param'] ) ? $pager['param'] : 'todo_page';
$bptodo_pager_current = isset( $pager['current'] ) ? max( 1, (int) $pager['current'] ) : 1;
$bptodo_pager_total   = isset( $pager['total_pages'] ) ? (int) $pager['total_pages'] : 1;
$bptodo_pager_label   = isset( $pager['label'] ) ? $pager['label'] : __( 'Todos pagination', 'bp-user-to-do-list' );

if ( $bptodo_pager_total < 2 ) {
	return;
}

/**
 * Build a page URL preserving all other current query args
 * (so the two tab pagers do not reset each other).
 *
 * @param int    $bptodo_page  Target page.
 * @param string $bptodo_param Query arg name.
 * @return string Escaped URL.
 */
$bptodo_pager_url = static function ( $bptodo_page, $bptodo_param ) {
	if ( $bptodo_page <= 1 ) {
		return esc_url( remove_query_arg( $bptodo_param ) );
	}
	return esc_url( add_query_arg( $bptodo_param, (int) $bptodo_page ) );
};

// Numbered window around the current page.
$bptodo_pager_range = 2;
$bptodo_pager_start = max( 1, $bptodo_pager_current - $bptodo_pager_range );
$bptodo_pager_end   = min( $bptodo_pager_total, $bptodo_pager_current + $bptodo_pager_range );
?>
<nav class="bptodo-pagination" aria-label="<?php echo esc_attr( $bptodo_pager_label ); ?>">
	<?php if ( $bptodo_pager_current > 1 ) : ?>
		<a class="bptodo-pagination__btn bptodo-pagination__btn--prev" href="<?php echo $bptodo_pager_url( $bptodo_pager_current - 1, $bptodo_pager_param ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in closure. ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<polyline points="15 18 9 12 15 6"></polyline>
			</svg>
			<?php esc_html_e( 'Previous', 'bp-user-to-do-list' ); ?>
		</a>
	<?php else : ?>
		<span class="bptodo-pagination__btn bptodo-pagination__btn--prev bptodo-pagination__btn--disabled" aria-disabled="true">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<polyline points="15 18 9 12 15 6"></polyline>
			</svg>
			<?php esc_html_e( 'Previous', 'bp-user-to-do-list' ); ?>
		</span>
	<?php endif; ?>

	<div class="bptodo-pagination__numbers">
		<?php if ( $bptodo_pager_start > 1 ) : ?>
			<a class="bptodo-pagination__num" href="<?php echo $bptodo_pager_url( 1, $bptodo_pager_param ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in closure. ?>">1</a>
			<?php if ( $bptodo_pager_start > 2 ) : ?>
				<span class="bptodo-pagination__ellipsis" aria-hidden="true">&hellip;</span>
			<?php endif; ?>
		<?php endif; ?>

		<?php for ( $bptodo_pager_i = $bptodo_pager_start; $bptodo_pager_i <= $bptodo_pager_end; $bptodo_pager_i++ ) : ?>
			<?php if ( $bptodo_pager_i === $bptodo_pager_current ) : ?>
				<span class="bptodo-pagination__num bptodo-pagination__num--current" aria-current="page"><?php echo esc_html( $bptodo_pager_i ); ?></span>
			<?php else : ?>
				<a class="bptodo-pagination__num" href="<?php echo $bptodo_pager_url( $bptodo_pager_i, $bptodo_pager_param ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in closure. ?>">
					<?php echo esc_html( $bptodo_pager_i ); ?>
				</a>
			<?php endif; ?>
		<?php endfor; ?>

		<?php if ( $bptodo_pager_end < $bptodo_pager_total ) : ?>
			<?php if ( $bptodo_pager_end < $bptodo_pager_total - 1 ) : ?>
				<span class="bptodo-pagination__ellipsis" aria-hidden="true">&hellip;</span>
			<?php endif; ?>
			<a class="bptodo-pagination__num" href="<?php echo $bptodo_pager_url( $bptodo_pager_total, $bptodo_pager_param ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in closure. ?>">
				<?php echo esc_html( $bptodo_pager_total ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php if ( $bptodo_pager_current < $bptodo_pager_total ) : ?>
		<a class="bptodo-pagination__btn bptodo-pagination__btn--next" href="<?php echo $bptodo_pager_url( $bptodo_pager_current + 1, $bptodo_pager_param ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in closure. ?>">
			<?php esc_html_e( 'Next', 'bp-user-to-do-list' ); ?>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<polyline points="9 18 15 12 9 6"></polyline>
			</svg>
		</a>
	<?php else : ?>
		<span class="bptodo-pagination__btn bptodo-pagination__btn--next bptodo-pagination__btn--disabled" aria-disabled="true">
			<?php esc_html_e( 'Next', 'bp-user-to-do-list' ); ?>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
				<polyline points="9 18 15 12 9 6"></polyline>
			</svg>
		</span>
	<?php endif; ?>
</nav>
