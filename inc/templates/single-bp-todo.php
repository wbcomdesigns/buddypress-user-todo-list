<?php
/**
 * The template for displaying pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other "pages" on your WordPress site will use a different template.
 *
 * @package bp-user-todo-list
 * @since 2.2.1
 */

get_header(); ?>
<?php get_sidebar( 'left' ); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php
			// Start the loop.
		while ( have_posts() ) :
			the_post();
			?>


		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<div class="bptodo-list-single">			
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				<?php
				$todo_priority = get_post_meta( $post->ID, 'todo_priority', true );
				if ( ! empty( $todo_priority ) ) {
					if ( 'critical' === $todo_priority ) {
						$priority_class = 'bptodo-priority-critical';
						$priority_text  = esc_html__( 'Critical', 'wb-todo' );
					} elseif ( 'high' === $todo_priority ) {
						$priority_class = 'bptodo-priority-high';
						$priority_text  = esc_html__( 'High', 'wb-todo' );
					} else {
						$priority_class = 'bptodo-priority-normal';
						$priority_text  = esc_html__( 'Normal', 'wb-todo' );
					}
				}
				$todo_cats = get_terms( array(
    'taxonomy'   => 'todo_category',
    'orderby'    => 'name',
    'hide_empty' => false,
) );
				$todo_cat    = wp_get_object_terms( $post->ID, 'todo_category' );
				$todo_cat_id = 0;
				if ( ! empty( $todo_cat ) && is_array( $todo_cat ) ) {
					$todo_cat_id = $todo_cat[0]->term_id;
				}
				?>
					<div class="signle-bptodo-meta">
						<div class="single-todo-priority"><span class="<?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority_text ); ?></span></div>
						<div class="single-todo-date"><?php echo get_the_date( '', $post->ID ); ?></div>
						<div class="single-todo-cat">
					<?php
					foreach ( $todo_cats as $todo_cat ) {
						if ( $todo_cat_id == $todo_cat->term_id ) {
							echo esc_html( $todo_cat->name );
						}
					}
					?>
						</div>
					</div>
				<div class="entry-content">
					<?php the_content(); ?>
					<?php
					wp_link_pages(
						array(
							'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'wb-todo' ) . '</span>',
							'after'       => '</div>',
							'link_before' => '<span>',
							'link_after'  => '</span>',
							'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'wb-todo' ) . ' </span>%',
							'separator'   => '<span class="screen-reader-text">, </span>',
						)
					);
					?>
				</div><!-- .entry-content -->
			</div>
		</article>

		<?php endwhile; ?>

		<?php if ( ! empty( bptodo_todo_user_report() ) ) : ?>
		
		<div class="single-to-report">
			<?php bptodo_todo_user_report(); ?>
		</div>

		<?php endif; ?>

	</main><!-- .site-main -->

</div><!-- .content-area -->

<?php get_sidebar( 'right' ); ?>
<?php get_footer(); ?>
