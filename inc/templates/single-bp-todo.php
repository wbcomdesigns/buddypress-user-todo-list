<?php
/**
 * The template for displaying single To-Do items
 *
 * This template focuses on displaying the content of To-Do items within the standard page structure.
 * It avoids altering the global header, footer, or sidebar styles.
 *
 * @package bp-user-todo-list
 * @since 2.2.1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header(); ?>

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
                
                <div class="signle-bptodo-meta">
                    <?php
                    // To-Do Priority
                    $todo_priority = get_post_meta( get_the_ID(), 'todo_priority', true );
                    if ( ! empty( $todo_priority ) ) {
                        switch ( $todo_priority ) {
                            case 'critical':
                                $priority_class = 'bptodo-priority-critical';
                                $priority_text  = esc_html__( 'Critical', 'wb-todo' );
                                break;
                            case 'high':
                                $priority_class = 'bptodo-priority-high';
                                $priority_text  = esc_html__( 'High', 'wb-todo' );
                                break;
                            default:
                                $priority_class = 'bptodo-priority-normal';
                                $priority_text  = esc_html__( 'Normal', 'wb-todo' );
                                break;
                        }
                        echo '<div class="single-todo-priority"><span class="' . esc_attr( $priority_class ) . '">' . esc_html( $priority_text ) . '</span></div>';
                    }

                    // To-Do Date
                    echo '<div class="single-todo-date">' . esc_html( get_the_date() ) . '</div>';

                    // To-Do Category
                    $todo_cat = wp_get_object_terms( get_the_ID(), 'todo_category' );
                    if ( ! empty( $todo_cat ) && ! is_wp_error( $todo_cat ) ) {
                        echo '<div class="single-todo-cat">' . esc_html( $todo_cat[0]->name ) . '</div>';
                    }
                    ?>
                </div>

                <div class="entry-content">
                    <?php
                    the_content();
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

    <?php if ( function_exists( 'bptodo_todo_user_report' ) && ! empty( bptodo_todo_user_report() ) ) : ?>
        <div class="single-to-report">
            <?php bptodo_todo_user_report(); ?>
        </div>
    <?php endif; ?>

    </main><!-- .site-main -->
</div><!-- .content-area -->

<?php get_footer(); ?>
