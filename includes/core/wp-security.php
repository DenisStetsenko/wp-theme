<?php
/**
 * Enhanced WordPress Security Configuration
 *
 * This file implements basic security hardening measures for WordPress installations
 */
defined( 'ABSPATH' ) || exit;

/**
 * Harden login error messages (prevent username discovery)
 */
add_filter( 'login_errors', static function() {
	return '<strong>ERROR</strong>: Stop guessing!';
});


/**
 * Remove WordPress version from various locations
 */
add_filter( 'the_generator', '__return_empty_string' );


/**
 * Hide WP version from RSS feeds
 */
add_filter( 'the_generator', function( $gen, $type ) {
	return ( $type === 'html' ) ? '' : $gen;
}, 10, 2 );


/**
 * Disable use XML-RPC & X-Pingback
 */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'wp_headers', function( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
});
