<?php
/**
 * Loading State Partial
 *
 * Displays skeleton loading placeholders while content is being fetched.
 * This provides a better user experience than a simple spinner.
 *
 * @package bp-user-todo-list
 * @since 3.6.0
 *
 * Expected variables:
 * @var int    $count  Number of skeleton cards to display (default: 3)
 * @var string $type   Type of skeleton: 'card', 'list', 'form' (default: 'card')
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Set defaults.
$count = isset( $count ) ? absint( $count ) : 3;
$type  = isset( $type ) ? $type : 'card';

?>

<?php if ( 'card' === $type ) : ?>
	<!-- Skeleton Card Grid -->
	<div class="bptodo-list bptodo-list--grid" aria-hidden="true" aria-label="<?php esc_attr_e( 'Loading todos...', 'wb-todo' ); ?>">
		<?php for ( $i = 0; $i < $count; $i++ ) : ?>
			<div class="bptodo-card bptodo-card--skeleton">
				<header class="bptodo-card__header">
					<div class="bptodo-skeleton bptodo-skeleton--circle" style="width: 24px; height: 24px;"></div>
					<div class="bptodo-skeleton bptodo-skeleton--title" style="flex: 1;"></div>
				</header>
				<div class="bptodo-card__content">
					<div class="bptodo-skeleton bptodo-skeleton--text" style="margin-bottom: 8px;"></div>
					<div class="bptodo-skeleton bptodo-skeleton--text" style="width: 70%;"></div>
				</div>
				<div class="bptodo-card__meta" style="display: flex; gap: 8px;">
					<div class="bptodo-skeleton" style="width: 60px; height: 20px; border-radius: 9999px;"></div>
					<div class="bptodo-skeleton" style="width: 80px; height: 20px; border-radius: 9999px;"></div>
				</div>
				<footer class="bptodo-card__footer">
					<div class="bptodo-skeleton" style="width: 60px; height: 32px; border-radius: 6px;"></div>
					<div class="bptodo-skeleton" style="width: 60px; height: 32px; border-radius: 6px;"></div>
				</footer>
			</div>
		<?php endfor; ?>
	</div>

<?php elseif ( 'list' === $type ) : ?>
	<!-- Skeleton List -->
	<div class="bptodo-skeleton-list" aria-hidden="true" aria-label="<?php esc_attr_e( 'Loading...', 'wb-todo' ); ?>">
		<?php for ( $i = 0; $i < $count; $i++ ) : ?>
			<div style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--bptodo-gray-200);">
				<div class="bptodo-skeleton bptodo-skeleton--circle" style="width: 24px; height: 24px;"></div>
				<div style="flex: 1;">
					<div class="bptodo-skeleton bptodo-skeleton--text" style="width: <?php echo esc_attr( rand( 60, 90 ) ); ?>%; margin-bottom: 4px;"></div>
					<div class="bptodo-skeleton bptodo-skeleton--text" style="width: <?php echo esc_attr( rand( 30, 50 ) ); ?>%; height: 12px;"></div>
				</div>
			</div>
		<?php endfor; ?>
	</div>

<?php elseif ( 'form' === $type ) : ?>
	<!-- Skeleton Form -->
	<div class="bptodo-skeleton-form" aria-hidden="true" aria-label="<?php esc_attr_e( 'Loading form...', 'wb-todo' ); ?>">
		<div style="margin-bottom: 16px;">
			<div class="bptodo-skeleton" style="width: 80px; height: 14px; margin-bottom: 8px;"></div>
			<div class="bptodo-skeleton" style="width: 100%; height: 40px; border-radius: 6px;"></div>
		</div>
		<div style="margin-bottom: 16px;">
			<div class="bptodo-skeleton" style="width: 100px; height: 14px; margin-bottom: 8px;"></div>
			<div class="bptodo-skeleton" style="width: 100%; height: 100px; border-radius: 6px;"></div>
		</div>
		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
			<div>
				<div class="bptodo-skeleton" style="width: 60px; height: 14px; margin-bottom: 8px;"></div>
				<div class="bptodo-skeleton" style="width: 100%; height: 40px; border-radius: 6px;"></div>
			</div>
			<div>
				<div class="bptodo-skeleton" style="width: 70px; height: 14px; margin-bottom: 8px;"></div>
				<div class="bptodo-skeleton" style="width: 100%; height: 40px; border-radius: 6px;"></div>
			</div>
		</div>
		<div style="display: flex; justify-content: flex-end; gap: 12px;">
			<div class="bptodo-skeleton" style="width: 80px; height: 40px; border-radius: 6px;"></div>
			<div class="bptodo-skeleton" style="width: 100px; height: 40px; border-radius: 6px;"></div>
		</div>
	</div>

<?php else : ?>
	<!-- Simple Spinner Fallback -->
	<div style="display: flex; justify-content: center; padding: 40px;">
		<div class="bptodo-spinner" aria-label="<?php esc_attr_e( 'Loading...', 'wb-todo' ); ?>"></div>
	</div>
<?php endif; ?>
