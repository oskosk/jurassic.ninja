<?php
/**
 * A lot of the big kickoff stuff.
 *
 * @package jurassic-ninja
 */

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
if ( ! class_exists( 'RationalOptionPages' ) ) {
	require_once __DIR__ . '/RationalOptionPages.php';
}
if ( ! class_exists( 'CustomNameGenerator' ) ) {
	require_once __DIR__ . '/class-customnamegenerator.php';
}

/**
 * Attempts to log debug messages if WP_DEBUG is on and the setting for log_debug_messages is on too.
 */
function debug() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG && settings( 'log_debug_messages', false ) ) {
		// phpcs:disable WordPress.PHP.DevelopmentFunctions
		$args = array_map(
			function ( $item ) {
				if ( gettype( $item ) === 'array' || gettype( $item ) === 'object' ) {
					return print_r( $item, true );
				}
				return $item;
			},
			func_get_args()
		);
		error_log( call_user_func_array( 'sprintf', $args ) );
		// phpcs:enable
	}
}

require_once __DIR__ . '/rest-api-stuff.php';
require_feature_files();
require_once __DIR__ . '/class-serverpilotprovisioner.php';

define( 'REST_API_NAMESPACE', 'jurassic.ninja' );
define( 'COMPANION_PLUGIN_URL', 'https://github.com/Automattic/companion/archive/master.zip' );
define( 'JETPACK_BETA_PLUGIN_URL', 'https://github.com/Automattic/jetpack-beta/releases/latest/download/jetpack-beta.zip' );
define( 'JETPACK_BETA_PLUGIN_DEV_URL', 'https://github.com/Automattic/jetpack-beta/archive/master.zip' );
define( 'SUBDOMAIN_MULTISITE_HTACCESS_TEMPLATE_URL', 'https://gist.githubusercontent.com/oskosk/8cac852c793df5e4946463e2e55dfdd6/raw/a60ce4122a69c1dd36c623c9b999c36c9c8d3db8/gistfile1.txt' );
define( 'SUBDIR_MULTISITE_HTACCESS_TEMPLATE_URL', 'https://gist.githubusercontent.com/oskosk/f5febd1bb65a2ace3d35feac949b47fd/raw/6ea8ffa013056f6793d3e8775329ec74d3304835/gistfile1.txt' );
define( 'REGULAR_SITE_HTACCESS_TEMPLATE_URL', 'https://gist.githubusercontent.com/oskosk/0dab794274742af9caddefbc73f0ad80/raw/504f60da86969a9d55487f0c4821d06928a97218/.htaccess' );

/**
 * Force the site to log the creator in on the first time they visit the site
 * Installs and activates the Jurassic Ninja companion plugin on the site.
 *
 * @param string $password System password for ssh.
 * @param string $sysuser User.
 */
function add_auto_login( $password, $sysuser ) {
	$companion_api_base_url = rest_url( REST_API_NAMESPACE );
	$companion_plugin_url = COMPANION_PLUGIN_URL;
	$cmd = 'wp option add auto_login 1'
		. " && wp option add jurassic_ninja_sysuser '$sysuser'"
		. " && wp option add jurassic_ninja_admin_password '$password'"
		. " && wp option add companion_api_base_url '$companion_api_base_url'"
		. " && wp plugin install --force $companion_plugin_url --activate";
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

/**
 * Makes sure the site has an .htaccess file
 */
function add_htaccess() {
	$file_url = REGULAR_SITE_HTACCESS_TEMPLATE_URL;
	$cmd = "wget '$file_url' -O .htaccess"
		. " && wp rewrite structure '/%year%/%monthnum%/%day%/%postname%/'"
		. ' && wp rewrite flush';
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

/**
 * Makes sure the site does not report new posts to Ping-O-Matic
 */
function stop_pingomatic() {
	$cmd = 'wp option update ping_sites ""';
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

/**
 * Just loops through a filtered array of files inside the features directory and requires them
 */
function require_feature_files() {
	$available_features = array(
		'/features/logged-in-user-email-address.php',
		'/features/content.php',
		'/features/multisite.php',
		'/features/ssl.php',
		'/features/plugins.php',
		'/features/jetpack-licensing.php',
		'/features/jetpack-beta.php',
		'/features/wc-smooth-generator.php',
		'/features/woocommerce-beta-tester.php',
		'/features/jetpack-crm-master.php',
		'/features/jetpack-debug-helper.php',
		'/features/client-example.php',
		'/features/wp-debug-log.php',
		'/features/block-xmlrpc.php',
		'/features/language.php',
		'/features/gutenberg-master.php',
		'/features/gutenberg-nightly.php',
		'/features/wordpress-4.php',
		'/features/themes.php',
	);

	$available_features = apply_filters( 'jurassic_ninja_available_features', $available_features );
	foreach ( $available_features as $feature_file ) {
		require_once PLUGIN_DIR . $feature_file;
	}
	return $available_features;
}

/**
 * Launches a new WordPress instance on the managed server
 *
 * @param  string $php_version          The PHP version to run the app on.
 * @param  array  $requested_features   Array of features to enable.
 *        boolean config-constants      Should we add the Config Constants plugin to the site?
 *        boolean auto_ssl              Should we add Let's Encrypt-based SSL for the site?
 *        boolean ssl                   Should we add the configured SSL certificate for the site?
 *        boolean gutenberg             Should we add Gutenberg to the site?
 *        boolean jetpack               Should we add Jetpack to the site?
 *        boolean jetpack-beta          Should we add Jetpack Beta Tester plugin to the site?
 *        boolean subdir_multisite      Should we enable subdir-based multisite on the site?
 *        boolean subdir_multisite      Should we enable subdomain-based multisite on the site?
 *        boolean woocommerce           Should we add WooCommerce plugin to the site?
 *        boolean wordpress-beta-tester Should we add Jetpack Beta Tester plugin to the site?
 *        boolean wp-debug-log          Should we set WP_DEBUG and WP_DEBUG log to true ?
 *        boolean wp-log-viewer         Should we add WP Log Viewer plugin to the site.
 * @param bool   $spare Spare site.
 *
 * @throws \Exception Throws on any error launching WP.
 *
 * @return array|null                    null or the app data as returned by ServerPilot's API on creation.
 */
function launch_wordpress( $php_version = 'default', $requested_features = array(), $spare = false ) {
	$default_features = array(
		'shortlife' => false,
	);
	$start_time = microtime( true );

	$features = array_merge( $default_features, $requested_features );
	/**
	 * Fired before launching a site, and as soon as we merge feature defaults and requested features
	 *
	 * Alloes to react to requested features in case some condition is not met. e.g. requesting both types of multisite installations.
	 *
	 * @since 3.0
	 *
	 * @param array $features defaults and requested features merged.
	 */
	do_action( 'jurassic_ninja_do_feature_conditions', $features );

	try {
		$app = null;
		if ( $spare ) {
			debug( 'Launching spare site' );
			$app = create_php_app( 'php7.0', $features, true );
		} else {
			debug( 'Launching site with features: %s', implode( ', ', array_keys( array_filter( $features ) ) ) );
			if ( settings( 'use_spare_sites', false ) ) {
				$app = get_spare_site( $php_version );
			}
			if ( ! $app && settings( 'launch_site_if_no_spare_available', true ) ) {
				$app = create_php_app( $php_version, $features, true );
			} elseif ( ! $app ) {
				throw new \Exception( "Couldn't get a spare site" );
			}
			// phpcs:disable
			// }
			// $app = $spare ? false : get_spare_site( $php_version );
			// if ( $app ) {
			// } else {
			// $app = create_php_app( $php_version, $features, $spare );
			// debug( 'Launching %s with features: %s', $app->domain, implode( ', ', array_keys( array_filter( $features ) ) ) );
			// }

			// if ( ! $spare ) {
			// phpcs:enable
			$site_title = settings( 'use_subdomain_based_wordpress_title', false ) ?
			ucwords( str_replace( '-', ' ', $app->subdomain ) ) :
			'My WordPress Site';
			/**
			 * Filters the WordPress options for setting up the site
			 *
			 * @since 3.0
			 *
			 * @param array $wordpress_options {
			 *           An array of properties used for setting up the WordPress site for the first time.
			 *           @type string site_title               The title of the site we're creating.
			 *           @type string admin_user               The username for the admin account.
			 *           @type string admin_password           The password or the admin account.
			 *           @type string admin_email              The email address for the admin account.
			 * }
			 */
			$wordpress_options = apply_filters(
				'jurassic_ninja_wordpress_options',
				array(
					'site_title' => $site_title,
					'admin_user' => 'demo',
					'admin_password' => $app->password,
					'admin_email' => settings( 'default_admin_email_address' ),
				)
			);
			install_wordpress_with_cli( $app->domain, $wordpress_options, $app->dbname, $app->dbusername, $app->dbpassword );

			log_new_site( $app, $app->password, $features['shortlife'], is_user_logged_in() ? wp_get_current_user() : '' );

			add_features_before_auto_login( $app, $features, $app->domain );

			debug( '%s: Adding .htaccess file', $app->domain );
			add_htaccess();

			// 2020-01-17
			// For some reason, automated scripts are being able to find out about a new Jurassic Ninja site before
			// the person that launched it reaches the site, thus the site is locked for them.
			debug( '%s: Stopping pings to Ping-O-Mattic', $app->domain );
			stop_pingomatic();

			debug( '%s: Adding Companion Plugin for Auto Login', $app->domain );
			add_auto_login( $app->password, $app->username );

			add_features_after_auto_login( $app, $features, $app->domain );

			debug( '%s: Adding features', $app->domain );

			// Run command via SSH
			// The commands to be run are the result of applying the `jurassic_ninja_feature_command` filter.
			run_commands_for_features( $app->username, $app->password, $app->domain );
			$diff = round( microtime( true ) - $start_time );
			debug( "Finished launching %s. Took %02d:%02d.\n", $app->domain, floor( $diff / 60 ), $diff % 60 );
		}
		return $app;
	} catch ( \Exception $e ) {
		debug( '%s: Error [%s]: %s', isset( $app->domain ) ? $app->domain : 'NO DOMAIN', $e->getCode(), $e->getMessage() );
		return null;
	}
}

/**
 * Create a slug from a string
 *
 * @param  string $str       The string to slugify.
 * @param  string $delimiter Character to use between words.
 * @return string            Slugified version of the string.
 */
function create_slug( $str, $delimiter = '-' ) {
	$slug = strtolower( trim( preg_replace( '/[\s-]+/', $delimiter, preg_replace( '/[^A-Za-z0-9-]+/', $delimiter, preg_replace( '/[&]/', 'and', preg_replace( '/[\']/', '', iconv( 'UTF-8', 'ASCII//TRANSLIT', $str ) ) ) ) ), $delimiter ) );
	return $slug;
}

/**
 * Returns the list of sites that are calculated to have expired
 *
 * @return Array List of sites
 */
function expired_sites() {
	$interval = settings( 'sites_expiration', 'INTERVAL 7 DAY' );
	$interval_shortlived = settings( 'shortlived_sites_expiration', 'INTERVAL 1 HOUR' );
	return db()->get_results(
		"select * from sites where ( last_logged_in IS NOT NULL AND last_logged_in < DATE_SUB( NOW(), $interval ) )
		OR ( last_logged_in is NULL and created < DATE_SUB( NOW(), $interval ) )
		OR ( shortlived and created < DATE_SUB( NOW(), $interval_shortlived ) )",
		\ARRAY_A
	);
}

/**
 * Extends the expiration date for a site
 *
 * @param  string $domain The name of the site.
 */
function extend_site_life( $domain ) {
	db()->update(
		'sites',
		array(
			'last_logged_in' => current_time( 'mysql', 1 ),
		),
		array(
			'domain' => $domain,
		)
	);
	if ( db()->last_error ) {
		l( db()->last_error );
	};
}

/**
 * Given an array of domains as ServerPilot returns in its API endpoint for an app
 * excludes, the wildcard entries from the array and returns it.
 *
 * @param  array $domains The array of domains for an app as returned by ServerPilot's API.
 * @return string          The main domain
 */
function figure_out_main_domain( $domains ) {
	$valid = array_filter(
		$domains,
		function ( $domain ) {
			return false === strpos( $domain, '*.' );
		}
	);
	// reset() trick to get first item.
	return reset( $valid );
}

/**
 * Downloads WordPress, creates a wp-config.php file and installs WordPress admin user.
 *
 * @param  [type] $domain            The domain name that will be configured for the site.
 * @param  [type] $wordpress_options WordPress options for the admin user and site. Resembling ServerPilot's parameter for creating an app.
 * @param  [type] $dbname            The database name this WordPress will connect to.
 * @param  [type] $dbusername        The database username this WordPress will use.
 * @param  [type] $dbpassword        The database password this WordPress will use.
 */
function install_wordpress_with_cli( $domain, $wordpress_options, $dbname, $dbusername, $dbpassword ) {
	$cmd = sprintf(
		'wp core download'
		. ' && wp config create --dbname="%s" --dbuser="%s" --dbpass="%s"'
		. ' && wp core install --url="%s" --title="%s" --admin_user="%s" --admin_password="%s" --admin_email="%s"',
		$dbname,
		$dbusername,
		$dbpassword,
		$domain,
		$wordpress_options['site_title'],
		$wordpress_options['admin_user'],
		$wordpress_options['admin_password'],
		$wordpress_options['admin_email']
	);
	add_filter(
		'jurassic_ninja_feature_command',
		function ( $s ) use ( $cmd ) {
			return "$s && $cmd";
		}
	);
}

/**
 * Generates a new username with a pseudo random name on the managed server.
 *
 * @param  string $password The password to be assigned for the user.
 *
 * @throws \Exception If WP_Error from attempting to create user.
 */
function generate_new_user( $password ) {
	$username = generate_random_username();
	$return = null;
	$return = provisioner()->create_sysuser( $username, $password );

	if ( is_wp_error( $return ) ) {
		throw new \Exception( 'Error creating sysuser: ' . $return->get_error_message() );
	}
	return $return;
}

/**
 * Returns a ServerPilot instance.
 */
function provisioner() {
	static $jurassic_ninja_provisioner;
	if ( ! $jurassic_ninja_provisioner ) {
		try {
			$provisioner_class = apply_filters( 'jurassic_ninja_provisioner_class', '\jn\ServerPilotProvisioner' );
			$jurassic_ninja_provisioner = new $provisioner_class();
		} catch ( \ServerPilotException $e ) {
			push_error( new \WP_error( $e->getCode(), $e->getMessage() ) );
		}
	}
	return $jurassic_ninja_provisioner;
}

/**
 * Generates a random string of 12 characters.
 *
 * @return string A string with random characters to be used as password for the WordPress administrator
 */
function generate_random_password() {
	$length = 12;
	return wp_generate_password( $length, false, false );
}

/**
 * Generates a random subdomain based on an adjective and sustantive.
 * The words come from:
 *      lib/words/adjectives.txt
 *      lib/words/nouns.txt
 * Tne return value is slugified.
 *
 * @param array $features Array of features.
 *
 * @return string A slugified subdomain.
 */
function generate_random_subdomain( $features ) {
	$generator = new CustomNameGenerator();
	$slug = '-';
	$collision_attempts = 10;
	do {
		$name = $generator->getName( settings( 'use_alliterations_for_subdomain', true ) );
		$slug = create_slug( $name );
		// Add moar randomness to shortlived sites.
		if ( $features['shortlife'] ) {
			$slug = sprintf( '%s-%s', $slug, wp_rand( 2, 500 ) );
		}
	} while ( subdomain_is_used( $slug ) && $collision_attempts-- > 0 );
	return $slug;
}

/**
 * Generates a random username starting with userxxxxx
 *
 * @return string A random username
 */
function generate_random_username() {
	$length = 4;
	return 'user' . bin2hex( random_bytes( $length ) );
}

/**
 * Attempts to log whatever it's feeded by using error_log and printf
 *
 * @param  mixed $stuff  Whatever.
 */
function l( $stuff ) {
	// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
	// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r
	error_log( print_r( $stuff, true ) );
	// phpcs:enable
}

/**
 * Add features before login.
 *
 * @param object $app     Passed by reference. This object contains the resulting data after creating a PHP app.
 * @param array  $features The list of features we're going to add to the WordPress installation.
 * @param string $domain  The domain under which this app will be running.
 */
function add_features_before_auto_login( &$app, $features, $domain ) {
	// Here PHP Codesniffer parses &$app as if it were a deprecated pass-by-reference but it is not.
	// phpcs:disable PHPCompatibility.PHP.ForbiddenCallTimePassByReference.NotAllowed
	/**
	 * Allows the enqueueing of commands for features with each launched site.
	 *
	 * This fires before adding the auto login features
	 *
	 * @since 3.0
	 *
	 * @param array $args {
	 *     All we need to describe a php app with WordPress
	 *
	 *     @type object $app                 Passed by reference. This object contains the resulting data after creating a PHP app.
	 *     $type array $features             The list of features we're going to add to the WordPress installation.
	 *     @type string $domain              The domain under which this app will be running.
	 * }
	 */
	do_action_ref_array( 'jurassic_ninja_add_features_before_auto_login', array( &$app, $features, $domain ) );
	// phpcs:enable
}

/**
 * Add features after auto login.
 *
 * @param object $app     Passed by reference. This object contains the resulting data after creating a PHP app.
 * @param array  $features The list of features we're going to add to the WordPress installation.
 * @param string $domain  The domain under which this app will be running.
 */
function add_features_after_auto_login( &$app, $features, $domain ) {
	// Here PHP Codesniffer parses &$app as if it were a deprecated pass-by-reference but it is not.
	// phpcs:disable PHPCompatibility.PHP.ForbiddenCallTimePassByReference.NotAllowed
	/**
	 * Allows the enqueueing of commands for features with each launched site.
	 *
	 * This fires after adding the auto login features
	 *
	 * @since 3.0
	 *
	 * @param array $args {
	 *     All we need to describe a php app with WordPress
	 *
	 *     @type object $app                 Passed by reference. This object contains the resulting data after creating a PHP app.
	 *     $type array $features             The list of features we're going to add to the WordPress installation.
	 *     @type string $domain              The domain under which this app will be running.
	 * }
	 */
	do_action_ref_array( 'jurassic_ninja_add_features_after_auto_login', array( &$app, $features, $domain ) );
	// phpcs:enable
}

/**
 * Get a spare site for a particular PHP version.
 *
 * @param string $php_version PHP Version.
 */
function get_spare_site( $php_version ) {
	// phpcs:disable WordPress.WP.DeprecatedFunctions.generate_random_passwordFound
	$lock = generate_random_password();
	// phpcs:enable
	$unused = db()->query( sprintf( "update spare_sites set locked_by='%s' where locked_by = '' limit 1", $lock ) );
	$unused = db()->get_results( sprintf( "select * from spare_sites where locked_by = '%s' limit 1", $lock ), \ARRAY_A );
	$unused = count( $unused ) ? $unused[0] : false;
	if ( ! $unused ) {
		debug( "Couldn't find an unused site" );
		return false;
	}
	$app = provisioner()->get_app( $unused['app_id'] );
	if ( is_wp_error( $app ) ) {
		debug( 'Problem fetching app info for app with id %s: %s', $unused['app_id'], $app->get_error_message() );
		if ( 404 === $app->get_error_code() ) {
			debug( 'Deleting rogue spare site with app id %s', $unused['app_id'] );
			db()->delete( 'spare_sites', array( 'id' => $unused['id'] ) );
		}
		return false;
	}
	$app->username = $unused['username'];
	$app->domain = $unused['domain'];
	$app->subdomain = explode( '.', $unused['domain'] )[0];
	$app->password = $unused['password'];
	$app->dbname = $app->name . '-wp-blah';
	$app->dbusername = $app->name;
	$app->dbpassword = $unused['password'];
	db()->delete( 'spare_sites', array( 'id' => $unused['id'] ) );

	provisioner()->update_app( $app->id, $php_version, null );
	return $app;
}

/**
 * Create PHP app.
 *
 * @param string $php_version PHP Version.
 * @param array  $features Array of features.
 * @param bool   $spare Spare or current site.
 *
 * @throws \Exception If WP_Errors are returned.
 */
function create_php_app( $php_version, $features, $spare = false ) {
	$start_time = microtime( true );
	$subdomain  = '';
	// phpcs:disable WordPress.WP.DeprecatedFunctions.generate_random_passwordFound
	$password = generate_random_password();
	// phpcs:enable
	$subdomain = generate_random_subdomain( $features );
		// title-case the subdomain
		// or default to the classic My WordPress Site.
	$domain = sprintf( '%s.%s', $subdomain, settings( 'domain' ) );

	// phpcs:disable
	// if ( $spare ) {
	// $domain = sprintf( '%s.spare', $user->name );
	// }
	// I'm not sure what the above is for.
	// phpcs:enable
	debug( 'Creating sysuser for %s', $domain );

	$user = generate_new_user( $password );

	debug( 'Creating app for %s under sysuser %s', $domain, $user->name );
	$app = provisioner()->create_app( $user, $php_version, $domain, $features );

	// phpcs:enable
	if ( is_wp_error( $app ) ) {
		throw new \Exception( 'Error creating app: ' . $app->get_error_message() );
	}
	// Reuse these credentials for the database, for now...
	$dbname = $app->name . '-wp-blah';
	$dbusername = $app->name;
	$dbpassword = $password;
	$db = provisioner()->create_database( $app->id, $dbname, $dbusername, $dbpassword );

	if ( is_wp_error( $db ) ) {
		throw new \Exception( 'Error creating database for app: ' . $app->get_error_message() );
	}
	if ( $spare ) {
		log_new_unused_site( $app, $password, $features['shortlife'], is_user_logged_in() ? wp_get_current_user() : '' );
	}
	// phpcs:disable PHPCompatibility.PHP.ForbiddenCallTimePassByReference.NotAllowed
	do_action_ref_array( 'jurassic_ninja_add_features_after_create_app', array( &$app, $features, $domain ) );
	// phpcs:enable
	$diff = round( microtime( true ) - $start_time );
	debug( 'Finished creating PHP app %s. Took %02d:%02d.\n', $domain, floor( $diff / 60 ), $diff % 60 );
		$app->username = $user->name;
		$app->domain = $domain;
		$app->subdomain = $subdomain;
		$app->password = $password;
		$app->dbname = $dbname;
		$app->dbusername = $dbusername;
		$app->dbpassword = $dbpassword;
	return $app;
}

/**
 * Stores a record for a freshly created site
 *
 * @param array  $app Site data as returned by ServerPilot's API on creation.
 * @param string $password Password.
 * @param bool   $shortlived Is the site shortlived.
 * @param string $launched_by Site launcher user, if known.
 */
function log_new_unused_site( $app, $password, $shortlived = false, $launched_by = null ) {
	$launched_by = $launched_by ? $launched_by->user_login : '';
	db()->insert(
		'spare_sites',
		array(
			'app_id' => $app->id,
			'username' => $app->name,
			'password' => $password,
			'domain' => figure_out_main_domain( $app->domains ),
			'created' => current_time( 'mysql', 1 ),
		)
	);
	if ( db()->last_error ) {
		l( db()->last_error );
	};
}

/**
 * Stores a record for a launched site
 *
 * @param array  $app Site data as returned by ServerPilot's API on creation.
 * @param string $password Password.
 * @param bool   $shortlived Is the site shortlived.
 * @param string $launched_by Site launcher user, if known.
 */
function log_new_site( $app, $password, $shortlived = false, $launched_by = null ) {
	$launched_by = $launched_by ? $launched_by->user_login : ( is_cli_running() ? 'cli' : '' );

	db()->insert(
		'sites',
		array(
			'username' => $app->name,
			'password' => $password,
			'domain' => figure_out_main_domain( $app->domains ),
			'created' => current_time( 'mysql', 1 ),
			'shortlived' => $shortlived,
			'launched_by' => $launched_by,
		)
	);
	if ( db()->last_error ) {
		l( db()->last_error );
	};
}

/**
 * Stores a record for a purged site
 *
 * @param  array $data Site data as returned by a query to the sites table.
 */
function log_purged_site( $data ) {
	db()->insert(
		'purged',
		array(
			'username' => $data['username'],
			'domain' => $data['domain'],
			'created' => $data['created'],
			'last_logged_in' => $data['last_logged_in'],
			'checked_in' => $data['checked_in'],
			'shortlived' => $data['shortlived'],
			'launched_by' => $data['launched_by'],
		)
	);
	db()->delete(
		'sites',
		array(
			'username' => $data['username'],
			'domain' => $data['domain'],
		)
	);
	if ( db()->last_error ) {
		l( db()->last_error );
	};
}

/**
 * Ensure there are enough space sites.
 *
 * @return int Number of spare sites.
 */
function maintain_spare_sites_pool() {
	$count = db()->get_var( 'select COUNT(*) from spare_sites' );
	$min_spare_sites = settings( 'min_spare_sites' );
	debug( 'Checking spare sites pool' );
	if ( ( $min_spare_sites - $count > 0 ) ) {
		debug( 'Launching a spare site' );
		launch_wordpress( 'php7.0', array(), true );
	} else {
		debug( 'No need to launch more spare sites' );
	}
	return $count;
}

/**
 * Returns all of the sites managed and created by this instance of Jurassic Ninja
 *
 * @return array The list of sites.
 */
function managed_sites() {
	return db()->get_results( 'select * from sites', \ARRAY_A );
}

/**
 * Spare sites getter.
 *
 * @return array List of spare sites.
 */
function spare_sites() {
	return db()->get_results( 'select * from spare_sites', \ARRAY_A );
}

/**
 * Updates the record for the site in the sites table indicating
 * that the creator has at least visited wp-admin once (the first time)
 *
 * @param  string $domain The name of the site.
 */
function mark_site_as_checked_in( $domain ) {
	db()->update(
		'sites',
		array(
			'checked_in' => current_time( 'mysql', 1 ),
		),
		array(
			'domain' => $domain,
		)
	);
	if ( db()->last_error ) {
		l( db()->last_error );
	};
}

/**
 * Deletes the system users (and thus the site and its database)
 * for which their sites have been detected as expired, or never used.
 *
 * @return [type] [description]
 */
function purge_sites() {
	$max_sites = 1;
	$sites = sites_to_be_purged();
	// Purge $max_sites at most so the purge task does not interfere
	// with sites creation given that ServerPilot runs tasks in series.
	$sites = array_slice( $sites, 0, $max_sites );
	$system_users = provisioner()->sysuser_list();
	$purge = array();

	foreach ( $system_users as $user ) {
		$user_sites = array_filter(
			$sites,
			function ( $site ) use ( $user ) {
				return $user->name === $site['username'];
			}
		);
		$user_sites = array_values( $user_sites );

		if ( empty( $user_sites ) ) {
			continue;
		}

		$purge[] = array(
			// A user is created for every site.
			'site' => $user_sites[0],
			'user' => $user,
		);
	}

	debug( 'Purging Jurassic Ninja sites' );
	foreach ( $purge as $data ) {
		$site = $data['site'];
		$user = $data['user'];
		$return = null;

		do_action( 'jurassic_ninja_purge_site', $site, $user );

		$return = provisioner()->delete_site( $user->id );

		if ( is_wp_error( $return ) ) {
			debug(
				'There was an error purging site for user %s: (%s) - %s',
				$user->id,
				$return->get_error_code(),
				$return->get_error_message()
			);
		}
	}
	foreach ( $sites as $site ) {
		log_purged_site( $site );
	}
	$purged = array_map(
		function ( $site ) {
			return $site['domain'];
		},
		$sites
	);
	return $purged;

}

/**
 * Runs a command on the manager server using the username and password for
 * a freshly created system user.
 *
 * @param string $user     System user for ssh.
 * @param string $password System password for ssh.
 * @param string $cmd      The command to run on the shell.
 * @return string          The command output
 */
function run_command_on_behalf( $user, $password, $cmd ) {
	$domain = settings( 'domain' );
	// Redirect all errors to stdout so exec shows them in the $output parameters.
	$run = "SSHPASS=$password sshpass -e ssh -oStrictHostKeyChecking=no $user@$domain '$cmd' 2>&1";
	$output = null;
	$return_value = null;
	// Use exec instead of shell_exect so we can know if the commands failed or not.
	// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
	exec( $run, $output, $return_value );
	// phpcs:enable
	if ( 0 !== $return_value ) {
		debug(
			'Commands run finished with code %s and output: %s',
			$return_value,
			implode( ' -> ', $output )
		);
		return new \WP_Error(
			'commands_did_not_run_successfully',
			"Commands didn't run OK"
		);
	}
	return $output;
}

/**
 * Runs a set of commands via ssh.
 * The command string is a result of applying filter `jurassic_ninja_feature_command`
 *
 * @param  string $user     [description].
 * @param  string $password [description].
 * @param  string $domain   [description].
 *
 * @throws \Exception If a WP_Error is returned.
 */
function run_commands_for_features( $user, $password, $domain ) {
	$wp_home = "~/apps/$user/public";
	$cmd = "cd $wp_home";
	/**
	 * Filters the string of commands that will be run on behalf of the freshly created user
	 * Use it like this, by concatenating the passed command with && and your command.
	 *
	 *    $cmd = 'wp plugin install whatever --activate';
	 *    add_filter( 'jurassic_ninja_feature_command', function ( $previous_commands ) use ( $mycmd ) {
	 *       return "$previous_commands && $mycmd";
	 *    } );
	 *
	 * @param string $cmd commands chained for running
	 */
	$filter_output = apply_filters( 'jurassic_ninja_feature_command', $cmd );
	debug( '%s: Running commands %s', $domain, $filter_output );
	$return = run_command_on_behalf( $user, $password, $filter_output );
	if ( is_wp_error( $return ) ) {
		throw new \Exception( "Commands didn't run OK" );
	}
	debug( '%s: Commands run OK', $domain );
}

/**
 * Calculates and returns sites that the creator has never visited.
 *
 * @return [type] [description]
 */
function sites_never_checked_in() {
	$interval = settings( 'sites_never_checked_in_expiration', 'INTERVAL 1 HOUR' );
	return db()->get_results( "select * from sites where checked_in is NULL and created < DATE_SUB( NOW(), $interval )", \ARRAY_A );
}

/**
 * Calculates and returns sites on which the creator has never logged in with credentials.
 * The sites include:
 *     expired_sites + sites_never_checked_in + sites_never_logged_in
 *
 * @return Array The list of sites that can be purged.
 */
function sites_to_be_purged() {
	$expired = expired_sites();
	$unused = sites_never_checked_in();
	return array_merge( $expired, $unused );
}

/**
 * Checks if a subdomain is already user by a running site.
 *
 * @param string $subdomain  The subdomain to check for collision with an already launched site.
 *
 * @return bool         Return true if the domain is used by a running site.
 */
function subdomain_is_used( $subdomain ) {
	$domain = sprintf( '%s.%s', $subdomain, settings( 'domain' ) );
	$results = db()->get_results( "select * from sites where domain='$domain' limit 1", \ARRAY_A );
	$results2 = db()->get_results( "select * from spare_sites where domain='$domain' limit 1", \ARRAY_A );
	return count( $results ) !== 0 && count( $results2 ) !== 0;
}
