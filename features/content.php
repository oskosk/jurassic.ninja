<?php
/**
 * Add default content.
 *
 * @package jurassic-ninja
 */

namespace jn;

add_action(
	'jurassic_ninja_init',
	function () {
		$defaults = array(
			'content' => false,
		);

		add_action(
			'jurassic_ninja_add_features_before_auto_login',
			function ( &$app = null, $features, $domain ) use ( $defaults ) {
				$features = array_merge( $defaults, $features );
				if ( $features['content'] ) {
					debug( '%s: Adding pre-generated content', $domain );
					add_content();
				}
			},
			1,
			3
		);

		add_filter(
			'jurassic_ninja_rest_create_request_features',
			function ( $features, $json_params ) {
				if ( isset( $json_params['content'] ) ) {
					$features['content'] = $json_params['content'];
				}
				return $features;
			},
			10,
			2
		);
	}
);

/**
 * Command to add content.
 */
function add_content() {
	$cmd = 'wget https://github.com/manovotny/wptest/archive/master.zip'
		. ' && unzip master.zip'
		. ' && echo "$(pwd) y $(pwd)/wptest-master/wptest.xml" | wptest-master/wptest-cli-install.sh';
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}
