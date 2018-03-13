<?php

namespace jn;

if ( ! defined( '\\ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/db-stuff.php';
require_once __DIR__ . '/settings-stuff.php';

/**
 * Used by the main plugin file to register a cron job
 * for purging sites.
 * @param  string $plugin_file Nothing super useful. the plugin path
 */
function add_cron_job( $plugin_file ) {
	if ( ! wp_next_scheduled( 'jurassic_ninja_purge' ) ) {
		wp_schedule_event( time(), 'hourly', 'jurassic_ninja_purge' );
	}

	add_action( 'jurassic_ninja_purge', 'jn\jurassic_ninja_purge_cron_task' );
	// Remove task on plugin deactivation
	if ( wp_next_scheduled( 'jurassic_ninja_purge' ) ) {
		register_deactivation_hook( $plugin_file, 'jn\jurassic_ninja_cron_task_deactivation');
	}

}

/**
 * Attempts to purge sites calculated as ready to be purged
 * @return [type] [description]
 */
function jurassic_ninja_purge_cron_task() {
	if ( settings( 'purge_sites_when_cron_runs', true ) ) {
		$return = purge_sites();
		if ( is_wp_error( $return ) ) {
			debug( 'There was an error purging sites: (%s) - %s',
				$return->get_error_code(),
				$return->get_error_message()
			);
		}
	}
}

/**
 * De-registers the jurassic_ninja_purge cron task
 */
function jurassic_ninja_cron_task_deactivation() {
	wp_clear_scheduled_hook( 'jurassic_ninja_purge' );
}
