<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * clone of wordpress/wp-admin/includes/plugin.php
 *
 * @see is_plugin_active_for_network()
 *
 * @param $plugin
 *
 * @return bool
 */
function wskl_is_plugin_active_for_network( $plugin ) {

	if ( ! is_multisite() ) {
		return false;
	}

	$plugins = get_site_option( 'active_sitewide_plugins' );
	if ( isset( $plugins[ $plugin ] ) ) {
		return true;
	}

	return false;
}

/**
 * clone of wordpress/wp-admin/includes/plugin.php
 *
 * @see is_plugin_active()
 *
 * @param $plugin
 *
 * @return bool
 */
function wskl_is_plugin_active( $plugin ) {

	return in_array( $plugin, (array) get_option( 'active_plugins', array() ) ) || wskl_is_plugin_active_for_network( $plugin );
}


function wskl_is_plugin_inactive( $plugin ) {

	return ! wskl_is_plugin_active( $plugin );
}


function wskl_woocommerce_found() {

	if ( ! did_action( 'plugins_loaded' ) ) {
		_doing_it_wrong( __FUNCTION__, 'This function must be called after action \'plugins_loaded\'', '3.2.3' );
		// wp_die( 'This function must be called after action \'plugins_loaded\'!' );
	}

	return class_exists( 'WooCommerce' );
}