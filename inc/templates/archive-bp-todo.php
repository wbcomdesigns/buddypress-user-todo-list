<?php

/**
 * The template for displaying archive todos
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * If you'd like to further customize these archive views, you may create a
 * new template file for each one. For example, tag.php (Tag archives),
 * category.php (Category archives), author.php (Author archives), etc.
 *
 * @package bp-user-todo-list
 * @since 2.2.1
 */

get_header(); ?>

<section id="primary" class="content-area">
	<main id="main" class="site-main" role="main">
		<?php if (have_posts()) : ?>
			<header class="page-header">
				<?php the_archive_title('<h1 class="page-title">', '</h1>'); ?>
			</header><!-- .page-header -->
			<?php
			while (have_posts()) :
				the_post();
			?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<header class="entry-header">
						<?php
						if (is_tax()) :
							the_title(sprintf('<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url(get_permalink())), '</a></h2>');
						else :
							the_title(sprintf('<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url(get_permalink())), '</a></h2>');
						endif;
						?>
					</header><!-- .entry-header -->
					<div class="entry-content">
						<div class="entry-meta">
							<?php
							if (function_exists('get_avatar')) {

								echo sprintf(
									'<a href="%1$s" rel="bookmark">%2$s</a>',
									esc_url(bp_members_get_user_url(get_the_author_meta('ID'))),
									get_avatar(get_the_author_meta('email'), 55)
								);
							}
							echo sprintf(
								/* translators: %s: post author name */
								esc_html_x('Post by: %s', 'post author', 'wb-todo'),
								'<span class="author vcard"><a class="url fn n" href="' . esc_url(bp_members_get_user_url(get_the_author_meta('ID'))) . '">' . esc_html(get_the_author()) . '</a></span>'
							);
							?>
						</div>
						<?php
						the_excerpt(
							sprintf(
								/* translators: %s: Post title. */
								__('Continue reading %s', 'wb-todo'),
								the_title('<span class="screen-reader-text">', '</span>', false)
							)
						);
						wp_link_pages(
							array(
								'before'      => '<div class="page-links"><span class="page-links-title">' . __('Pages:', 'wb-todo') . '</span>',
								'after'       => '</div>',
								'link_before' => '<span>',
								'link_after'  => '</span>',
								'pagelink'    => '<span class="screen-reader-text">' . __('Page', 'wb-todo') . ' </span>%',
								'separator'   => '<span class="screen-reader-text">, </span>',
							)
						);
						?>
						<p class="no-margin">
							<a href="<?php the_permalink(); ?>" title="<?php
																		/* translators: %s: post title */
																		echo esc_attr(sprintf(__('Permalink to %s', 'wb-todo'), the_title_attribute('echo=0')));
																		?>" class="read-more button"><?php esc_html_e('Read More', 'wb-todo'); ?></a>
						</p>
					</div><!-- .entry-content -->
				</article>
			<?php
			endwhile;
			// Previous/next page navigation.
			the_posts_pagination(
				array(
					'prev_text'          => __('Previous page', 'wb-todo'),
					'next_text'          => __('Next page', 'wb-todo'),
					'before_page_number' => '<span class="meta-nav screen-reader-text">' . __('Page', 'wb-todo') . ' </span>',
				)
			);
		else :
			?>
			<section class="no-results not-found">
				<header class="page-header">
					<h1 class="page-title"><?php esc_html_e('Nothing Found', 'wb-todo'); ?></h1>
				</header><!-- .page-header -->
				<div class="page-content">
					<p></p>
				</div><!-- .page-content -->
			</section><!-- .no-results -->
		<?php endif; ?>
	</main><!-- .site-main -->
</section><!-- .content-area -->

<?php get_footer(); ?>