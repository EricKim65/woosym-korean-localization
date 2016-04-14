<?php

wskl_check_abspath();

if ( ! class_exists( 'WSKL_Settings_Dabory_SMS' ) ) :

	class WSKL_Settings_Dabory_SMS extends WC_Settings_Page {

		public function __construct() {

			$this->id    = 'wskl-dabory-sms';
			$this->label = __( '다보리 SMS', 'wskl' );

			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
			add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );

			add_action( 'woocommerce_admin_field_message_sending', array( $this, 'message_sending' ) );
		}

		public function get_sections() {

			$sections = array(
				''                 => __( '일반', 'wskl' ),
				'new-order'        => __( 'New order', 'woocommerce' ),
				'processing-order' => __( 'Processing order', 'woocommerce' ),
				'completed-order'  => __( 'Completed order', 'woocommerce' ),
				'customer-note'    => __( 'Customer note', 'woocommerce' ),
				'new-account'      => __( 'New account', 'woocommerce' ),
				'payment-bacs'     => __( 'BACS 결제', 'wskl' ),
				'messaging'        => __( '메시지 보내기', 'wskl' ),
			);

			return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
		}

		public function output() {

			global $current_section;

			$settings = $this->get_settings( $current_section );

			WC_Admin_Settings::output_fields( $settings );
		}

		public function get_settings( $current_section ) {

			switch ( $current_section ) {
				case '':
					$settings = array(

						array(
							'type'  => 'title',
							'title' => __( '서비스 제공자 설정', 'wskl' ),
							'id'    => 'provider_options',
						),
						array(
							'id'      => 'provider_id',
							'type'    => 'text',
							'title'   => '아이디',
							'desc'    => '',
							'default' => '',
						),
						array(
							'id'      => 'provider_word',
							'type'    => 'password',
							'title'   => '패스워드',
							'desc'    => '',
							'default' => '',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'provider_options',
						),

						array(
							'type'  => 'title',
							'title' => __( '문자메시지 일반 옵션', 'wskl' ),
							'id'    => 'general_options',
						),
						array(
							'id'      => 'sender_phone',
							'title'   => '발신번호',
							'type'    => 'text',
							'desc'    => '',
							'default' => '',
						),
						array(
							'id'      => 'shop_manager_phones',
							'title'   => '상점관리자 수신번호',
							'type'    => 'textarea',
							'desc'    => '',
							'default' => '',
						),
						array(
							'type' => 'sms_provider',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'general_options',
						),
					);
					break;

				case 'new-order':
					$settings = array(
						array(
							'id'    => 'new-order_options',
							'title' => __( 'New order', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
							'desc'  => '',
							'type'  => 'title',
						),

						array(
							'id'    => 'send_to_managers',
							'title' => '상점 관리자에게 문자 전송',
							'desc'  => '',
							'type'  => 'checkbox',
						),

						array(
							'id'    => 'message-title',
							'title' => '메시지 제목',
							'desc'  => '',
							'type'  => 'text',
						),

						array(
							'id'    => 'message-content',
							'title' => '메시지 내용',
							'desc'  => '',
							'type'  => 'textarea',
						),

						array(
							'type' => 'sectionend',
							'id'   => 'new-order_options',
						),
					);
					break;

				case 'processing-order':
					$settings = array(
						array(
							'id'    => 'processing-order_options',
							'title' => __( 'Processing order', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
							'desc'  => '',
							'type'  => 'title',
						),

						array(
							'id'    => 'enabled',
							'title' => '활성화',
							'desc'  => '',
							'type'  => 'checkbox',
						),

						array(
							'id'    => 'message-title',
							'title' => '메시지 제목',
							'desc'  => '',
							'type'  => 'text',
						),

						array(
							'id'    => 'message-content',
							'title' => '메시지 내용',
							'desc'  => '',
							'type'  => 'textarea',
						),

						array(
							'id'    => 'send_to_managers',
							'title' => '상점 관리자에게 문자 전송',
							'desc'  => '',
							'type'  => 'checkbox',
						),

						array(
							'type' => 'sectionend',
							'id'   => 'processing-order_options',
						),
					);
					break;

				case 'completed-order':
					$settings = array(
						array(
							'id'    => 'completed-order_options',
							'title' => __( 'Completed order', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
							'desc'  => '',
							'type'  => 'title',
						),

						array(
							'id'    => 'enabled',
							'title' => '활성화',
							'desc'  => '',
							'type'  => 'checkbox',
						),

						array(
							'id'    => 'message-title',
							'title' => '메시지 제목',
							'desc'  => '',
							'type'  => 'text',
						),

						array(
							'id'    => 'message-content',
							'title' => '메시지 내용',
							'desc'  => '',
							'type'  => 'textarea',
						),

						array(
							'id'    => 'send_to_managers',
							'title' => '상점 관리자에게 문자 전송',
							'desc'  => '',
							'type'  => 'checkbox',
						),

						array(
							'type' => 'sectionend',
							'id'   => 'completed-order_options',
						),
					);
					break;

				case 'customer-note':
					$settings = array(
						array(
							'id'    => 'customer-note_options',
							'title' => __( 'Customer note', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
							'desc'  => '',
							'type'  => 'title',
						),

						array(
							'id'    => 'enabled',
							'title' => '활성화',
							'desc'  => '',
							'type'  => 'checkbox',
						),

						array(
							'id'    => 'message-title',
							'title' => '메시지 제목',
							'desc'  => '',
							'type'  => 'text',
						),

						array(
							'id'    => 'message-content',
							'title' => '메시지 내용',
							'desc'  => '',
							'type'  => 'textarea',
						),

						array(
							'type' => 'sectionend',
							'id'   => 'customer-note_options',
						),
					);
					break;

				case 'new-account':
					$settings = array(
						array(
							'id'    => 'new-account_options',
							'title' => __( 'New account', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
							'desc'  => '',
							'type'  => 'title',
						),

						array(
							'id'    => 'enabled',
							'title' => '활성화',
							'desc'  => '',
							'type'  => 'checkbox',
						),

						array(
							'id'    => 'message-title',
							'title' => '메시지 제목',
							'desc'  => '',
							'type'  => 'text',
						),

						array(
							'id'    => 'message-content',
							'title' => '메시지 내용',
							'desc'  => '',
							'type'  => 'textarea',
						),

						array(
							'type' => 'sectionend',
							'id'   => 'new-account-order_options',
						),
					);
					break;

				case 'payment-bacs':
					$settings = array(
						array(
							'id'    => 'payment_bacs_options',
							'title' => __( 'BACS 결제', 'wskl' ) . ' ' . __( '설정', 'wskl' ),
							'desc'  => '',
							'type'  => 'title',
						),

						array(
							'id'    => 'enabled',
							'title' => '활성화',
							'desc'  => '',
							'type'  => 'checkbox',
						),

						array(
							'id'    => 'message-title',
							'title' => '메시지 제목',
							'desc'  => '',
							'type'  => 'text',
						),

						array(
							'id'    => 'message-content',
							'title' => '메시지 내용',
							'desc'  => '',
							'type'  => 'textarea',
						),

						array(
							'id'    => 'send_to_managers',
							'title' => '상점 관리자에게 문자 전송',
							'desc'  => '',
							'type'  => 'checkbox',
						),

						array(
							'type' => 'sectionend',
							'id'   => 'payment-bacs_options',
						),
					);
					break;

				case 'messaging':

					global $hide_save_button;

					$hide_save_button = TRUE;

					$settings = array(
						array(
							'id'    => 'messaging_options',
							'title' => '메시지 보내기',
							'desc'  => '',
							'type'  => 'title',
						),
						array(
							'id'      => 'message_type',
							'title'   => '메시지 타입',
							'type'    => 'select',
							'options' => array(
								'sms' => '단문 메시지 (SMS)',
								'lms' => '장문 메시지 (LMS)',
								'mms' => '멀티미디어 메시지 (MMS)',
							),
						),
						array(
							'id'                => 'message_point',
							'title'             => __( '잔여 포인트', 'wskl' ),
							'type'              => 'text',
							'css'               => 'width: 10em',
							'custom_attributes' => array(
								'readonly' => '',
							),
							'desc'              => __( '포인트', 'wskl' ),
						),
						array(
							'id'    => 'message_type',
							'title' => '메시지 본문',
							'type'  => 'textarea',
							'css'   => 'width: 640px; height: 480px;',
						),
						array(
							'id'   => 'message_sending',
							'type' => 'message_sending',
						),
						array(
							'id'   => 'messaging_options',
							'type' => 'sectionend',
						),
					);
					break;

				default:
					$settings = array();
					break;
			}

			return $settings;
		}

		public function message_sending() {

			wskl_get_template( 'admin/dabory-sms/message-sending.php' );
		}

		public function save() {

			global $current_section;

			$settings = $this->get_settings( $current_section );

			WC_Admin_Settings::save_fields( $settings );
		}
	}

endif;