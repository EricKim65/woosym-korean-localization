<?php
/**
 * 페이앱(http://payapp.kr/) 우커머스 용 결제 모듈
 */

/**
 * @action  plugin_loaded
 * @used-by add_action
 * @see     WC_Payment_Gateway
 */
function init_wc_gateway_payapp() {

	if ( class_exists( 'WC_Payment_Gateway' ) && ! class_exists( 'WC_Gate_PayApp' ) ) {

		/**
		 * Class WC_Gateway_PayApp
		 */
		class WC_Gateway_PayApp extends WC_Payment_Gateway {

			public function __construct() {

				$this->id         = 'payapp';
				$this->icon       = null;
				$this->has_fields = false;

				$this->init_form_fields();
				$this->init_settings();

				$this->title       = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );

				$this->method_title       = __( '페이앱', 'wskl' );
				$this->method_description = __( '페이앱', 'wskl' );

				add_action(
					'woocommerce_update_options_payment_gateways_' . $this->id,
					array( $this, 'process_admin_options', )
				);

				/**
				 * 주문 확정 버튼 클릭 다음의 페이지인 "주문 지불" 화면에서 별도의 안내를 위한 액션
				 *
				 * @see    woocommerce/includes/shortcodes/class-wc-shortcode-checkout.php
				 * @see    WC_Shortcode_Checkout::order_pay()
				 */
				add_action(
					'after_woocommerce_pay',
					array( $this, 'callback_after_woocommerce_pay' )
				);

				/**
				 * 체크아웃 페이지의 자바스크립트 로드
				 */
				add_action(
					'wp_enqueue_scripts',
					array( $this, 'callback_wp_enqueue_scripts' )
				);

				/**
				 * 우커머스 wc-api 콜백. 페이앱이 주는 피드백에 대응.
				 *
				 * @see woocommerce/includes/class-wc-api.php
				 * @see WC_API::handle_api_requests()
				 */
				add_action(
					'woocommerce_api_wskl-payapp-feedback',
					array( $this, 'callback_payapp_feedback' )
				);
			}

			/**
			 * 체크아웃 페이지의 자바스크립트 로드
			 *
			 * @action wp_enqueue_scripts
			 */
			public function callback_wp_enqueue_scripts() {

				wp_register_script(
					'wskl-payapp-checkout-js',
					plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/payapp-checkout.js',
					array( 'jquery' )
				);

				wp_localize_script(
					'wskl-payapp-checkout-js',
					'payapp_checkout',
					array(
						'loadingPopupUrl' => plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/payapp-loading.php',
					)
				);

				wp_enqueue_script( 'wskl-payapp-checkout-js' );
			}

			/**
			 * 주문 지불 페이지에서 사용자를 위한 설명 유도
			 *
			 * @action after_woocommerce_pay
			 */
			public function callback_after_woocommerce_pay() {

				if ( ! isset( $_GET['key'] ) ) {
					$order_key = wc_get_order()->order_key;
				} else {
					$order_key = esc_attr( $_GET['key'] );
				}

				wp_register_script(
					'wskl-payapp-status-js',
					plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/payapp-status.js',
					array( 'jquery' ),
					null,
					true
				);

				wp_localize_script(
					'wskl-payapp-status-js',
					'payAppStatus',
					array(
						'ajaxUrl'         => add_query_arg(
							array(
								'order_key' => $order_key,
								'wc-ajax'   => 'wskl-payapp-status',
							),
							home_url( '/' )
						),
						'pollingRetryMax' => 60,
						'failureRedirect' => wc()->cart->get_checkout_url(),
					)
				);

				wp_enqueue_script( 'wskl-payapp-status-js' );

				// 결제 유도 안내 메시지.
				$status_check_message = $this->get_option( 'status_check_message' );
				if ( $status_check_message ) {
					echo wpautop( wptexturize( $status_check_message ) );
				}

				$screenshot_url = plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/image/payapp/payapp-screenshot.png';

				echo '<p>' . __( '결제창 팝업은 아래 그림처럼 생성됩니다.', 'wskl' ) . '</p>';
				echo '<img src="' . esc_url( $screenshot_url ) . '" />';
			}

			public function init_form_fields() {

				$this->form_fields = array(

					'enabled'              => array(
						'title'   => __( 'Enable/Disable', 'woocommerce' ),
						'type'    => 'checkbox',
						'label'   => __( '페이앱 사용 활성화', 'wskl' ),
						'default' => 'yes',
					),
					'title'                => array(
						'title'       => __( 'Title', 'woocommerce' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
						'default'     => __( '페이앱으로 결제', 'wskl' ),
						'desc_tip'    => true,
					),
					'description'          => array(
						'title'       => __( 'Description', 'woocommerce' ),
						'type'        => 'textarea',
						'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
						'default'     => __( '페이앱(http://payapp.co.kr)의 비 Active-X 방식(카드사에 따라 차이 있음)의 결제 방식을 사용합니다. ', 'woocommerce' ),
						'desc_tip'    => true,
					),
					'payapp_user_id'       => array(
						'title'       => __( '페이앱 판매자 회원 아이디', 'wskl' ),
						'type'        => 'text',
						'description' => __( '페이앱 판매자의 회원 아이디를 입력합니다.', 'wskl' ),
						'default'     => '',
						'desc_tip'    => true,
					),
					'link_key'             => array(
						'title'       => __( '페이앱 연동 KEY', 'wskl' ),
						'type'        => 'text',
						'description' => __( '페이앱 연동 정보입니다. 페이앱 판매점 관리자 > 설정메뉴 > 연동정보에서 찾을 수 있습니다.', 'wskl' ),
						'default'     => '',
						'desc_tip'    => true,
					),
					'link_val'             => array(
						'title'       => __( '페이앱 연동 VALUE', 'wskl' ),
						'type'        => 'text',
						'description' => __( '페이앱 연동 정보입니다. 페이앱 판매점 관리자 > 설정메뉴 > 연동정보에서 찾을 수 있습니다.', 'wskl' ),
						'default'     => '',
						'desc_tip'    => true,
					),
					'status_check_message' => array(
						'title'       => __( '주문 지불 페이지 메시지', 'wskl' ),
						'type'        => 'textarea',
						'description' => __( '결제 중 브라우저는 주문 지불 페이지에서 페이앱 서버가 Feedback URL을 통해 승인이 되기까지 대기합니다. 이 때 고객에게 보여 줄 메시지를 여기서 작성할 수 있습니다.', 'wskl' ),
						'default'     => __( '페이앱의 결제 팝업 창에서 결제를 진행해 주시면 됩니다. 약 5분 내로 결제가 완료되어야 합니다.', 'wskl' ),
						'desc_top'    => true,
					),
				);
			}

			public function process_payment( $order_id ) {

				$order = wc_get_order( $order_id );

				$args = $this->get_api_arguments( $order );

				// 페이앱 결제 요청 API 콜
				$url      = 'http://api.payapp.kr/oapi/apiLoad.html';
				$response = wp_remote_post( $url, $args );

				if ( $response['response']['code'] != 200 ) {
					return array(
						'result'   => 'failure',
						'messages' => 'Bad response: ' . $response['response']['code'] . ' ' . $response['response']['message'],
					);
				}

				$body = array();
				parse_str( $response['body'], $body );

				if ( $body['state'] != 1 ) {

					$msg = $this->parse_payapp_error_message( $body['errorMessage'] );
					wc_add_notice( $msg, 'error' );

					return array(
						'result' => 'failure',
					);
				}

				$url = $order->get_checkout_payment_url( true );

				return array(
					'result'   => 'success',
					'redirect' => $url,
					'payApp'   => $body,
				);
			}

			/**
			 * @used-by process_payment
			 *
			 * @param WC_Order $order
			 *
			 * @return array wc_remote_post 함수에 사용될 파라미터를 담음.
			 */
			private function get_api_arguments( WC_Order &$order ) {

				$item_count = $order->get_item_count();

				if ( ! $item_count ) {
					throw new \LogicException( 'items are empty!' );
				}

				$items      = $order->get_items();
				$first_item = reset( $items );

				if ( $item_count == 1 ) {
					$goods_name = $first_item['name'];
				} else {
					$fmt        = _n( '외 %d개 상품', '외 %d개 상품들', $item_count - 1, 'wskl' );
					$goods_name = $first_item['name'] . sprintf( $fmt, $item_count - 1 );
				}

				$args = array(
					'sslverify' => false,
					'timeout'   => 15,
					'body'      => array(

						// 결제요청, 필수
						'cmd'         => 'payrequest',

						// 판매자 아이디, 필수
						'userid'      => $this->get_option( 'payapp_user_id' ),

						// 상품명, 필수
						'goodname'    => html_entity_decode( $goods_name ),

						// 결제요청 금액 (1,000원 이상), 필수
						'price'       => $order->order_total,

						// 수신자 휴대폰번호 (구매자), 필수
						'recvphone'   => preg_replace( '/[+\-\s]/', '', $order->billing_phone ),

						// 결제요청시 메모
						'memo'        => '',

						// 주소요청 여부
						'reqaddr'     => '0',

						// 피드백 URL, feedbackurl 은 외부에서 접근이 가능해야 합니다. payapp 서버에서 호출 하는 페이지 입니다.
						'feedbackurl' => str_replace(
							'https:', // search
							'http:',  // replace
							add_query_arg( 'wc-api', 'wskl-payapp-feedback', home_url( '/' ) )
						),

						// 임의변수1. 우리 구현에서는 order ID 를 기록
						'var1'        => $order->id,

						// 임의변수2. 우리 구현에서는 order key 를 기록
						'var2'        => $order->order_key,

						// 결제요청 SMS 발송여부 ('n'인 경우 SMS 발송 안함)
						'smsuse'      => 'n',

						// 통화기호 (krw:원화결제, usd:US달러 결제)
						'currency'    => 'krw',

						// 국제전화 국가번호 (currency가 usd일 경우 필수)
						'vccode'      => '',

						// 결제완료 이동 URL (결제완료 후 매출전표 페이지에서 "확인" 버튼 클릭시 이동)
						'returnurl'   => '',

						// 결제수단 선택 (휴대전화:phone, 신용카드:card, 계좌이체:rbank, 가상계좌:vbank)
						// 판매자 사이트 "설정" 메뉴의 "결제 설정"이 우선 합니다.
						// 해외결제는 현재 신용카드 결제만 가능하며, 입력된 값은 무시됩니다.
						'openpaytype' => 'card',

						// feedbackurl 의 응답이 'SUCCESS'가 아닌 경우 feedbackurl 호출을 재시도 합니다. (총 10회)
						'checkretry'  => 'n',
					),
				);

				return $args;
			}

			/**
			 * 페이앱이 피드백 URL 에 대응하는 콜백.
			 * 피드백 파라미터를 확인하고 올바른 경우에는 최종적으로 주문 내역을 결제된 것으로 업데이트한다.
			 *
			 * @action woocommerce_api_{wskl-payapp-feedback}
			 */
			public function callback_payapp_feedback() {

				$from_post = function ( $key_name, $sanitize = '', $default = '' ) {

					$v = $default;

					if ( isset( $_POST[ $key_name ] ) ) {
						$v = $_POST[ $key_name ];
					}

					if ( is_callable( $sanitize ) ) {
						$v = $sanitize( $v );
					}

					return $v;
				};

				$payapp_uid = $from_post( 'userid' );
				$link_key   = $from_post( 'linkkey' );
				$link_val   = $from_post( 'linkval' );
				$order_id   = $from_post( 'var1', 'absint' );
				$order_key  = $from_post( 'var2', 'sanitize_text_field' );
				$cst_url    = $from_post( 'csturl', 'sanitize_text_field' );     // 전표 주소
				$pay_memo   = $from_post( 'pay_memo', 'sanitize_text_field' );   // 구매자가 기록한 메모
				$mul_no     = $from_post( 'mul_no', 'sanitize_text_field' );     // 결제요청번호
				$pay_state  = $from_post( 'pay_state', 'absint' );               // 결제요청상태 (1: 요청, 4: 결제완료, 8, 16, 32: 요청취소, 9, 64: 승인취소)
				$pay_type   = $from_post( 'pay_type', 'absint' );                // 결제수단 (1: 신용카드, 2: 휴대전화)
				$pay_date   = $from_post( 'pay_date', 'sanitize_text_field' );

				// check payapp_uid
				if ( $payapp_uid != $this->get_option( 'payapp_user_id' ) ) {
					return;
				}

				// check link key and link val
				if ( $link_key != $this->get_option( 'link_key' ) || $link_val != $this->get_option( 'link_val' ) ) {
					return;
				}

				$order = wc_get_order( $order_id );
				if ( ! $order ) {
					return;
				}

				// check order key
				if ( $order_key != $order->order_key ) {
					return;
				}

				// 승인
				if ( $pay_state == 4 ) {

					// 전표 기록
					update_post_meta( $order_id, 'wskl_payapp_cst_url', $cst_url );

					switch ( $pay_type ) {
						case 1:
							$card_name  = $from_post( 'card_name', 'sanitize_text_field' );  // 신용카드시 카드 이름
							$order_note = sprintf(
								__( '결제가 성공적으로 처리됨.<ul><li>결제방법: 신용카드</li><li>카드 이름: %s</li><li>페이앱 결제요청번호: %s</li><li>승인시각: %s</li><li>구매자의 결제창 메시지: %s</li></ul>', 'wskl' ),
								$card_name,
								$mul_no,
								$pay_date,
								$pay_memo
							);
							break;

						case 2:
							$order_note = sprintf(
								__( '결제가 성공적으로 처리됨.<ul><li>결제방법: 휴대전화</li><li>페이앱 결제요청번호: %s</li><li>승인시각: %s</li><li>구매자의 결제창 메시지: %s</li></ul>', 'wskl' ),
								$mul_no,
								$pay_date,
								$pay_memo
							);
							break;

						default:
							$order_note = sprintf(
								__( '결제가 성공적으로 처리됨.<ul><li>결제방법: 기타</li><li>페이앱 결제요청번호: %s</li><li>승인시각: %s</li><li>구매자의 결제창 메시지: %s</li></ul>', 'wskl' ),
								$mul_no,
								$pay_date,
								$pay_memo
							);
					}

					$order->add_order_note( $order_note );
					$order->payment_complete();
					$order->reduce_order_stock();
					wc_empty_cart();
				}
			}

			/**
			 * payapp API가 에러를 전달하는 경우 구체적인 에러 메시지를 보다 고객이 알기 편한 메시지로 변환.
			 * 이 메시지는 체크아웃 페이지의 알림 영역에 게시된다.
			 *
			 * @param string $message
			 *
			 * @return string
			 */
			private function parse_payapp_error_message( $message ) {

				// 잘못된 전화번호 값.
				if ( strpos( $message, 'recvphone' ) !== false ) {
					return __( '전화번호 형식이 잘못되었습니다. 000-0000-0000 형태로 입력해 주세요.', 'wskl' );
				}

				return $message;
			}
		}
	}
}
