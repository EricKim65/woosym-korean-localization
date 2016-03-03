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
		$wpdb->query(
			'CREATE TABLE IF NOT EXISTS sym_custom_data (
        		id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        		order_id INT NOT NULL,
        		custom_key VARCHAR(32) NOT NULL,
        		custom_value TEXT
      		) ENGINE = INNODB;'
		);
	}

	// register activation hook
	register_activation_hook( WSKL_MAIN_FILE, 'wskl_sym_custom_data_activate' );

endif;
