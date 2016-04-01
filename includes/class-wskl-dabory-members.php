<?php
wskl_check_abspath();


/**
 * Class WSKL_Dabory_Members
 */
class WSKL_Dabory_Members {

	const WP_MEMBERS = 'wp-members/wp-members.php';

	/**
	 * initialization
	 */
	public static function init() {

		if ( wskl_is_plugin_inactive( self::WP_MEMBERS ) ) {
			return;
		}

		if ( is_admin() ) {
			wskl_load_module( '/includes/admin/class-wskl-dabory-members-admin.php', 'enable_dabory_members' );
		}

		// 회원 등록 서브모듈
		wskl_load_module( '/includes/lib/dabory-members/wskl-dabory-members-registration.php' );
	}
}


WSKL_Dabory_Members::init();
