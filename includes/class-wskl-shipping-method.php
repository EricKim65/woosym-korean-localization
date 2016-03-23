<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wskl_shipping_class_init() {

	if ( ! class_exists( 'WSKL_Shipping_Method' ) ) :

		class WSKL_Shipping_Method extends WC_Shipping_Method {

			public function __construct() {

				$this->id                 = 'wskl_shipping_method';
				$this->method_title       = __( '다보리 배송', 'wskl' );
				$this->method_description = __( '한국 배송에 맞는 배송', 'wskl' );

				$this->enabled = 'yes';
				$this->title   = __( '다보리 배송', 'wskl' );

				$this->init();
			}

			public function init() {

				$this->init_form_fields();
				$this->init_settings();

				add_action( 'woocommerce_update_options_shipping_' . $this->id,
				            array( $this, 'process_admin_options' ) );
			}

			public function init_form_fields() {

				$this->form_fields = array(
					'enabled'              => array(
						'title'   => __( 'Enable/Disable', 'woocommerce' ),
						'type'    => 'checkbox',
						'label'   => __( 'Enable this shipping method',
						                 'woocommerce' ),
						'default' => 'no',
					),
					'title'                => array(
						'title'       => __( 'Method Title', 'woocommerce' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.',
						                     'woocommerce' ),
						'default'     => __( '다보리 배송!', 'wskl' ),
						'desc_tip'    => TRUE,
					),
					'ordinary_cost'        => array(
						'title'       => __( '일반 요금', 'wskl' ),
						'type'        => 'text',
						'placeholder' => '',
						'description' => '',
						'default'     => '3000',
						'desc_tip'    => TRUE,
					),
					'island_mountain_cost' => array(
						'title'       => __( '도서산간지방 요금', 'wskl' ),
						'type'        => 'text',
						'placeholder' => '',
						'description' => '',
						'default'     => '',
						'desc_tip'    => TRUE,
					),
				);
			}
		} // class WSKL_Shipping_Method

	endif;
}

function wskl_add_shipping_method( array $methods ) {

	$methods[] = 'WSKL_Shipping_Method';

	return $methods;
}

add_action( 'woocommerce_shipping_init', 'wskl_shipping_class_init' );
add_filter( 'woocommerce_shipping_methods', 'wskl_add_shipping_method' );