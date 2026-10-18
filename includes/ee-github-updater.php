<?php
/**
 * GitHub Release Updater for WordPress Plugins (Update URI: https://github.com/<owner>/<repo>)
 *
 * Works both inside a plugin and as a standalone Must-Use plugin (mu-plugins/grmt-github-updater.php).
 * Any plugin with an `Update URI: https://github.com/<owner>/<repo>` header is automatically
 * checked against GitHub Releases and excluded from WordPress.org updates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'grmt_github_fetch_latest_release' ) ) {

	/**
	 * Fetch and cache the latest GitHub release metadata for `<owner>/<repo>`.
	 *
	 * @param string $repo_slug Repository in `owner/repo` format.
	 * @return array|false Decoded release array or false on failure.
	 */
	function grmt_github_fetch_latest_release( $repo_slug ) {
		if ( ! preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repo_slug ) ) {
			return false;
		}

		$cache_key = 'grmt_gh_rel_' . md5( $repo_slug );
		$force     = isset( $_GET['force-check'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $force ) {
			$cached = get_site_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return ! empty( $cached['tag_name'] ) ? $cached : false;
			}
		}

		$api_url = 'https://api.github.com/repos/' . $repo_slug . '/releases/latest';
		$headers = array(
			'Accept'     => 'application/vnd.github+json',
			'User-Agent' => 'WordPress-GitHub-Updater/' . get_bloginfo( 'version' ),
		);

		if ( defined( 'GRMT_GITHUB_TOKEN' ) && GRMT_GITHUB_TOKEN ) {
			$headers['Authorization'] = 'Bearer ' . GRMT_GITHUB_TOKEN;
		}

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 10,
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Cache negative result briefly (15 min) to avoid hammering GitHub API.
			set_site_transient( $cache_key, array(), 15 * MINUTE_IN_SECONDS );
			return false;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			set_site_transient( $cache_key, array(), 15 * MINUTE_IN_SECONDS );
			return false;
		}

		set_site_transient( $cache_key, $data, 6 * HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * Filter `update_plugins_github.com` to serve plugin updates from GitHub Releases.
	 *
	 * @param array|false $update      The plugin update data with the latest details.
	 * @param array       $plugin_data Plugin headers.
	 * @param string      $plugin_file Plugin basename (e.g. `simple-file-list/simple-file-list.php`).
	 * @return array|false
	 */
	function grmt_github_plugin_update_check( $update, $plugin_data, $plugin_file ) {
		if ( empty( $plugin_data['UpdateURI'] ) ) {
			return $update;
		}

		$path = wp_parse_url( $plugin_data['UpdateURI'], PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			return $update;
		}

		$repo_slug = trim( $path, '/' );
		$release   = grmt_github_fetch_latest_release( $repo_slug );
		if ( ! $release ) {
			return $update;
		}

		$latest_version  = ltrim( (string) $release['tag_name'], 'vV' );
		$current_version = isset( $plugin_data['Version'] ) ? (string) $plugin_data['Version'] : '0.0.0';

		if ( version_compare( $latest_version, $current_version, '<=' ) ) {
			return $update;
		}

		$plugin_slug = dirname( $plugin_file );
		if ( '.' === $plugin_slug ) {
			$plugin_slug = basename( $plugin_file, '.php' );
		}

		// Prefer an attached `<plugin-slug>.zip` release asset over GitHub's auto-generated zipball
		// because release assets unpack cleanly to `<plugin-slug>/` without commit hashes.
		$package_url = '';
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if (
					! empty( $asset['browser_download_url'] ) &&
					! empty( $asset['name'] ) &&
					'.zip' === substr( strtolower( $asset['name'] ), -4 )
				) {
					$package_url = $asset['browser_download_url'];
					if ( $asset['name'] === $plugin_slug . '.zip' ) {
						break;
					}
				}
			}
		}

		if ( ! $package_url ) {
			return $update;
		}

		return array(
			'id'      => $plugin_data['UpdateURI'],
			'slug'    => $plugin_slug,
			'plugin'  => $plugin_file,
			'version' => $latest_version,
			'url'     => isset( $release['html_url'] ) ? $release['html_url'] : $plugin_data['UpdateURI'],
			'package' => $package_url,
			'tested'  => get_bloginfo( 'version' ),
		);
	}
	add_filter( 'update_plugins_github.com', 'grmt_github_plugin_update_check', 10, 3 );

	/**
	 * Populate the "View version X.Y.Z details" modal in wp-admin for GitHub-hosted plugins.
	 *
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The type of information being requested from the Plugin Installation API.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object|array
	 */
	function grmt_github_plugin_info_modal( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
			return $result;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( get_plugins() as $plugin_file => $plugin_data ) {
			$slug = dirname( $plugin_file );
			if ( '.' === $slug ) {
				$slug = basename( $plugin_file, '.php' );
			}

			if ( $slug !== $args->slug || empty( $plugin_data['UpdateURI'] ) ) {
				continue;
			}

			$host = wp_parse_url( $plugin_data['UpdateURI'], PHP_URL_HOST );
			$path = wp_parse_url( $plugin_data['UpdateURI'], PHP_URL_PATH );
			if ( 'github.com' !== $host || ! is_string( $path ) ) {
				continue;
			}

			$repo_slug = trim( $path, '/' );
			$release   = grmt_github_fetch_latest_release( $repo_slug );
			if ( ! $release ) {
				return $result;
			}

			$latest_version = ltrim( (string) $release['tag_name'], 'vV' );
			$changelog      = ! empty( $release['body'] ) ? wpautop( esc_html( $release['body'] ) ) : '<p>See GitHub Release for details.</p>';

			return (object) array(
				'name'          => $plugin_data['Name'],
				'slug'          => $slug,
				'version'       => $latest_version,
				'author'        => $plugin_data['Author'],
				'homepage'      => $plugin_data['UpdateURI'],
				'Last_Updated'  => isset( $release['published_at'] ) ? $release['published_at'] : '',
				'sections'      => array(
					'description' => $plugin_data['Description'],
					'changelog'   => $changelog,
				),
				'download_link' => isset( $release['assets'][0]['browser_download_url'] ) ? $release['assets'][0]['browser_download_url'] : '',
			);
		}

		return $result;
	}
	add_filter( 'plugins_api', 'grmt_github_plugin_info_modal', 20, 3 );
}
