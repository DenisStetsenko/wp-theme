<?php
$wp_related_query  = new WP_Query( array(
	'post_type'      => 'post',
	'posts_per_page' => 3,
	'order'          => 'DESC',
	'orderby'        => 'date',
	'post_status'    => 'publish',
	'post__not_in'   => array(get_the_ID()),
) );

if ( $wp_related_query->have_posts() ) : $originalPostUrl = get_the_permalink(); ?>
	
	<aside id="related-posts" aria-label="<?php _e('Suggested Articles', 'wp-theme'); ?>" role="region">

		<header class="section-title">
			<h2><?php _e( 'You Might Also Like', 'wp-theme' ); ?></h2>
		</header>

		<div class="row posts-loop g-4 g-lg-5">
			<?php while ( $wp_related_query->have_posts() ) : $wp_related_query->the_post(); ?>
				<?php get_template_part( 'template-parts/category/post-loop', 'item', array( 'original_post' => $originalPostUrl, 'layout' => '3-cols', 'include-author-block' => 1 ) ); ?>
			<?php endwhile; ?>
		</div>
		
	</aside>
<?php endif; wp_reset_query(); ?>