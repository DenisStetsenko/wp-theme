<?php
// Theme Updater
include get_theme_file_path('/includes/core/wp-theme-updater.php');

// Theme Setup
include get_theme_file_path('/includes/core/wp-theme-setup.php');

// WordPress Security
include get_theme_file_path('/includes/core/wp-security.php');

// WordPress Helpers
include get_theme_file_path('/includes/core/wp-helpers.php');

// WordPress Editor Customizer
include get_theme_file_path('/includes/core/wp-tinymce-editor.php');

// Load Hero Icons
include get_theme_file_path('/includes/core/wp-hero-icons.php');


if ( ! function_exists( 'wp_custom_scripts_and_styles' ) ) {
	/**
	 * Enqueue Scripts and Styles for Front-End
	 */
  function wp_custom_scripts_and_styles(){
    wp_enqueue_script('jquery');
	  
	  // Fix to: Does not use passive listeners to improve scrolling performance
	  wp_add_inline_script( 'jquery',
		  'jQuery.event.special.touchstart = {
				setup: function (_, ns, handle) {
					this.addEventListener("touchstart", handle, {passive: !ns.includes("noPreventDefault")});
				}
			};
			jQuery.event.special.touchmove = {
				setup: function (_, ns, handle) {
					this.addEventListener("touchmove", handle, {passive: !ns.includes("noPreventDefault")});
				}
			};
			jQuery.event.special.wheel = {
				setup: function (_, ns, handle) {
					this.addEventListener("wheel", handle, {passive: true});
				}
			};
			jQuery.event.special.mousewheel = {
				setup: function (_, ns, handle) {
					this.addEventListener("mousewheel", handle, {passive: true});
				}
			};'
	  );
	  // END OF Fix to: Does not use passive listeners to improve scrolling performance

    // Special script for comments
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
      wp_enqueue_script( 'comment-reply' );
    }
		
  }
}
add_action('wp_enqueue_scripts', 'wp_custom_scripts_and_styles');


if ( ! function_exists( 'wp_custom_google_fonts_url' ) ) {
	/**
	 * Get Google Fonts URL
	 * @return string
	 */
	function wp_custom_google_fonts_url() {
		return apply_filters(
			'wp_custom_google_fonts_url',
			esc_url( 'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap' )
		);
	}
}


if ( ! function_exists( 'wp_svg_icon' ) ) {
	/**
	 * Inline SVG icon
	 */
	function wp_svg_icon( $icon ) {
		$output = ''; // Always return a string
		
		if ( ! $icon ) {
			return $output;
		}
		
		// If it looks like an SVG URL
		if ( str_ends_with( $icon, '.svg' ) ) {
			$response = wp_safe_remote_get( $icon, [
				'headers' => [
					'Referer' => home_url( $_SERVER['REQUEST_URI'] ),
				],
				'timeout' => 10,
			] );
			if ( is_wp_error( $response ) ) {
				// Fallback: use <img> if request failed
				$output = sprintf( '<img class="img-fluid" src="%s" alt="icon" />', esc_url( $icon ) );
			} else {
				$code = wp_remote_retrieve_response_code( $response );
				$body = wp_remote_retrieve_body( $response );
				
				if ( $code === 200 && $body ) {
					$output = $body;
				} else {
					$output = sprintf( '<img class="img-fluid" src="%s" alt="icon" />', esc_url( $icon ) );
				}
			}
		}
		else {
			$output = sprintf( '<img class="img-fluid" src="%s" alt="icon" />', esc_url( $icon ) );
		}
		
		return $output;
	}
}