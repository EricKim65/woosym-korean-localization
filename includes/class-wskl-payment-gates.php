<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSKL_Payment_Gates {

	private static $pay_gate;

	public static function init() {

		// NOTE: 옵션 켜져 있는 것과 pay_gate_agency 찾을 수 있는 것은 별개의 사항으므로 에러 체크가 필요함.
		static::$pay_gate = get_option( wskl_get_option_name( 'pg_agency' ) );

		// NOTE: 이전 버전 (<=3.2.0) 기능 호환을 위해 사용. 앞으로는 글로벌 변수는 사용을 자제할 것.
		static::export_globals();

		if ( static::$pay_gate && ! empty( static::$pay_gate ) ) {

			$pg_main_path   = WSKL_PATH . '/includes/lib/class-pg-' . static::$pay_gate . '-main.php';
			$pg_common_path = WSKL_PATH . '/includes/lib/class-pg-' . static::$pay_gate . '-common.php';

			if ( file_exists( $pg_main_path ) && file_exists( $pg_common_path ) ) {

				/** @noinspection PhpIncludeInspection */
				require_once( $pg_main_path );

				/** @noinspection PhpIncludeInspection */
				require_once( $pg_common_path );

				/**
				 * Woocommerce REST API V3 action.
				 *
				 * @see \WC_API::handle_api_requests()
				 */
				add_action( 'woocommerce_api_request', array( __CLASS__, 'add_api_request' ) );

			} else {
				add_action( 'admin_notices', array( __CLASS__, 'output_pay_gate_error' ) );
			}
		}
	}

	/**
	 * 이전 버전 기능 호환을 위해
	 */
	private static function export_globals() {

		global $woocommerce_ver21_less;
		global $pay_gate_agency;
		global $sym_checkout_titles;
		global $sym_checkout_desc;
		global $sym_pg_agency;

		$woocommerce_ver21_less = version_compare( WOOCOMMERCE_VERSION, '2.1', '<' ) ? true : false;
		$pay_gate_agency        = get_option( wskl_get_option_name( 'pg_agency' ) );
		$sym_checkout_titles    = static::get_checkout_methods();
		$sym_checkout_desc      = static::get_checkout_method_postfix();
		$sym_pg_agency          = get_option( wskl_get_option_name( 'pg_agency' ) );
	}

	public static function get_checkout_methods( $extra_agency_slug = '' ) {

		static $methods;

		if( !$methods ) {
			$methods = array(
				'credit'  => __( '신용카드', 'wskl' ),
				'remit'   => __( '실시간 계좌이체', 'wskl' ),
				'virtual' => __( '가상계좌 이체', 'wskl' ),
				'mobile'  => __( '모바일소액결제', 'wskl' ),
			);
		}

		switch( $extra_agency_slug ) {
			case 'iamport':
				return array_merge( $methods, array( 'kakao_pay' => __( '카카오페이', 'wskl' ) ) );
		}

		return $methods;
	}

	public static function get_checkout_method_postfix() {

		return __( '로 결제합니다.', 'wskl' );
	}

	public static function add_api_request( $api_request ) {

		if ( class_exists( $api_request ) ) {
			new $api_request();
		}
	}

	public static function output_pay_gate_error() {

		printf(
			'<div class="notice error"><p class="wskl-warning">%s: %s</p></div>',
			__( '다음 PG 모듈이 발견되지 않았습니다.', 'wskl' ),
			static::$pay_gate
		);
	}

	public static function get_pay_gates() {

		return array(
			'payapp' => __( '페이앱', 'wskl' ),
			'kcp' => __( 'KCP', 'wskl' ),
			'inicis' => __( '이니시스', 'wskl' ),
			'ags' => __( '올더게이트', 'wskl' ),
			'iamport' => __( '아임포트', 'wskl' ),
		);
	}
}


WSKL_Payment_Gates::init();