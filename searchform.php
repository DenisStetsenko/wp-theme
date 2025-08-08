<?php
/**
 * The searchform.php template.
 *
 * Used any time that get_search_form() is called.
 *
 * @link https://developer.wordpress.org/reference/functions/wp_unique_id/
 * @link https://developer.wordpress.org/reference/functions/get_search_form/
 */

$uid 				= wp_unique_id( 's-' );
$aria_label = ( isset( $args['aria_label'] ) && $args['aria_label'] !== '' )
						? 'aria-label="' . esc_attr( $args['aria_label'] ) . '"'
						: '';
?>

<form role="search" method="GET" class="search-form" action="<?php echo esc_url( home_url( '/' ) ) ?>" <?php echo $aria_label ?>>
	<label for="<?php echo esc_attr( $uid ) ?>" class="sr-only">
		<?php echo esc_html_x( 'Search for:', 'label', 'wp-theme' ) ?>
	</label>

	<div class="flex rounded-md shadow-sm overflow-hidden">
		<input type="search" id="<?php echo esc_attr($uid) ?>" name="s" value="<?php echo esc_attr(get_search_query()) ?>"
					 placeholder="<?php echo esc_attr_x( 'Search…', 'placeholder', 'wp-theme' ) ?>" />

		<button type="submit">
			<?php echo esc_html_x( 'Search', 'submit button', 'wp-theme' ) ?>
		</button>
	</div>
</form>