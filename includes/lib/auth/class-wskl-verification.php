<?php

require_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );
require_once( 'class-wskl-auth-info.php' );

use wskl\lib\cassandra\ClientAPI;
use wskl\lib\cassandra\OrderItemRelation;


class WSKL_Verification {

	private $verified = NULL;

	public function __construct() {

		$this->initialize();
	}

	public function initialize() {

		/**
		 * @see woocommerce/templates/checkout/payment.php
		 * @see woocommerce/includes/wc-template-functions.php woocommerce_checkout_payment()
		 */
		add_action(
			'woocommerce_review_order_before_submit',
			array( $this, 'show_unverified_warning' ),
			99
		);

		/**
		 * @see woocommerce/includes/class-wc-payment-gateways.php get_available_payment_gateways()
		 */
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'check_payment_gateways' ) );
	}

	public function show_unverified_warning() {

		if ( ! is_checkout() ) {
			return;
		}

		if ( ! $this->verify() ) {

			$default_path = WSKL_PATH . '/includes/lib/auth/templates/';
			wskl_get_template( 'checkout-unauthorized.php', array(), $default_path );

			// add_filter( 'woocommerce_order_button_html', array( $this, 'remove_submit' ), 999, 0 );
		}
	}

//	public function remove_submit() {
//		return '';
//	}

	private function verify() {

		if ( ! wskl_is_option_enabled( 'enable_sym_pg' ) ) {
			return TRUE;
		}

		if ( ! is_null( $this->verified ) && is_bool( $this->verified ) ) {
			return $this->verified;
		}

		$info = new WSKL_Auth_Info( 'payment' );

		$key_type = $info->get_key_type();
		$key_value = $info->get_key_value();
		$site_url = site_url();

		// verification null 은 인증 실패. false 는 인증 서버 다운 등의 이유로 인증 시도가 이뤄지지 못함.
		$verification = ClientAPI::verify( $key_type, $key_value, $site_url );

		if ( $verification instanceof OrderItemRelation ) {

			$info->set_oir( $verification );
			$info->save();

			$this->verified = TRUE;

		} else if ( $verification === NULL ) { // 인증 실패

			$this->verified = FALSE;

		} else if ( $verification === FALSE ) { // 인증 불가 (서버의 이상)

			$this->verified = $info->is_verified();
		}

		return $this->verified;
	}

	public function check_payment_gateways( array $available_gateways ) {

		$wskl_pay_pates = \WSKL_Payment_Gates::get_pay_gates();

		$verified = $this->verify();

		/** @var \WC_Payment_Gateway $gateway */
		foreach( $available_gateways as $idx => $gateway ) {

			if( !( $gateway instanceof \WC_Payment_Gateway ) ) {
				continue;
			}

			$gate = substr( $gateway->id, 0, strpos( $gateway->id, '_' ) );

			if ( ! isset( $wskl_pay_pates[ $gate ] ) ) {
				continue;
			}

			if ( $verified ) {
				continue;
			}

			unset( $available_gateways[ $idx ] );
		}

		return $available_gateways;
	}
}


return new WSKL_Verification();
