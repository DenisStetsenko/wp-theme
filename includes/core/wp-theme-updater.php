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
	
	private string $theme_slug;
	private string $github_user;
	private string $github_repo;
	private string $github_api;
	private string $github_zip;
	private string $version;
	private ?string $token;
	private bool $is_private;
	
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
		
		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'check_theme_updates' ] );
		add_filter( 'upgrader_post_install', [ $this, 'fix_theme_directory' ], 10, 3 );
	}
	
	/**
	 * Trigger theme update check
	 * @param $transient
	 *
	 * @return mixed
	 * @throws JsonException
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
			]
		];
		if ( $this->is_private && ! empty( $this->token ) ) {
			$args['headers']['Authorization'] = 'token ' . $this->token;
		}
		
		$response = wp_remote_get( $this->github_api, $args );
		if ( is_wp_error( $response ) ) {
			error_log( '[WP_Theme_Updater] Error fetching GitHub API: ' . $response->get_error_message() );
			return $transient;
		}
		
		if ( wp_remote_retrieve_response_code($response) === 403 ) {
			error_log( '[WP_Theme_Updater] GitHub API rate limit exceeded.' );
			return $transient;
		}
		
		$data = json_decode( wp_remote_retrieve_body( $response ), true, 512, JSON_THROW_ON_ERROR );
		if ( empty( $data['tag_name'] ) ) {
			error_log( "[WP_Theme_Updater] GitHub API response missing 'tag_name'." );
			return $transient;
		}
		
		$new_version = ltrim( $data['tag_name'], 'v' );
		if ( version_compare( $this->version, $new_version, '<' ) ) {
			
			if ( $this->is_private && ! empty( $this->token ) ) {
				$package_url = add_query_arg( 'access_token', $this->token, $data['zipball_url'] );
			} else {
				$package_url = $data['zipball_url'] ?? ($this->github_zip . $data['tag_name'] . '.zip');
			}
			
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
			
		} else {
			error_log( "[WP_Theme_Updater] No update available for theme '{$this->theme_slug}'." );
		}
		
		return $transient;
	}
	
	/**
	 * Fixes the theme directory name after update.
	 * Hooked into 'upgrader_post_install'.
	 */
	public function fix_theme_directory($true, $hook_extra, $result) {
		global $wp_filesystem;
		
		// Only run for our theme
		if ( ! isset( $hook_extra['theme'] ) || $hook_extra['theme'] !== $this->theme_slug ) {
			return $true;
		}
		
		$theme_dir = get_theme_root() . '/' . $this->theme_slug;
		$temp_dir  = $result['destination']; // Temporary GitHub-extracted dir
		
		// Delete old theme (if it exists)
		if ( $wp_filesystem->exists( $theme_dir ) ) {
			$wp_filesystem->delete( $theme_dir, true );
		}
		
		// Rename temp dir to the correct theme slug
		$wp_filesystem->move( $temp_dir, $theme_dir );
		
		return $true;
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