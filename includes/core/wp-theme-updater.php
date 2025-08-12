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
 *
 * !!! NOTES !!!
 * define( 'WP_DISABLE_PARENT_THEME_UPDATE', true );            => will completely disable WP Theme Updates checker
 * define( 'WP_THEME_UPDATER_THEME_SLUG', 'my-custom-theme' );  => set the custom theme slug
 * define( 'WP_THEME_UPDATER_GITHUB_REPO', 'my-custom-theme' ); => set the custom GitHub repository slug
 * define( 'WP_THEME_UPDATER_GITHUB_USER', 'newGitHubUser' );   => set the custom GitHub user
 * define( 'WP_THEME_UPDATER_GITHUB_TOKEN', 'xxxYYYzzz' );      => set the custom GitHub token (for private repository)
 * define( 'WP_THEME_UPDATER_IS_PRIVATE_REPO', true );          => switch to Private GitHub repository
 */
class WP_Theme_Updater {
	
	// Added static instance for singleton pattern (prevents memory leaks)
	private static ?self $instance = null;
	
	// Constants
	private const CACHE_DURATION  = 600; // 10 minutes
	private const API_TIMEOUT     = 30;
	
	// Vars
	private string  $theme_slug;
	private string  $github_user;
	private string  $github_repo;
	private string  $github_api;
	private string  $github_zip;
	private string  $version;
	private ?string $token;
	private bool    $is_private;
	
	// Use Private constructor to all only instance via ::getInstance()
	private function __construct( array $config = [] ) {
		
		if ( defined( 'WP_DISABLE_PARENT_THEME_UPDATE' ) && WP_DISABLE_PARENT_THEME_UPDATE ) {
			return;
		}
		
		// Set all properties from config/constants
		$this->set_configuration( $config );
		
		// Validate required fields
		if ( empty( $this->theme_slug ) || empty( $this->github_user ) || empty( $this->github_repo ) ) {
			error_log( '[WP_Theme_Updater] Missing required configuration: theme_slug, github_user, or github_repo' );
			return;
		}
		
		// Validate if theme exists
		$theme = wp_get_theme( $this->theme_slug );
		if ( ! $theme->exists() ) {
			error_log( "[WP_Theme_Updater] Theme '{$this->theme_slug}' does not exist. Nothing to update." );
			return;
		}
		
		// Set version and GitHub URLs
		$this->version    = $theme->parent() ? $theme->parent()->get( 'Version' ) : $theme->get( 'Version' );
		$this->github_api = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
		$this->github_zip = "https://github.com/{$this->github_user}/{$this->github_repo}/archive/refs/tags/";
		
		// Validate repository and releases exist before proceeding
		if ( ! $this->validate_repository() ) {
			return;
		}
		
		// Register hooks only in appropriate contexts
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			$this->register_hooks();
		}
		
		// Deletes our transients if we're force-checking the updater
		$this->clear_transient_forced();
	}
	
	/**
	 * Singleton Pattern
	 *
	 * @param array $config
	 * @return WP_Theme_Updater|null
	 */
	public static function getInstance( array $config = [] ): ?self {
		if ( self::$instance === null ) {
			self::$instance = new self( $config );
			
			// If initialization failed (missing config), return null
			if ( empty( self::$instance->theme_slug ) ) {
				self::$instance = null;
				
				return null;
			}
		}
		
		return self::$instance;
	}
	
	
	/**
	 * Register WordPress hooks
	 */
	private function register_hooks(): void {
		add_filter( 'http_request_args', [ $this, 'disable_native_wp_update_check' ], 5, 2 );
		add_filter( 'http_request_args', [ $this, 'add_auth_header' ], 10, 2 );
		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'check_theme_updates' ] );
		add_filter( 'upgrader_post_install', [ $this, 'fix_theme_directory' ], 10, 3 );
		add_action( 'upgrader_process_complete', [ $this, 'restore_active_theme' ], 10, 2 );
	}
	
	
	/**
	 * Set configuration from config array and constants
	 */
	private function set_configuration( array $config ): void {
		$this->theme_slug   = $this->get_config_value( $config, 'theme_slug', 'WP_THEME_UPDATER_THEME_SLUG' );
		$this->github_user  = $this->get_config_value( $config, 'github_user', 'WP_THEME_UPDATER_GITHUB_USER' );
		$this->github_repo  = $this->get_config_value( $config, 'github_repo', 'WP_THEME_UPDATER_GITHUB_REPO' );
		$this->token        = $this->get_config_value( $config, 'token', 'WP_THEME_UPDATER_GITHUB_TOKEN' ) ?: null;
		$this->is_private   = (bool) $this->get_config_value( $config, 'is_private', 'WP_THEME_UPDATER_IS_PRIVATE_REPO', false );
		
		// Sanitize inputs
		$this->github_user = sanitize_text_field( $this->github_user );
		$this->github_repo = sanitize_text_field( $this->github_repo );
		$this->theme_slug  = sanitize_file_name( $this->theme_slug );
		
		if ( $this->token ) {
			$this->token = sanitize_text_field( $this->token );
		}
	}
	
	
	/**
	 * Get configuration value from array or constant
	 */
	private function get_config_value( array $config, string $key, string $constant_name, $default = '' ) {
		
		if ( defined( $constant_name ) ) {
			return constant( $constant_name );
		}
		
		if ( ! empty( $config[ $key ] ) ) {
			return $config[ $key ];
		}
		
		return $default;
	}
	
	
	/**
	 * Validate GitHub repository and check if releases exist
	 */
	private function validate_repository(): bool {
		$cache_key      = 'wp_github_theme_repository_' . $this->theme_slug;
		$cached_result  = get_transient( $cache_key );
		
		if ( $cached_result !== false ) {
			return (bool) $cached_result;
		}
		
		$args = [
			'headers' => [
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress Theme Updater'
			],
			'timeout' => self::API_TIMEOUT,
		];
		if ( ! empty( $this->token ) ) {
			$args['headers']['Authorization'] = 'Bearer ' . $this->token;
		}
		
		$response = wp_remote_get( $this->github_api, $args );
		if ( is_wp_error( $response ) ) {
			error_log( '[WP_Theme_Updater] Error checking repository.' );
			$this->cache_validation_result( $cache_key, false, self::CACHE_DURATION ); // Cache failure for 10 minutes
			return false;
		}
		
		$response_code = wp_remote_retrieve_response_code( $response );
		
		if ( $response_code === 403 ) {
			error_log(sprintf(
				'[WP_Theme_Updater] GitHub API rate limit of "%1$d" requests exceeded. Limit resets at %2$s UTC timezone.',
				wp_remote_retrieve_header($response, 'x-ratelimit-limit'),
				date('F j, Y H:i:s', wp_remote_retrieve_header($response, 'x-ratelimit-reset'))
			));
			$this->cache_validation_result( $cache_key, false, self::CACHE_DURATION ); // Cache failure for 10 minutes
			return false;
		}
		
		if ( $response_code !== 200 ) {
			error_log( "[WP_Theme_Updater] Repository check failed with status: {$response_code}" );
			$this->cache_validation_result( $cache_key, false, self::CACHE_DURATION ); // Cache failure for 10 minutes
			return false;
		}
		
		try {
			$data = json_decode( wp_remote_retrieve_body( $response ), true, 512, JSON_THROW_ON_ERROR );
			
			if ( empty( $data['tag_name'] ) ) {
				error_log( '[WP_Theme_Updater] Release data missing tag_name' );
				$this->cache_validation_result( $cache_key, false, HOUR_IN_SECONDS ); // Longer cache for "no releases"
				return false;
			}
			
		} catch ( JsonException ) {
			error_log( '[WP_Theme_Updater] Invalid JSON in repository response.' );
			$this->cache_validation_result( $cache_key, false, self::CACHE_DURATION ); // Cache failure for 10 minutes
			return false;
		}
		
		
		// Success - cache for longer
		$this->cache_validation_result( $cache_key, true, 12 * HOUR_IN_SECONDS );
		return true;
	}
	
	
	/**
	 * Cache validation result with appropriate duration
	 */
	private function cache_validation_result( string $cache_key, bool $result, int $duration ): void {
		set_transient( $cache_key, $result ? 1 : 0, $duration );
	}
	
	
	/**
	 * Appending ?access_token= is deprecated by GitHub. For private repos, you must use the Authorization: token <TOKEN> header.
	 * @param $args
	 * @param $url
	 *
	 * @return mixed
	 */
	public function add_auth_header( $args, $url ) {
		if ( empty( $this->token ) ) {
			return $args;
		}
		
		// only add for github urls
		if ( ! str_contains( $url, 'github.com' ) && ! str_contains( $url, 'api.github.com' ) ) {
			return $args;
		}
		
		if ( ! isset( $args['headers'] ) || ! is_array( $args['headers'] ) ) {
			$args['headers'] = [];
		}
		
		// prefer token header for all GitHub requests
		$args['headers']['Authorization'] = 'Bearer ' . $this->token;
		
		return $args;
	}
	
	
	/**
	 * Trigger theme update check
	 * @param $transient
	 *
	 * @return mixed
	 */
	public function check_theme_updates( $transient ) {
		if ( empty( $transient->checked[ $this->theme_slug ] ) ) {
			return $transient;
		}
		
		// Cache API response for 10 minutes (adjust duration as needed).
		$cache_key  = 'wp_github_theme_update_' . $this->theme_slug;
		$data       = get_transient( $cache_key );
		
		if ( false === $data ) {
			$args = [
				'headers' => [
					'Accept'     => 'application/vnd.github.v3+json',
					'User-Agent' => 'WordPress Theme Updater'
				],
				'timeout' => self::API_TIMEOUT,
			];
			if ( ! empty( $this->token ) ) {
				$args['headers']['Authorization'] = 'Bearer ' . $this->token;
			}
			
			$response = wp_remote_get( $this->github_api, $args );
			if ( is_wp_error( $response ) ) {
				error_log( '[WP_Theme_Updater] Error fetching GitHub API.' );
				return $transient;
			}
			
			$response_code =  wp_remote_retrieve_response_code( $response );
			
			if ( $response_code === 403 ) {
				error_log(sprintf(
					'[WP_Theme_Updater] GitHub API rate limit of "%1$d" requests exceeded. Limit resets at %2$s UTC timezone.',
					wp_remote_retrieve_header($response, 'x-ratelimit-limit'),
					date('F j, Y H:i:s', wp_remote_retrieve_header($response, 'x-ratelimit-reset'))
				));
				return $transient;
			}
			
			if ( $response_code === 404 ) {
				error_log( '[WP_Theme_Updater] GitHub Repository Not Found.', );
				return $transient;
			}
			
			try {
				$data = json_decode( wp_remote_retrieve_body( $response ), true, 512, JSON_THROW_ON_ERROR );
			} catch ( JsonException $e ) {
				error_log( '[WP_Theme_Updater] JSON decode error: ' . $e->getMessage() );
				return $transient;
			}
			
			if ( empty( $data ) || ! is_array( $data ) ) {
				error_log('[WP_Theme_Updater] Empty or invalid GitHub API response.');
				return $transient;
			}
			
			set_transient( $cache_key, $data, self::CACHE_DURATION );
		}
		
		if ( empty( $data['tag_name'] ) ) {
			error_log( "[WP_Theme_Updater] GitHub API response missing 'tag_name'." );
			return $transient;
		}
		
		$new_version = ltrim( $data['tag_name'], 'v' );
		if ( version_compare( $this->version, $new_version, '<' ) ) {
			$package_url = $data['zipball_url'] ?? ( $this->github_zip . $data['tag_name'] . '.zip' );
			
			if ( empty( $package_url ) || ! filter_var( $package_url, FILTER_VALIDATE_URL ) ) {
				error_log( 'Invalid GitHub package URL: ' . $package_url );
				return $transient;
			}
			
			$transient->response[ $this->theme_slug ] = [
				'theme'       => $this->theme_slug,
				'new_version' => $new_version,
				'url'         => '',
				'package'     => $package_url
			];
			
		}
		
		return $transient;
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
	public function disable_native_wp_update_check( $request, $url ): array {
		if ( str_contains( $url, '//api.wordpress.org/themes/update-check/1.1/' ) ) {
			
			try {
				$data = json_decode( $request['body']['themes'], false, 512, JSON_THROW_ON_ERROR );
			} catch ( JsonException $e ) {
				error_log( '[WP_Theme_Updater] JSON decode error: ' . $e->getMessage() );
				return $request;
			}
			
			unset( $data->themes->{$this->theme_slug} );
			$request['body']['themes'] = wp_json_encode( $data );
		}
		
		return $request;
	}
	
	
	/**
	 * Fixes the theme directory name after update.
	 * Keep new version in a backup folder
	 * This prevents leaving site with broken theme if move fails.
	 * Hooked into 'upgrader_post_install'.
	 */
	public function fix_theme_directory($response, $hook_extra, $result) {
		// Only run for our theme
		if ( ! isset( $hook_extra['theme'] ) || $hook_extra['theme'] !== $this->theme_slug ) {
			return $response;
		}
		
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			WP_Filesystem();
		}
		
		if ( ! $wp_filesystem ) {
			error_log( '[WP_Theme_Updater] WP_Filesystem not available' );
			return $response;
		}
		
		$theme_dir  = wp_normalize_path( get_theme_root() . '/' . $this->theme_slug );
		$github_dir = wp_normalize_path( $result['destination'] );
		
		// nothing to do, clear cache only
		if ( $theme_dir === $github_dir ) {
			$this->clear_transient();
			return $response;
		}
		
		$backup_dir = $theme_dir . '-backup-' . time();
		
		// if current theme exists, move it to backup
		if ( $wp_filesystem->exists( $theme_dir ) && ! $wp_filesystem->move( $theme_dir, $backup_dir ) ) {
			error_log("[WP_Theme_Updater] Failed to move existing theme to backup: {$theme_dir} -> {$backup_dir}");
			return $response;
		}
		
		// move new extracted folder into place
		if ( ! $wp_filesystem->move( $github_dir, $theme_dir ) ) {
			error_log("[WP_Theme_Updater] Failed to move new theme into place: {$github_dir} -> {$theme_dir}");
			// try to restore backup
			if ( $wp_filesystem->exists( $backup_dir ) ) {
				$restored = $wp_filesystem->move( $backup_dir, $theme_dir );
				$message  = $restored ? 'Successfully restored backup.' : 'CRITICAL: Failed to restore backup!';
				error_log("[WP_Theme_Updater] {$message}");
			}
			return $response;
		}
		
		// new theme moved successfully, remove backup
		if ( $wp_filesystem->exists( $backup_dir ) ) {
			$wp_filesystem->delete( $backup_dir, true );
		}
		
		// clear caches and transients
		$this->clear_transient();
		
		return $response;
	}
	
	/**
	 * To ensure it remains active, you can explicitly store the active theme slug before update and re-set it after update.
	 * @param $upgrader
	 * @param $hook_extra
	 *
	 * @return void
	 */
	public function restore_active_theme($upgrader, $hook_extra) {
		if ( isset( $hook_extra['type'], $hook_extra['themes'] )
		     && $hook_extra['type'] === 'theme'
		     && in_array( $this->theme_slug, (array) $hook_extra['themes'], true ) ) {
			
			$current = get_option('stylesheet');
			if ( $current === $this->theme_slug ) {
				return;
			}
			
			$active_theme = wp_get_theme( $current );
			$parent = $active_theme->parent();
			if ( $parent && $parent->get_stylesheet() === $this->theme_slug ) {
				// child of updated parent -- do not switch
				return;
			}
			
			// Only switch if WP set a default (or different) theme
			switch_theme( $this->theme_slug );
			$this->clear_transient();
		}
	}
	
	
	/**
	 * Clears our transient and object cache after updating
	 */
	public function clear_transient(): void {
		delete_transient( 'wp_github_theme_update_' . $this->theme_slug );
		delete_transient( 'wp_github_theme_repository_' . $this->theme_slug );
		delete_site_transient('update_themes');
		wp_cache_delete('update_themes', 'site-transient');
		wp_clean_themes_cache();
	}
	
	/**
	 * Clears the transient when forced from the upgrader
	 */
	private function clear_transient_forced(): void {
		global $pagenow;
		
		if ( 'update-core.php' === $pagenow && isset($_GET['force-check']) ) {
			$this->clear_transient();
		} elseif ( 'themes.php' === $pagenow && current_user_can('update_themes') ) {
			$this->clear_transient();
		}
	}
	
}


if ( ! function_exists( 'wp_custom_theme_update' ) ) {
	/**
	 * Check for updates
	 * @return void
	 */
	function wp_custom_theme_update(): void {
		// Only load when actually needed
		if ( ! is_admin() && ! wp_doing_cron() && ! wp_doing_ajax() ) {
			return;
		}
		
		// Only instantiate once
		static $initialized = false;
		if ( $initialized ) {
			return;
		}
		$initialized = true;
		
		// Check for the theme updates
		$updater = WP_Theme_Updater::getInstance( [
			'theme_slug'  => 'wp-theme',
			'github_user' => 'DenisStetsenko',
			'github_repo' => 'wp-theme'
		] );
		
		if ( null === $updater ) {
			error_log( '[WP_Theme_Updater] Failed to initialize theme updater - check configuration!' );
		}
		
	}
}
add_action( 'after_setup_theme', 'wp_custom_theme_update' );