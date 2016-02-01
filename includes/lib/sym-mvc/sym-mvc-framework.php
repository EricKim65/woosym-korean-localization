<?php

define( 'SYM_MVC_PATH', __DIR__ );

require_once( SYM_MVC_PATH . '/includes/lib/sym-mvc-helper.php' );
require_once( SYM_MVC_PATH . '/includes/lib/class-sym-custom-data.php' );
require_once( SYM_MVC_PATH . '/includes/class-sym-mvc-main.php' );

if ( is_admin() ) {
	include_once( SYM_MVC_PATH . '/includes/class-sym-mvc-settings.php' );
	include_once( SYM_MVC_PATH . '/includes/lib/class-sym-mvc-admin-api.php' );
}


function sym_custom_data_activate() {

	global $wpdb;

	// create custom order data table
	$wpdb->query( '
      CREATE TABLE IF NOT EXISTS sym_custom_data (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        custom_key VARCHAR(32) NOT NULL,
        custom_value TEXT
      ) ENGINE = INNODB;
    ' );
}

// register activation hook
register_activation_hook( __FILE__, 'sym_custom_data_activate' );

/**
 * Make sure plugin is loaded first
 */
function sym_custom_data_init() {

	// init path
	$path    = str_replace( WP_PLUGIN_DIR . '/', '', __FILE__ );
	$plugins = get_option( 'active_plugins' );
	if ( ! $plugins ) {
		return;
	}

	// search for plugin key
	$key = array_search( $path, $plugins );

	// check if plugin key is found
	if ( ! $key ) {
		return;
	}

	// shift the key to the first position
	array_splice( $plugins, $key, 1 );
	array_unshift( $plugins, $path );

	// update active plugins
	update_option( 'active_plugins', $plugins );
}

// register activation action
add_action( 'activated_plugin', 'sym_custom_data_init' );
