<?php
/**
 * Sidebar
 *
 * Content for our sidebar, provides prompt for logged in users to create widgets
 */
?>

<div id="right-sidebar" class="position-relative flex-grow-1">
	<div class="sticky-top wow fadeInUp" data-wow-delay="50ms" data-wow-duration="700ms">
		<?php
		if ( is_single() && 'post' == get_post_type() && is_page_template('single-comparison-template.php') && is_active_sidebar( 'comparison-sidebar' ) ) {
			dynamic_sidebar( 'comparison-sidebar' );
		}
		elseif ( is_single() && 'post' == get_post_type() && is_active_sidebar( 'review-sidebar' ) ) {
			dynamic_sidebar( 'review-sidebar' );
		}
		elseif ( is_page_template('template-about.php') && is_active_sidebar( 'about-page-sidebar' ) ) {
			
			if ( has_post_thumbnail() ) : ?>
			<aside id="media-image" role="region" aria-label="Sidebar Element"
					 class="widget rounded-3 font-secondary bg-light-gray border fs-4 widget_media_image d-none d-lg-block">
				<?php the_post_thumbnail('full', array( 'loading' => 'eager', 'class' => 'img-fluid image' )); ?>
			</aside>
		<?php endif;
			
			dynamic_sidebar( 'about-page-sidebar' );
		}
		elseif ( 'page' == get_post_type() && is_active_sidebar( 'page-sidebar' ) ) {
			dynamic_sidebar( 'page-sidebar' );
		}
		?>
	</div>
</div>