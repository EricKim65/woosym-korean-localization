<?php

wskl_check_abspath();

require_once( WSKL_PATH . '/includes/admin/dabory-sms/class-wskl-sms-text-substitution.php' );


if ( ! class_exists( 'WSKL_Settings_Dabory_SMS' ) ) :

	class WSKL_Settings_Dabory_SMS extends WC_Settings_Page {

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
			// 메시지 테스트 출력
			add_action( 'woocommerce_admin_field_message_tester', array( __CLASS__, 'message_tester' ), 10, 0 );

			add_action(
				'woocommerce_admin_field_magic_text_information',
				array( __CLASS__, 'output_substitution_information' ),
				10,
				0
			);
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
					$settings = $this->get_settings_general();
					break;

				case 'new-order':
					$settings = $this->get_settings_new_order();
					break;

				case 'processing-order':
					$settings = $this->get_settings_processing_order();
					break;

				case 'completed-order':
					$settings = $this->get_settings_completed_order();
					break;

				case 'customer-note':
					$settings = $this->get_settings_customer_note();
					break;

				case 'new-account':
					$settings = $this->get_settings_new_account();
					break;

				case 'payment-bacs':
					$settings = $this->get_settings_payment_bacs();
					break;

				default:
					$settings = array();
					break;
			}

			return $settings;
		}

		public function save() {

			global $current_section;

			$settings = $this->get_settings( $current_section );

			WC_Admin_Settings::save_fields( $settings );
		}

		public static function provider_additional() { ?>
			<tr valign="top">
				<td colspan="2" class="forminp forminp-provider-information">
					<p>
						문자 메시지는 '<a href="http://www.mdalin.co.kr" target="_blank">문자달인</a>'을 통해 제공됩니다.
						<a href="https://www.mdalin.co.kr:444/callback_mgr/callback_manager.php" target="_blank">발신자
							등록</a> |
						<a href="http://www.mdalin.co.kr/pay/pay.html" target="_blank">포인트 충전</a> |
						<a href="http://www.mdalin.co.kr/member/member_join.php" target="_blank">회원가입</a>
					</p>
				</td>
			</tr>
			<?php
		}

		public static function message_tester() {

			wskl_get_template( 'admin/settings/dabory-sms-message-tester.php' );
		}

		public static function output_substitution_information() {

			$substitution = new WSKL_SMS_Text_Substitution();

			$order_magic_texts = $substitution->get_order_magic_texts();
			$user_magic_texts  = $substitution->get_user_magic_texts();

			wskl_get_template(
				'admin/settings/dabory-sms-substitution.php',
				array(
					'order_magic_texts' => &$order_magic_texts,
					'user_magic_texts'  => &$user_magic_texts,
				)
			);
		}

		private function get_settings_general() {

			return array(

				array(
					'type'  => 'title',
					'title' => __( '서비스 제공자 설정', 'wskl' ),
					'id'    => 'provider_options',
				),
				array(
					'id'      => wskl_get_option_name( 'sms_provider_id' ),
					'type'    => 'text',
					'title'   => '아이디',
					'desc'    => '',
					'default' => '',
				),
				array(
					'id'      => wskl_get_option_name( 'sms_provider_password' ),
					'type'    => 'password',
					'title'   => '패스워드',
					'desc'    => '',
					'default' => '',
				),
				array(
					'type' => 'sms_provider_additional',
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
					'id'      => wskl_get_option_name( 'sms_sender_phone' ),
					'title'   => __( '발신번호', 'wskl' ),
					'type'    => 'text',
					'desc'    => __( '발신자의 전화번호는 반드시 사전에 발신자 등록이 되어야 합니다.', 'wskl' ),
					'default' => '',
				),
				array(
					'id'      => wskl_get_option_name( 'sms_receiver_meta_field' ),
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
			);
		}

		private function get_settings_new_order() {

			return array(
				array(
					'id'    => 'new-order_options',
					'title' => __( 'New order', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => '',
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_new_order_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '새 주문이 발생하면 고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_new_order_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_new_order_message_title' ),
					'title' => __( '메시지 제목', 'wskl' ),
					'desc'  => __( '단문메시지(SMS)에서는 생략됩니다.', 'wskl' ),
					'type'  => 'text',
				),
				array(
					'id'                => wskl_get_option_name( 'sms_new_order_message_content' ),
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

		private function get_settings_processing_order() {

			return array(
				array(
					'id'    => 'processing-order_options',
					'title' => __( 'Processing order', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => __( '', '' ),
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_processing_order_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '주문 상태가 \'처리중\'으로 변경되면 고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_processing_order_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_processing_order_message_title' ),
					'title' => __( '메시지 제목', 'wskl' ),
					'desc'  => __( '단문메시지(SMS)에서는 생략됩니다.', 'wskl' ),
					'type'  => 'text',
				),
				array(
					'id'                => wskl_get_option_name( 'sms_processing_order_message_content' ),
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

		private function get_settings_completed_order() {

			return array(
				array(
					'id'    => 'completed-order_options',
					'title' => __( 'Completed order', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => '',
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_completed_order_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '주문 상태가 \'완료됨\'으로 변경되면 고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_completed_order_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_completed_order_message_title' ),
					'title' => __( '메시지 제목', 'wskl' ),
					'desc'  => __( '단문메시지(SMS)에서는 생략됩니다.', 'wskl' ),
					'type'  => 'text',
				),
				array(
					'id'                => wskl_get_option_name( 'sms_completed_order_message_content' ),
					'title'             => __( '메시지 내용', 'wskl' ),
					'desc'              => __(
						'메시지 본문 템플릿을 작성하세요. 단문메시지(SMS) 1건으로 처리 되지 않는 긴 문자는 장문메시지(LMS)로 전송됩니다.',
						'wskl'
					),
					'type'              => 'textarea',
					'default'           => '[{site_title}] 주문이 완료되었습니다. #{order_number} - {order_date}',
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

		private function get_settings_customer_note() {

			return array(
				array(
					'id'    => 'customer-note_options',
					'title' => __( 'Customer note', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => '',
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_customer_note_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '고객이 메모를 작성하면 고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_customer_note_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'      => wskl_get_option_name( 'sms_customer_note_message_title' ),
					'title'   => __( '메시지 제목', 'wskl' ),
					'desc'    => __( '단문메시지(SMS)에서는 생략됩니다 ', 'wskl' ),
					'type'    => 'text',
					'default' => __( '주문에 메모가 추가되었습니다.', 'wskl' ),
				),
				array(
					'id'                => wskl_get_option_name( 'sms_customer_note_message_content' ),
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

		private function get_settings_new_account() {

			return array(
				array(
					'id'    => 'new-account_options',
					'title' => __( 'New account', 'woocommerce' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => '',
					'type'  => 'title',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_new_account_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '새 계정이 생성되면 그 계정의 휴대전화번호로 문자 메시지를 전송합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'      => wskl_get_option_name( 'sms_new_account_message_title' ),
					'title'   => __( '메시지 제목', 'wskl' ),
					'desc'    => __( '단문메시지(SMS)에서는 생략됩니다 ', 'wskl' ),
					'type'    => 'text',
					'default' => __( '새 계정이 만들어졌습니다. {{site_name}}', 'wskl' ),
				),
				array(
					'id'                => wskl_get_option_name( 'sms_new_account_message_content' ),
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
					'id'   => 'new-account-order_options',
				),
			);
		}

		private function get_settings_payment_bacs() {

			return array(
				array(
					'id'    => 'payment_bacs_options',
					'title' => __( 'BACS 결제', 'wskl' ) . ' ' . __( '설정', 'wskl' ),
					'desc'  => __( '무통장입금(BACS)으로 결제시 별도의 문자 메세지를 전송합니다. 예) 입금 계좌 정보 안내.', 'wskl' ),
					'type'  => 'title',
				),

				array(
					'id'    => wskl_get_option_name( 'sms_payment_bacs_enabled' ),
					'title' => __( '활성화', 'wskl' ),
					'desc'  => __( '고객에게 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'    => wskl_get_option_name( 'sms_payment_bacs_send_to_managers' ),
					'title' => __( '상점 관리자에게 문자 전송', 'wskl' ),
					'desc'  => __( '같은 내용을 상점 관리자에게도 문자 메시지로 통보합니다.', 'wskl' ),
					'type'  => 'checkbox',
				),
				array(
					'id'      => wskl_get_option_name( 'sms_payment_bacs_message_title' ),
					'title'   => __( '메시지 제목', 'wskl' ),
					'desc'    => __( '단문메시지(SMS)에서는 생략됩니다 ', 'wskl' ),
					'type'    => 'text',
					'default' => '{site_title} 무통장입금 안내',
				),
				array(
					'id'                => wskl_get_option_name( 'sms_payment_bacs_message_content' ),
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