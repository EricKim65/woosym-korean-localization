<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( WSKL_PATH . '/includes/lib/geoip/geoip.inc' );


class WSKL_IP_Block {

	public static function init() {


		$database_files = self::get_geolocate_database_files();

		foreach ( $database_files as $file ) {

			if ( ! file_exists( $file ) ) {

				WC_Geolocation::update_database();

				// check again for stability.
				if ( ! file_exists( $file ) ) {
					add_action( 'admin_notices',
					            array(
						            __CLASS__,
						            'output_database_not_found',
					            ) );

					return;
				}
			}
		}

		add_action( 'init', array( __CLASS__, 'wskl_country_ip_block' ) );

		/**
		 * @see wordpress/wp-includes/options.php
		 * @see update_option()
		 */
		add_filter( 'pre_update_option_' . wskl_get_option_name( 'white_ipcode_list' ),
		            array( __CLASS__, 'set_target_domain' ) );
	}

	public static function get_geolocate_database_files() {

		$upload_dir = wp_upload_dir();

		return array(
			$upload_dir['basedir'] . '/GeoIP.dat',
			$upload_dir['basedir'] . '/GeoIPv6.dat',
		);
	}

	/**
	 * @action  admin_notices
	 * @used-by init()
	 */
	public static function output_database_not_found() {

		printf( '<div class="error notice"><p>%s</p></div>',
		        __( '다보리 차단보안기능 알림: Geolocate 데이터베이스 파일이 발견되지 않아, 차단 기능이 동작하지 않고 있습니다.',
		            'wskl' ) );
	}

	public static function wskl_country_ip_block() {

		// 옵션을 업데이트할 때 발생하는 값을 체크하여,
		// DB 덤프 등으로 옮겨 놓은 직후 임시 상태의 사이트에 대해서 IP 블록을 생략.
		$target = wskl_get_option( 'ip_block_target' );
		if ( site_url() != $target ) {
			return;
		}

		// result sample: array( 'country' => KR, 'state' => '' );
		$result     = WC_Geolocation::geolocate_ip( $_SERVER['REMOTE_ADDR'] );
		$white_list = preg_replace( '/\s+/',
		                            '',
		                            get_option( 'wskl_white_ipcode_list' ) );

		// not a valid white list. ip block will be disabled.
		if ( empty( $white_list ) ) {
			return;
		}

		// at least open for wp-login,

		$white_list = explode( ',', $white_list );
		$allowed    = FALSE;

		foreach ( $white_list as $country_code ) {
			if ( $country_code == $result['country'] ) {
				$allowed = TRUE;
				break;
			}
		}

		if ( ! $allowed ) {
			wp_die( 'Blocked by IP' );
		}
	}

	public static function set_target_domain( $value ) {

		update_option( wskl_get_option_name( 'ip_block_target' ), site_url() );

		return $value;
	}
}


WSKL_IP_Block::init();
