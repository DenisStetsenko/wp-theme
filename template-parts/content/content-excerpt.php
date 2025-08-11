<?php
/**
 * Template part for displaying post archives and search results
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header class="entry-header">
		<?php
		the_title( sprintf( '<h2 class="entry-title"><a href="%s">', esc_url( get_permalink() ) ), '</a></h2>' );
		?>
	</header>

	<div class="entry-content">
		<?php
		if ( has_excerpt() ) {
			the_excerpt();
		} else {
			echo wp_trim_words( get_the_content(), 20, '...' );
		} ?>
	</div>

</article>
