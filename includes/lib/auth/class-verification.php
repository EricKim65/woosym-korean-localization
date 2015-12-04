<?php

namespace wskl\lib\auth;

require_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );
require_once( 'class-auth-model.php' );

use wskl\lib\cassandra\ClientAPI;
use wskl\lib\cassandra\OrderItemRelation;


class Verification {

	private $verified;

	public function __construct() {

		$this->initialize();
	}

	public function initialize() {

		/**
		 * @see woocommerce/templates/checkout/payment.php
		 * @see woocommerce/includes/wc-template-functions.php woocommerce_checkout_payment()
		 */
		add_action( 'woocommerce_review_order_before_submit', array( $this, 'show_unverified_warning' ) );

		/**
		 * @see woocommerce/includes/class-wc-payment-gateways.php get_available_payment_gateways()
		 */
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'check_payment_gateways' ) );

		$this->verified = $this->verify();
	}

	public function show_unverified_warning() {

		if( ! is_checkout() ) {
			return;
		}

		if( ! $this->verified ) {

			$default_path = WSKL_PATH . '/includes/lib/auth/templates/';
			wc_get_template( 'checkout-unauthorized.php', array(), '', $default_path );
			// add_filter( 'woocommerce_order_button_html', array( $this, 'remove_submit' ), 999, 0 );
		}
	}

//	public function remove_submit() {
//		return '';
//	}

	public function check_payment_gateways( array $available_gateways ) {

		/** @var \WC_Payment_Gateway $gateway */
		foreach( $available_gateways as $idx => $gateway ) {

			if( !( $gateway instanceof \WC_Payment_Gateway ) ) {
				continue;
			}

			if( FALSE === array_search( $gateway->id, array( 'ags_credit', 'kcp_credit', 'inicis_credit' ) ) ) {
				continue;
			}

			if( $this->verified ) {
				continue;
			}

			unset( $available_gateways[ $idx ] );
		}

		return $available_gateways;
	}

	private function verify() {

		if( !wskl_is_option_enabled( 'enable_sym_pg' ) ) {
			return TRUE;
		}

		$info = new Auth_Model( 'payment' );

		$key_type     = $info->get_key_type();
		$key_value    = $info->get_key_value();
		$site_url     = site_url();

		// verification null 은 인증 실패. false 는 인증 서버 다운 등의 이유로 인증 시도가 이뤄지지 못함.
		$verification = ClientAPI::verify( $key_type, $key_value, $site_url );

		if ( $verification instanceof OrderItemRelation ) {

			$info->set_value( $verification );
			$info->save();

			return TRUE;

		} else if( $verification === NULL ) { // 인증 실패

			return FALSE;

		} else if ( $verification === FALSE ) { // 인증 불가.

			return $info->is_verified();
		}

		return FALSE;
	}
}