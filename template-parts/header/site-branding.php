<!-- Your site title as branding in the menu -->
<?php if ( has_custom_logo() ) : ?>
	<div class="site-logo has-custom-logo">
		<?php the_custom_logo(); ?>
	</div>
<?php else : ?>
	<div class="site-name">
		<a href="<?php echo esc_url( home_url( '/' ) ) ?>">
			<?php echo esc_html( get_bloginfo( 'name' ) ) ?>
		</a>
	</div>
<?php endif; ?>
<!-- end custom logo -->