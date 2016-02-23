<?php


class WSKL_Combined_Tax {

	public static function init() {

		add_action( 'update_option_' . wskl_get_option_name( 'enable_combined_tax' ),
		            array( __CLASS__, 'callback_init_combined_tax_classes' ),
		            10, 3 );
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

		$predefined_tax_classes = array(
			__( '과세 상품', 'wskl' ),
			__( '비과세 상품', 'wskl' ),
		);

		$redefined = array_map( 'trim', array_merge( $predefined_tax_classes,
		                                             array_diff( WC_Tax::get_tax_classes(),
		                                                         $predefined_tax_classes ) ) );

		update_option( 'woocommerce_tax_classes', implode( "\n", $redefined ) );


		///////////////////////////////////////////////
		// check and add '과세 상품' class

		$taxed_class = sanitize_title( $predefined_tax_classes[0] );

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

		$taxed_rate_label = __( '과세 상품 세율', 'wskl' );
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

		$untaxed_class = sanitize_title( $predefined_tax_classes[1] );

		/** @var array $untaxed */
		$untaxed = WC_Tax::find_rates( array(
			                               'country'   => 'KR',
			                               'state'     => '',
			                               'city'      => '',
			                               'postcode'  => '',
			                               'tax_class' => $untaxed_class,
		                               ) );

		$untaxed_rate_label = __( '비과세 상품 세율', 'wskl' );
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

	public static function callback_init_combined_tax_classes( $old_value, $value, $option ) {

		if( $value == 'on' ) {
			error_log( 'value is on init class' );
			static::init_tax_classes();
		}
	}
}


WSKL_Combined_Tax::init();