# WordPress Starter Theme

A WordPress Starter Theme with automatic updates directly from GitHub repositories, including private repositories.

## Features
- 🔄 Automatic theme updates directly from GitHub
- 🔒 Compatible with private repositories via personal access tokens
- 🛡️ Secure authentication with GitHub personal access token
- 📊 Built-in tools to verify GitHub API connectivity

## Requirements
- WordPress 6.5 or higher
- PHP 8.0 or higher
- GitHub personal access token (for private repositories)
- `wp_remote_get()` function enabled (WordPress HTTP API)

## Installation

### Method 1: Manual Installation
1. Download theme files in `.zip` format
2. Upload `wp-theme.zip` folder to your /wp-admin/themes.php directory
3. Activate new theme

### Method 2: Git Clone
```aiignore
cd /path/to/wordpress/wp-content/themes/
git clone git@github.com:DenisStetsenko/wp-theme.git wp-theme
```


## Configuration

### 1. GitHub Personal Access Token

To use WP Theme Updater with Private repositories, you'll need a GitHub personal access token:

1. Go to [GitHub Settings > Developer settings > Personal access tokens > Tokens (classic)](https://github.com/settings/tokens)
2. Click "Generate new token (classic)"
3. Give it a descriptive name (e.g., "WP Theme Updater")
4. Set Expiration (e.g., "No expiration")
5. Select the following scopes:
    - **`repo [repo:status, repo_deployment, public_repo, repo:invite, security_events]`** - Full control of private repositories (required)
    - **`read:packages`** - Read packages (optional, only if using GitHub packages)
6. Click "Generate token"
7. Make sure to save your personal access token. You won’t be able to see it again!

### 2. Plugin Configuration

There are 6 global constants you can use to override default plugin settings.
It is recommended to save them in `wp-config.php`

#### Disable WP Theme Updates checker
If you ever decide to stop automatic updates  
`define( 'WP_DISABLE_PARENT_THEME_UPDATE', true );` 

#### WordPress Theme Slug
If this theme has different slug than `wp-theme`  
Add `define( 'WP_THEME_UPDATER_THEME_SLUG', 'my-custom-theme' );`

#### GitHub repository slug
If your GitHub repository slug is different than `wp-theme`    
`define( 'WP_THEME_UPDATER_GITHUB_REPO', 'my-custom-theme' );`

#### GitHub user
`define( 'WP_THEME_UPDATER_GITHUB_USER', 'newGitHubUser' );`

#### GitHub Token
If you set repository to Private, you must to add GitHub token  
`define( 'WP_THEME_UPDATER_GITHUB_TOKEN', 'xxxYYYzzz' );`

#### GitHub Repository Type
By default WP Theme Updater is using Public repository type. If you set your repository to Private, you must change that  
`define( 'WP_THEME_UPDATER_IS_PRIVATE_REPO', true );`


## Release Management
WP Theme Updater works best with GitHub releases:
1. Create a new release in your GitHub repository
2. Use semantic versioning (e.g., `v1.0.0`, `v1.2.3`)
3. WP Theme Updater will automatically detect new releases
4. WordPress will show update notifications when new versions are available

## Troubleshooting
### Common Issues
#### "Connection failed" Error
- Verify your GitHub token is correct
- Ensure the token has `repo` scope for private repositories
- Check if your server can make outbound HTTPS requests

#### "Repository not found" Error
- Verify the repository owner and name are correct
- Ensure your token has access to the repository (for private repos)
- Check if the repository exists and is accessible

#### "Invalid ZIP archive" Error
- Ensure your repository has releases or can be downloaded as ZIP
- Check if the repository contains valid theme files
- Verify the repository structure matches WordPress requirements

#### Installation Fails
- Check WordPress files permissions
- Ensure the destination directory is writable
- Verify the repository contains valid WordPress theme/plugin structure

### Server Requirements
Ensure your server supports:
- `wp_remote_get()` function
- Outbound HTTPS connections to `api.github.com`
- ZIP file extraction (`unzip_file()` function)

## Security Considerations
- **Token Security**: Store GitHub tokens securely in `wp-config.php` and never commit them to version control
- **File Permissions**: Ensure proper WordPress file permissions are maintained
- **SSL Verification**: The plugin enforces SSL certificate verification for all GitHub API requests

## License
This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```