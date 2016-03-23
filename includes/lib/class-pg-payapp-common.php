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

	if ( class_exists( 'WC_Payment_Gateway' ) && ! class_exists( 'WC_Gateway_PayApp_Base' ) ) {

		/**
		 * Class WC_Gateway_PayApp
		 */
		class WC_Gateway_PayApp_Base extends WC_Payment_Gateway {

			/**
			 * @var string 세부 결제방식. WSKL_Payment_Gates::get_checkout_methods() array 중 키를 기록
			 *
			 * @see WSKL_Payment_Gates::get_checkout_methods()
			 */
			public $checkout_method = '';

			/**
			 * @var string 결제 방법 설명. WSKL_Payment_Gates::get_checkout_methods() array 중 값을 기록
			 */
			public $checkout_method_description = '';

			/**
			 * WC_Gateway_PayApp_Base constructor.
			 *
			 * @see variate()
			 */
			public function __construct() {

				$this->id         = 'payapp';
				$this->icon       = NULL;
				$this->has_fields = FALSE;

				// 나머지 설정읜 variate() 참고
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

				$this->settings['enabled'] = wskl_yes_or_no( wskl_is_option_enabled( 'enable_sym_pg' ) && wskl_get_option( 'pg_agency' ) == 'payapp' && in_array( $this->checkout_method,
				                                                                                                                                                  $this->get_option( 'checkout_methods' ) ) );
			}


			/**
			 * 반드시 $this->checkout_method_description 대입 후에 사용!
			 *
			 * @used-by variate()
			 */
			public function init_form_fields() {

				$this->form_fields = array(
					'title'       => array(
						'title'       => __( 'Title', 'woocommerce' ),
						'type'        => 'text',
						'description' => __( 'This controls the title which the user sees during checkout.',
						                     'woocommerce' ),
						'default'     => $this->checkout_method_description,
						'desc_tip'    => TRUE,
					),
					'description' => array(
						'title'       => __( 'Description', 'woocommerce' ),
						'type'        => 'textarea',
						'description' => __( 'Payment method description that the customer will see on your checkout.',
						                     'woocommerce' ),
						'default'     => $this->checkout_method_description . WSKL_Payment_Gates::get_checkout_method_postfix(),
						'desc_tip'    => TRUE,
					),
				);

				if ( $this->checkout_method == 'credit' ) {
					$this->form_fields['description']['default'] = <<<EOD
신용카드로 결제합니다.<br>
1.페이앱 결제는 1,2번째 칸은 ActiveX 인증 방식이 아니므로 결제창에서 신용카드 번호만 입력하면 신속하게 결제됩니다.<br>
2. 페이앱 결제창의 3번째 칸에서는 기존의 ActiveX 방식(ISP)이 지원되므로 기존의 방식으로 결제가 가능합니다.<br>
EOD;
				}
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

				$url = $order->get_checkout_payment_url( TRUE );

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
					$fmt        = _n( '외 %d개 상품', '외 %d개 상품들', $item_count - 1,
					                  'wskl' );
					$goods_name = $first_item['name'] . sprintf( $fmt,
					                                             $item_count - 1 );
				}

				// from wskl's pay slugs to payapp's pay type
				$idx              = array_search( $this->checkout_method,
				                                  array_keys( WSKL_Payment_Gates::get_checkout_methods() ) );
				$payapp_pay_types = array( 'card', 'rbank', 'vbank', 'phone' );
				$pay_type         = $payapp_pay_types[ $idx ];

				$feedback_url = str_replace( 'https:', // search
				                             'http:',  // replace
				                             add_query_arg( 'wc-api',
				                                            'wskl-payapp-feedback',
				                                            home_url( '/' ) ) );

				error_log( 'Our feedback URL for PayApp: ' . $feedback_url );

				$args = array(
					'sslverify' => FALSE,
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
						'recvphone'   => preg_replace( '/[+\-\s]/', '',
						                               $order->billing_phone ),

						// 결제요청시 메모
						'memo'        => '',

						// 주소요청 여부
						'reqaddr'     => '0',

						// 피드백 URL, feedbackurl 은 외부에서 접근이 가능해야 합니다. payapp 서버에서 호출 하는 페이지 입니다.
						'feedbackurl' => $feedback_url,

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
			 * payapp API가 에러를 전달하는 경우 구체적인 에러 메시지를 보다 고객이 알기 편한 메시지로 변환.
			 * 이 메시지는 체크아웃 페이지의 알림 영역에 게시된다.
			 *
			 * @param string $message
			 *
			 * @return string
			 */
			private function parse_payapp_error_message( $message ) {

				// 잘못된 전화번호 값.
				if ( strpos( $message, 'recvphone' ) !== FALSE ) {
					return __( '전화번호 형식이 잘못되었습니다. 000-0000-0000 형태로 입력해 주세요.',
					           'wskl' );
				}

				return $message;
			}

			/**
			 * @param $slug
			 *
			 * @used-by WC_Gateway_PayApp_Credit
			 * @used-by WC_Gateway_PayApp_Remit
			 * @used-by WC_Gateway_PayApp_Virtual
			 * @used-by WC_Gateway_PayApp_Mobile
			 */
			protected function variate( $slug ) {

				$methods            = WSKL_Payment_Gates::get_checkout_methods();
				$payment_gate_names = WSKL_Payment_Gates::get_pay_gates();

				// ID
				$this->id = 'payapp_' . $slug;

				// checkout method and description. Defined in WC_Gateway_PayApp_Base
				$this->checkout_method             = $slug;      // this is important!
				$this->checkout_method_description = $methods[ $slug ];

				// $this->checkout_method_description 변수를 사용하므로 뒤에 위치할 것.
				$this->init_form_fields();
				$this->init_settings();

				// Title: 결제 화면에서 보임
				$this->title       = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );

				$this->enabled = $this->get_option( 'enabled' );

				// 다보리 설정 URL
				$tab_href = add_query_arg( array(
					                           'page' => WSKL_MENU_SLUG,
					                           'tab'  => 'checkout-payment-gate',
				                           ), admin_url( 'admin.php ' ) );

				// Method title: 우커머스 > 설정 > 결제 (checkout) 탭에서 확인
				$this->method_title = $payment_gate_names['payapp'] . " - {$methods[ $slug ]}";

				// Method description: 우커머스 > 설정 > 결제 (checkout) 탭에서 확인
				$this->method_description = __( '세부 설정은 ', 'wskl' );
				$this->method_description .= wskl_html_anchor( __( '다보리 &gt; 지불기능' ),
				                                               array( 'href' => $tab_href ),
				                                               TRUE );
				$this->method_description .= __( ' 메뉴에서 설정하세요', 'wskl' );

				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id,
				            array(
					            $this,
					            'process_admin_options',
				            ) );
			}

			public static function get_gateway_methods() {

				$enabled_methods = wskl_get_option( 'checkout_methods' );
				$available_methods = array_keys(
					WSKL_Payment_Gates::get_checkout_methods()
				);
				$result = array();

				$checkout_methods = array_intersect(
					$enabled_methods,
					$available_methods
				);

				foreach ( $checkout_methods as $key ) {

					$class_name = 'WC_Gateway_PayApp_' . ucfirst( $key );

					if ( class_exists( $class_name ) ) {
						$result[] = $class_name;
					}
				}

				return $result;
			}
		}


		class WC_Gateway_PayApp_Credit extends WC_Gateway_Payapp_Base {

			function __construct() {

				parent::__construct();

				$this->variate( 'credit' );
			}
		}


		class WC_Gateway_PayApp_Remit extends WC_Gateway_Payapp_Base {

			function __construct() {

				parent::__construct();

				$this->variate( 'remit' );
			}
		}


		class WC_Gateway_PayApp_Virtual extends WC_Gateway_Payapp_Base {

			function __construct() {

				parent::__construct();

				$this->variate( 'virtual' );
			}
		}


		class WC_Gateway_PayApp_Mobile extends WC_Gateway_Payapp_Base {

			function __construct() {

				parent::__construct();

				$this->variate( 'mobile' );
			}
		}
	}
}
