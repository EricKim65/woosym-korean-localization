<?php

if ( ! class_exists( 'Woosym_Korean_Localization' ) ) :

	final class Woosym_Korean_Localization extends Sym_Mvc_Main {

		public function __construct( $file = '', $version = '1.0.0' ) {

			parent::__construct( $file, $version );

			if ( get_option( $this->_prefix . 'enable_sym_checkout' ) == 'on' ) {
				add_action( 'woocommerce_init', array( $this, 'woosym_daum_kaddress' ), 1 );
				add_action( 'wp_enqueue_scripts', array( $this, 'js_and_css' ) );
			}

			if ( get_option( $this->_prefix . 'disable_sku' ) == 'on' ) {
				add_filter( 'wc_product_sku_enabled', '__return_false' );
			}

			if ( get_option( $this->_prefix . 'disable_returntoshop' ) == 'on' ) {
				add_filter( 'woocommerce_return_to_shop_redirect', array( $this, 'sym_change_empty_cart_button_url' ) );
			}

			if ( get_option( $this->_prefix . 'korean_won' ) == 'on' ) {
				add_filter( 'woocommerce_currencies', array( $this, 'woosym_kwon_currency' ) );
				add_filter( 'woocommerce_currency_symbol', array( $this, 'woosym_kwon_currency_symbol' ), 10, 2 );
			}

			add_filter( 'the_title', array( $this, 'order_received_title' ), 10, 2 );
			add_action( 'woocommerce_thankyou', array( $this, 'order_received_addition' ) );

			/**
			 * @see woocommerce/includes/wc-template-functions.php woocommerce_form_field()
			 */
			add_filter( 'woocommerce_form_field_args', array( $this, 'wskl_customize_checkout_field_args' ), 10, 3 );
			add_filter( 'woocommerce_form_field_email', array( $this, 'wskl_customize_checkout_email_field' ), 10, 4 );
			// add_filter( 'woocommerce_form_field_tel', array( $this, 'wskl_customize_checkout_tel_field' ), 10, 4 );
			add_filter( 'woocommerce_form_field_country', array(
				$this,
				'wskl_customize_checkout_country_field',
			), 10, 4 );
			add_filter( 'woocommerce_form_field_button', array( $this, 'wskl_customize_checkout_button_type' ), 10, 4 );
		} // End __construct ()

		function order_received_title( $title, $id ) {

			if ( is_order_received_page() && get_the_ID() === $id ) {
				$title = "주문이 완료되었습니다.";
			}

			return $title;
		}

		function order_received_addition( $order_id ) {

			echo __( '<p><h5>  주문에 감사드리며 항상 정성을 다 하겠습니다 !</h5></p>', $this->_folder );
		}


		function woosym_kwon_currency( $currencies ) {

			$currencies['KRW'] = __( '대한민국', 'woocommerce' );

			return $currencies;
		}

		function woosym_kwon_currency_symbol( $currency_symbol, $currency ) {

			switch ( $currency ) {
				case 'KRW':
					$currency_symbol = '원';
					break;
			}

			return $currency_symbol;
		}

		function sym_change_empty_cart_button_url() {

			return get_site_url();
		}

//Add local Method here

		public function js_and_css() {

			// Load frontend JS & CSS
			if ( is_ssl() ) {
				$daum_zip_api_url = 'https://spi.maps.daum.net/imap/map_js_init/postcode.v2.js';
			} else {
				$daum_zip_api_url = 'http://dmaps.daum.net/map_js_init/postcode.v2.js';
			}

			wp_enqueue_script( 'daum_maps', $daum_zip_api_url, null, null, false );  //맨앞에 넣음
			wp_enqueue_script( 'daum_zipcode', $this->assets_url . 'js/daum-zipcode.js', null, null, true );  //맨뒤에 넣음
		}

		public function woosym_daum_kaddress() {

			add_filter( 'woocommerce_billing_fields', array( $this, 'woosym_address_billing_kr' ) );
			add_filter( 'woocommerce_shipping_fields', array( $this, 'woosym_address_shipping_kr' ) );
		}

		function woosym_address_billing_kr() {

			$billing_shipping_sw = 'billing_';

			return self::woosym_address_common( $billing_shipping_sw );
		}

		function woosym_address_shipping_kr() {

			$billing_shipping_sw = 'shipping_';

			return self::woosym_address_common( $billing_shipping_sw );
		}

		static function woosym_address_common( $billing_shipping_sw ) {

			$fields = array(

				'first_name' => array(
					'label'    => __( '이름', 'woocommerce' ),
					'required' => true,
					'class'    => array( 'form-row-first' ),
				),
				'company'    => array(
					'label'    => __( '회사명', 'woocommerce' ),
					'required' => true,
					'class'    => array( 'form-row-last' ),
				),

				'filler_1' => array(
					'type'  => 'clear',
					'label' => __( 'blank', 'woocommerce' ),
					'clear' => true,
				),

				'zipcode_button' => array(
					'label' => __( '우편번호 검색', 'woocommerce' ),
					'value' => __( '우편번호 검색', 'woocommerce' ),
					'class' => array( 'form-row-wide' ),
					'type'  => 'button',
				),

				'postcode'  => array(
					'label'       => __( '우편번호', 'woocommerce' ),
					'placeholder' => __( '우편번호', 'woocommerce' ),
					'required'    => true,
					'class'       => array( 'form-row-wide', 'address-field' ),
				),
				'address_1' => array(
					'label'             => __( '주소', 'woocommerce' ),
					'placeholder'       => _x( '기본주소', 'placeholder', 'woocommerce' ),
					'required'          => true,
					'class'             => array( 'form-row-wide', 'address-field' ),
					'custom_attributes' => array(
						'autocomplete' => 'no',
					),
				),
				'address_2' => array(
					'placeholder'       => _x( '상세주소', 'placeholder', 'woocommerce' ),
					'class'             => array( 'form-row-wide', 'address-field' ),
					'required'          => false,
					'custom_attributes' => array(
						'autocomplete' => 'no',
					),
				),

				'email' => array(
					'label'             => __( '이메일', 'woocommerce' ),
					'placeholder'       => _x( '이메일', 'placeholder', 'woocommerce' ),
					'class'             => array( 'form-row-first', 'address-field' ),
					'required'          => true,
					'custom_attributes' => array(
						'autocomplete' => 'no',
					),
				),

				'phone' => array(
					'label'             => __( '모바일폰', 'woocommerce' ),
					'placeholder'       => _x( '모바일폰', 'placeholder', 'woocommerce' ),
					'class'             => array( 'form-row-last', 'address-field' ),
					'required'          => true,
					'custom_attributes' => array(
						'autocomplete' => 'no',
					),
				),

			);

			$address_fields = array();

			foreach ( $fields as $key => $value ) {
				if ( $key != 'company' ) {
					$address_fields[ $billing_shipping_sw . $key ] = $value;
				} else {
					if ( get_option( 'wskl_company' ) == 'on' ) {
						$address_fields[ $billing_shipping_sw . $key ] = $value;
					}
				}
			}

			return $address_fields;
		}

		public function wskl_customize_checkout_field_args( $args, $key, $value ) {

			if ( $key == 'billing_phone' || $key == 'billing_postcode' ) {

				$args['class'][0] = 'form-row-wide';
			}

			return $args;
		}

		public function wskl_customize_checkout_email_field( $field, $key, $args, $value ) {

			if ( $key == 'billing_email' ) {
				$field = sprintf( '<input type="hidden" name="billing_email" id="billing_email" value=%s />', $value );
			}

			return $field;
		}

		public function wskl_customize_checkout_tel_field( $field, $key, $args, $value ) {

			if ( $key == 'billing_phone' ) {
				$field = sprintf( '<input type="hidden" name="billing_phone" id="billing_phone" value=%s />', $value );
			}

			return $field;
		}

		public function wskl_customize_checkout_country_field( $field, $key, $args, $value ) {

			if ( $key == 'billing_country' ) {
				$field = sprintf( '<input type="hidden" name="billing_country" id="billing_country" value=%s />', $value );
			}

			return $field;
		}

		public function wskl_customize_checkout_button_type( $field, $key, $args, $value ) {

			if ( $key == 'billing_zipcode_button' || $key == 'shipping_zipcode_button' ) {

				if ( isset( $args['required'] ) && $args['required'] ) {
					$args['class'][] = 'validate-required';
					$required        = sprintf(
						'<abbr class="required" title="%s">*</abbr>',
						esc_attr__( 'required', 'woocommerce' )
					);
				} else {
					$required = '';
				}

				if ( isset( $args['clear'] ) && $args['clear'] ) {
					$div_clear = '<div class="clear"></div>';
				} else {
					$div_clear = '';
				}

				$escaped_key   = esc_attr( $key );
				$escaped_label = esc_attr( $args['label'] );

				$field = sprintf(
					'<p class="form-row %s">',
					esc_attr( implode( ' ', $args['class'] ) )
				);

				$field .= sprintf(
					'<label for="%s" class="%s">%s%s</label>',
					$escaped_key,
					esc_attr( implode( ' ', $args['label_class'] ) ),
					$escaped_label,
					$required
				);

				$field .= sprintf(
					'<input type="button" name="%s" id="%s" value="%s" /></p>%s',
					$escaped_key,
					$escaped_key,
					$escaped_label,
					$div_clear
				);

			}

			return $field;
		}
	}

endif;

function WSKL() {
	return Woosym_Korean_Localization::instance( WSKL_PREFIX, WSKL_MAIN_FILE, WSKL_VERSION );
}

$GLOBALS['wskl'] = WSKL();
