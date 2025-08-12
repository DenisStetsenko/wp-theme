<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header class="entry-header">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
	</header>

	<div class="entry-content">
		<?php
		the_content();

		wp_link_pages( array(
			'before'   => '<nav class="page-links" aria-label="' . esc_attr__( 'Page', 'wp-theme' ) . '">',
			'after'    => '</nav>',
			/* translators: %: Page number. */
			'pagelink' => esc_html__( 'Page %', 'wp-theme' ),
		) );
		?>
	</div>

</article>
