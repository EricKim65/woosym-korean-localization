<?php

require_once( WSKL_PATH . '/includes/lib/geoip/geoip.inc' );


class WSKL_IP_Block {

	public static function init() {

		add_action( 'init', array( __CLASS__, 'wskl_country_ip_block' ) );
	}

	public static function wskl_country_ip_block() {

		// result sample: array( 'country' => KR, 'state' => '' );
		$result     = WC_Geolocation::geolocate_ip( $_SERVER['REMOTE_ADDR'] );
		$white_list = preg_replace( '/\s+/', '', get_option( 'wskl_white_ipcode_list' ) );

		// not a valid white list. ip block will be disabled.
		if( empty( $white_list ) ) {
			return;
		}

		$white_list = explode( ',', $white_list );
		$allowed    = false;

		foreach ( $white_list as $country_code ) {
			if ( $country_code == $result['country'] ) {
				$allowed = true;
				break;
			}
		}

		if ( ! $allowed ) {
			wp_die( 'Blocked by IP' );
		}
	}
}


WSKL_IP_Block::init();
