<?php
// ACF: Product Comparison
$top_products = get_field('top_products');

// Support custom "anchor" values.
$anchor = "";
if ( ! empty( $block['anchor'] ) ) {
	$anchor = 'id="' . esc_attr( $block['anchor'] ) . '"';
}

// Create class attribute allowing for custom "className" and "align" values.
$class_name = 'wp-block-acf-top-products-list product-summary-wrapper overflow-hidden rounded-3 bg-light-gray border py-4 px-4 my-5';
if ( ! empty( $block['className'] ) )   $class_name .= ' ' . $block['className'];

if ( ! empty( $top_products && array_filter($top_products) ) ) { ?>
	<div <?= esc_attr( $anchor ); ?> class="<?= esc_attr( $class_name ); ?>">
		<?php
		foreach ( $top_products as $key => $product ) :
			$first = $key === array_key_first($top_products);
			$product['logo'] && $product['logo']['alt'] ? $alt_top = $product['logo']['alt'] : $alt_top = $product['heading']['title'];
			?>
			<div class="top-picks d-flex flex-wrap justify-content-between <?= $first ? 'best' : ''; ?>">
				
				<?= $product['logo'] ? '<div class="column-preview rounded-2 bg-white d-flex align-items-center justify-content-center py-2 px-2 position-relative mb-4 mb-sm-0">
																					<img class="img-fluid" src="'.$product['logo']['sizes']['top-picks-thumbnail'].'" loading="lazy" alt="'. $alt_top . '">
																			 </div>' : null ; ?>
				
				<div class="d-flex <?= $product['logo'] ? 'column-description' : 'col-lg-8'; ?>">
					<?php if ( $product['heading'] ) : ?>
						<div class="top-picks-heading font-secondary flex-grow-1 ps-1 pe-1 ps-sm-4 pe-sm-0 pe-xl-4 py-2 d-flex flex-column justify-content-between">
							<div class="top">
								<?= $product['heading']['subtitle']     ? '<p class="subtitle mb-1 font-secondary fs-6 fw-bolder text-gray text-uppercase">'.wp_strip_all_tags($product['heading']['subtitle']).'</p>' 	: null; ?>
								<?= $product['heading']['title'] 		    ? '<p class="title fs-4 fw-bold mb-2">'.wp_strip_all_tags($product['heading']['title']).'</p>' 	: null; ?>
								<?= $product['heading']['description'] 	? '<p class="description fs-5 mb-2">'.wp_kses_post($product['heading']['description']).'</p>' 	: null; ?>
							</div>
							<div class="bottom lh-sm">
								<?php if ( $product['heading']['title'] && ! empty($product['review_link']) ) : ?>
									<a class="more" href="<?= esc_url($product['review_link']['url']); ?>">
										<i class="icon-down"></i><?= esc_attr($product['review_link']['title']); ?>
									</a>
								<?php else : ?>
									<a class="more" href="<?php the_permalink(); ?>#<?= sanitize_title_with_dashes($product['heading']['title']); ?>">
										<i class="icon-down"></i><?php _e('Jump to Review', 'wp-theme'); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
				
				<div class="d-flex align-items-center justify-content-end <?= $product['logo'] ? 'column-link' : 'col-lg-4'; ?> text-lg-end mt-4 mt-xl-0">
					<?= acf_link($product['link'], 'btn btn-primary btn-sm btn-price affiliate-link w-100'); ?>
				</div>
			
			</div>
			
			<?php endforeach; ?>
	</div>
<?php } else {
	echo '<pre class="text-center">TOP PRODUCTS LIST</pre>';
}