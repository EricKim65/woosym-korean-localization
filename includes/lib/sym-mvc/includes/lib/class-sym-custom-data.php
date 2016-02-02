<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Sym_Custom_Data' ) ) :

	class Sym_Custom_Data {

		private $order_id;
		private $fields;

		public static function extend( &$order ) {

			$order->custom = new Sym_Custom_Data( $order->id );
		}

		private function __construct( $order_id ) {

			$this->order_id = $order_id;
			$this->fields   = array();

			// get data from the database
			$this->populate();
		}

		private function populate() {

			global $wpdb;

			$fields = $wpdb->get_results(
				'SELECT * FROM sym_custom_data WHERE order_id = ' . $this->order_id,
				OBJECT
			);

			foreach ( $fields as $field ) {
				$value = json_decode( $field->custom_value, true );

				if ( $value != null ) {
					$this->fields[ $field->custom_key ] = $value;
				} else {
					$this->fields[ $field->custom_key ] = $field->custom_value;
				}
			}
		}

		public function save() {

			global $wpdb;

			$wpdb->query( 'DELETE FROM sym_custom_data WHERE order_id = ' . $this->order_id );

			foreach ( $this->fields as $key => $value ) {
				if ( empty( $value ) ) {
					continue;
				}

				if ( is_object( $value ) || is_array( $value ) ) {
					$value = json_encode( $value );
				}

				$wpdb->insert( 'sym_custom_data', array(
					'order_id'     => $this->order_id,
					'custom_key'   => $key,
					'custom_value' => $value,
				), array( '%d', '%s', '%s' ) );
			}
		}

		public function __isset( $key ) {

			return isset( $this->fields[ $key ] );
		}

		public function __unset( $key ) {

			unset( $this->fields[ $key ] );
		}

		public function __get( $key ) {

			return isset( $this->fields[ $key ] ) ? $this->fields[ $key ] : null;
		}

		public function __set( $key, $value ) {

			$this->fields[ $key ] = $value;
		}
	}

endif;