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
	
	private string  $theme_slug;
	private string  $github_user;
	private string  $github_repo;
	private string  $github_api;
	private string  $github_zip;
	private string  $version;
	private ?string $token;
	private bool    $is_private;
	
	public function __construct( array $config ) {
		$this->theme_slug  = $config['theme_slug']  ?? '';
		$this->github_user = $config['github_user'] ?? '';
		$this->github_repo = $config['github_repo'] ?? '';
		$this->is_private  = $config['is_private']  ?? false;
		
		if ( ! empty( $config['token'] ) ) {
			$this->token = $config['token'];
		} elseif ( defined( 'GITHUB_TOKEN' ) ) {
			$this->token = GITHUB_TOKEN;
		} else {
			$this->token = '';
		}
		
		$theme         = wp_get_theme( $this->theme_slug );
		$this->version = $theme->parent()
											? $theme->parent()->get( 'Version' )
											: $theme->get( 'Version' );
		
		$this->github_api = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
		$this->github_zip = "https://github.com/{$this->github_user}/{$this->github_repo}/archive/refs/tags/";
		
		add_filter( 'http_request_args', [ $this, 'add_auth_header' ], 10, 2 );
		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'check_theme_updates' ] );
		add_filter( 'upgrader_post_install', [ $this, 'fix_theme_directory' ], 10, 3 );
		
		add_action( 'upgrader_process_complete', [ $this, 'restore_active_theme' ], 10, 2 );
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
		$args['headers']['Authorization'] = 'token ' . $this->token;
		
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
			error_log( "[WP_Theme_Updater] No checked version found for theme '{$this->theme_slug}', skipping update check." );
			return $transient;
		}
		
		$args = [
			'headers' => [
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress Theme Updater'
			],
			'timeout' => 20,
		];
		if ( ! empty( $this->token ) ) {
			$args['headers']['Authorization'] = 'token ' . $this->token;
		}
		
		$response = wp_remote_get( $this->github_api, $args );
		if ( is_wp_error( $response ) ) {
			error_log( '[WP_Theme_Updater] Error fetching GitHub API: ' . $response->get_error_message() );
			return $transient;
		}
		
		if ( wp_remote_retrieve_response_code( $response ) === 403 ) {
			error_log( '[WP_Theme_Updater] GitHub API rate limit exceeded.' );
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
				'url'         => $data['html_url'] ?? '',
				'package'     => $package_url
			];
			
		}
		
		return $transient;
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
		
		$theme_dir  = wp_normalize_path( get_theme_root() . '/' . $this->theme_slug );
		$github_dir = wp_normalize_path( $result['destination'] );
		
		if ( $theme_dir === $github_dir ) {
			// nothing to do
			delete_site_transient( 'update_themes' );
			wp_clean_themes_cache();
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
				$wp_filesystem->move( $backup_dir, $theme_dir );
				error_log( '[WP_Theme_Updater] Restored backup theme after failed move.' );
			}
			return $response;
		}
		
		// new theme moved successfully, remove backup
		if ( $wp_filesystem->exists( $backup_dir ) ) {
			$wp_filesystem->delete( $backup_dir, true );
		}
		
		// clear caches and transients
		wp_clean_themes_cache();
		delete_site_transient( 'update_themes' );
		
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
		if ( isset($hook_extra['type'], $hook_extra['themes'])
		     && $hook_extra['type'] === 'theme'
		     && in_array($this->theme_slug, (array) $hook_extra['themes'], true) ) {
			
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
			wp_clean_themes_cache();
			delete_site_transient('update_themes');
		}
	}
	
}


if ( ! function_exists( 'wp_custom_theme_update' ) ) {
	/**
	 * Check for updates
	 * @return void
	 */
	function wp_custom_theme_update() {
		new WP_Theme_Updater( [
			'theme_slug'  => 'wp-theme',
			'github_user' => 'DenisStetsenko',
			'github_repo' => 'wp-theme'
		] );
	}
}
add_action( 'after_setup_theme', 'wp_custom_theme_update' );