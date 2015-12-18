<?php
namespace wskl\lib\carts;

require_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );
require_once( WSKL_PATH . '/includes/lib/auth/class-auth-model.php' );

use wskl\lib\auth\Auth_Model;
use wskl\lib\cassandra\AddToCartAPI;


class AddToCarts {

	public function __construct() {

		$this->initialize();
	}

	public function initialize() {

		/**
		 * validation 과는 관계가 별로 없다. 괜찮은 훅 선언이 있다면 변경해도 좋음.
		 * 카트에 물품을 넣고 그 정보를 cassandra API 를 통해 전송한다.
		 * @see woocommerce/includes/class-wc-form-handler.php \WC_Form_Handler::add_to_cart_action()
		 */
		add_filter(
			'woocommerce_add_to_cart_validation',
			array( $this, 'callback_add_to_cart_validation' ),
			20, 3
		);
	}

	/**
	 *
	 * filter: woocommerce_add_to_cart_validation
	 *
	 * @param $is_valid
	 * @param $product_id
	 * @param $quantity
	 *
	 * @return bool
	 */
	public function callback_add_to_cart_validation( $is_valid, $product_id, $quantity ) {

		$auth = new Auth_Model( 'marketing-automation' );

		if( $auth->is_verified() ) {

			$key_type  = $auth->get_key_type();
			$key_value = $auth->get_key_value();
			$user_id   = $auth->get_value()->get_user_id();

			$site_url  = site_url();

			AddToCartAPI::send_data( $key_type, $key_value, $site_url, $user_id, $product_id );
		}

		// Actually this method is nothing to do with validation process.
		return $is_valid;
	}
}