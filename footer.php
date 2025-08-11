<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 */
?>

</div><!-- #wrapper -->

<footer id="colophon" class="site-footer">
  <div class="container">
		<p>
			<?php
			printf(
					/* translators: 1: Year, 2: Site name. */
					__( '©%1$s %2$s. All Rights Reserved.', 'wp-theme' ),
					date( 'Y' ),
					esc_html( get_bloginfo( 'name' ) )
			);
			?>
		</p>
  </div>
</footer>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>