<?php

namespace wskl\lib\posts;

require_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );
require_once( WSKL_PATH . '/includes/lib/auth/class-auth-model.php' );

use wskl\lib\auth\Auth_Model;
use wskl\lib\cassandra\PostAPI;


class Post_Export {

	public static function initialize() {

		add_action( 'save_post', array( __CLASS__, 'callback_save_post' ), 99, 3 );
	}

	public static function callback_save_post( $post_id, \WP_Post $post, $update ) {

		if ( !$update || defined( 'DOING_AJAX' ) || defined( 'DOING_AUTOSAVE' ) ) {
			return;
		}

		$auth = new Auth_Model( 'marketing-automation' );

		if ( $auth->is_verified() ) {

			$key_type  = $auth->get_key_type();
			$key_value = $auth->get_key_value();
			$user_id   = $auth->get_value()->get_user_id();

			$site_url = site_url();

			PostAPI::send_post( $key_type, $key_value, $site_url, $user_id, $post_id );
		}
	}
}