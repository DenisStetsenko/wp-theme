<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wp_custom_theme_add_my_tc_button' ) ) {
	/**
	 * Add TinyMCE Buttons
	 * @return void
	 */
  function wp_custom_theme_add_my_tc_button() {

    // Check user permissions
    if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) {
	    return;
    }

    // Check if WYSIWYG is enabled
    if ( get_user_option('rich_editing') === 'true') {
      add_filter('mce_external_plugins', 'wp_custom_theme_add_tinymce_plugin');
    }

    // Enqueue the CSS files if using custom icons via the CSS property background-image
	  $css_path = 'assets/styles/css/tinymce-buttons.css';
    wp_enqueue_style( 'wp_custom_theme_tc_button_css',  get_theme_file_uri( $css_path ), [], filemtime( get_theme_file_path( $css_path ) ) );
  }
}
add_action('admin_enqueue_scripts', 'wp_custom_theme_add_my_tc_button');


if ( ! function_exists( 'wp_custom_theme_add_tinymce_plugin' ) ) {
	/**
	 * Create the custom TinyMCE plugins
	 * @param $plugin_array
	 *
	 * @return mixed
	 */
  function wp_custom_theme_add_tinymce_plugin($plugin_array) {
	  $script_path = get_theme_file_uri( 'assets/scripts/tinymce-buttons.js' );
    $plugin_array['wp_custom_theme_tc_button'] = apply_filters( 'wp_custom_theme_add_tinymce_plugin', $script_path );
    
		return $plugin_array;
  }
}

if ( ! function_exists( 'wp_custom_wysiwyg_toolbars' ) ) {
	/**
	 * Add the buttons to the TinyMCE array of buttons that display, so they appear in the WYSIWYG editor
	 * @param $buttons
	 *
	 * @return mixed
	 */
  function wp_custom_wysiwyg_toolbars( $buttons ){
		
    $buttons[] = 'wp_custom_theme_tc_button';

    return $buttons;
  }
}
add_filter( 'mce_buttons' , 'wp_custom_wysiwyg_toolbars'  );