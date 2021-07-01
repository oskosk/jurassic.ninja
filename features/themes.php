<?php
/**
 * Themes installer.
 *
 * @package jurassic-ninja
 */

namespace jn;

/**
 *
 * This feature allows installation of a few allowed themes that are available on
 * the Themes Directory.
 */

add_action(
	'jurassic_ninja_init',
	function () {
		$allowlist = array(
			'tt1-blocks' => 'FSE: 2021 but with Blocks',
			'blockbase'  => 'Blockbase (FSE)',
		);
		// Set all defaults to false.
		// Will probably add a filter here.
		$defaults = array_map(
			function () {
				return false;
			},
			$allowlist
		);

		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app = null, $features, $domain ) use ( $defaults, $allowlist ) {
				$features = array_merge( $defaults, $features );
				foreach ( $allowlist as $slug => $name ) {
					if ( isset( $features[ $slug ] ) && $features[ $slug ] ) {
						debug( '%s: Adding %s', $domain, $name );
						add_directory_theme( $slug );
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
						'tt1-blocks' => (bool) settings( 'add_tt1-blocks_by_default', false ),
					)
				);
			}
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) use ( $allowlist ) {
				foreach ( $allowlist as $slug => $name ) {
					if ( ! isset( $json_params[ $slug ] ) ) {
						continue;
					}
					if ( $json_params[ $slug ] ) {
						$features[ $slug ] = true;
					} elseif ( false === $json_params[ $slug ] ) {
						$features[ $slug ] = false;
					}
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
			'jurassic_ninja_settings_options_page',
			function ( $options_page ) {
				/**
				 * Filter settings about default themes.
				 *
				 * @param array $settings_default_themes Array of settings entries. See RationalOptionPages docs.
				 */
				$fields = apply_filters(
					'jurassic_ninja_settings_options_page_default_themes',
					array(
						'tt1-blocks' => array(
							'id' => 'add_tt1-blocks_by_default',
							'title' => __( 'Add FSE 2021 Theme to every launched WordPress', 'jurassic-ninja' ),
							'text' => __( 'Install FSE 2021 Theme on launch', 'jurassic-ninja' ),
							'type' => 'checkbox',
							'checked' => false,
						),
					)
				);
				$settings = array(
					'title' => __( 'Default themes', 'jurassic-ninja' ),
					'text' => '<p>' . __( 'Choose themes you want installed on launch by default.', 'jurassic-ninja' ) . '</p>',
					'fields' => $fields,
				);

				$options_page[ SETTINGS_KEY ]['sections']['themes'] = $settings;
				return $options_page;
			},
			1
		);
	},
	1
);

/**
 * Installs and activates a given theme from the Themes Directory.
 *
 * @param string $theme_slug Theme's slug.
 */
function add_directory_theme( $theme_slug ) {
	$cmd = "wp theme install $theme_slug";
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}
