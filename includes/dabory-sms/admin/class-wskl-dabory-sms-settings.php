<?php

wskl_check_abspath();

require_once( WSKL_PATH . '/includes/dabory-sms/providers/class-wskl-dabory-sms-provider-loading.php' );


if ( ! class_exists( 'WSKL_Dabory_SMS_Settings' ) ) :

	class WSKL_Dabory_SMS_Settings {

		/**
		 * 세팅 id 는 option 테이블에 바로 적용됨.
		 * 이름 규칙: "sms_{$scenario}_"를 prefixing.
		 * 단 모든 option 에는 WSKL_PREFIX 가 붙으므로, 최종적으로 DB 테이블에는 WSKL_PREFIX. "sms_{$scenario}_" 형태로 기록됨.
		 *
		 * @param $current_section
		 *
		 * @return array
		 */
		public static function static_get_settings( $current_section ) {

			switch ( $current_section ) {
				case '':
					$settings = self::get_settings_general();
					break;

				case 'new-order':
					$settings = self::get_settings_new_order();
					break;

				case 'cancelled-order':
					$settings = self::get_settings_cancelled_order();
					break;

				case 'failed-order':
					$settings = self::get_settings_failed_order();
					break;

				case 'processing-order':
					$settings = self::get_settings_processing_order();
					break;

				case 'completed-order':
					$settings = self::get_settings_completed_order();
					break;

				case 'refunded-order':
					$settings = self::get_settings_refunded_order();
					break;

				case 'customer-note':
					$settings = self::get_settings_customer_note();
					break;

				case 'customer-new-account':
					$settings = self::get_settings_customer_new_account();
					break;

				case 'customer-reset-password':
					$settings = self::get_settings_customer_reset_password();
					break;

				case 'payment-bacs':
					$settings = self::get_settings_payment_bacs();
					break;

				default:
					$settings = array();
					break;
			}

			return $settings;
		}

		/**
		 * 일반 옵션
		 *
		 * @return array
		 */
		private static function get_settings_general() {

			$auth_section = WSKL_Dabory_SMS_Provider_Loading::get_auth_section_settings();

			return array_merge(
				$auth_section,
				array(
					array(
						'type'  => 'title',
						'title' => __( '문자메시지 일반 옵션', 'wskl' ),
						'id'    => 'general_options',
					),
					array(
						'id'      => wskl_get_option_name( 'sms_sender_phone' ),
						'title'   => __( '발신번호', 'wskl' ),
						'type'    => 'text',
						'desc'    => __( '발신자의 전화번호는 반드시 사전에 발신자 등록이 되어야 합니다.', 'wskl' ),
						'default' => '',
					),
					array(
						'id'      => wskl_get_option_name( 'sms_receiver_meta_field' ),
						'type'    => 'text',
						'title'   => __( '전화번호 메타 필드', 'wskl' ),
						'desc'    => __( '문자메시지 발송에 필요한 고객의 휴대전화를 저장하는 메타 필드의 이름입니다. 기본값: billing_phone', 'wskl' ),
						'default' => 'billing_phone',
					),
					array(
						'id'                => wskl_get_option_name( 'sms_shop_manager_phones' ),
						'title'             => '상점관리자 수신번호',
						'type'              => 'textarea',
						'desc'              => __( '상점관리자의 전화번호를 한 줄에 하나씩 입력합니다.', 'wskl' ),
						'custom_attributes' => array(
							'rows' => 10,
							'cols' => 18,
						),
						'default'           => '',
					),

					array(
						'type' => 'message_tester',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'general_options',
					),
				)
			);
		}

		/**
		 * 새 주문 옵션
		 *
		 * @return array
		 */
		private static function get_settings_new_order() {

			return array(
				array(
					'id'    => 'new-order_options',
					'title' => __( 'New order', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => '',
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_new-order_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '새 주문이 발생하면 고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_new-order_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_new-order_message_title' ),
					'title' => __( '메시지 제목', 'wskl' ),
					'desc'  => __( '단문메시지(SMS)에서는 생략됩니다.', 'wskl' ),
					'type'  => 'text',
				),
				array(
					'id'                => wskl_get_option_name( 'sms_new-order_message_content' ),
					'title'             => __( '메시지 내용', 'wskl' ),
					'desc'              => __(
						'메시지 본문 템플릿을 작성하세요. 단문메시지(SMS) 1건으로 처리 되지 않는 긴 문자는 장문메시지(LMS)로 전송됩니다.',
						'wskl'
					),
					'type'              => 'textarea',
					'default'           => '[{site_title}] 새 주문이 만들어졌습니다. #{order_number} - {order_date}',
					'custom_attributes' => array(
						'rows' => 7,
						'cols' => 80,
					),
				),
				array(
					'type' => 'magic_text_information',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'new-order_options',
				),
			);
		}

		/**
		 * 주문 취소
		 *
		 * @return array
		 */
		private static function get_settings_cancelled_order() {

			return array(
				array(
					'id'    => 'cancelled-order_options',
					'title' => __( 'Cancelled order', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => '',
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_cancelled-order_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '주문이 취소되면 고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_cancelled-order_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_cancelled-order_message_title' ),
					'title' => __( '메시지 제목', 'wskl' ),
					'desc'  => __( '단문메시지(SMS)에서는 생략됩니다.', 'wskl' ),
					'type'  => 'text',
				),
				array(
					'id'                => wskl_get_option_name( 'sms_cancelled-order_message_content' ),
					'title'             => __( '메시지 내용', 'wskl' ),
					'desc'              => __(
						'메시지 본문 템플릿을 작성하세요. 단문메시지(SMS) 1건으로 처리 되지 않는 긴 문자는 장문메시지(LMS)로 전송됩니다.',
						'wskl'
					),
					'type'              => 'textarea',
					'default'           => '[{site_title}] 주문이 취소되었습니다.. #{order_number} - {order_date}',
					'custom_attributes' => array(
						'rows' => 7,
						'cols' => 80,
					),
				),
				array(
					'type' => 'magic_text_information',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'cancelled-order_options',
				),
			);
		}

		/**
		 * 주문 실패
		 *
		 * @return array
		 */
		private static function get_settings_failed_order() {

			return array(
				array(
					'id'    => 'failed-order_options',
					'title' => __( 'Failed order', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => '',
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_failed-order_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '주문이 실패하면 고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_failed-order_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_failed-order_message_title' ),
					'title' => __( '메시지 제목', 'wskl' ),
					'desc'  => __( '단문메시지(SMS)에서는 생략됩니다.', 'wskl' ),
					'type'  => 'text',
				),
				array(
					'id'                => wskl_get_option_name( 'sms_failed-order_message_content' ),
					'title'             => __( '메시지 내용', 'wskl' ),
					'desc'              => __(
						'메시지 본문 템플릿을 작성하세요. 단문메시지(SMS) 1건으로 처리 되지 않는 긴 문자는 장문메시지(LMS)로 전송됩니다.',
						'wskl'
					),
					'type'              => 'textarea',
					'default'           => '[{site_title}] 주문이 실패했습니다. #{order_number} - {order_date}',
					'custom_attributes' => array(
						'rows' => 7,
						'cols' => 80,
					),
				),
				array(
					'type' => 'magic_text_information',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'failed-order_options',
				),
			);
		}

		/**
		 * 주문 처리중
		 *
		 * @return array
		 */
		private static function get_settings_processing_order() {

			return array(
				array(
					'id'    => 'processing-order_options',
					'title' => __( 'Processing order', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => __( '', '' ),
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_processing-order_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '주문 상태가 \'처리중\'으로 변경되면 고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_processing-order_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_processing-order_message_title' ),
					'title' => __( '메시지 제목', 'wskl' ),
					'desc'  => __( '단문메시지(SMS)에서는 생략됩니다.', 'wskl' ),
					'type'  => 'text',
				),
				array(
					'id'                => wskl_get_option_name( 'sms_processing-order_message_content' ),
					'title'             => __( '메시지 내용', 'wskl' ),
					'desc'              => __(
						'메시지 본문 템플릿을 작성하세요. 단문메시지(SMS) 1건으로 처리 되지 않는 긴 문자는 장문메시지(LMS)로 전송됩니다.',
						'wskl'
					),
					'type'              => 'textarea',
					'default'           => '[{site_title}] 주문되었습니다. #{order_number} - {order_date}',
					'custom_attributes' => array(
						'rows' => 7,
						'cols' => 80,
					),
				),
				array(
					'type' => 'magic_text_information',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'processing-order_options',
				),
			);
		}

		/**
		 * 주문 완료
		 *
		 * @return array
		 */
		private static function get_settings_completed_order() {

			return array(
				array(
					'id'    => 'completed-order_options',
					'title' => __( 'Completed order', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => '',
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_completed-order_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '주문 상태가 \'완료됨\'으로 변경되면 고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_completed-order_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_completed-order_message_title' ),
					'title' => __( '메시지 제목', 'wskl' ),
					'desc'  => __( '단문메시지(SMS)에서는 생략됩니다.', 'wskl' ),
					'type'  => 'text',
				),
				array(
					'id'                => wskl_get_option_name( 'sms_completed-order_message_content' ),
					'title'             => __( '메시지 내용', 'wskl' ),
					'desc'              => __(
						'메시지 본문 템플릿을 작성하세요. 단문메시지(SMS) 1건으로 처리 되지 않는 긴 문자는 장문메시지(LMS)로 전송됩니다.',
						'wskl'
					),
					'type'              => 'textarea',
					'default'           => '[{site_title}] 주문이 완료되었습니다. #{order_number} - {order_date} {tracking-number}',
					'custom_attributes' => array(
						'rows' => 7,
						'cols' => 80,
					),
				),
				array(
					'type' => 'magic_text_information',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'completed-order_options',
				),
			);
		}

		/**
		 * 환불 처리: partial refund, full refund.
		 *
		 * @return array
		 */
		private static function get_settings_refunded_order() {

			return array(
				array(
					'id'    => 'refunded-order_options',
					'title' => __( 'Refunded order', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => '',
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_refunded-order_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '주문 상태가 \'환불됨\'으로 변경되면 고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_refunded-order_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_refunded-order_message_title' ),
					'title' => __( '메시지 제목', 'wskl' ),
					'desc'  => __( '단문메시지(SMS)에서는 생략됩니다.', 'wskl' ),
					'type'  => 'text',
				),
				array(
					'id'                => wskl_get_option_name( 'sms_refunded-order_message_content' ),
					'title'             => __( '메시지 내용', 'wskl' ),
					'desc'              => __(
						'메시지 본문 템플릿을 작성하세요. 단문메시지(SMS) 1건으로 처리 되지 않는 긴 문자는 장문메시지(LMS)로 전송됩니다.',
						'wskl'
					),
					'type'              => 'textarea',
					'default'           => '[{site_title}] 환불 처리되었습니다. #{order_number} - {order_date}',
					'custom_attributes' => array(
						'rows' => 7,
						'cols' => 80,
					),
				),
				array(
					'type' => 'magic_text_information',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'refunded-order_options',
				),
			);
		}

		/**
		 * 주문 노트: 사업자에게서 고객에게 작성하는 노트만 해당
		 *
		 * @return array
		 */
		private static function get_settings_customer_note() {

			return array(
				array(
					'id'    => 'customer-note_options',
					'title' => __( 'Customer note', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => '',
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_customer-note_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '고객이 메모를 작성하면 고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_customer-note_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'      => wskl_get_option_name( 'sms_customer-note_message_title' ),
					'title'   => __( '메시지 제목', 'wskl' ),
					'desc'    => __( '단문메시지(SMS)에서는 생략됩니다 ', 'wskl' ),
					'type'    => 'text',
					'default' => __( '주문에 메모가 추가되었습니다.', 'wskl' ),
				),
				array(
					'id'                => wskl_get_option_name( 'sms_customer-note_message_content' ),
					'title'             => __( '메시지 내용', 'wskl' ),
					'desc'              => __(
						'메시지 본문 템플릿을 작성하세요. 단문메시지(SMS) 1건으로 처리 되지 않는 긴 문자는 장문메시지(LMS)로 전송됩니다.',
						'wskl'
					),
					'type'              => 'textarea',
					'default'           => '[{site_title}] 주문 #{order_number}에 메모가 추가되었습니다 - {order_date}',
					'custom_attributes' => array(
						'rows' => 7,
						'cols' => 80,
					),
				),
				array(
					'type' => 'magic_text_information',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'customer-note_options',
				),
			);
		}

		/**
		 * 새 계정
		 *
		 * @return array
		 */
		private static function get_settings_customer_new_account() {

			return array(
				array(
					'id'    => 'customer-new-account_options',
					'title' => __( 'New account', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => '새 계정이 생성되면 문자를 송신합니다. WP-Members 플러그인이 활성화 되어야 합니다.',
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_customer-new-account_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '새 계정이 생성되면 그 계정의 휴대전화번호로 문자 메시지를 전송합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'      => wskl_get_option_name( 'sms_customer-new-account_phone_meta_field' ),
					'title'   => __( '휴대전화 메타 필드', 'wskl' ),
					'desc'    => __( '문자메시지 발송에 필요한 고객의 휴대전화를 저장하는 메타 필드의 이름입니다. 기본값: phone1', 'wskl' ),
					'type'    => 'text',
					'default' => 'phone1',
				),
				array(
					'id'      => wskl_get_option_name( 'sms_customer-new-account_message_title' ),
					'title'   => __( '메시지 제목', 'wskl' ),
					'desc'    => __( '단문메시지(SMS)에서는 생략됩니다 ', 'wskl' ),
					'type'    => 'text',
					'default' => __( '새 계정이 만들어졌습니다. {site_title}', 'wskl' ),
				),
				array(
					'id'                => wskl_get_option_name( 'sms_customer-new-account_message_content' ),
					'title'             => __( '메시지 내용', 'wskl' ),
					'desc'              => __(
						'메시지 본문 템플릿을 작성하세요. 단문메시지(SMS) 1건으로 처리 되지 않는 긴 문자는 장문메시지(LMS)로 전송됩니다.',
						'wskl'
					),
					'type'              => 'textarea',
					'default'           => '[{site_title}] {user_login}님 가입을 환영합니다.',
					'custom_attributes' => array(
						'rows' => 7,
						'cols' => 80,
					),
				),
				array(
					'type' => 'magic_text_information',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'customer-new-account-order_options',
				),
			);
		}

		/**
		 * 패스워드 초기화
		 *
		 * @return array
		 */
		private static function get_settings_customer_reset_password() {

			return array(
				array(
					'id'    => 'customer-reset-password_options',
					'title' => __( 'Reset password', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => '비밀번호가 초기화되면 문자를 송신합니다. WP-Members 플러그인이 활성화 되어야 합니다.',
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_customer-reset-password_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '비밀번호가 초기화되면 그 계정의 휴대전화번호로 문자 메시지를 전송합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'      => wskl_get_option_name( 'sms_customer-reset-password_phone_meta_field' ),
					'title'   => __( '휴대전화 메타 필드', 'wskl' ),
					'desc'    => __( '문자메시지 발송에 필요한 고객의 휴대전화를 저장하는 메타 필드의 이름입니다. 기본값: phone1', 'wskl' ),
					'type'    => 'text',
					'default' => 'phone1',
				),
				array(
					'id'      => wskl_get_option_name( 'sms_customer-reset-password_message_title' ),
					'title'   => __( '메시지 제목', 'wskl' ),
					'desc'    => __( '단문메시지(SMS)에서는 생략됩니다 ', 'wskl' ),
					'type'    => 'text',
					'default' => __( '[{site_title}]비밀번호가 초기화되었습니다', 'wskl' ),
				),
				array(
					'id'                => wskl_get_option_name( 'sms_customer-reset-password_message_content' ),
					'title'             => __( '메시지 내용', 'wskl' ),
					'desc'              => __(
						'메시지 본문 템플릿을 작성하세요. 단문메시지(SMS) 1건으로 처리 되지 않는 긴 문자는 장문메시지(LMS)로 전송됩니다.',
						'wskl'
					),
					'type'              => 'textarea',
					'default'           => '[{site_title}] {user_login}님, 비밀번호가 초기화되었습니다. 메일함을 확인하세요.',
					'custom_attributes' => array(
						'rows' => 7,
						'cols' => 80,
					),
				),
				array(
					'type' => 'magic_text_information',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'customer-reset-password-order_options',
				),
			);
		}

		/**
		 * BACS 지불에 대해 계좌 정보 알림
		 *
		 * @return array
		 */
		private static function get_settings_payment_bacs() {

			return array(
				array(
					'id'    => 'payment_bacs_options',
					'title' => __( 'BACS 결제', 'wskl' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => __( '무통장입금(BACS)으로 결제시 별도의 문자 메세지를 전송합니다. 예) 입금 계좌 정보 안내.', 'wskl' ),
					'type'  => 'title',
				),

				array(
					'id'    => wskl_get_option_name( 'sms_payment-bacs_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_payment-bacs_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'      => wskl_get_option_name( 'sms_payment-bacs_message_title' ),
					'title'   => __( '메시지 제목', 'wskl' ),
					'desc'    => __( '단문메시지(SMS)에서는 생략됩니다 ', 'wskl' ),
					'type'    => 'text',
					'default' => '{site_title} 무통장입금 안내',
				),
				array(
					'id'                => wskl_get_option_name( 'sms_payment-bacs_message_content' ),
					'title'             => __( '메시지 내용', 'wskl' ),
					'desc'              => __(
						'메시지 본문 템플릿을 작성하세요. 단문메시지(SMS) 1건으로 처리 되지 않는 긴 문자는 장문메시지(LMS)로 전송됩니다.',
						'wskl'
					),
					'type'              => 'textarea',
					'default'           => '[{site_title}] 주문 감사드립니다. #{order_number} - {order_date}. 입금정보 **은행 1234-56-7890 예금주',
					'custom_attributes' => array(
						'rows' => 7,
						'cols' => 80,
					),
				),
				array(
					'type' => 'order_magic_text_information',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'payment-bacs_options',
				),
			);
		}
	}

endif;