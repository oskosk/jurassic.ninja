<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

/**
 * Adds a REST interface to this plugin
 */
function add_rest_api_endpoints() {
	$permission_callback = [
		'permission_callback' => function () {
			return settings( 'lock_launching', false ) ? current_user_can( 'manage_options' ) : true ;
		},
	];

	$specialops_permission_callback = [
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
	];
	add_post_endpoint( 'create', function ( $request ) {
		$defaults = [
			'jetpack' => (bool)settings( 'add_jetpack_by_default', true ),
			'jetpack-beta' => (bool) settings( 'add_jetpack_beta_by_default', false ),
			'woocommerce' => (bool) settings( 'add_woocommerce_by_default', false ),
			'wp-debug-log' => (bool) settings( 'set_wp_debug_log_by_default', false ),
			'shortlife' => false,
			'subdomain_multisite' => false,
			'ssl' => (bool) settings( 'ssl_use_custom_certificate', false ),
		];
		$json_params = $request->get_json_params();

		if ( ! settings( 'enable_launching', true ) ) {
			return new \WP_Error( 'site_launching_disabled', __( 'Site launching is disabled right now' ), [
				'status' => 503,
			] );
		}

		$features = array_merge( $defaults, [
			'shortlife' => isset( $json_params['shortlived'] ) && (bool) $json_params['shortlived'],
		] );
		if ( isset( $json_params['jetpack'] ) ) {
			$features['jetpack'] = $json_params['jetpack'];
		}
		if ( isset( $json_params['woocommerce'] ) ) {
			$features['woocommerce'] = $json_params['woocommerce'];
		}
		if ( isset( $json_params['wp-debug-log'] ) ) {
			$features['wp-debug-log'] = $json_params['wp-debug-log'];
		}

		/**
		 * Filters the features requested through the /create REST API endpoint
		 *
		 * If any filter returns a WP_Error, then the request is finished with status 500
		 *
		 * @param array $features    The current feature flags.
		 * @param array $json_params The body of the json request.
		 */
		$features = apply_filters( 'jurassic_ninja_rest_create_request_features', $features, $json_params );

		if ( is_wp_error( $features ) ) {
			return $features;
		}

		if ( isset( $json_params['jetpack-beta'] ) ) {
			$url = get_jetpack_beta_url( $json_params['branch'] );

			if ( $url === null ) {
				return new \WP_Error(
					'failed_to_launch_site_with_branch',
					esc_html__( 'Invalid branch name or not ready yet: ' . $json_params['branch'] ),
					[
						'status' => 400,
					]
				);
			}
			$features['jetpack-beta'] = $json_params['jetpack-beta'];
			$features['branch'] = $json_params['branch'];
		}

		$data = launch_wordpress( 'php7.0', $features );
		if ( null === $data ) {
			return new \WP_Error(
				'failed_to_launch_site',
				esc_html__( 'There was an error launching the site.' ),
				[
					'status' => 500,
				]
			);
		}
		// See note in launch_wordpress() about why we can't launch subdomain_multisite with ssl.
		$schema = $features['ssl'] && ! $features['subdomain_multisite'] ? 'https' : 'http';
		$url = "$schema://" . figure_out_main_domain( $data->domains );

		$output = [
			'url' => $url,
		];
		return $output;
	}, $permission_callback );

	add_post_endpoint( 'specialops/create', function ( $request ) {
		$json_params = $request->get_json_params();
		$defaults = [
			'subdomain_multisite' => false,
			'ssl' => (bool) settings( 'ssl_use_custom_certificate', false ),
		];
		$features = $json_params && is_array( $json_params ) ? $json_params : [];
		$features = array_merge( $defaults, $features );
		if ( ! settings( 'enable_launching', true ) ) {
			return new \WP_Error( 'site_launching_disabled', __( 'Site launching is disabled right now' ), [
				'status' => 503,
			] );
		}

		$data = launch_wordpress( $features['runtime'], $features );
		if ( null === $data ) {
			return new \WP_Error(
				'failed_to_launch_site',
				esc_html__( 'There was an error launching the site.' ),
				[
					'status' => 500,
				]
			);
		}
		// See note in launch_wordpress() about why we can't launch subdomain_multisite with ssl.
		$schema = $features['ssl'] && ! $features['subdomain_multisite'] ? 'https' : 'http';
		$url = "$schema://" . figure_out_main_domain( $data->domains );

		$output = [
			'url' => $url,
		];
		return $output;
	}, $specialops_permission_callback );

	add_post_endpoint( 'extend', function ( $request ) {
		$body = $request->get_json_params() ? $request->get_json_params() : [];
		if ( ! isset( $body['domain'] ) ) {
			return new \WP_Error( 'no_domain_in_body', __( 'You must pass a valid "domain" prop in the body' ) );
		}
		extend_site_life( $body['domain'] );

		$output = [
			'url' => $body['domain'],
		];

		return $output;
	} );

	add_post_endpoint( 'checkin', function ( $request ) {
		$body = $request->get_json_params() ? $request->get_json_params() : [];
		if ( ! isset( $body['domain'] ) ) {
			return new \WP_Error( 'no_domain_in_body', __( 'You must pass a valid "domain" prop in the body' ) );
		}
		mark_site_as_checked_in( $body['domain'] );

		$output = [
			'url' => $body['domain'],
		];

		return $output;
	} );
}

/**
 * Adds a callback to a REST endpoint for the POST method.
 * Depends on global constant REST_API_NAMESPACE.
 *
 * @param [type] $path                        New Path to add
 * @param [type] $callback                    The function that will handle the request. Must return Array.
 * @param ?array  $register_rest_route_options Either empty or a succesful object
 */
function add_post_endpoint( $path, $callback, $register_rest_route_options = [] ) {
	$namespace = REST_API_NAMESPACE;

	$options = array_merge( $register_rest_route_options, [
		'methods' => \WP_REST_Server::CREATABLE,
	] );
	return add_endpoint( $namespace, $path, $callback, $options );
}

/**
 * Adds a callback to a REST endpoint for the GET method.
 * Depends on global constant REST_API_NAMESPACE.
 *
 * @param [type] $path                        New Path to add
 * @param [type] $callback                    The function that will handle the request. Must return Array.
 * @param ?array  $register_rest_route_options Either empty or a succesful object
 */
function add_get_endpoint( $path, $callback, $register_rest_route_options = [] ) {
	$namespace = REST_API_NAMESPACE;
	$options = array_merge( $register_rest_route_options, [
		'methods' => \WP_REST_Server::READABLE,
	] );
	return add_endpoint( $namespace, $path, $callback, $options );
}

/**
 * Handy function to register a hook and create a REST API endpoint easily
 * Users register_rest_route()
 *
 * @param string $namespace                   namespace for the endpoint
 * @param string $path                        The endpoint's path
 * @param callable $callback                  The callback to use
 * @param [type] $register_rest_route_options Extra optinos to register_rest_route
 */
function add_endpoint( $namespace, $path, $callback, $register_rest_route_options ) {
	// Wrap the $callback passed to catch every Exception that could be thrown in it
	$wrapit = function ( \WP_REST_Request $request ) use ( $callback ) {
		// We'll wrap whatever the $callback returns
		// so we can report Exception errors in every response (third parameter).
		$response = [];

		try {
			global $response;
			$data = $callback( $request );
			$response['status'] = 'ok';
			$response['data'] = $data;

			if ( is_wp_error( $data ) ) {
				$response = $data;
			}
		} catch ( Exception $e ) {
			global $response;
			$response = [
				'status' => 'error',
				'error' => [
					'code' => $e->getCode(),
					'message' => $e->getMessage(),
				],
				'data' => null,
			];
		}
		return $response;
	};

	$options = array_merge( $register_rest_route_options, [
		'callback' => $wrapit,
	] );

	add_action( 'rest_api_init', function () use ( $namespace, $path, $options ) {
		register_rest_route( $namespace, $path, $options );
	} );
}

function get_jetpack_beta_url( $branch_name ) {
	$branch_name = str_replace( '/', '_', $branch_name );
	$manifest_url = "https://betadownload.jetpack.me/jetpack-branches.json";
	$manifest = json_decode( wp_remote_retrieve_body( wp_remote_get( $manifest_url ) ) );

	if ( ( 'rc' === $branch_name || 'master' === $branch_name ) && isset( $manifest->{$branch_name}->download_url ) ) {
		return $manifest->{$branch_name}->download_url;
	}

	if ( isset( $manifest->pr->{$branch_name}->download_url ) ) {
		return $manifest->pr->{$branch_name}->download_url;
	}
}
