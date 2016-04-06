<?php
wskl_check_abspath();

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

	return class_exists( 'WooCommerce' );
}