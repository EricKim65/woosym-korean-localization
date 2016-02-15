<?php
/**
 * 페이앱 (http://payapp.kr)
 *
 * 1. 페이앱 REST API 를 이용해 결제 요청
 * 2. API 응답으로 payurl 주소가 리턴.
 * 3. 고객을 payurl 주소로 이동시켜 결제 유도.
 * 4. 결제가 이루어지면 페이앱은 1번 결제 요청 시 첨부한 feedbackurl 값으로 POST 전송을 보냄. (페이앱 서버에서 접속하며, 고객의 브라우저와 무관)
 * 5. 페이앱의 피드백을 보고 주문에 대한 결제가 올바르게 승인되었는지 확인한 후, 나머지 결제 처리를 진행.
 */

/**
 * Class PayApp_Main
 */
class PayApp_Main {

	public static function init() {

		/**
		 * 게이트웨이 삽입
		 */
		add_filter(
			'woocommerce_payment_gateways',
			array( __CLASS__, 'callback_woocommerce_payment_gateways' )
		);

		/**
		 * URL 파라미터 "wc-ajax=wskl-payapp-status" 에 대응.
		 *
		 * @see woocommerce\includes\class-wc-ajax.php
		 * @see WC_Ajax::do_wc_ajax()
		 */
		add_action(
			'wc_ajax_' . 'wskl-payapp-status',
			array( __CLASS__, 'callback_payapp_status' )
		);

		/**
		 * @uses init_wc_gateway_payapp 파일 class-pg-payapp-common.php 에 정의.
		 */
		add_action( 'plugins_loaded', 'init_wc_gateway_payapp' );
	}

	/**
	 * @filter woocommerce_payment_gateways
	 *
	 * @param array $methods
	 *
	 * @return array
	 */
	public static function callback_woocommerce_payment_gateways( array $methods ) {

		$payapp_methods = WC_Gateway_Payapp::get_gateway_methods();

		return array_merge( $methods, $payapp_methods );
	}

	/**
	 * AJAX 콜백. order key 에 대응해서 해당 order 의 주문 상태를 반환한다.
	 *
	 * 요구하는 파라미터: $_GET['order_key']
	 * JSON 응답:
	 *  success:      bool
	 *  message:      string  success or 에러 메시지
	 *  redirect:     string  리다이렉트 주소
	 *  order_id:     int     success=true 이면 order id
	 *  order_status: string  success=true 이면 order status 문자열. (pending|processing|completed)
	 *
	 * @action wc_ajax_{wskl-payapp-status}
	 */
	public static function callback_payapp_status() {

		if ( ! defined( 'DOING_AJAX' ) || ! defined( 'WC_DOING_AJAX' ) ) {
			die( - 1 );
		}

		$order_key = isset( $_GET['order_key'] ) ? sanitize_text_field( $_GET['order_key'] ) : 0;
		$order     = wc_get_order( wc_get_order_id_by_order_key( $order_key ) );

		if ( !$order ) {
			wc_add_notice( __( '주문 과정에 문제가 발생했습니다. 다시 시도해 주세요.', 'wskl' ), 'error' );
			wp_send_json(
				array(
					'success'  => false,
					'message'  => 'An invalid order key received.',
					'redirect' => wc_get_checkout_url(),
				)
			);
			die();
		}

		if ( $order->has_status( array( 'processing', 'completed' ) ) ) {
			$redirect = $order->get_checkout_order_received_url();
		} else {
			$redirect = '';
		}

		wp_send_json(
			array(
				'success'      => true,
				'message'      => 'success',
				'order_id'     => $order->id,
				'order_status' => $order->get_status(),
				'redirect'     => $redirect,
			)
		);

		die();
	}
}

PayApp_Main::init();