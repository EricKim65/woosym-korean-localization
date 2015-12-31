<?php
namespace wskl\lib\logs;

require_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );
require_once( WSKL_PATH . '/includes/lib/auth/class-auth-model.php' );

use wskl\lib\auth\Auth_Model;
use wskl\lib\cassandra\AddToCartAPI;
use wskl\lib\cassandra\TodaySeenAPI;
use wskl\lib\cassandra\WishListAPI;


class Product_Logs {

	public static function initialize() {

		$fqn = __CLASS__;

		// add-to-cart
		if( \wskl_is_option_enabled( 'enable_add_to_cart_log' ) ) {

			/**
			 * validation 과는 관계가 별로 없다. 괜찮은 훅 선언이 있다면 변경해도 좋음.
			 * 카트에 물품을 넣고 그 정보를 cassandra API 를 통해 전송한다.
			 * @see woocommerce/includes/class-wc-form-handler.php \WC_Form_Handler::add_to_cart_action()
			 */
			add_filter(
				'woocommerce_add_to_cart_validation',
				array( $fqn , 'callback_add_to_cart_validation' ),
				20, 4
			);
		}

		if( \wskl_is_option_enabled( 'enable_wish_list_log' ) ) {

		}

		// count only and only if the page is loaded by the user's direct click
		if( \wskl_is_option_enabled( 'enable_today_seen_log' ) ) {

			/**
			 * send today-seen log
			 *
			 * @see woocommerce/templates/content-single-product.php
			 */
			add_action(
				'woocommerce_before_single_product',
				array( $fqn, 'callback_woocommerce_before_single_product' ),
				99, 0
			);
		}
	}

	/**
	 *
	 * filter: woocommerce_add_to_cart_validation
	 *
	 * @param $is_valid
	 * @param $product_id
	 * @param $quantity
	 * @param $variation_id
	 *
	 * @return bool
	 */
	public static function callback_add_to_cart_validation( $is_valid, $product_id, $quantity, $variation_id = 0 /*, array $variations = array() */) {

		$auth = new Auth_Model( 'marketing-automation' );

		if( $auth->is_verified() ) {

			$key_type  = $auth->get_key_type();
			$key_value = $auth->get_key_value();
			$user_id   = $auth->get_value()->get_user_id();

			$site_url  = site_url();

			AddToCartAPI::send_data( $key_type, $key_value, $site_url, $user_id, $product_id, $quantity, $variation_id );
		}

		// Actually this method is nothing to do with validation process.
		return $is_valid;
	}

	public static function callback_woocommerce_before_single_product() {

		$auth = new Auth_Model( 'marketing-automation' );

		if( $auth->is_verified() ) {

			$key_type  = $auth->get_key_type();
			$key_value = $auth->get_key_value();
			$user_id   = $auth->get_value()->get_user_id();

			$site_url  = site_url();

			$product_id = get_the_ID();

			TodaySeenAPI::send_data( $key_type, $key_value, $site_url, $user_id, $product_id, 0, 0 );
		}
	}
}