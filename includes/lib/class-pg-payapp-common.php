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

	if ( class_exists( 'WC_Payment_Gateway' ) && ! class_exists( 'WC_Gateway_PayApp' ) ) {

		/**
		 * Class WC_Gateway_PayApp
		 */
		class WC_Gateway_PayApp extends WC_Payment_Gateway {

			public $checkout_method = '';

			public function __construct() {

				$this->id         = 'payapp';
				$this->icon       = null;
				$this->has_fields = false;

				$this->init_form_fields();
				$this->init_settings();

				$tab_href = add_query_arg( array(
					'page' => WSKL_MENU_SLUG,
					'tab'  => 'checkout-payment-gate',
				), admin_url( 'admin.php ' ) );

				$this->method_title       = __( '페이앱', 'wskl' );
				$this->method_description = '<a href="' . esc_attr( $tab_href ) . '">' . __( '다보리 &gt; 지불기능', 'wskl' ) . '</a> ' . __( '메뉴에서 설정하세요', 'wskl' );

				//				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
				//					$this,
				//					'process_admin_options',
				//				) );

				/**
				 * 주문 확정 버튼 클릭 다음의 페이지인 "주문 지불" 화면에서 별도의 안내를 위한 액션
				 *
				 * @see    woocommerce/includes/shortcodes/class-wc-shortcode-checkout.php
				 * @see    WC_Shortcode_Checkout::order_pay()
				 */
				add_action( 'after_woocommerce_pay', array( $this, 'callback_after_woocommerce_pay' ) );

				/**
				 * 체크아웃 페이지의 자바스크립트 로드
				 */
				add_action( 'wp_enqueue_scripts', array( $this, 'callback_wp_enqueue_scripts' ) );

				/**
				 * 우커머스 wc-api 콜백. 페이앱이 주는 피드백에 대응.
				 *
				 * @see woocommerce/includes/class-wc-api.php
				 * @see WC_API::handle_api_requests()
				 */
				add_action( 'woocommerce_api_wskl-payapp-feedback', array( $this, 'callback_payapp_feedback' ) );
			}

			public function set_checkout_method( $method ) {

				$checkout_methods  = WSKL_Pay_Gates::get_checkout_methods();
				$method_title      = $checkout_methods[ $method ];
				$this->title       = $this->get_option( 'title' ) . ' ' . $method_title;
				$this->description = $method_title . WSKL_Pay_Gates::get_checkout_method_postfix();

				$this->enabled = true;
			}

			public function init_settings() {

				parent::init_settings();

				$options_to_import = array(
					'payapp_user_id',
					'payapp_link_key',
					'payapp_link_val',
					'checkout_methods',
				);

				foreach ( $options_to_import as $key ) {
					$this->settings[ $key ] = get_option( wskl_get_option_name( $key ) );
				}

				if ( get_option( wskl_get_option_name( 'enable_sym_pg' ) ) && get_option( wskl_get_option_name( 'pg_agency' ) ) == $this->id ) {

					$this->settings['enabled'] = 'yes';
				} else {
					$this->settings['enabled'] = 'no';
				}
			}

			/**
			 * 체크아웃 페이지의 자바스크립트 로드
			 *
			 * @action wp_enqueue_scripts
			 */
			public function callback_wp_enqueue_scripts() {

				wp_register_script( 'wskl-payapp-checkout-js', plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/payapp-checkout.js', array( 'jquery' ) );

				wp_localize_script( 'wskl-payapp-checkout-js', 'payapp_checkout', array(
					'loadingPopupUrl' => plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/payapp-loading.php',
				) );

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

				wp_register_script( 'wskl-payapp-status-js', plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/payapp-status.js', array( 'jquery' ), null, true );

				wp_localize_script( 'wskl-payapp-status-js', 'payAppStatus', array(
					'ajaxUrl'         => add_query_arg( array(
						'order_key' => $order_key,
						'wc-ajax'   => 'wskl-payapp-status',
					), home_url( '/' ) ),
					'pollingRetryMax' => 60,
					'failureRedirect' => wc()->cart->get_checkout_url(),
				) );

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
					//					'title'       => array(
					//						'title'       => __( 'Title', 'woocommerce' ),
					//						'type'        => 'text',
					//						'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					//						'default'     => __( '페이앱', 'wskl' ),
					//						'desc_tip'    => true,
					//					),
					//					'description' => array(
					//						'title'       => __( 'Description', 'woocommerce' ),
					//						'type'        => 'textarea',
					//						'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
					//						'default'     => __( '', 'wskl' ),
					//						'desc_tip'    => true,
					//					),
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

				// from wskl's pay slugs to payapp's pay type
				$idx              = array_search( $this->checkout_method, array_keys( WSKL_Pay_Gates::get_checkout_methods() ) );
				$payapp_pay_types = array( 'card', 'rbank', 'vbank', 'phone' );
				$pay_type         = $payapp_pay_types[ $idx ];

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
						'feedbackurl' => str_replace( 'https:', // search
							'http:',  // replace
							add_query_arg( 'wc-api', 'wskl-payapp-feedback', home_url( '/' ) ) ),

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
						'openpaytype' => $pay_type,

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

				$payapp_uid = wskl_POST( 'userid' );
				$link_key   = wskl_POST( 'linkkey' );
				$link_val   = wskl_POST( 'linkval' );
				$order_id   = wskl_POST( 'var1', 'absint' );
				$order_key  = wskl_POST( 'var2', 'sanitize_text_field' );
				$cst_url    = wskl_POST( 'csturl', 'sanitize_text_field' );     // 전표 주소
				$pay_memo   = wskl_POST( 'pay_memo', 'sanitize_text_field' );   // 구매자가 기록한 메모
				$mul_no     = wskl_POST( 'mul_no', 'sanitize_text_field' );     // 결제요청번호
				$pay_state  = wskl_POST( 'pay_state', 'absint' );               // 결제요청상태 (1: 요청, 4: 결제완료, 8, 16, 32: 요청취소, 9, 64: 승인취소)
				$pay_type   = wskl_POST( 'pay_type', 'absint' );                // 결제수단 (1: 신용카드, 2: 휴대전화)
				$pay_date   = wskl_POST( 'pay_date', 'sanitize_text_field' );

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
							$card_name  = wskl_POST( 'card_name', 'sanitize_text_field' );  // 신용카드시 카드 이름
							$order_note = sprintf( __( '결제가 성공적으로 처리됨.<ul><li>결제방법: 신용카드</li><li>카드 이름: %s</li><li>페이앱 결제요청번호: %s</li><li>승인시각: %s</li><li>구매자의 결제창 메시지: %s</li></ul>', 'wskl' ), $card_name, $mul_no, $pay_date, $pay_memo );
							break;

						case 2:
							$order_note = sprintf( __( '결제가 성공적으로 처리됨.<ul><li>결제방법: 휴대전화</li><li>페이앱 결제요청번호: %s</li><li>승인시각: %s</li><li>구매자의 결제창 메시지: %s</li></ul>', 'wskl' ), $mul_no, $pay_date, $pay_memo );
							break;

						default:
							$order_note = sprintf( __( '결제가 성공적으로 처리됨.<ul><li>결제방법: 기타</li><li>페이앱 결제요청번호: %s</li><li>승인시각: %s</li><li>구매자의 결제창 메시지: %s</li></ul>', 'wskl' ), $mul_no, $pay_date, $pay_memo );
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

			private function variate( $slug ) {

				$methods         = WSKL_Pay_Gates::get_checkout_methods();
				$checkout_method = $methods[ $slug ];

				$this->id              = 'payapp_' . $slug;
				$this->checkout_method = $slug;      // this is important!
				$this->title           = $this->method_title . " - {$checkout_method}";
				$this->description     = $checkout_method . WSKL_Pay_Gates::get_checkout_method_postfix() . '<br>1. 페이앱 결제는 1,2번째 칸은 ActiveX 인증 방식이 아니므로 결제창에서 신용카드 번호만 입력하면 신속하게 결제됩니다.<br>
	  2. 페이앱 결제창의 3번째 칸에서는 기존의 ActiveX 방식이 지원되므로 기존의 방식으로 결제가 가능합니다.';

				$this->enabled = $this->settings['enabled'];
			}

			public static function get_gateway_methods() {

				$checkout_methods  = get_option( wskl_get_option_name( 'checkout_methods' ) );
				$available_methods = array();

				if ( is_array( $checkout_methods ) && ! empty( $checkout_methods ) ) {

					$instance = new static();

					foreach ( $checkout_methods as $method ) {

						$cloned = clone $instance;
						$cloned->variate( $method );
						$available_methods[] = $cloned;
					}
				}

				return $available_methods;
			}
		}
	}
}
