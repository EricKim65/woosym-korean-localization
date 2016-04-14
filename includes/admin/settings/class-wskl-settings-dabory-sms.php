<?php

wskl_check_abspath();

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
					$settings = array(

						array(
							'type'  => 'title',
							'title' => __( '서비스 제공자 설정', 'wskl' ),
							'id'    => 'provider_options',
						),
						array(
							'id'      => wskl_get_option_name( 'dabory_sms_provider_id' ),
							'type'    => 'text',
							'title'   => '아이디',
							'desc'    => '',
							'default' => '',
						),
						array(
							'id'      => wskl_get_option_name( 'dabory_sms_provider_password' ),
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
							'id'      => wskl_get_option_name( 'dabory_sms_sender_phone' ),
							'title'   => '발신번호',
							'type'    => 'text',
							'css'     => 'width: 320px;',
							'desc'    => __( '발신자의 전화번호는 반드시 사전에 발신자 등록이 되어야 합니다.', 'wskl' ),
							'default' => '',
						),
						array(
							'id'      => wskl_get_option_name( 'dabory_sms_shop_manager_phones' ),
							'title'   => '상점관리자 수신번호',
							'type'    => 'textarea',
							'desc'    => __( '상점관리자의 전화번호를 한 줄에 하나씩 입력합니다.', 'wskl' ),
							'default' => '',
						),
						array(
							'type' => 'message_tester',
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

		public static function message_tester() { ?>
			<tr valign="top">
				<th class="titledesc" scope="row">
					<label for=""><?php _e( '메시지 테스트', 'wskl' ); ?></label>
				</th>
				<td class="forminp forminp-sms-point">
					<button id="dabory-sms-message-tester" type="button" class="button button-secondary">
						<?php _e( '메시지 테스트', 'wskl' ); ?>
					</button>
					<span class="description">
						<?php _e( '발신번호로 테스트 문자를 보냅니다. SMS 포인트를 소모합니다.', 'wskl' ); ?>
					</span>
					<script type="application/javascript">
						(function ($) {
							$('button#dabory-sms-message-tester').click(function () {
								if (confirm('<?php _e( '테스트 문자를 보내시겠습니까?', 'wskl' )?>')) {
									$.ajax({
										'url': ajaxurl,
										'method': 'post',
										'async': true,
										'data': {
											'action': 'dabory-sms-tester',
											'dabory-sms-tester-nonce': '<?php echo wp_create_nonce(
												'dabory-sms-tester-nonce'
											) ?>'
										},
										success: function (response) {
											if (response.success) {
												alert('<?php _e( '문자를 성공적으로 보냈습니다.', 'wskl' );?>');
											} else {
												alert(response.data);
											}
										}
									});
								}
							});
						})(jQuery);
					</script>
				</td>
			</tr>
			<?php
		}
	}

endif;