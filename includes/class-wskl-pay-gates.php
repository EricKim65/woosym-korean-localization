<?php

class WSKL_Pay_Gates {

	private static $pay_gate;

	public static function init() {

		if ( wskl_is_option_enabled( 'enable_sym_pg' ) ) {

			// NOTE: 옵션 켜져 있는 것과 pay_gate_agency 찾을 수 있는 것은 별개의 사항으므로 에러 체크가 필요함.
			static::$pay_gate = get_option( wskl_get_option_name( 'pg_agency' ) );

			if ( static::$pay_gate && ! empty( $pay_gate_agency ) ) {

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
					add_action( 'woocommerce_api_request', array( __CLASS__, 'wskl_add_api_request' ) );

				} else {
					add_action( 'admin_notices', array( __CLASS__, 'output_pay_gate_error' ) );
				}
			}
		}
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
}

WSKL_Pay_Gates::init();