<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists('wp_custom_theme_setup') ) {
	/**
	 * Set wp theme defaults and register support for various WordPress features.
	 * @return void
	 */
  function wp_custom_theme_setup() {

    // By adding theme support, we declare that this theme does not use a hard-coded <title> tag
	  // in the document head, and expect WordPress to provide it for us.
    add_theme_support( 'title-tag' );

	  // Switch default core markup for search form, comment form, and comments to output valid HTML5.
	  add_theme_support( 'html5', array(
		  'search-form', 'navigation-widgets', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'
	  ) );

    // Add widget support shortcodes
    add_filter('widget_text', 'do_shortcode');

    // Support for Featured Images
    add_theme_support( 'post-thumbnails' );

		// Add support for core custom logo.
    add_theme_support( 'custom-logo', array(
      'height'      => 100,
      'width'       => 200,
      'flex-height' => true,
      'flex-width'  => true,
      'header-text' => [ 'site-title', 'site-description' ],
    ) );
	  
	  // Add support for core custom header.
	  add_theme_support( 'custom-header', array(
		  'default-image' => get_theme_file_uri('assets/images/default-header.png'),
		  'default-text-color'      => '000',
		  'width'                   => 1920,
		  'height'                  => 400,
		  'flex-width'              => true,
		  'flex-height'             => true
	  ) );
	  
	  // Add support for core custom background.
	  add_theme_support( 'custom-background', array(
		  'default-color' => '#ffffff',
	  ) );

    // This feature adds RSS feed links to HTML <head>
    add_theme_support( 'automatic-feed-links' );

    // Register Navigation Menu
    register_nav_menus( array(
      'header-primary'  => esc_html__( 'Header Menu', 'wp-theme' )
    ) );

	  // Add theme support WooCommerce
	  add_theme_support( 'woocommerce' );

    // Add support for Block Editor styles.
    add_theme_support( 'wp-block-styles' );
		add_theme_support( 'responsive-embeds' );
	  add_theme_support( 'align-wide' );
	  add_theme_support( 'custom-line-height' );
	  add_theme_support( 'link-color' );
	  add_theme_support( 'custom-spacing' );
	  add_theme_support( 'custom-units' );

    // Editor Style
    add_theme_support( 'editor-style' );
  }
}
add_action( 'after_setup_theme', 'wp_custom_theme_setup' );