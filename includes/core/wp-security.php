<?php
defined( 'ABSPATH' ) || exit;

/**
 * Harden login error messages (prevent username discovery)
 */
add_filter( 'login_errors', static function() {
	return '<strong>ERROR</strong>: Stop guessing!';
});


/**
 * Removes the generator tag with WP version numbers. Hackers will use this to find weak and old WP installs
 */
add_filter( 'the_generator', '__return_empty_string' );


/**
 * Disable use XML-RPC & X-Pingback
 */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'wp_headers', function( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
});
