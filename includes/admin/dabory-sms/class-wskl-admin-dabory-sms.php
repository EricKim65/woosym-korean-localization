<?php


class WSKL_Admin_Dabory_SMS {

	public static function init() {

		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'add_settings_pages' ) );
	}

	public static function add_settings_pages( array $settings ) {

		require_once( WSKL_PATH . '/includes/admin/settings/class-wskl-settings-dabory-sms.php' );

		$settings[] = new WSKL_Settings_Dabory_SMS();

		return $settings;
	}
}


WSKL_Admin_Dabory_SMS::init();
