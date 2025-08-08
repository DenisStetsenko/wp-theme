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
class WP_Theme_Updater {
	/**
	 * Theme slug (directory name of the theme).
	 *
	 * @var string|mixed
	 */
	private string $theme_slug;
	
	/**
	 * GitHub username or organization name.
	 *
	 * @var string
	 */
	private string $github_user;
	
	/**
	 * GitHub repository name.
	 *
	 * @var string
	 */
	private string $github_repo;
	
	/**
	 * Base GitHub API endpoint for the repository.
	 *
	 * @var string
	 */
	private string $github_api;
	
	/**
	 * Direct URL to the theme ZIP file.
	 *
	 * @var string
	 */
	private string $github_zip;
	
	/**
	 * Currently installed theme version.
	 *
	 * @var string
	 */
	private string $version;
	
	/**
	 * Personal access token for GitHub (only required for private repos).
	 *
	 * @var string|null
	 */
	private ?string $token;
	
	/**
	 * Indicates whether the repository is private.
	 *
	 * @var bool
	 */
	private bool $is_private;
	
	public function __construct(string $theme_slug, string $github_user, string $github_repo, string $version, ?string $token = null, bool $is_private = false) {
		$this->theme_slug  = $theme_slug;
		$this->github_user = $github_user;
		$this->github_repo = $github_repo;
		$this->version     = $version;
		$this->is_private  = $is_private;
		if ( ! empty( $token ) ) {
			$this->token = $token;
		} elseif ( defined( 'GITHUB_TOKEN' ) ) {
			$this->token = GITHUB_TOKEN;
		} else {
			$this->token = '';
		}
		
		$this->github_api  = "https://api.github.com/repos/{$github_user}/{$github_repo}/releases/latest";
		$this->github_zip  = "https://github.com/{$github_user}/{$github_repo}/archive/refs/tags/";
		
		add_filter('http_request_args', [$this, 'add_auth_header'], 10, 2);
		add_filter('site_transient_update_themes', [$this, 'check_update']);
		add_filter('themes_api', [$this, 'theme_info'], 10, 3);
		add_filter('upgrader_post_install', [$this, 'update_theme_directory'], 10, 3);
	}
	
	private function request($url) {
		$headers = [ 'User-Agent' => 'WordPress Theme Updater' ];
		
		if ( ! empty( $this->token ) ) {
			$headers['Authorization'] = 'token ' . $this->token;
		}
		
		$response = wp_remote_get( $url, [ 'headers' => $headers ] );
		
		if ( is_wp_error( $response ) ) {
			error_log('GitHub API request failed: ' . $response->get_error_message());
			return false;
		}
		
		$response_code = wp_remote_retrieve_response_code($response);
		if ( $response_code === 403 ) {
			error_log( 'GitHub API rate limit exceeded.' );
			return false;
		}
		
		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			error_log('GitHub API response body empty');
			return false;
		}
		
		try {
			return json_decode( $body, false, 512, JSON_THROW_ON_ERROR );
		} catch (JsonException $e) {
			error_log('GitHub API JSON decode error: ' . $e->getMessage());
			return false;
		}
	}
	
	public function add_auth_header($args, $url) {
		if ( $this->is_private && ! empty( $this->token ) && str_contains( $url, "github.com/{$this->github_user}/{$this->github_repo}/zipball" ) ) {
			if ( ! isset( $args['headers'] ) ) {
				$args['headers'] = [];
			}
			$args['headers']['Authorization'] = 'token ' . $this->token;
		}
		
		return $args;
	}
	
	public function check_update( $transient ) {
		if ( empty( $transient->checked[ $this->theme_slug ] ) ) {
			return $transient;
		}
		
		// Cache API response for 12 hours
		$cache_key  = 'github_theme_update_' . $this->theme_slug;
		$data       = get_transient( $cache_key );
		
		if ( false === $data ) {
			$data = $this->request( $this->github_api );
			if ( $data ) {
				set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
			}
		}
		
		if ( ! $data || ! isset( $data->tag_name ) ) {
			return $transient;
		}
		
		$new_version = ltrim( $data->tag_name, 'v' );
		
		if ( version_compare( $this->version, $new_version, '<' ) ) {
			$package_url = $data->zipball_url ?? ($this->github_zip . $data->tag_name . '.zip');
			
			if ( empty( $package_url ) || ! filter_var( $package_url, FILTER_VALIDATE_URL ) ) {
				error_log( 'Invalid GitHub package URL: ' . $package_url );
				return $transient;
			}
			
			$transient->response[ $this->theme_slug ] = [
				'theme'       => $this->theme_slug,
				'new_version' => $new_version,
				'url'         => "https://github.com/{$this->github_user}/{$this->github_repo}",
				'package'     => $package_url
			];
		}
		
		return $transient;
	}
	
	public function theme_info( $res, $action, $args ) {
		if ( $action !== 'theme_information' || $args->slug !== $this->theme_slug ) {
			return $res;
		}
		
		return (object) [
			'name'     => ucfirst( str_replace( '-', ' ', $this->theme_slug ) ),
			'slug'     => $this->theme_slug,
			'version'  => $this->version,
			'homepage' => "https://github.com/{$this->github_user}/{$this->github_repo}"
		];
	}
	
	/**
	 * Fixes the theme directory name after update.
	 * Hooked into 'upgrader_post_install'.
	 */
	public function update_theme_directory( $true, $hook_extra, $result ) {
		global $wp_filesystem;
		
		// Check filesystem availability
		if ( ! $wp_filesystem || ! is_a( $wp_filesystem, 'WP_Filesystem_Base' ) ) {
			error_log( 'Filesystem not initialized' );
			return new WP_Error( 'fs_unavailable', __( 'Filesystem not available', 'wp-theme' ) );
		}
		
		// Only run for our theme
		if ( ! isset( $hook_extra['theme'] ) || $hook_extra['theme'] !== $this->theme_slug ) {
			return $true;
		}
		
		$theme_dir  = get_theme_root() . '/' . $this->theme_slug;
		$temp_dir   = $result['destination']; // Temporary GitHub-extracted dir
		$was_active = ( $this->theme_slug === get_option( 'stylesheet' ) );
		
		// Delete old theme (if it exists)
		if ( $wp_filesystem->exists( $theme_dir ) ) {
			$wp_filesystem->delete( $theme_dir, true );
		}
		
		// Rename temp dir to the correct theme slug
		$wp_filesystem->move( $temp_dir, $theme_dir );
		
		// Check if theme was active and reactivate it
		if ( $was_active ) {
			switch_theme( $this->theme_slug );
		}
		
		return $true;
	}
	
}