<?php

define( 'WSKL_SYM_MVC_PATH', __DIR__ );

require_once( WSKL_SYM_MVC_PATH . '/includes/lib/sym-mvc-helper.php' );
require_once( WSKL_SYM_MVC_PATH . '/includes/lib/class-wskl-sym-custom-data.php' );
require_once( WSKL_SYM_MVC_PATH . '/includes/class-wskl-sym-mvc-main.php' );

if ( is_admin() ) {
	require_once( WSKL_SYM_MVC_PATH . '/includes/class-wskl-sym-mvc-settings.php' );
	require_once( WSKL_SYM_MVC_PATH . '/includes/lib/class-wskl-sym-mvc-admin-api.php' );
}

if ( ! function_exists( 'wskl_sym_custom_data_activate' ) ) :

	function wskl_sym_custom_data_activate() {

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
	register_activation_hook( __FILE__, 'wskl_sym_custom_data_activate' );

endif;


if ( ! function_exists( 'wskl_sym_custom_data_init' ) ) :
	/**
	 * Make sure plugin is loaded first
	 */
	function wskl_sym_custom_data_init() {

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
	add_action( 'activated_plugin', 'wskl_sym_custom_data_init' );

endif;


