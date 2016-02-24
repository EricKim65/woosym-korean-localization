<?php


class WSKL_Combined_Tax {

	private static $predefined_tax_classes = NULL;

	public static function init() {

		if ( ! static::$predefined_tax_classes ) {
			static::$predefined_tax_classes = array(
				__( 'Regular VAT', 'wskl' ),
				__( 'Exclude Tax', 'wskl' ),
			);
		}

		add_action( 'update_option_' . wskl_get_option_name( 'enable_combined_tax' ),
		            array( __CLASS__, 'callback_init_combined_tax_classes' ),
		            10, 3 );

		if ( wskl_is_option_enabled( 'hide_display_cart_tax' ) ) {
			add_filter( 'woocommerce_cart_totals_order_total_html',
			            array( __CLASS__, 'callback_hide_include_tax' ) );
		}

		if ( wskl_is_option_enabled( 'enable_combined_tax' ) ) {
			add_filter( 'woocommerce_pay_form_args',
			            array( __CLASS__, 'callback_pay_form_args' ), 20 );
		}
	}

	private static function find_tax_rate_label( array $rates, $label ) {

		foreach ( $rates as $rate ) {
			if ( isset( $rate['label'] ) && $rate['label'] == $label ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	private static function init_tax_classes() {

		$redefined = array_map( 'trim',
		                        array_merge( static::$predefined_tax_classes,
		                                     array_diff( WC_Tax::get_tax_classes(),
		                                                 static::$predefined_tax_classes ) ) );

		update_option( 'woocommerce_tax_classes', implode( "\n", $redefined ) );


		///////////////////////////////////////////////
		// check and add '과세 상품' class

		$taxed_class = sanitize_title( static::$predefined_tax_classes[0] );

		/** @var array $taxed following keys are present:
		 *                   - rate
		 *                   - label
		 *                   - shipping
		 *                   - compound
		 */
		$taxed = WC_Tax::find_rates( array(
			                             'country'   => 'KR',
			                             'state'     => '',
			                             'city'      => '',
			                             'postcode'  => '',
			                             'tax_class' => $taxed_class,
		                             ) );

		$taxed_rate_label = __( '과세율', 'wskl' );
		$taxed_rate_found = static::find_tax_rate_label( $taxed,
		                                                 $taxed_rate_label );

		if ( ! $taxed_rate_found ) {
			WC_Tax::_insert_tax_rate( array(
				                          'tax_rate_country'  => 'KR',
				                          'tax_rate_state'    => '',
				                          'tax_rate'          => '10.00',
				                          'tax_rate_name'     => $taxed_rate_label,
				                          'tax_rate_priority' => 1,
				                          'tax_rate_shipping' => 0,
				                          'tax_rate_compound' => 0,
				                          'tax_rate_order'    => 0,
				                          'tax_rate_class'    => $taxed_class,
			                          ) );
		}

		///////////////////////////////////////////////
		// check and add '비과세 상품' class

		$untaxed_class = sanitize_title( static::$predefined_tax_classes[1] );

		/** @var array $untaxed */
		$untaxed = WC_Tax::find_rates( array(
			                               'country'   => 'KR',
			                               'state'     => '',
			                               'city'      => '',
			                               'postcode'  => '',
			                               'tax_class' => $untaxed_class,
		                               ) );

		$untaxed_rate_label = __( '비과세율', 'wskl' );
		$untaxed_rate_found = static::find_tax_rate_label( $untaxed,
		                                                   $untaxed_rate_label );


		if ( ! $untaxed_rate_found ) {
			WC_Tax::_insert_tax_rate( array(
				                          'tax_rate_country'  => 'KR',
				                          'tax_rate_state'    => '',
				                          'tax_rate'          => '0.00',
				                          'tax_rate_name'     => $untaxed_rate_label,
				                          'tax_rate_priority' => 1,
				                          'tax_rate_shipping' => 0,
				                          'tax_rate_compound' => 0,
				                          'tax_rate_order'    => 0,
				                          'tax_rate_class'    => $untaxed_class,
			                          ) );
		}
	}

	private static function reset_tax_classes() {

		$redefined = array_map( 'trim', array_diff( WC_Tax::get_tax_classes(),
		                                            static::$predefined_tax_classes ) );

		update_option( 'woocommerce_tax_classes', implode( "\n", $redefined ) );
	}

	private static function set_tax_options() {

		///////////////////////////////////////////////////////////////////////
		// TAX Options
		///////////////////////////////////////////////////////////////////////
		// Enable Taxes: Enabled taxes and tax calculations
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Prices Entered With Tax: Yes, I will enter prices inclusive of tax
		update_option( 'woocommerce_prices_include_tax', 'yes' );

		// Calculate Tax Based on: Customer shipping addresses
		update_option( 'woocommerce_tax_based_on', 'shipping' );

		// Shipping Tax Class: Shipping tax class based on cart items
		update_option( 'woocommerce_shipping_tax_class', '' );

		// Rounding: Round tax at subtotal level, instead of rounding per line
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );

		// Display Prices in the Shop: Including tax
		update_option( 'woocommerce_tax_display_shop', 'incl' );

		// Display Prices During Cart and Checkout: Including tax
		update_option( 'woocommerce_tax_display_cart', 'incl' );

		// Price Display Suffix: no choice
		update_option( 'woocommerce_price_display_suffix', '' );

		// Display Tax Totals: As a single total
		update_option( 'woocommerce_tax_total_display', 'single' );

		///////////////////////////////////////////////////////////////////////
		// Shipping Options
		///////////////////////////////////////////////////////////////////////
		$flat_rate_settings = get_option( 'woocommerce_flat_rate_settings' );

		$flat_rate_settings['enabled'] = 'yes';
		$flat_rate_settings['availability'] = 'specific';
		$flat_rate_settings['countries'] = array( 'KR' );
		$flat_rate_settings['tax_status'] = 'taxable';
		$flat_rate_settings['cost'] = '2727'; // KRW 3,000 - VAT

		update_option( 'woocommerce_flat_rate_settings', $flat_rate_settings );
	}

	public static function callback_init_combined_tax_classes( $old_value, $value, $option ) {

		if ( $value == 'on' ) {
			static::init_tax_classes();
			static::set_tax_options();
		} else {
			static::reset_tax_classes();
		}
	}

	public static function callback_hide_include_tax( $value ) {

		$value = preg_replace( '/<small class="includes_tax">.+<\/small>/', '',
		                       $value );

		return $value;
	}

	public static function callback_pay_form_args( array $pay_form_args ) {

		// var_dump( $pay_form_args );

		$pg_agency = wskl_get_option( 'pg_agency' );

		if ( method_exists( __CLASS__, "combined_tax_{$pg_agency}" ) ) {
			return call_user_func_array( array(
				                             __CLASS__,
				                             "combined_tax_{$pg_agency}",
			                             ), array( $pay_form_args, ) );
		}

		return $pay_form_args;
	}

	private static function combined_tax_kcp( array $pay_form_args ) {

		// 복합과세를 위한 서비스 코드
		$pay_form_args['tax_flag'] = 'TG03';

		/**
		 * == 노트 ==
		 * good_mny = (공급가액) + (부가세)   진체 지불할 금액
		 *
		 * (과세 승인 금액)   = 과세 상품의 공급가액
		 * (비과세 승인 금액) = 비과세 상품의 공급가액 + 0  // 비과세이므로 부가가치세는 0
		 * (부가가치세)      = 과세상품의 부가가치세
		 */

		// 부가가치세
		$taxes_total = WC()->cart->get_taxes_total();

		// 배송비
		$shipping_price = WC()->cart->shipping_total;
		$shipping_tax   = wc_round_tax_total( WC()->cart->shipping_tax_total );

		// 과세/비과세 승인금액의 합.
		$supply_value_vat     = $shipping_price - $taxes_total;
		$supply_value_non_vat = 0;

		foreach ( WC()->cart->get_cart() as $item ) {
			if ( $item['line_tax'] == 0 ) {
				$supply_value_non_vat += ( $item['data']->price * $item['quantity'] ); // 비과세상품
			} else {
				$supply_value_vat += ( $item['data']->price * $item['quantity'] ); // 과세상품
			}
		}

		// 과세 승인금액
		$pay_form_args['comm_tax_mny'] = $supply_value_vat;

		// 비과세 승인금액
		$pay_form_args['comm_free_mny'] = $supply_value_non_vat;

		// 부가가치세
		$pay_form_args['comm_vat_mny'] = $taxes_total + $shipping_tax;

		return $pay_form_args;
	}
}


WSKL_Combined_Tax::init();