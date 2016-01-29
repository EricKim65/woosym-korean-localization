<?php
/**
 * 아임포트 결제 모듈 (http://www.iamport.kr/)
 *
 * 만약의 경우라도 아임포트 자체에서 제공하는 플러그인 (https://ko.wordpress.org/plugins/iamport-for-woocommerce/) 과의
 * 이름 충돌을 피하기 위해 'iamport' 라는 슬러그에 'wskl_' 접두사를 붙이도록 한다.
 */

class WSKL_Iamport_Main {

	public static function init() {

		/**
		 * 아임포트 게이트웨이 삽입
		 */
		add_filter(
			'woocommerce_payment_gateways', array( __CLASS__, 'callback_woocommerce_payment_gateways' )
		);

		/**
		 * @uses init_wc_gateway_iamport 파일 class-pg-iamport-common.php 에 정의.
		 */
		add_action( 'plugins_loaded', 'init_wc_gateway_wskl_iamport' );
	}

	/**
	 * @filter woocommerce_payment_gateways
	 *
	 * @param array $methods
	 *
	 * @return array list of available payment gateways
	 */
	public static function callback_woocommerce_payment_gateways( array $methods ) {

		// $methods[] = 'WC_Gateway_WSKL_Iamport';

		$wskl_iamport_methods = WC_Gateway_WSKL_Iamport::get_gateway_methods();

		return array_merge( $methods, $wskl_iamport_methods );
	}
}

WSKL_Iamport_Main::init();
