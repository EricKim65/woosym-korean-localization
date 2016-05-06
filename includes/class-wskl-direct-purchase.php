<?php


class WSKL_Direct_Purchase {

	public static function init() {

		// 즉시 구매 버튼 추가.
		add_action(
			'woocommerce_after_add_to_cart_button',
			array( __CLASS__, 'woocommerce_after_add_to_cart_button_callback' )
		);

		// ajax 요청으로 해당 물품에 대해 바로 장바구니에 집어 넣고
		// cart 페이지로 이동하면 됨
		add_action(
			'wc_ajax_wskl_direct_purchase_action',
			array( __CLASS__, 'direct_purchase_action_callback' )
		);

		add_action(
			'wp_ajax_test_action',
			array( __CLASS__, 'test_action' )
		);

		add_action(
			'wp_enqueue_scripts',
			array( __CLASS__, 'enqueue_scripts_callback' )
		);
	}

	public static function test_action() {

		echo 'test passed';
		die();
	}

	/**
	 * 각 상품 구매 페이지에서 'add to cart' 버튼 다음 'direct purchase' 버튼이 나오게끔 처리
	 */
	public static function woocommerce_after_add_to_cart_button_callback() { ?>
		<style type="text/css">
			div.variations_button {
				float: left;
			}

			/* to align buttons in variable product pages */
		</style>
		<span class="wskl-direct-purchase spacing">&nbsp;</span>
		<button type="button" id="wskl-direct-purchase" class="single_add_to_cart_button button alt">
			<?php _e( '바로구매', 'wskl' ); ?>
		</button>
		<div style="clear:both;"></div>
	<?php }

	/**
	 * direct purchase 의 ajax call 처리를 담당하는 함수
	 */
	public static function direct_purchase_action_callback() {

		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			die();
		}

		$product_id        = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $_REQUEST['add-to-cart'] ) );
		$quantity          = empty( $_REQUEST['quantity'] ) ? 1 : wc_stock_amount( $_POST['quantity'] );
		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', TRUE, $product_id, $quantity );
		$product_status    = get_post_status( $product_id );

		if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity ) && $product_status == 'publish' ) {

			// do_action( 'woocommerce_ajax_added_to_cart', $product_id );

			$data = array(
				'success'     => TRUE,
				'product_url' => apply_filters(
					'woocommerce_cart_redirect_after_error',
					get_permalink( $product_id ),
					$product_id
				),
			);

			wp_send_json( $data );

		} else {

			// If there was an error adding to the cart, redirect to the product page to show any errors
			$data = array(
				'error'       => FALSE,
				'product_url' => apply_filters(
					'woocommerce_cart_redirect_after_error',
					get_permalink( $product_id ),
					$product_id
				),
				'reason'      => 'boo boo',
			);

			wp_send_json( $data );
		}

		die();
	}

	/**
	 * direct purchase 버튼의 동적 처리를 담당하는 스크립트 로드
	 */
	public static function enqueue_scripts_callback() {

		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );

		if ( $post_type == 'product' ) {
			wp_register_script(
				'wskl-direct-purchase',
				plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/direct-purchase.js',
				array( 'jquery' ),
				NULL,
				TRUE
			);

			if ( version_compare( WC()->version, '2.5.0', '>=' ) ) {
				$checkout_url = wc_get_checkout_url();
			} else {
				$checkout_url = WC()->cart->get_checkout_url();
			}

			wp_localize_script(
				'wskl-direct-purchase',
				'wskl_direct_purchase_object',
				array(
					'ajax_url'     => get_permalink( $post_id ),
					'checkout_url' => $checkout_url,
					// 'woocommerce_cart_redirect_after_add' => filter_var( get_option('woocommerce_cart_redirect_after_add'), FILTER_VALIDATE_BOOLEAN ),
				)
			);
			wp_enqueue_script( 'wskl-direct-purchase' );
		}
	}
}


WSKL_Direct_Purchase::init();
