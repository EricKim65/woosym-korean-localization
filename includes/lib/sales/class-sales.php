<?php
namespace wskl\lib\sales;

require_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );
require_once( WSKL_PATH . '/includes/lib/auth/class-auth-model.php' );

use wskl\lib\auth\Auth_Model;
use wskl\lib\cassandra\SalesAPI;


class Sales {

	public function __construct() {

		$this->initialize();
	}

	public function initialize() {

		/**
		 * @see woocommerce/includes/abstracts/abstract-wc-order.php update_status()
		 */
		add_action( 'woocommerce_order_status_processing', array( $this, 'callback_order_status') );

		/**
		 *  @see woocommerce/includes/abstracts/abstract-wc-order.php update_status()
		 */
		// add_action( 'woocommerce_order_status_completed', array( $this, 'callback_order_status' ) );
	}

	public function callback_order_status( $order_id ) {

		$order = wc_get_order( $order_id );

		// 중복 전송 금지 하려면 아래 세 줄의 주석을 해제.
//		$completed_date = get_post_meta( $order_id, '_completed_date', true );
//		if( !empty( $completed_date ) ) {
//			return;
//		}

		$this->send_sales_log( $order );
	}

	private function send_sales_log( $order_id ) {

		$auth = new Auth_Model( 'payment' );

		if ( $auth->is_verified() ) {

			$key_type  = $auth->get_key_type();
			$key_value = $auth->get_key_value();
			$site_url  = site_url();

			SalesAPI::send_data( $key_type, $key_value, $site_url, $order_id );
		}
	}
}