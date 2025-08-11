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
	  add_theme_support( 'html5', [
		  'search-form', 'navigation-widgets', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'
	  ] );

    // Add widget support shortcodes
    add_filter('widget_text', 'do_shortcode');

    // Support for Featured Images
    add_theme_support( 'post-thumbnails' );

		// Add support for core custom logo.
    add_theme_support( 'custom-logo', [
      'height'      => 100,
      'width'       => 200,
      'flex-height' => true,
      'flex-width'  => true,
      'header-text' => [ 'site-title', 'site-description' ],
    ] );
	  
	  // Add support for core custom header.
	  add_theme_support( 'custom-header', [
		  'default-image' => get_theme_file_uri('assets/images/default-header.png'),
		  'default-text-color'      => '000',
		  'width'                   => 1920,
		  'height'                  => 400,
		  'flex-width'              => true,
		  'flex-height'             => true
	  ] );
	  
	  // Add support for core custom background.
	  add_theme_support( 'custom-background', [
		  'default-color' => '#ffffff',
	  ]);

    // This feature adds RSS feed links to HTML <head>
    add_theme_support( 'automatic-feed-links' );

    // Register Navigation Menu
    register_nav_menus( [
      'header-primary'  => esc_html__( 'Header Menu', 'wp-theme' ),
      'footer'          => esc_html__( 'Footer Menu', 'wp-theme' ),
			'social' 	        => esc_html__( 'Social Accounts', 'wp-theme' ),
    ] );

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
	  $editor_stylesheet_path = 'assets/styles/css/editor-style.css';
	  $editor_stylesheet_uri  = get_theme_file_uri( $editor_stylesheet_path ) . '?ver=' . filemtime( get_theme_file_path( $editor_stylesheet_path ) );
	  
		add_editor_style( [ wp_custom_google_fonts_url(), $editor_stylesheet_uri ] );

    // remove render gutenberg svg_filters junk
    remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
    remove_action( 'wp_body_open', 'gutenberg_global_styles_render_svg_filters' );
	  
	  // Remove feed icon link from legacy RSS widget.
	  add_filter( 'rss_widget_feed_link', '__return_empty_string' );
  }
}
add_action( 'after_setup_theme', 'wp_custom_theme_setup' );


if ( ! function_exists( 'wp_custom_block_editor_styles' ) ) {
	/**
	 * Block Editor Custom styling
	 * @return void
	 */
	function wp_custom_block_editor_styles() {
		$editor_stylesheet_path = 'assets/styles/css/block-editor.css';
	  wp_enqueue_style( 'wp-custom-editor-styles',
	    get_theme_file_uri( $editor_stylesheet_path ), [], filemtime( get_theme_file_path( $editor_stylesheet_path ) )
	  );
	}
}
add_action('enqueue_block_editor_assets', 'wp_custom_block_editor_styles');