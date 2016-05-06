<?php


class WSKL_Essential_Module {

	public static function init() {

		// 휴면계정 설정
		wskl_load_module( '/includes/inactive-accounts/class-wskl-inactive-accounts.php', 'enable_inactive_accounts' );

		// 한국 원화 표시 설정
		if ( wskl_is_option_enabled( 'korean_won' ) ) {
			add_filter( 'woocommerce_currencies', array( __CLASS__, 'callback_currencies' ) );
			add_filter( 'woocommerce_currency_symbol', array( __CLASS__, 'callback_currency_symbol' ), 10, 2 );
		}

		// SKU 사용 해제
		if ( wskl_is_option_enabled( 'disable_sku' ) ) {
			add_filter( 'wc_product_sku_enabled', '__return_false' );
		}

		// 상점으로 돌아가기 버튼 해제
		if ( wskl_is_option_enabled( 'disable_returntoshop' ) ) {
			add_filter(
				'woocommerce_return_to_shop_redirect',
				array( __CLASS__, 'callback_return_to_ship_redirect' )
			);
		}

		if ( WSKL()->is_request( 'frontend' ) ) {
			// 관련상품표시
			if ( absint( wskl_get_option( 'related_products_count' ) ) ) {
				$priority = absint( wskl_get_option( 'related_products_priority' ) );
				add_filter(
					'woocommerce_output_related_products_args',
					array( __CLASS__, 'callback_related_products_args' ),
					$priority
				);
			}

			/** 한국형 주소 및 체크아웃 필드 구성 */
			wskl_load_module(
				'/includes/class-wskl-sym-checkout.php',
				'enable_sym_checkout'
			);
		}

		/** 입금인 지정 기능 (BACS 입금자 다른 이름) */
		wskl_load_module(
			'/includes/class-wskl-bacs-payer-name.php',
			'enable_bacs_payer_name'
		);

		/** 복합과세 */
		wskl_load_module( '/includes/class-wskl-combined-tax.php' );
	}

	/**
	 * @callback
	 * @filter    woocommerce_currencies
	 *
	 * @param  array $currencies
	 *
	 * @return array
	 */
	public static function callback_currencies( $currencies ) {

		$currencies['KRW'] = _x( '대한민국', '한국 원화 설정', 'wskl' );

		return $currencies;
	}

	/**
	 * @callback
	 * @filter    woocommerce_currency_symbol
	 *
	 * @param $currency_symbol
	 * @param $currency
	 *
	 * @return string
	 */
	public static function callback_currency_symbol( $currency_symbol, $currency ) {

		switch ( $currency ) {
			case 'KRW':
				$currency_symbol = __( '원', 'wskl' );
				break;
		}

		return $currency_symbol;
	}

	/**
	 * @callback
	 * @action
	 *
	 * @return string
	 */
	public static function callback_return_to_ship_redirect() {

		return get_site_url();
	}

	/**
	 * @callback
	 * @filter    woocommerce_output_related_products_args
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public static function callback_related_products_args( $args ) {

		$args['posts_per_page'] = absint( wskl_get_option( 'related_products_count' ) );
		$args['columns']        = absint( wskl_get_option( 'related_products_columns' ) );

		return $args;
	}
}


WSKL_Essential_Module::init();
