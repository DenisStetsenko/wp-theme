<?php
// ACF: Product Card
$product_card_heading = get_field('heading');
$product_card_image   = get_field('hero_image');
$product_card_link   	= get_field('affilate_link');
$best 								= $product_card_heading['mark_as_best'];
$best_label 					= $best ? 'best' : '';

// Support custom "anchor" values.
if ( ! empty( $block['anchor'] ) ) {
	$anchor = esc_attr( $block['anchor'] );
} else {
	$anchor = acf_uniqid("acf-product-card");
}

// Create class attribute allowing for custom "className" and "align" values.
$class_name = 'wp-block-acf-product-card-item product-card my-5';
if ( ! empty( $block['className'] ) )   $class_name .= ' ' . $block['className'];

if ( ! empty( $product_card_heading && array_filter($product_card_heading) ) ) { ?>
	
	<div class="<?= esc_attr( $class_name ); ?>">
		<!-- PRODUCT HEADING -->
		<?php
		$title_tag 							= $product_card_heading['title_tag'];
		$original_product_title = $product_card_heading['title']; ?>
		<header class="product-heading mb-4">
			<?= "<$title_tag class=\"title m-0\">"; ?>
				<?php
				if ( preg_match('/^\d+\./', $original_product_title) ) {
					$product_title 	= preg_replace('/^\d+\.\s*/', '', $original_product_title); // Use preg_replace to remove the number and dot from the beginning of the string.
					$number 				= preg_replace('/^(\d+\.\s*).*/', '$1', $original_product_title); // Use preg_replace to keep only the number and dot at the beginning of the string.
					echo $number ? '<span class="number">'. $number .'</span>' : "";
				} else {
					$product_title = $original_product_title;
				}
				
				if ( $product_card_link ) {
					$link_target = $product_card_link['target'] ? 'target="_blank" rel="nofollow sponsored"' : '';
					echo '<a class="affiliate-link" href="'. esc_url($product_card_link['url']) .'" '.$link_target.'>'. sanitize_text_field($product_title) . '</a>';
				} else {
					echo sanitize_text_field($product_title);
				}
				?>
			<?= "</$title_tag>"; ?>
			<?= $product_card_heading['subtitle'] ? '<p class="subtitle mt-3 font-secondary text-black fw-bold text-uppercase fs-4 ls-lg">'. $product_card_heading['subtitle'].'</p>' 	: null; ?>
		</header>
		<!-- / PRODUCT HEADING -->
		
		<!-- PRODUCT BIG IMAGE -->
		<?php if ( $product_card_image ) {
			echo '<figure class="post-thumbnail figure position-relative bg-white rounded overflow-hidden w-100 d-flex align-items-center justify-content-center mb-5 '.$best_label.'">';
			echo '<img class="figure-img img-fluid w-100" src="'. $product_card_image['sizes']['large'] .'" loading="lazy" alt="'. esc_attr($product_card_image['alt']) .'">';
			echo $product_card_image['caption'] ? '<figcaption class="figure-caption font-secondary text-gray fs-6">' . esc_attr($product_card_image['caption']) . '</figcaption>' : '';
			echo '</figure>';
		} ?>
		<!-- / PRODUCT BIG IMAGE -->
	</div>

<?php } else {
	echo '<pre class="text-center">PRODUCT CARD</pre>';
}