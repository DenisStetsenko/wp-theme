<?php

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
		$this->theme_slug  = $config['theme_slug'] ?? '';
		$this->github_user = $config['github_user'] ?? '';
		$this->github_repo = $config['github_repo'] ?? '';
		$this->is_private  = $config['is_private'] ?? false;
		
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
		
		$branch           = $config['branch'] ?? 'main';
		$this->github_api = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
		$this->github_zip = "https://github.com/{$this->github_user}/{$this->github_repo}/archive/refs/heads/{$branch}.zip";
		
		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'check_theme_updates' ] );
	}
	
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
		
		$data = json_decode( wp_remote_retrieve_body( $response ), true, 512, JSON_THROW_ON_ERROR );
		if ( empty( $data['tag_name'] ) ) {
			error_log( "[WP_Theme_Updater] GitHub API response missing 'tag_name'." );
			return $transient;
		}
		
		$remote_version = ltrim( $data['tag_name'], 'v' );
		
		if ( version_compare( $this->version, $remote_version, '<' ) ) {
			$package_url = $this->github_zip;
			
			if ( ! empty( $this->token ) ) {
				$package_url = add_query_arg( 'access_token', $this->token, $package_url );
				
			} elseif ( empty( $package_url ) || ! filter_var( $package_url, FILTER_VALIDATE_URL ) ) {
				error_log( 'Invalid GitHub package URL: ' . $package_url );
				return $transient;
			}
			
			$transient->response[ $this->theme_slug ] = [
				'theme'       => $this->theme_slug,
				'new_version' => $remote_version,
				'url'         => $data['html_url'] ?? '',
				'package'     => $package_url
			];
			
		} else {
			error_log( "[WP_Theme_Updater] No update available for theme '{$this->theme_slug}'." );
		}
		
		return $transient;
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
			'github_repo' => 'wp-theme',
			'branch'      => 'main',
			'is_private'  => false
		] );
	}
}
add_action( 'after_setup_theme', 'wp_custom_theme_update' );