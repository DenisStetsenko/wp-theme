<?php
/**
 * Handles automatic WordPress theme updates from a GitHub repository.
 *
 * Connects to the GitHub API to fetch the latest release data, compares it with
 * the currently installed theme version, and integrates with WordPress' update
 * system to provide update notifications and install the new version.
 *
 * Supports public and private repositories (via personal access token).
 * Requires repository owner, repository name, and optional authentication token.
 */
class Updater {
	
	/**
	 * The repository.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $repo;
	
	/**
	 * Theme name.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $name;
	
	/**
	 * Theme slug.
	 *
	 * @access private
	 * @since 1.0
	 * @var string
	 */
	private $slug;
	
	/**
	 * Theme version.
	 *
	 * @access private
	 * @since 1.1
	 * @var string
	 */
	private $ver;
	
	
	/**
	 * The response from the API.
	 *
	 * @access private
	 * @since 1.0
	 * @var array
	 */
	private $response;
	
	/**
	 * Constructor.
	 *
	 * @access public
	 * @since 1.0
	 * @param array $args The arguments for this theme.
	 */
	public function __construct( $args ) {
		$this->name = $args['name'];
		$this->slug = $args['slug'];
		$this->repo = $args['repo'];
		$this->ver  = $args['ver'];
		
		$this->response = $this->get_response();
		// Check for theme updates.
		add_filter( 'http_request_args', [ $this, 'update_check' ], 5, 2 );
		// Inject theme updates into the response array.
		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'update_themes' ] );
		add_filter( 'pre_set_transient_update_themes', [ $this, 'update_themes' ] );
	}
	
	/**
	 * Gets the releases URL.
	 *
	 * @access private
	 * @since 1.0
	 * @return string
	 */
	private function get_releases_url() {
		return 'https://api.github.com/repos/' . $this->repo . '/releases';
	}
	
	/**
	 * Get the response from the Github API.
	 *
	 * @access private
	 * @since 1.0
	 * @return array
	 */
	private function get_response() {
		// Check transient.
		$cache = get_site_transient( md5( $this->get_releases_url() ) );
		if ( $cache ) {
			return $cache;
		}
		$response = wp_remote_get( $this->get_releases_url() );
		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$response = json_decode( wp_remote_retrieve_body( $response ), true, 512, JSON_THROW_ON_ERROR );
			set_site_transient( md5( $this->get_releases_url() ), $response, 12 * HOUR_IN_SECONDS );
		}
	}
	
	/**
	 * Get the new version file.
	 *
	 * @access private
	 * @since 1.0
	 * @return string
	 */
	private function get_latest_package() {
		if ( ! $this->response ) {
			return false;
		}
		foreach ( $this->response as $release ) {
			if ( isset( $release['assets'] ) && isset( $release['assets'][0] ) && isset( $release['assets'][0]['browser_download_url'] ) ) {
				return $release['assets'][0]['browser_download_url'];
			}
		}
	}
	
	/**
	 * Get the new version.
	 *
	 * @access private
	 * @since 1.0
	 * @return string
	 */
	private function get_latest_version() {
		if ( ! $this->response ) {
			return false;
		}
		foreach ( $this->response as $release ) {
			if ( isset( $release['tag_name'] ) ) {
				return str_replace( 'v', '', $release['tag_name'] );
			}
		}
	}
	
	/**
	 * Disables requests to the wp.org repository for this theme.
	 *
	 * @since 1.0
	 *
	 * @param array  $request An array of HTTP request arguments.
	 * @param string $url The request URL.
	 * @return array
	 */
	public function update_check( $request, $url ) {
		if ( str_contains( $url, '//api.wordpress.org/themes/update-check/1.1/' ) ) {
			$data = json_decode( $request['body']['themes'], false, 512, JSON_THROW_ON_ERROR );
			unset( $data->themes->{$this->slug} );
			$request['body']['themes'] = wp_json_encode( $data );
		}
		return $request;
	}
	
	/**
	 * Inject update data for this theme.
	 *
	 * @since 1.0
	 *
	 * @param object $transient The pre-saved value of the `update_themes` site transient.
	 * @return object
	 */
	public function update_themes( $transient ) {
		if ( isset( $transient->checked ) ) {
			$current_version = $this->ver;
			
			if ( version_compare( $current_version, $this->get_latest_version(), '<' ) ) {
				$transient->response[ $this->name ] = [
					'theme'       => $this->name,
					'new_version' => $this->get_latest_version(),
					'url'         => 'https://github.com/' . $this->repo . '/releases',
					'package'     => $this->get_latest_package(),
				];
			}
		}
		return $transient;
	}
}


if ( ! function_exists('wp_custom_theme_update') ) {
	/**
	 * Check for updates
	 * @return void
	 */
	function wp_custom_theme_update() {
		$theme = wp_get_theme();
		
		new Updater(
			[
				'name' => 'WP Theme',                  // Theme Name.
				'repo' => 'DenisStetsenko/wp-theme',   // Theme repository.
				'slug' => 'wp-theme',                  // Theme Slug.
				'ver'  => $theme->parent() ? $theme->parent()->get('Version') :  $theme->get('Version') // Theme Version.
			]
		);
		
	}
}
add_action( 'after_setup_theme', 'wp_custom_theme_update' );
