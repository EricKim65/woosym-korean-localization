<?php

require_once( WSKL_PATH . '/includes/lib/auth/class-wskl-auth-info.php' );
require_once( WSKL_PATH . '/includes/lib/cassandra-php/api-handler.php' );

use CassandraPHP\PostAPI;


if ( ! defined( 'LAST_POST_EXPORT' ) ) {
	define( 'LAST_POST_EXPORT', wskl_get_option_name( 'last_post_export' ) );
}


class WSKL_Post_Export {

	public static function initialize() {

		if ( ! wskl_license_authorized( 'marketing' ) ) {
			return;
		}

		/**
		 * 블로그 자동 포스팅 메타 박스
		 */
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box_post_export' ), 10, 1 );

		add_action( 'save_post', array( __CLASS__, 'callback_save_post' ), 99, 3 );
	}

	public static function meta_box_post_export( $post_type /*, $post */ ) {

		$post_export_types = wskl_get_option( 'post_export_types' );

		if ( ! in_array( $post_type, $post_export_types ) ) {
			return;
		}
		
		add_meta_box(
			'wskl-post-export-meta-box',
			__( '블로그 자동 포스팅', 'wskl' ),
			array( __CLASS__, 'output_meta_box_post_export' ),
			NULL,
			'side',
			'high',
			array()
		);
	}

	/**
	 * @param \WP_Post $post
	 * @param array    $callback_args keys: id, title, callback, args
	 */
	public static function output_meta_box_post_export( WP_Post $post, array $callback_args ) {

		$context = array(
			'metadata' => get_post_meta( $post->ID, wskl_get_option_name( 'post_export_metadata' ), TRUE ),
			'post'     => &$post,
		);

		wskl_get_template( 'metaboxes/marketing-post-export.php', $context );
	}

	public static function callback_save_post( $post_id, \WP_Post $post, $update ) {

		if ( ! $update || defined( 'DOING_AJAX' ) || defined( 'DOING_AUTOSAVE' ) ) {
			return;
		}

		$is_export_allowed = filter_var( wskl_POST( 'allow-export' ), FILTER_VALIDATE_BOOLEAN );

		if ( ! $is_export_allowed ) {
			return;
		}

		$auth = new WSKL_Auth_Info( 'marketing' );

		if ( $auth->is_verified() ) {

			$key_type  = $auth->get_key_type();
			$key_value = $auth->get_key_value();
			$user_id   = $auth->get_oir()->get_user_id();
			$site_url  = site_url();

			$remote_post_id = PostAPI::send_post( $key_type, $key_value, $site_url, $user_id, $post_id );

			if ( $remote_post_id ) {

				$metadata = array(
					'post_modified'     => $post->post_modified,
					'post_modified_gmt' => $post->post_modified_gmt,
					'exported'          => time(),
					'remote_post_id'    => $remote_post_id,
				);

				update_post_meta( $post_id, wskl_get_option_name( 'post_export_metadata' ), $metadata );
			}
		}
	}
}


WSKL_Post_Export::initialize();