<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header class="entry-header post-header">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
	</header>

	<div class="entry-content">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail('large', array( 'class' => 'post-thumbnail' ) );
		}

		the_content();

		wp_link_pages( array(
			'before'   => '<nav class="page-links" aria-label="' . esc_attr__( 'Page', 'wp-theme' ) . '">',
			'after'    => '</nav>',
			/* translators: %: Page number. */
			'pagelink' => esc_html__( 'Page %', 'wp-theme' ),
		));

		the_tags( '<div class="post-tags">', ', ', '</div>' );
		?>
	</div><!-- .entry-content -->

</article><!-- #post-<?php the_ID(); ?> -->
