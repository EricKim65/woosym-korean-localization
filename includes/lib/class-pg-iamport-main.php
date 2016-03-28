<?php
/**
 * 아임포트 결제 모듈 (http://www.iamport.kr/)
 *
 * 만약의 경우라도 아임포트 자체에서 제공하는 플러그인
 * (https://ko.wordpress.org/plugins/iamport-for-woocommerce/) 과의 이름 충돌을 피하기 위해
 * 'iamport' 라는 슬러그에 'wskl_' 접두사를 붙이도록 한다.
 */

if ( ! class_exists( 'WSKL_Iamport_Main' ) ) :

	class WSKL_Iamport_Main {

		public static function init() {

			add_action( 'init', array(
				__CLASS__,
				'callback_register_vbank_order_status',
			) );

			/**
			 * @uses init_wc_gateway_iamport 파일 class-pg-iamport-common.php 에 정의.
			 */
			add_action( 'init', 'init_wc_gateway_wskl_iamport' );

			/**
			 * 아임포트 게이트웨이 삽입
			 */
			add_filter( 'woocommerce_payment_gateways', array(
				__CLASS__,
				'callback_woocommerce_payment_gateways',
			) );

			/**
			 * 체크아웃 페이지의 자바스크립트 로드 - 아임포트 플러그인의 스크립트를 검사하므로 약간 순위를 낮춤.
			 */
			add_action( 'wp_enqueue_scripts',
			            array( __CLASS__, 'callback_wp_enqueue_scripts' ), 20 );

			/**
			 * @see woocommerce/templates/order/order-details.php
			 */
			add_action( 'woocommerce_order_details_after_order_table',
			            array( __CLASS__, 'iamport_order_detail' ) );

			add_filter( 'wc_order_statuses',
			            array( __CLASS__, 'callback_wc_order_statuses' ) );

			add_action( 'woocommerce_api_wskl_iamport' , array( __CLASS__, 'check_payment_response' ) );
		}

		/**
		 * @filter woocommerce_payment_gateways
		 *
		 * @param array $methods
		 *
		 * @return array list of available payment gateways
		 */
		public static function callback_woocommerce_payment_gateways( array $methods ) {

			$wskl_iamport_methods = WC_Gateway_WSKL_Iamport::get_gateway_methods();

			return array_merge( $methods, $wskl_iamport_methods );
		}


		public static function callback_wp_enqueue_scripts() {

			// 스크립트 핸들 'iamport_script' (아임포트 우커머스 플러그인이 쓰는 핸들)
			if ( ! wp_script_is( 'iamport_script' ) ) {
				wp_enqueue_script( 'wskl_iamport-payment-js',
				                   plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/iamport.payment-1.1.0.js' );
			}
			wp_enqueue_script( 'wskl_iamport-checkout-js',
			                   plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/iamport-checkout.js' );
		}

		/**
		 * @action woocommerce_order_details_after_order_table
		 *
		 * @param WC_Order $order
		 */
		public static function iamport_order_detail( \WC_Order $order ) {

			$checkout_methods = WSKL_Payment_Gates::get_checkout_methods( 'iamport' );

			$pay_method  = $order->iamport_paymethod;
			$receipt_url = $order->iamport_receipt_url;

			$vbank_name = $order->iamport_vbank_name;
			$vbank_num  = $order->iamport_vbank_num;
			$vbank_date = $order->iamport_vbank_date;

			$transction_id = $order->get_transaction_id();

			ob_start();

			switch ( $pay_method ) {
				case 'card':
					$pay_method_text = $checkout_methods['credit'];
					include_once( WSKL_PATH . '/includes/lib/iamport/template-simple.php' );
					break;

				case 'trans':
					$pay_method_text = $checkout_methods['remit'];
					include_once( WSKL_PATH . '/includes/lib/iamport/template-simple.php' );
					break;

				case 'phone':
					$pay_method_text = $checkout_methods['mobile'];
					include_once( WSKL_PATH . '/includes/lib/iamport/template-simple.php' );
					break;

				case 'vbank':
					$pay_method_text = $checkout_methods['virtual'];
					include_once( WSKL_PATH . '/includes/lib/iamport/template-vbank.php' );
					break;

				case 'kakao':
					$pay_method_text = $checkout_methods['kakao_pay'];
					include_once( WSKL_PATH . '/includes/lib/iamport/template-simple.php' );
					break;
			}

			ob_end_flush();
		}

		public static function callback_register_vbank_order_status() {

			register_post_status( 'wc-awaiting-vbank', array(
				'label'                     => '가상계좌 입금대기 중',
				'public'                    => TRUE,
				'exclude_from_search'       => FALSE,
				'show_in_admin_all_list'    => TRUE,
				'show_in_admin_status_list' => TRUE,
				'label_count'               => _n_noop( '가상계좌 입금대기 중 <span class="count">(%s)</span>',
				                                        '가상계좌 입금대기 중 <span class="count">(%s)</span>' ),
			) );
		}

		public static function callback_wc_order_statuses( $order_statuses ) {

			$new_order_statuses = array();

			// pending status다음에 추가
			foreach ( $order_statuses as $key => $status ) {

				$new_order_statuses[ $key ] = $status;

				if ( 'wc-pending' === $key ) {
					$new_order_statuses['wc-awaiting-vbank'] = '가상계좌 입금대기 중';
				}
			}

			return $new_order_statuses;
		}

		public static function check_payment_response() {

			if ( ! empty( $_REQUEST['imp_uid'] ) ) {

				//결제승인 결과조회
				require_once( WSKL_PATH . '/includes/lib/iamport/iamport.php' );

				$imp_uid     = $_REQUEST['imp_uid'];
				$rest_key    = wskl_get_option( 'iamport_rest_key' );
				$rest_secret = wskl_get_option( 'iamport_rest_secret' );

				$iamport = new Iamport( $rest_key, $rest_secret );
				$result  = $iamport->findByImpUID( $imp_uid );

				if ( $result->success ) {

					$payment_data = $result->data;

					if ( empty( $_REQUEST['order_id'] ) ) { //call by iamport notification
						$order_id = wc_get_order_id_by_order_key( $payment_data->merchant_uid );
					} else {
						$order_id = $_REQUEST['order_id'];
					}

					$order = wc_get_order( $order_id );

					update_post_meta( $order_id, '_iamport_provider',
					                  $payment_data->pg_provider );
					update_post_meta( $order_id, '_iamport_paymethod',
					                  $payment_data->pay_method );
					update_post_meta( $order_id, '_iamport_receipt_url',
					                  $payment_data->receipt_url );

					if ( $payment_data->status == 'paid' ) {

						if ( $order->order_total == $payment_data->amount ) {
							if ( ! $order->has_status( array(
								                           'processing',
								                           'completed',
							                           ) )
							) {
								$order->payment_complete( $payment_data->imp_uid ); //imp_uid
								wp_redirect( $order->get_checkout_order_received_url() );

								return;
							}
						} else {
							$order->add_order_note( '요청하신 결제금액이 다릅니다.' );
							wc_add_notice( '요청하신 결제금액이 다릅니다.', 'error' );
						}

					} else if ( $payment_data->status == 'ready' ) {

						if ( $payment_data->pay_method == 'vbank' ) {

							$vbank_name = $payment_data->vbank_name;
							$vbank_num  = $payment_data->vbank_num;
							$vbank_date = $payment_data->vbank_date;

							//가상계좌 입금할 계좌정보 기록
							update_post_meta( $order_id,
							                  '_iamport_vbank_name',
							                  $vbank_name );
							update_post_meta( $order_id,
							                  '_iamport_vbank_num',
							                  $vbank_num );
							update_post_meta( $order_id,
							                  '_iamport_vbank_date',
							                  $vbank_date );

							//가상계좌 입금대기 중
							$order->update_status( 'awaiting-vbank',
							                       __( '가상계좌 입금대기 중',
							                           'iamport' ) );
							wp_redirect( $order->get_checkout_order_received_url() );

							return;

						} else {

							$order->add_order_note( '실제 결제가 이루어지지 않았습니다.' );
							wc_add_notice( '실제 결제가 이루어지지 않았습니다.', 'error' );
						}

					} else if ( $payment_data->status == 'failed' ) {

						$order->add_order_note( '결제요청 승인에 실패하였습니다.' );
						wc_add_notice( '결제요청 승인에 실패하였습니다.', 'error' );
					}

				} else {

					$payment_data = &$result->data;

					if ( ! empty( $_REQUEST['order_id'] ) ) {

						$order = new WC_Order( $_REQUEST['order_id'] );

						$order->update_status( 'failed' );
						$order->add_order_note( '결제승인정보를 받아오지 못했습니다. 관리자에게 문의해주세요' . $payment_data->error['message'] );

						wc_add_notice( $payment_data->error['message'],
						               'error' );
						$redirect_url = $order->get_checkout_payment_url( TRUE );

						wp_redirect( $redirect_url );
					}
				}
			}
		}
	}

endif;


WSKL_Iamport_Main::init();
