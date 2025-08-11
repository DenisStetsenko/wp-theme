<?php
/**
 * Template part for displaying a message that posts cannot be found
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<header class="page-header">
		<?php if ( is_search() ) : ?>
			<h1 class="page-title">
				<?php
				printf(
					/* translators: %s: Search term. */
					esc_html__( 'Results for "%s"', 'wp-theme' ),
					'<span class="search-term">' . esc_html( get_search_query() ) . '</span>'
				);
				?>
			</h1>
		<?php else : ?>
			<h1 class="page-title"><?php esc_html_e( 'Nothing here', 'wp-theme' ); ?></h1>
		<?php endif; ?>
	</header>

	<div class="entry-content">
		<?php if ( is_search() ) : ?>
			<p><?php esc_html_e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'wp-theme' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'wp-theme' ); ?></p>
		<?php endif; ?>
	</div>

</article>