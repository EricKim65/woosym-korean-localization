<?php

wskl_check_abspath();

require_once( WSKL_PATH . '/includes/dabory-sms/class-wskl-sms-text-substitution.php' );
require_once( WSKL_PATH . '/includes/dabory-sms/admin/class-wskl-dabory-sms-settings.php' );
require_once( WSKL_PATH . '/includes/dabory-sms/providers/class-wskl-dabory-sms-provider-loading.php' );


if ( ! class_exists( 'WSKL_Dabory_SMS_Settings_Page' ) ) :

	/**
	 * Class WSKL_Dabory_SMS_Settings_Page
	 *
	 * @since 3.3.0
	 */
	class WSKL_Dabory_SMS_Settings_Page extends WC_Settings_Page {

		public function __construct() {

			$this->id    = 'wskl-dabory-sms';
			$this->label = __( '다보리 SMS', 'wskl' );

			// add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			// add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
			// add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
			// add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );

			// this line replaces above four add_* function calls
			parent::__construct();

			// admin_field_{$type} actions
			// 메시지 공급자 추가 출력
			add_action(
				'woocommerce_admin_field_sms_provider_additional',
				array( __CLASS__, 'provider_additional' ),
				10,
				0
			);
			// woocommerce_admin_field_sms_provider_additional 콜백에서, 재차 수행될 커스텀 action
			add_action(
				'dabory_sms_provider_additional',
				array( 'WSKL_Dabory_SMS_Provider_Loading', 'output_provider_additional' )
			);

			// 메시지 테스트 출력
			add_action( 'woocommerce_admin_field_message_tester', array( __CLASS__, 'message_tester' ), 10, 0 );

			// 치환 문자열 정보
			add_action(
				'woocommerce_admin_field_magic_text_information',
				array( __CLASS__, 'output_substitution_information' ),
				10,
				0
			);
		}

		public function get_sections() {

			$sections = array(
				''                        => __( '일반', 'wskl' ),
				'new-order'               => __( 'New order', 'woocommerce' ),
				'cancelled-order'         => __( 'Cancelled order', 'woocommerce' ),
				'failed-order'            => __( 'Failed order', 'woocommerce' ),
				'processing-order'        => __( 'Processing order', 'woocommerce' ),
				'completed-order'         => __( 'Completed order', 'woocommerce' ),
				'refunded-order'          => __( 'Refunded order', 'woocommerce' ),
				'customer-note'           => __( 'Customer note', 'woocommerce' ),
				'customer-new-account'    => __( 'New account', 'woocommerce' ),
				'customer-reset-password' => __( 'Reset password', 'woocommerce' ),
				'payment-bacs'            => __( 'BACS 결제', 'wskl' ),
			);

			return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
		}

		public function output() {

			global $current_section;

			$settings = $this->get_settings( $current_section );

			WC_Admin_Settings::output_fields( $settings );
		}

		public function get_settings( $current_section ) {

			return WSKL_Dabory_SMS_Settings::static_get_settings( $current_section );
		}

		public function save() {

			global $current_section;

			$settings = $this->get_settings( $current_section );

			WC_Admin_Settings::save_fields( $settings );
		}

		public static function provider_additional() {

			do_action( 'dabory_sms_provider_additional' );
		}

		public static function message_tester() {

			wskl_get_template( 'dabory-sms/admin/settings/dabory-sms-message-tester.php' );
		}

		public static function output_substitution_information() {

			$substitution = new WSKL_SMS_Text_Substitution();

			$order_magic_texts = $substitution->get_order_magic_texts();
			$user_magic_texts  = $substitution->get_user_magic_texts();

			wskl_get_template(
				'dabory-sms/admin/settings/dabory-sms-substitution.php',
				array(
					'order_magic_texts' => &$order_magic_texts,
					'user_magic_texts'  => &$user_magic_texts,
				)
			);
		}
	}

endif;