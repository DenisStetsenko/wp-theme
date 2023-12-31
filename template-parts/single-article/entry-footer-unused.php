<?php
$footer_content = get_field('footer_content');

if ( $footer_content['show_footer_content'] ) : ?>
	<section class="post-footer-content">
		
		<?php if ( $footer_content['content'] ) :
			echo '<div class="entry-content entry-content-headings-styling mobile-content-sm-size">';
				$footer_entry_content = preg_replace_callback("#<(h2)>(.*?)</\\1>#", "retitle", $footer_content['content']);
				echo apply_filters('the_content', $footer_entry_content);
			echo '</div>';
		endif; ?>
		
		<?php if ( $footer_content['faq'] && array_filter($footer_content['faq']) ) : ?>
			<div id="accordionFooter" class="accordion font-secondary mt-5">
				
				<?php $i = 1; foreach( $footer_content['faq'] as $item ) : ?>
					<div class="accordion-item rounded overflow-hidden">
						<h2 class="accordion-header" id="heading-<?= $i; ?>">
							<button class="accordion-button fw-bold <?= $i == 1 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $i; ?>" aria-expanded="<?= $i == 1 ? 'true' : 'false'; ?>" aria-controls="collapse-<?= $i; ?>">
								<?= wp_strip_all_tags($item['question']); ?>
							</button>
						</h2>
						<div id="collapse-<?= $i; ?>" class="accordion-collapse collapse <?= $i == 1 ? 'show' : ''; ?>" aria-labelledby="heading-<?= $i; ?>" data-bs-parent="#accordionFooter">
							<div class="accordion-body pt-0 ">
								<?= $item['answer'] ? '<div class="entry-content fs-4 font-secondary">' . apply_filters('the_content', $item['answer']) . '</div>' : null; ?>
							</div>
						</div>
					</div>
				<?php $i++; endforeach; ?>
				
			</div>
		<?php endif; ?>
	</section>
<?php endif; ?>