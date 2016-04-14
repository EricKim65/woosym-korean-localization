<?php


class WSKL_Dabory_SMS {

	public static function init() {

		if ( WSKL()->is_request( 'admin' ) ) {
			wskl_load_module( '/includes/admin/dabory-sms/class-wskl-admin-dabory-sms.php' );
		}
	}
}


WSKL_Dabory_SMS::init();