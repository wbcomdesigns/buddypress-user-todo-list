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
	  <header class="entry-header">
			<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
	  </header><!-- .entry-header -->
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
	</article>
			<?php endwhile; ?>
			<div class="single-to-report">
				<?php bptodo_todo_user_report(); ?>
			</div>

		</main><!-- .site-main -->
	</div><!-- .content-area -->
<?php get_sidebar( 'right' ); ?>
<?php get_footer(); ?>
