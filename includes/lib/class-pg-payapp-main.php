<?php
/**
 * 페이앱 (http://payapp.kr)
 *
 * 1. 페이앱 REST API 를 이용해 결제 요청
 * 2. API 응답으로 payurl 주소가 리턴.
 * 3. 고객을 payurl 주소로 이동시켜 결제 유도.
 * 4. 결제가 이루어지면 페이앱은 1번 결제 요청 시 첨부한 feedbackurl 값으로 POST 전송을 보냄. (페이앱 서버에서
 * 접속하며, 고객의 브라우저와 무관)
 * 5. 페이앱의 피드백을 보고 주문에 대한 결제가 올바르게 승인되었는지 확인한 후, 나머지 결제 처리를 진행.
 */


if ( ! class_exists( 'PayApp_Main' ) ) :
	/**
	 * Class PayApp_Main
	 */
	class PayApp_Main {

		public static function init() {

			/**
			 * 게이트웨이 삽입
			 */
			add_filter( 'woocommerce_payment_gateways', array(
				__CLASS__,
				'callback_woocommerce_payment_gateways',
			) );

			/**
			 * URL 파라미터 "wc-ajax=wskl-payapp-status" 에 대응.
			 *
			 * @see woocommerce\includes\class-wc-ajax.php
			 * @see WC_Ajax::do_wc_ajax()
			 */
			add_action( 'wc_ajax_' . 'wskl-payapp-status',
			            array( __CLASS__, 'callback_payapp_status' ) );

			/**
			 * @uses init_wc_gateway_payapp 파일 class-pg-payapp-common.php 에 정의.
			 */
			add_action( 'plugins_loaded', 'init_wc_gateway_payapp' );

			/**
			 * 주문 확정 버튼 클릭 다음의 페이지인 "주문 지불" 화면에서 별도의 안내를 위한 액션
			 *
			 * @see    woocommerce/includes/shortcodes/class-wc-shortcode-checkout.php
			 * @see    WC_Shortcode_Checkout::order_pay()
			 */
			add_action( 'after_woocommerce_pay',
			            array( __CLASS__, 'callback_after_woocommerce_pay' ) );

			/**
			 * 체크아웃 페이지의 자바스크립트 로드
			 */
			add_action( 'wp_enqueue_scripts',
			            array( __CLASS__, 'callback_wp_enqueue_scripts' ) );

			/**
			 * 우커머스 wc-api 콜백. 페이앱이 주는 피드백에 대응.
			 *
			 * @see woocommerce/includes/class-wc-api.php
			 * @see WC_API::handle_api_requests()
			 */
			add_action( 'woocommerce_api_wskl-payapp-feedback',
			            array( __CLASS__, 'callback_payapp_feedback' ) );
		}

		/**
		 * 체크아웃 페이지의 자바스크립트 로드
		 *
		 * @action wp_enqueue_scripts
		 */
		public static function callback_wp_enqueue_scripts() {

			wp_register_script( 'wskl-payapp-checkout-js',
			                    plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/payapp-checkout.js',
			                    array( 'jquery' ), WSKL_VERSION );
			wp_localize_script( 'wskl-payapp-checkout-js', 'payapp_checkout',
			                    array( 'loadingPopupUrl' => plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/payapp-loading.php', ) );
			wp_enqueue_script( 'wskl-payapp-checkout-js' );
		}

		/**
		 * 주문 지불 페이지에서 사용자를 위한 설명 유도
		 *
		 * @action after_woocommerce_pay
		 */
		public static function callback_after_woocommerce_pay() {

			if ( ! isset( $_GET['key'] ) ) {
				$order_key = wc_get_order()->order_key;
			} else {
				$order_key = esc_attr( $_GET['key'] );
			}

			wp_register_script( 'wskl-payapp-status-js',
			                    plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/payapp-status.js',
			                    array( 'jquery' ), WSKL_VERSION, TRUE );

			wp_localize_script( 'wskl-payapp-status-js', 'payAppStatus', array(
				'ajaxUrl'         => add_query_arg( array(
					                                    'order_key' => $order_key,
					                                    'wc-ajax'   => 'wskl-payapp-status',
				                                    ), home_url( '/' ) ),
				'pollingRetryMax' => 60,
				'failureRedirect' => wc()->cart->get_checkout_url() // wc_get_checkout_url() // wc_get_checkout_url() should be > 2.5.0,
			) );

			wp_enqueue_script( 'wskl-payapp-status-js' );

			// 결제 유도 안내 메시지.
			//		$status_check_message = $this->get_option( 'status_check_message' );
			//		if ( $status_check_message ) {
			//			echo wpautop( wptexturize( $status_check_message ) );
			//		}

			$screenshot_url = plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/image/payapp/payapp-screenshot.png';

			echo '<p>' . __( '결제창 팝업은 아래 그림처럼 생성됩니다.', 'wskl' ) . '</p>';
			echo '<img src="' . esc_url( $screenshot_url ) . '" />';
		}

		/**
		 * @filter woocommerce_payment_gateways
		 *
		 * @param array $methods
		 *
		 * @return array
		 */
		public static function callback_woocommerce_payment_gateways( array $methods ) {

			$payapp_methods = WC_Gateway_Payapp_Base::get_gateway_methods();

			return array_merge( $methods, $payapp_methods );
		}

		/**
		 * 페이앱이 피드백 URL 에 대응하는 콜백.
		 * 피드백 파라미터를 확인하고 올바른 경우에는 최종적으로 주문 내역을 결제된 것으로 업데이트한다.
		 *
		 * @action woocommerce_api_{wskl-payapp-feedback}
		 */
		public static function callback_payapp_feedback() {

			error_log( '페이앱 피드백 URL 호출됨! 일시 (UTC time): ' . date( 'Y-m-d H:i:s') );

			$payapp_uid = wskl_POST( 'userid' );
			$link_key   = wskl_POST( 'linkkey' );
			$link_val   = wskl_POST( 'linkval' );
			$order_id   = wskl_POST( 'var1', 'absint' );
			$order_key  = wskl_POST( 'var2', 'sanitize_text_field' );
			$cst_url    = wskl_POST( 'csturl',
			                         'sanitize_text_field' );     // 전표 주소
			$pay_memo   = wskl_POST( 'pay_memo',
			                         'sanitize_text_field' );   // 구매자가 기록한 메모
			$mul_no     = wskl_POST( 'mul_no',
			                         'sanitize_text_field' );     // 결제요청번호
			$pay_state  = wskl_POST( 'pay_state',
			                         'absint' );               // 결제요청상태 (1: 요청, 4: 결제완료, 8, 16, 32: 요청취소, 9, 64: 승인취소)
			$pay_type   = wskl_POST( 'pay_type',
			                         'absint' );                // 결제수단 (1: 신용카드, 2: 휴대전화)
			$pay_date   = wskl_POST( 'pay_date', 'sanitize_text_field' );

			// check payapp_uid
			if ( $payapp_uid != wskl_get_option( 'payapp_user_id' ) ) {
				error_log( __( '페이앱 USER ID 에러', 'wskl' ) );

				return;
			}

			// check link key and link val
			if ( $link_key != wskl_get_option( 'payapp_link_key' ) || $link_val != wskl_get_option( 'payapp_link_val' ) ) {
				error_log( __( '페이앱 연동 KEY, 혹은 연동 VALUE가 올바르지 않음', 'wskl' ) );

				return;
			}

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				error_log( __( '잘못된 주문 ID', 'wskl' ) . ": {$order_id}" );

				return;
			}

			// check order key
			if ( $order_key != $order->order_key ) {
				error_log( __( '잘못된 주문 KEY', 'wskl' ) . ": {$order_key}" );

				return;
			}

			// 승인
			if ( $pay_state == 4 ) {

				// 전표 기록
				update_post_meta( $order_id, 'wskl_payapp_cst_url', $cst_url );

				switch ( $pay_type ) {
					case 1:
						$card_name  = wskl_POST( 'card_name',
						                         'sanitize_text_field' );  // 신용카드시 카드 이름
						$order_note = sprintf( __( '결제가 성공적으로 처리됨.<ul><li>결제방법: 신용카드</li><li>카드 이름: %s</li><li>페이앱 결제요청번호: %s</li><li>승인시각: %s</li><li>구매자의 결제창 메시지: %s</li></ul>',
						                           'wskl' ), $card_name,
						                       $mul_no, $pay_date, $pay_memo );
						break;

					case 2:
						$order_note = sprintf( __( '결제가 성공적으로 처리됨.<ul><li>결제방법: 휴대전화</li><li>페이앱 결제요청번호: %s</li><li>승인시각: %s</li><li>구매자의 결제창 메시지: %s</li></ul>',
						                           'wskl' ), $mul_no, $pay_date,
						                       $pay_memo );
						break;

					default:
						$order_note = sprintf( __( '결제가 성공적으로 처리됨.<ul><li>결제방법: 기타</li><li>페이앱 결제요청번호: %s</li><li>승인시각: %s</li><li>구매자의 결제창 메시지: %s</li></ul>',
						                           'wskl' ), $mul_no, $pay_date,
						                       $pay_memo );
				}

				$order->add_order_note( $order_note );
				$order->payment_complete();
				$order->reduce_order_stock();
				wc_empty_cart();
			}
		}

		/**
		 * AJAX 콜백. order key 에 대응해서 해당 order 의 주문 상태를 반환한다.
		 *
		 * 요구하는 파라미터: $_GET['order_key']
		 * JSON 응답:
		 *  success:      bool
		 *  message:      string  success or 에러 메시지
		 *  redirect:     string  리다이렉트 주소
		 *  order_id:     int     success=true 이면 order id
		 *  order_status: string  success=true 이면 order status 문자열.
		 *  (pending|processing|completed)
		 *
		 * @action wc_ajax_{wskl-payapp-status}
		 */
		public static function callback_payapp_status() {

			if ( ! defined( 'DOING_AJAX' ) || ! defined( 'WC_DOING_AJAX' ) ) {
				die( - 1 );
			}

			$order_key = isset( $_GET['order_key'] ) ? sanitize_text_field( $_GET['order_key'] ) : 0;
			$order     = wc_get_order( wc_get_order_id_by_order_key( $order_key ) );

			if ( ! $order ) {
				wc_add_notice( __( '주문 과정에 문제가 발생했습니다. 다시 시도해 주세요.', 'wskl' ),
				               'error' );
				wp_send_json( array(
					              'success'  => FALSE,
					              'message'  => 'An invalid order key received.',
					              'redirect' => wc_get_checkout_url(),
				              ) );
				die();
			}

			if ( $order->has_status( array( 'processing', 'completed' ) ) ) {
				$redirect = $order->get_checkout_order_received_url();
			} else {
				$redirect = '';
			}

			wp_send_json( array(
				              'success'      => TRUE,
				              'message'      => 'success',
				              'order_id'     => $order->id,
				              'order_status' => $order->get_status(),
				              'redirect'     => $redirect,
			              ) );

			die();
		}
	}

endif;

PayApp_Main::init();