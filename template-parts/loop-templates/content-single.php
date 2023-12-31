<?php
/**
 * Single post partial template
 */

// Display Author Block
get_template_part('template-parts/single-article/author-block');

// Affiliate Disclosure
get_template_part('template-parts/single-article/affiliate-disclosure'); ?>

<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">
	
	<?php get_template_part('template-parts/single-article/post-thumbnail')?>
	
	<div class="hero-content <?= get_page_template_slug( get_the_ID() ) ? 'comparison-summary' : 'review-summary'; ?>">
		
		<div class="narrow-content">

			<div class="widget content-widget fs-4 shadow-none py-5 px-4 d-lg-none mb-4">
				<?php echo do_shortcode('[ez-toc]'); ?>
			</div>
			
			<div class="entry-content mobile-content-sm-size" itemprop="description">
				<?php the_content(); ?>
			</div>
		</div>
	</div>
	
</article><!-- #post-## -->