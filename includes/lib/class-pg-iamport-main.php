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
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'callback_woocommerce_payment_gateways' ) );

		/**
		 * @uses init_wc_gateway_iamport 파일 class-pg-iamport-common.php 에 정의.
		 */
		add_action( 'plugins_loaded', 'init_wc_gateway_wskl_iamport' );

		/**
		 * @see woocommerce/templates/order/order-details.php
		 */
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'iamport_order_detail' ) );

		add_action( 'init', array( __CLASS__, 'callback_register_vbank_order_status' ) );

		add_filter( 'wc_order_statuses', array( __CLASS__, 'callback_wc_order_statuses' ) );
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

	/**
	 * @action woocommerce_order_details_after_order_table
	 *
	 * @param WC_Order $order
	 */
	public static function iamport_order_detail( \WC_Order $order ) {

		$checkout_methods = WSKL_Payment_Gates::get_checkout_methods( 'iamport' );

		$pay_method  = $order->iamport_paymethod;
		$receipt_url = $order->iamport_receipt_url;

		$vbank_name = $order->iamport_vbank_name;
		$vbank_num  = $order->iamport_vbank_num;
		$vbank_date = $order->iamport_vbank_date;

		$transction_id = $order->get_transaction_id();

		ob_start();

		switch( $pay_method ) {
			case 'card':
				$pay_method_text = $checkout_methods['credit'];
				include_once( WSKL_PATH . '/includes/lib/iamport/template-simple.php' );
				break;

			case 'trans':
				$pay_method_text = $checkout_methods['remit'];
				include_once( WSKL_PATH . '/includes/lib/iamport/template-simple.php' );
				break;

			case 'phone':
				$pay_method_text = $checkout_methods['mobile'];
				include_once( WSKL_PATH . '/includes/lib/iamport/template-simple.php' );
				break;

			case 'vbank':
				$pay_method_text = $checkout_methods['virtual'];
				include_once( WSKL_PATH . '/includes/lib/iamport/template-vbank.php' );
				break;

			case 'kakao':
				$pay_method_text = $checkout_methods['kakao_pay'];
				include_once( WSKL_PATH . '/includes/lib/iamport/template-simple.php' );
				break;
		}

		ob_end_flush();
	}

	public static function callback_register_vbank_order_status() {

		register_post_status(
			'wc-awaiting-vbank',
			array(
				'label'                     => '가상계좌 입금대기 중',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( '가상계좌 입금대기 중 <span class="count">(%s)</span>', '가상계좌 입금대기 중 <span class="count">(%s)</span>' )
			)
		);
	}

	public static function callback_wc_order_statuses( $order_statuses ) {

		$new_order_statuses = array();

		// pending status다음에 추가
		foreach ( $order_statuses as $key => $status ) {

			$new_order_statuses[ $key ] = $status;

			if ( 'wc-pending' === $key ) {
				$new_order_statuses['wc-awaiting-vbank'] = '가상계좌 입금대기 중';
			}
		}

		return $new_order_statuses;
	}
}


WSKL_Iamport_Main::init();
