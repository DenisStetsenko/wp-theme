<?php
// Theme Updater
include get_theme_file_path('/includes/core/wp-theme-updater.php');

// Theme Setup
include get_theme_file_path('/includes/core/wp-theme-setup.php');

// WordPress Security
include get_theme_file_path('/includes/core/wp-security.php');


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