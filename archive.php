<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 */

get_header();
$description = get_the_archive_description();

if ( have_posts() ) { ?>

	<header class="page-header archive-header">
	  <?php the_archive_title( '<h1 class="page-title">', '</h1>' ); ?>
	  <?php if ( $description ) : ?>
			<div class="archive-description"><?php echo wp_kses_post( wpautop( $description ) ) ?></div>
	  <?php endif; ?>
	</header>

	<?php
	// Start the Loop.
	while ( have_posts() ) {
		the_post();
		get_template_part( 'template-parts/content/content', 'excerpt' );
	}

	// Previous/next page navigation.
	the_posts_navigation();

	// If no content, include the "No posts found" template.
} else {
	get_template_part( 'template-parts/content/content-none' );
}

get_footer();