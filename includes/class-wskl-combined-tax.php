<?php


/**
 * Class WSKL_Combined_Tax
 *
 * 복합과세를 위한 클래스.
 *
 * 다보리 옵션에서 핵심기능 - 복합과세 활성화를 선택한 경우에 동작한다.
 *
 * # 기능
 * 1. 상품의 과세/비과세 설정에 따라 세금 계산을 진행한다.
 * 2. 복합과세 메뉴 설정하고 옵션을 저장할 때 미리 정해진대로의 세팅을 초기화한다.
 *
 * # 사용법
 * 과세/비과세 계산을 하기 위해서는 대시보드 '상품' 메뉴에서 각 상품에 대해 과세/비과세
 * 설정을 해야 한다. 설정은 각 상품 정보의 '세금 클래스' 항목에서 선택할 수 있다.
 * '과세' 상품이면 "Regular VAT", '비과세' 상품이면 "Exclude Tax"를 선택하면 된다.
 *
 * # 주의
 * 세금 계산을 올바르게 하기 위해서는 전 상품에 대해 과세/비과세 설정을 정확히 해야 한다.
 * 다시 말해 과세 상품이라면 "Regular VAT"를,
 * 비과세 상품이라면 "Exclude Tax"를 선택해야 한다. 다른 선택을 해서
 * 결제 시 신용카드 전표에 올바르게 부가세가 찍히지 않은 사항에 대해서는 책임질 수 없다.
 *
 * # 배송비와의 연계
 * 고객이 과세 상품이나 과세 상품과 비과세 상품을 섞어 구매하는 경우는 관계 없다.
 * 그러나 비과세 상품만을 선택해 결제를 진행하는 경우에는 약간의 조정이 필요하다.
 * 기본적으로 우커머스가 기본적으로 배송비까지 비과세로 파악하여 배송비의 부가세를
 * 계산하지 않는 일이 발생하기 때문이다.
 *
 * 반드시 알아 두어야 할 사실 하나가 더 있다. 배송 클래스 설정에서 배송비를 설정할 때는
 * 세금을 제하고 입력해야 한다. 그러므로 만일 배송비가 3000원이라면, 실제 설정에서 입력할
 * 배송비는 2727원이 된다.
 *
 * 이 클래스는 세금 계산 단계에서 비과세로만 구매한 경우에 대해 배송비의 부가세를 보정한다.
 *
 */
class WSKL_Combined_Tax {

	/** @var array 복합과세에서 우리가 사용할 세금 클래스들 */
	private static $predefined_tax_classes = NULL;

	/** @var null 과세, 비과세율 클래스에서 각각 사용할 세율 이름 */
	private static $predefined_tax_rates = NULL;

	public static function init() {

		if ( ! static::$predefined_tax_classes ) {
			static::$predefined_tax_classes = array(
				'Standard',  // 과세용 클래스
				'Zero Rate', // 비과세용 클래스
			);
		}

		if ( ! static::$predefined_tax_rates ) {
			static::$predefined_tax_rates = array(
				__( '부가세율', 'wskl' ),
				__( '부가세율', 'wskl' ),
			);
		}

		/** 복합과세 옵션 업데이트. 복합과세가 활성화되면 관련 설정을 초기화. */
		add_action(
			'update_option_' . wskl_get_option_name( 'enable_combined_tax' ),
			array( __CLASS__, 'callback_init_combined_tax_classes' ),
			10,
			3
		);

		if ( wskl_is_option_enabled( 'hide_display_cart_tax' ) ) {
			/** 부가세 항목이 합계와 같이 출력되는 것을 숨긴다.  */
			add_filter(
				'woocommerce_cart_totals_order_total_html',
				array( __CLASS__, 'callback_hide_include_tax' )
			);
		}

		if ( wskl_is_option_enabled( 'enable_combined_tax' ) ) {

			/** 결제 단계에서 각 PG 마다 과세 설정에 대한 세부 옵션을 조정한다. */
			add_filter(
				'woocommerce_pay_form_args',
				array( __CLASS__, 'callback_pay_form_args' ),
				20
			);

			// 완전히 다른 세금 클래스인 경우는 이런 필터링을 걸쳐야 하지만,
			// 과세/비과세를 담당하는 클래스를 Standard, Zero Rate 로 잡으면 굳이 필요 없다.
			//			/** 배송비의 부가세를 판단한다. 만일 비과세 상품으로만 쇼핑을 한 경우 배송비에 대해 부가세를 부여한다. */
			//			add_filter( 'woocommerce_package_rates', array(
			//				__CLASS__,
			//				'callback_woocommerce_package_rates',
			//			), 10, 2 );
		}
	}

	/** @noinspection PhpUnusedLocalVariableInspection */
	/**
	 * @filter  woocommerce_package_rates
	 *
	 * @param array $rates
	 * @param       $package
	 *
	 * @return array
	 */
	public static function callback_woocommerce_package_rates(
		array $rates,
		/** @noinspection PhpUnusedParameterInspection */
		$package
	) {

		if ( wskl_is_option_enabled( 'enable_combined_tax' ) ) {

			$tax_class = sanitize_title( static::$predefined_tax_classes[0] );
			/** @var array $vat_tax_rate 과세 클래스의 세율 */
			$vat_tax_rate = WC_Tax::find_rates(
				array(
					'country'   => 'KR',
					'state'     => '',
					'city'      => '',
					'postcode'  => '',
					'tax_class' => ( $tax_class == 'standard' ) ? '' : $tax_class,
				)
			);

			$vat_tax_keys = array_keys( $vat_tax_rate );

			/** @var int $vat_tax_rate_id 과세 클래스 세율 아이디 */
			$vat_tax_rate_id = $vat_tax_keys[0];

			/** @var float $vat_rate 부가세 세율 */
			$vat_rate = 0;

			// 과세율 검색
			foreach ( $vat_tax_rate as $item ) {
				if ( isset( $item['label'] ) && $item['label'] == static::$predefined_tax_rates[0] ) {
					$vat_rate = $item['rate'];
					break;
				}
			}

			/** @var array $tax_free_rate 비과세 클래스의 세율. 자명히, 0.0% */
			$tax_free_rate = WC_Tax::find_rates(
				array(
					'country'   => 'KR',
					'state'     => '',
					'city'      => '',
					'postcode'  => '',
					'tax_class' => sanitize_title(
						static::$predefined_tax_classes[1]
					),
				)
			);

			$tax_free_rate_keys = array_keys( $tax_free_rate );

			/** @var int $tax_free_rate_id 비과세 클래스 세율 아이디. */
			$tax_free_rate_id = $tax_free_rate_keys[0];

			/** @var WC_Shipping_Rate $rate */
			foreach ( $rates as &$rate ) {

				/** @var array $taxes */
				$taxes = &$rate->taxes;

				/**
				 * @var int   $id    세율 아이디
				 * @var float $price 세금액
				 */
				foreach ( $taxes as $id => $price ) {
					// 배송 세금이 비과세로 잡혀 있으면 해당 항목을 삭제하고
					// 강제로 부가세로 대치. 여기서 반올림할 필요는 없음.
					if ( $id == $tax_free_rate_id ) {
						unset( $taxes[ $id ] );
						$taxes[ $vat_tax_rate_id ] = $rate->cost / (float) $vat_rate;
						break;
					}
				}
			}
		}

		return $rates;
	}

	/**
	 * @action update_option_{$option_name}
	 *
	 * @uses   init_tax_classes
	 * @uses   reset_tax_classes
	 * @uses   set_tax_options
	 *
	 * @param $old_value
	 * @param $value
	 * @param $option
	 */
	public static function callback_init_combined_tax_classes(
		/** @noinspection PhpUnusedParameterInspection */
		$old_value,
		$value,
		/** @noinspection PhpUnusedParameterInspection */
		$option
	) {

		if ( $value == 'on' ) {
			static::init_tax_classes();
			// static::set_tax_options();  // 조정의 여지가 있어 주석화.
		} else {
			static::reset_tax_classes();
		}
	}

	/**
	 * @used-by callback_init_combined_tax_classes
	 */
	private static function init_tax_classes() {

		$standard_class_pos = array_search(
			'Standard',
			static::$predefined_tax_classes
		);
		if ( $standard_class_pos !== FALSE ) {
			$tax_classes = array_merge(
				array_slice(
					static::$predefined_tax_classes,
					0,
					$standard_class_pos
				),
				array_slice(
					static::$predefined_tax_classes,
					$standard_class_pos + 1
				)
			);
		} else {
			$tax_classes = &static::$predefined_tax_classes;
		}

		$redefined = array_map(
			'trim',
			array_merge(
				$tax_classes,
				array_diff(
					WC_Tax::get_tax_classes(),
					$tax_classes
				)
			)
		);

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
		$taxed = WC_Tax::find_rates(
			array(
				'country'   => 'KR',
				'state'     => '',
				'city'      => '',
				'postcode'  => '',
				'tax_class' => $taxed_class,
			)
		);

		$taxed_rate_label = static::$predefined_tax_rates[0]; //과세율
		$taxed_rate_found = static::find_tax_rate_label(
			$taxed,
			$taxed_rate_label
		);

		if ( ! $taxed_rate_found ) {
			WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country'  => 'KR',
					'tax_rate_state'    => '',
					'tax_rate'          => '10.00',
					'tax_rate_name'     => $taxed_rate_label,
					'tax_rate_priority' => 1,
					'tax_rate_shipping' => 1,
					'tax_rate_compound' => 0,
					'tax_rate_order'    => 0,
					'tax_rate_class'    => $taxed_class,
				)
			);
		}

		///////////////////////////////////////////////
		// check and add '비과세 상품' class

		$untaxed_class = sanitize_title( static::$predefined_tax_classes[1] );

		/** @var array $untaxed */
		$untaxed = WC_Tax::find_rates(
			array(
				'country'   => 'KR',
				'state'     => '',
				'city'      => '',
				'postcode'  => '',
				'tax_class' => $untaxed_class,
			)
		);

		$untaxed_rate_label = static::$predefined_tax_rates[1]; // 비과세율
		$untaxed_rate_found = static::find_tax_rate_label(
			$untaxed,
			$untaxed_rate_label
		);


		if ( ! $untaxed_rate_found ) {
			WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country'  => 'KR',
					'tax_rate_state'    => '',
					'tax_rate'          => '0.00',
					'tax_rate_name'     => $untaxed_rate_label,
					'tax_rate_priority' => 1,
					'tax_rate_shipping' => 0,
					'tax_rate_compound' => 0,
					'tax_rate_order'    => 0,
					'tax_rate_class'    => $untaxed_class,
				)
			);
		}
	}

	/**
	 * 세율 항목 중 해당 레이블이 포함되었는지 확인.
	 *
	 * @used-by init_tax_classes
	 *
	 * @param array $rates
	 * @param       $label
	 *
	 * @return bool
	 */
	private static function find_tax_rate_label( array $rates, $label ) {

		foreach ( $rates as $rate ) {
			if ( isset( $rate['label'] ) && $rate['label'] == $label ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * @used-by callback_init_combined_tax_classes
	 */
	private static function reset_tax_classes() {

		$redefined = array_map(
			'trim',
			array_diff(
				WC_Tax::get_tax_classes(),
				static::$predefined_tax_classes
			)
		);

		update_option( 'woocommerce_tax_classes', implode( "\n", $redefined ) );
	}

	public static function callback_hide_include_tax( $value ) {

		$value = preg_replace(
			'/<small class="includes_tax">.+<\/small>/',
			'',
			$value
		);

		return $value;
	}

	public static function callback_pay_form_args( array $pay_form_args ) {

		$pg_agency = wskl_get_option( 'pg_agency' );

		if ( wskl_is_option_enabled(
			     "enable_combined_tax_{$pg_agency}"
		     ) && method_exists(
			     __CLASS__,
			     "combined_tax_{$pg_agency}"
		     )
		) {
			return call_user_func_array(
				array(
					__CLASS__,
					"combined_tax_{$pg_agency}",
				),
				array( $pay_form_args, )
			);
		}

		return $pay_form_args;
	}

	/**
	 * @used-by callback_init_combined_tax_classes
	 */
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

		$flat_rate_settings['enabled']      = 'yes';
		$flat_rate_settings['availability'] = 'specific';
		$flat_rate_settings['countries']    = array( 'KR' );
		$flat_rate_settings['tax_status']   = 'taxable';
		$flat_rate_settings['cost']         = '2727'; // KRW 3,000 - VAT

		update_option( 'woocommerce_flat_rate_settings', $flat_rate_settings );
	}


	/** @noinspection PhpUnusedPrivateMethodInspection */

	/**
	 * @used-by callback_pay_form_args
	 *
	 * @param array $pay_form_args
	 *
	 * @return array
	 */
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

		$order = wc_get_order( $pay_form_args['ordr_idxx'] );

		$taxes_total = $order->get_total_tax();

		// 과세/비과세 승인금액의 합. 비과세는 공급가를 더함.
		$supply_value_non_vat = 0;

		foreach ( $order->get_items() as $item ) {
			if ( $item['line_tax'] == 0 ) {
				$supply_value_non_vat += $item['line_total'];  // 비과세상품
			} /* else {
				$supply_value_vat += wc_round_tax_total( $item['line_total'] );
			} */
		}

		$total_price = $order->get_total();

		$supply_value_vat = $total_price - $supply_value_non_vat - $taxes_total;

		// 과세 승인금액
		$pay_form_args['comm_tax_mny'] = $supply_value_vat;

		// 비과세 승인금액
		$pay_form_args['comm_free_mny'] = $supply_value_non_vat;

		// 부가가치세
		$pay_form_args['comm_vat_mny'] = $taxes_total;

		return $pay_form_args;
	}
}


WSKL_Combined_Tax::init();