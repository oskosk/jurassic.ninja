<?php
/**
 * It's beta time!
 *
 * @package jurassic-ninja
 */

namespace jn;

add_action(
	'jurassic_ninja_added_rest_api_endpoints',
	function () {
		// Old endpoint, deprecated.
		add_get_endpoint(
			'available-jetpack-built-branches',
			function () {
				$manifest_url = 'https://betadownload.jetpack.me/jetpack-branches.json';
				$manifest = json_decode( wp_remote_retrieve_body( wp_remote_get( $manifest_url ) ) );
				$output = $manifest;
				return $output;
			}
		);

		// New endpoints.
		add_get_endpoint(
			'jetpack-beta/plugins',
			function () {
				$manifest_url = 'https://betadownload.jetpack.me/plugins.json';
				$manifest = json_decode( wp_remote_retrieve_body( wp_remote_get( $manifest_url ) ) );
				$output = $manifest;
				return $output;
			}
		);

		add_get_endpoint(
			'jetpack-beta/plugins/(?P<plugin>[a-zA-Z0-9-]+)/branches',
			function ( $data ) {
				$plugin = $data['plugin'];
				$manifest_url = 'https://betadownload.jetpack.me/plugins.json';
				$manifest = json_decode( wp_remote_retrieve_body( wp_remote_get( $manifest_url ) ) );
				if ( ! isset( $manifest->{$plugin}->manifest_url ) ) {
					return new \WP_Error( 'unknown_plugin', 'Plugin not known.', array( 'status' => 404 ) );
				}
				$manifest_url = $manifest->{$plugin}->manifest_url;
				$manifest = json_decode( wp_remote_retrieve_body( wp_remote_get( $manifest_url ) ) );
				$output = $manifest;
				return $output;
			}
		);
	}
);

add_action(
	'jurassic_ninja_init',
	function () {
		$defaults = array(
			'jetpack-beta' => false,
			'branch' => false,
			'branches' => array(),
		);

		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app = null, $features, $domain ) use ( $defaults ) {
				$features = array_merge( $defaults, $features );
				if ( $features['jetpack-beta'] ) {
					debug( '%s: Adding Jetpack Beta Tester Plugin', $domain );
					add_jetpack_beta_plugin();
				}

				// Deprecated parameter.
				if ( $features['branch'] && ! $features['branches'] ) {
					$features['branches'] = array( 'jetpack' => $features['branch'] );
				}

				if ( $features['branches'] ) {
					foreach ( $features['branches'] as $plugin_name => $branch_name ) {
						if ( $branch_name ) {
							debug( '%s: Activating %s plugin %s branch in Beta plugin', $domain, $plugin_name, $branch_name );
							activate_jetpack_branch( $plugin_name, $branch_name );
						}
					}
				}
			},
			10,
			3
		);

		add_filter(
			'jurassic_ninja_rest_feature_defaults',
			function ( $defaults ) {
				return array_merge(
					$defaults,
					array(
						'jetpack-beta' => (bool) settings( 'add_jetpack_beta_by_default', false ),
					)
				);
			}
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) {
				if ( isset( $json_params['jetpack-beta'] ) && $json_params['jetpack-beta'] ) {

					// Deprecated parameter.
					if ( isset( $json_params['branch'] ) && empty( $json_params['branches'] ) ) {
						$json_params['branches'] = array( 'jetpack' => $json_params['branch'] );
					}

					// Default.
					if ( ! isset( $json_params['branches'] ) ) {
						$json_params['branches'] = array( 'jetpack' => 'master' );
					}

					$error = null;
					foreach ( $json_params['branches'] as $plugin_name => $branch_name ) {
						if ( ! $branch_name ) {
							continue;
						}

						$url = get_jetpack_beta_url( $plugin_name, $branch_name );
						if ( null === $url ) {
							$error = new \WP_Error(
								'failed_to_launch_site_with_branch',
								/* translators: %1$s: Plugin slug. %2$s: GitHub branch name */
								sprintf( esc_html__( 'Invalid branch name for %1$s or not ready yet: %2$s', 'jurassic-ninja' ), $plugin_name, $branch_name ),
								array(
									'status' => 400,
								)
							);
							break;
						}
						$features['branches'][ $plugin_name ] = $branch_name;
					}
					$features['jetpack-beta'] = null === $error ? $json_params['jetpack-beta'] : $error;
					$features['branch'] = isset( $features['branches']['jetpack'] ) ? $features['branches']['jetpack'] : null; // Deprecated.
				}

				return $features;
			},
			10,
			2
		);
	}
);

add_action(
	'jurassic_ninja_admin_init',
	function () {
		add_filter(
			'jurassic_ninja_settings_options_page_default_plugins',
			function ( $fields ) {
				$field = array(
					'add_jetpack_beta_by_default' => array(
						'id' => 'add_jetpack_beta_by_default',
						'title' => __( 'Add Jetpack Beta Tester plugin to every launched WordPress', 'jurassic-ninja' ),
						'text' => __( 'Install and activate Jetpack Beta Tester on launch', 'jurassic-ninja' ),
						'type' => 'checkbox',
						'checked' => false,
					),
				);
				return array_merge( $fields, $field );
			},
			10
		);
	}
);

/**
 * Installs and activates Jetpack Beta Tester plugin on the site.
 */
function add_jetpack_beta_plugin() {
	$jetpack_beta_plugin_url = JETPACK_BETA_PLUGIN_URL;
	$cmd = "wp plugin install $jetpack_beta_plugin_url --activate";
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

/**
 * Activates jetpack branch in Beta plugin
 *
 * @param string $plugin_name Plugin name.
 * @param string $branch_name Branch name.
 */
function activate_jetpack_branch( $plugin_name, $branch_name ) {
	$cmd = "wp jetpack-beta activate $plugin_name $branch_name";
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

/**
 * Get URL for beta download.
 *
 * @param string $plugin_name Plugin name.
 * @param string $branch_name Branch name.
 *
 * @return string|null Download URL.
 */
function get_jetpack_beta_url( $plugin_name, $branch_name ) {
	$manifest_url = 'https://betadownload.jetpack.me/plugins.json';
	$manifest = json_decode( wp_remote_retrieve_body( wp_remote_get( $manifest_url ) ) );
	if ( ! isset( $manifest->{$plugin_name}->manifest_url ) ) {
		return null;
	}

	$branch_name = str_replace( '/', '_', $branch_name );
	$manifest_url = $manifest->{$plugin_name}->manifest_url;
	$manifest = json_decode( wp_remote_retrieve_body( wp_remote_get( $manifest_url ) ) );

	if ( ( 'rc' === $branch_name || 'master' === $branch_name ) && isset( $manifest->{$branch_name}->download_url ) ) {
		return $manifest->{$branch_name}->download_url;
	}

	if ( isset( $manifest->pr->{$branch_name}->download_url ) ) {
		return $manifest->pr->{$branch_name}->download_url;
	}

	return null;
}
