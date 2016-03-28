<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class WSKL_Sym_Checkout
 *
 * 한국형 주소 찾기와 결제 페이지를 활성화.
 */
class WSKL_Sym_Checkout {

	public static function init() {

		/** 초기화 액션 */
		add_action( 'woocommerce_init',
		            array( __CLASS__, 'customize_checkout_address_fields' ) );

		/** JS 삽입. */
		add_action( 'wp_enqueue_scripts',
		            array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * 체크아웃 페이지의 청구 주소와 배송 주소를 커스터마이즈
	 *
	 * @action  woocommerce_init
	 * @used-by WSKL_Sym_Checkout::init()
	 */
	public static function customize_checkout_address_fields() {

		/**
		 * @filter 'woocommerce_{$type}fields
		 * @see    woocommerce/includes/class-wc-countries.php
		 * @see    WC_Countries::get_address_fields()
		 */
		add_filter( 'woocommerce_billing_fields',
		            array( __CLASS__, 'billing_address' ),
		            10,
		            2 );

		/**
		 * @filter woocommerce_{$type}fields
		 * @see    woocommerce/includes/class-wc-countries.php
		 * @see    WC_Countries::get_address_fields()
		 */
		add_filter( 'woocommerce_shipping_fields',
		            array( __CLASS__, 'shipping_address' ),
		            10,
		            2 );

		/**
		 * @filter woocommerce_form_field_{$type}
		 * @see woocommerce/includes/wc-template-functions.php
		 * @see woocommerce_form_field()
		 */
		add_filter( 'woocommerce_form_field_hidden',
		            array( __CLASS__, 'output_hidden_form_fields' ),
		            10,
		            4 );

		add_filter( 'woocommerce_form_field_button',
		            array( __CLASS__, 'output_button_form_field' ),
		            10,
		            4 );
	}

	/**
	 * 청구 주소 필드 편집, 재구성
	 *
	 * @filter  woocommerce_billing_fields
	 * @used-by WSKL_Sym_Checkout::customize_checkout_address_fields()
	 *
	 * @param array  $address_fields
	 * @param string $country 2-length country code (ISO 3166-1 alpha2)
	 *
	 * @return array
	 */
	public static function billing_address( $address_fields, $country ) {

		switch ( $country ) {
			case 'KR':
				return static::address_common_korean( 'billing_',
				                                      $address_fields );
		}

		return $address_fields;
	}

	/**
	 * 배송/청구 주소 두 가지는 공통점이 많으므로 하나의 함수로 처리.
	 *
	 * @used-by WSKL_Sym_Checkout::billing_address()
	 * @used-by WSKL_Sym_Checkout::shipping_address()
	 *
	 * @param string $prefix
	 * @param array  $address_fields
	 *
	 * @return array
	 */
	private static function address_common_korean( $prefix, $address_fields ) {

		// address_fields 로 전해지는 array 의 키 기본 값은 다음과 같다. (순서대로)
		// {$prefix}_first_name: form-row-first
		// {$prefix}_last_name : form-row-last
		// {$prefix}_company: form-row-wide
		// {$prefix}_email: form-row-first
		// {$prefix}_phone: form-row-last
		// {$prefix}_country: form-row-wide address-field update_totals_on_change
		// {$prefix}_address_1: form-row-wide address-field
		// {$prefix}_address_2: form-row-wide address-field
		// {$prefix}_city: form-row-wide address-field
		// {$prefix}_state: form-row-first address-field
		// {$prefix}_postcode: form-row-last address-field

		$company_enabled = wskl_is_option_enabled( 'company' );

		$field_template = array(

			'first_name' => array(
				'label'    => __( '이름', 'wskl' ),
				'required' => TRUE,
				'class'    => ( $company_enabled ) ? array( 'form-row-first' ) : array( 'form-row-wide' ),
			),

			// last_name 생략. fist_name 에 성과 이름을 모두 담음.

			// 원래는 form-row-wide 지만, 회사명을 이름 뒤에 넣음.
			'company'    => array(
				'label'    => __( '회사명', 'wskl' ),
				'required' => TRUE,
				'class'    => array( 'form-row-last' ),
				'clear'    => TRUE,
			),

			'filler_1' => array(
				'type'  => 'clear',
				'label' => 'blank',
				'clear' => TRUE,
			),

			'postcode' => array(
				'label'       => __( '우편번호', 'wskl' ),
				'placeholder' => __( '우편번호', 'wskl' ),
				'required'    => TRUE,
				'class'       => array( 'form-row-first', 'address-field' ),
				'validate'    => array( 'postcode' ),
			),

			'zipcode_button' => array(
				'label' => __( '우편번호 검색', 'wskl' ),
				'value' => __( '우편번호 검색', 'wskl' ),
				'class' => array( 'form-row-last' ),
				'type'  => 'button',
			),

			// billing_city 부분을 무시하고 address_1 을 사용.
			'address_1'      => array(
				'label'             => __( '주소', 'wskl' ),
				'placeholder'       => _x( '주소 - 시/도(번지 이전까지)',
				                           'placeholder',
				                           'wskl' ),
				'required'          => TRUE,
				'class'             => array(
					'form-row-wide',
					'address-field',
				),
				'custom_attributes' => array(
					'autocomplete' => 'no',
				),
			),

			'address_2' => array(
				'placeholder'       => _x( '주소 - 번지 이후',
				                           'placeholder',
				                           'wskl' ),
				'class'             => array(
					'form-row-wide',
					'address-field',
				),
				'required'          => FALSE,
				'custom_attributes' => array(
					'autocomplete' => 'no',
				),
			),

			'email' => array(
				'type'        => 'email',
				'label'       => __( '이메일 주소', 'wskl' ),
				'placeholder' => _x( '이메일 주소', 'placeholder', 'wskl' ),
				'class'       => array( 'form-row-first', ),
				'required'    => TRUE,
				'validate'    => array( 'email' ),
			),

			'phone'     => array(
				'label'       => __( '휴대전화 번호', 'wskl' ),
				'type'        => 'text',
				'placeholder' => _x( '000-0000-0000', 'placeholder', 'wskl' ),
				'clear'       => TRUE,
				'class'       => array( 'input-text', 'form-row-last', ),
				'required'    => TRUE,
				'validate'    => array( 'phone' ),
			),

			// 이후 필드는 사용자가 미리 채워 넣은 기본값으로 사용
			// 그리고 실제 결제 폼에서는 보이지 않도록 처리
			'last_name' => array(
				'type'     => 'hidden',
				'label'    => __( '성 (따로 기재하는 경우)', 'wskl' ),
				'required' => FALSE,
			),

			'country' => array(
				'type'     => 'hidden',
				'label'    => __( '국가', 'wskl' ),
				'required' => FALSE,
			),

			'city' => array(
				'type'     => 'hidden',
				'label'    => __( '주소 - 시/도(번지 이전까지)', 'wskl' ),
				'required' => FALSE,
			),

			'state' => array(
				'type'     => 'hidden',
				'label'    => __( '주/군', 'wskl' ),
				'required' => FALSE,
			),
		);

		$output = array();

		foreach ( $field_template as $key => $value ) {
			// 회사명 표시 옵션이 켜져 있을 때만 회사 필드를 집어 넣음.
			if ( $key == 'company' && ! $company_enabled ) {
				continue;
			}
			$output[ $prefix . $key ] = $value;
		}

		return $output;
	}

	/**
	 * 배송 주소 필드 편집, 재구성
	 *
	 * @filter  woocommerce_shipping_fields
	 * @used-by WSKL_Sym_Checkout::customize_checkout_address_fields()
	 *
	 * @param array  $address_fields
	 * @param string $country 2-length country code (ISO 3166-1 alpha2)
	 *
	 * @return array
	 */
	public static function shipping_address( $address_fields, $country ) {

		switch ( $country ) {
			case 'KR':
				return static::address_common_korean( 'shipping_',
				                                      $address_fields );
		}

		return $address_fields;
	}

	public static function output_hidden_form_fields(
		$field,
		$key,
		/** @noinspection PhpUnusedParameterInspection */
		$args,
		$value
	) {

		return sprintf( '<input type="hidden" name="%s" id="%s" value="%s" />',
		                esc_attr( $key ),
		                esc_attr( $key ),
		                esc_attr( $value ) );
	}

	/**
	 * 버튼을 별도로 생성함.
	 *
	 * @filter  woocommerce_form_field_button
	 * @used-by WSKL_Sym_Checkout::customize_checkout_address_fields()
	 *
	 * @param $field
	 * @param $key
	 * @param $args
	 * @param $value
	 *
	 * @return string
	 */
	public static function output_button_form_field(
		$field,
		$key,
		$args,
		/** @noinspection PhpUnusedParameterInspection */
		$value
	) {

		if ( $key == 'billing_zipcode_button' || $key == 'shipping_zipcode_button' ) {

			if ( isset( $args['required'] ) && $args['required'] ) {
				$args['class'][] = 'validate-required';
				$required        = sprintf( '<abbr class="required" title="%s">*</abbr>',
				                            esc_attr__( 'required',
				                                        'woocommerce' ) );
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

			$field = sprintf( '<p class="form-row %s">',
			                  esc_attr( implode( ' ', $args['class'] ) ) );
			$field .= sprintf( '<label for="%s" class="%s">%s%s</label>',
			                   $escaped_key,
			                   esc_attr( implode( ' ', $args['label_class'] ) ),
			                   '&nbsp;', // $escaped_label,
			                   $required );
			$field .= sprintf( '<input type="button" class="button" name="%s" id="%s" value="%s" /></p>%s',
			                   $escaped_key,
			                   $escaped_key,
			                   $escaped_label,
			                   $div_clear );
		}

		return $field;
	}

	/**
	 * @action  wp_enqueue_scripts
	 * @used-by WSKL_Sym_Checkout::init()
	 */
	public static function enqueue_scripts() {

		// Load frontend JS & CSS
		if ( is_ssl() ) {
			$daum_zip_api_url = 'https://spi.maps.daum.net/imap/map_js_init/postcode.v2.js';
		} else {
			$daum_zip_api_url = 'http://dmaps.daum.net/map_js_init/postcode.v2.js';
		}

		wp_enqueue_script( 'daum-postcode-v2-js',
		                   $daum_zip_api_url,
		                   NULL,
		                   WSKL_VERSION,
		                   FALSE );  // in the header area

		wp_enqueue_script( 'daum_zipcode-js',
		                   plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/daum-zipcode.js',
		                   array( 'daum-postcode-v2-js', ),
		                   WSKL_VERSION,
		                   TRUE );  // in the footer area
	}
}


WSKL_Sym_Checkout::init();