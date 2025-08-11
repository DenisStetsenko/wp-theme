<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 */
get_header(); ?>

	<div id="404-page-wrapper">
		<div class="container">

			<header class="section-title">
				<h1 class="page-title"><?php esc_html_e( '404', 'wp-theme' ); ?></h1>
				<h2 class="page-title"><?php esc_html_e( 'Page Not Found', 'wp-theme' ); ?></h2>
			</header>

			<div class="error-404">
				<div class="page-content">
					<p><?php esc_html_e( "The page you're looking for can not be found", 'wp-theme' ); ?></p>
				</div>
			</div>
			
		</div>
	</div>

<?php get_footer();