<?php

namespace wskl\lib\auth;

require_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );

use wskl\lib\cassandra\OrderItemRelation;


class Auth_Model {

	public static  $nonce_action                   = 'wskl-client-license-nonce-action';
	public static  $nonce_name                     = 'wskl-client-license-nonce-name';
	public static  $admin_post_activation_action   = 'wskl-client-license-activate-action';
	public static  $admin_post_verification_action = 'wskl-client-license-verification-action';

	private $license_type = '';
	private $meta_key     = '';

	/** @var  OrderItemRelation */
	private $value;

	public function __construct( $license_type ) {

		$this->license_type = $license_type;
		$this->meta_key     = 'wskl-client-license-information-' . $license_type;

		$this->load();
	}

	public function load() {

		$this->value = get_option( $this->meta_key, FALSE );
	}

	public function save() {

		update_option( $this->meta_key, $this->value );
	}

	public function &get_value() {

		return $this->value;
	}

	public function set_value( OrderItemRelation $value ) {

		$this->value = $value;
	}

	public function is_available() {

		if ( $this->value instanceof OrderItemRelation ) {

			return $this->value->get_order_item_id() > 0 && $this->value->get_key() && $this->value->get_user_id() > 0;
		}

		return FALSE;
	}

	public function is_active() {

		if ( $this->is_available() ) {

			return $this->get_value()->get_key()->is_active();
		}

		return FALSE;
	}

	public function is_expired() {

		if ( $this->is_available() ) {

			return $this->get_value()->get_key()->is_expired();
		}

		return FALSE;
	}

	public function is_verified() {

		return $this->is_active() && ! $this->is_expired();
	}

	public function reset() {

		$this->value = FALSE;
		$this->save();
	}

	public function get_key_value() {

		if ( $this->is_available() ) {
			return $this->get_value()->get_key()->get_key();
		}

		return NULL;
	}

	public function get_key_type() {

		if ( $this->is_available() ) {

			return $this->get_value()->get_key()->get_type();
		}

		return NULL;
	}
}
