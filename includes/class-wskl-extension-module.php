<?php


class WSKL_Extension_Module {

	public static function init() {

		/** 배송추적 */
		wskl_load_module( '/includes/class-wskl-shipping-tracking.php', 'enable_ship_track' );

		/** 바로 구매 */
		wskl_load_module( '/includes/class-wskl-direct-purchase.php', 'enable_direct_purchase' );

		/** 다보리 배송 */
		wskl_load_module( '/includes/class-wskl-shipping-method.php', 'enable_korean_shipping' );

		/** 다보리 멤버스 */
		wskl_load_module( '/includes/dabory-members/class-wskl-dabory-members.php', 'enable_dabory_members' );

		/** 다보리 SMS */
		wskl_load_module( '/includes/dabory-sms/class-wskl-dabory-sms.php', 'enable_dabory_sms' );

		/** 소셜 로그인 */
		wskl_load_module( '/includes/lib/class-social-login.php', 'enable_social_login' );

		/** IP blocking */
		wskl_load_module( '/includes/class-wskl-ip-block.php', 'enable_countryip_block' );

		if ( WSKL()->is_request( 'frontend' ) ) {
			// 상품 리뷰 탭 숨김
			if ( wskl_is_option_enabled( 'hide_product_review_tab' ) ) {
				add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'callback_hide_product_review_tab' ) );
			}
		}
	}

	/**
	 * 상품 리뷰 탭을 숨긴다.
	 *
	 * @callback
	 * @filter    woocommerce_product_tabs
	 *
	 * @param $tabs
	 *
	 * @return array
	 */
	public function callback_hide_product_review_tab( $tabs ) {

		if ( isset( $tabs['reviews'] ) ) {
			unset( $tabs['reviews'] );
		}

		return $tabs;
	}
}


WSKL_Extension_Module::init();
