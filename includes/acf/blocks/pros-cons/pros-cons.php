<?php
// ACF: Pros & Cons
$pros_and_cons = get_field('pros_and_cons');


// Support custom "anchor" values.
$anchor = "";
if ( ! empty( $block['anchor'] ) ) {
	$anchor = esc_attr( $block['anchor'] );
}

// Create class attribute allowing for custom "className" and "align" values.
$class_name = 'wp-block-pros-and-cons pros-and-cons d-sm-flex flex-wrap mt-6 mb-7 rounded border overflow-hidden font-secondary my-5';
if ( ! empty( $block['className'] ) )   $class_name .= ' ' . $block['className'];

if ( $pros_and_cons && array_filter($pros_and_cons) ) : ?>
	<div class="<?= esc_attr( $class_name ); ?>">
		
		<?php if ( $pros_and_cons['pros'] ) : ?>
			<div class="column pros">
				<div class="heading fw-bold font-secondary text-center"><span><?php _e('Pros', 'wp-theme'); ?></span></div>
				<ul class="list pros-list list-unstyled m-0">
					<?php foreach ($pros_and_cons['pros'] as $list_item) : ?>
						<li><span class="icon"></span><?= $list_item['list_item']; ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		
		<?php if ( $pros_and_cons['cons'] ) : ?>
			<div class="column cons">
				<div class="heading fw-bold font-secondary text-center"><span><?php _e('Cons', 'wp-theme'); ?></span></div>
				<ul class="list cons-list list-unstyled m-0">
					<?php foreach ($pros_and_cons['cons'] as $list_item) : ?>
						<li><span class="icon"></span><?= $list_item['list_item']; ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		
	</div>
<?php endif; ?>
