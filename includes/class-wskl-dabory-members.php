<?php


class WSKL_Dabory_Members {

	const WP_MEMBERS = 'wp-members/wp-members.php';

	public static function init() {

		if ( wskl_is_plugin_inactive( self::WP_MEMBERS ) ) {
			return;
		}

		if ( is_admin() ) {
			wskl_load_module( '/includes/admin/class-wskl-dabory-members-admin.php', 'enable_dabory_members' );
		}
	}
}


WSKL_Dabory_Members::init();
