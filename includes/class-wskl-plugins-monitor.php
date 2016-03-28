<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WSKL_Plugin_Monitor
 */
class WSKL_Plugin_Monitor {

	public static $plugins = array();

	public static function check_plugin_status() {

		foreach ( static::$plugins as $p ) {

			$plugin_file = $p[0];
			$trigger     = $p[1];
			$callback    = $p[2];

			if ( empty( $trigger ) || ! is_callable( $callback ) ) {
				continue;
			}

			switch ( $trigger ) {

				case 'active':
					if ( wskl_is_plugin_active( $plugin_file ) ) {
						call_user_func_array( $callback, array( $plugin_file, $trigger ) );
					}
					break;

				case 'inactive':
					if ( wskl_is_plugin_inactive( $plugin_file ) ) {
						call_user_func_array( $callback, array( $plugin_file, $trigger ) );
					}
					break;

			}
		}
	}
}


/**
 * @param        $plugin_file
 * @param string $trigger
 * @param string $callback
 */
function wskl_add_plugin_status( $plugin_file, $trigger = 'active', $callback = '' ) {

	WSKL_Plugin_Monitor::$plugins[] = array( $plugin_file, $trigger, $callback );
}

/**
 *
 */
function wskl_check_plugin_status() {

	apply_filters( 'wskl_plugin_status', WSKL_Plugin_Monitor::$plugins );
	WSKL_Plugin_Monitor::check_plugin_status();
}

