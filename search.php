<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 */
get_header();

if ( have_posts() ) { ?>
	<header class="page-header search-header">
		<h1 class="page-title">
			<?php
			printf(
				/* translators: %s: Search term. */
				esc_html__( 'Results for "%s"', 'wp-theme' ),
				'<span class="search-term">' . esc_html( get_search_query() ) . '</span>'
			);
			?>
		</h1>

		<p class="search-result-count">
			<?php
			printf(
				esc_html(
					/* translators: %d: The number of search results2. */
					_n(
						'We found %d result for your search.',
						'We found %d results for your search.',
						(int) $wp_query->found_posts,
						'wp-theme'
					)
				),
				(int)	$wp_query->found_posts
			);
			?>
		</p>
	</header>


	<?php
	// Start the Loop.
	while ( have_posts() ) {
		the_post();
		get_template_part( 'template-parts/content/content-excerpt' );
	}

	// Previous/next page navigation.
	the_posts_navigation();

} else {

	// If no content, include the "No posts found" template.
	get_template_part( 'template-parts/content/content-none' );
}

get_footer();