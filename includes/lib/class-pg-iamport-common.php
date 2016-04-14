<?php

/**
 * @action  plugin_loaded
 * @used-by add_action
 * @see     WC_Payment_Gateway
 */
function init_wc_gateway_wskl_iamport() {

	if ( class_exists( 'WC_Payment_Gateway' ) && ! class_exists( 'WC_Gateway_WSKL_Iamport' ) ) {

		class WC_Gateway_WSKL_Iamport extends WC_Payment_Gateway {

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

			public function __construct() {

				$this->id         = 'wskl_iamport';  // 아임포트 플러그인과의 충돌을 방지하기 위해 접두사 추가.
				$this->icon       = NULL;
				$this->has_fields = FALSE;

				// 나머지 설정은 variate() 참고
			}

			public function init_settings() {

				parent::init_settings();

				$options_to_import = array(
					'iamport_user_code',
					'iamport_rest_key',
					'iamport_rest_secret',
					'checkout_methods',
				);

				foreach ( $options_to_import as $key ) {
					$this->settings[ $key ] = wskl_get_option( $key );
				}

				$this->settings['enabled'] = wskl_yes_or_no( wskl_is_option_enabled( 'enable_sym_pg' ) && wskl_get_option( 'pg_agency' ) == 'iamport' && in_array( $this->checkout_method,
				                                                                                                                                                   $this->settings['checkout_methods'] ) );
			}

			public function init_form_fields() {

				/**
				 * @see woocommerce/includes/admin/settings/class-wc-settings-checkout.php
				 * @see woocommerce/includes/abstracts/abstract-wc-settings-api.php
				 * @see WC_Settings_Payment_Gateways::output()
				 * @see WC_Settings_API::admin_options()
				 * @see WC_Settings_API::generate_settings_html()
				 */
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
			}

			public function process_payment( $order_id ) {

				$order = wc_get_order( $order_id );

				if ( $order->has_status( array(
					                         'processing',
					                         'completed',
				                         ) )
				) {
					$redirect_url = $order->get_checkout_order_received_url();
				} else {
					$redirect_url = $order->get_checkout_payment_url( TRUE );
				}

				$iamport_info = $this->iamport_payment_info( $order_id );

				return array(
					'result'    => 'success',
					'redirect'  => $redirect_url,
					'order_id'  => $order_id,
					'order_key' => $order->order_key,
					'iamport'   => $iamport_info,
				);
			}


			public function generate_multicheckbox_html( $key, $data ) {

				$field    = $this->get_field_key( $key );
				$defaults = array(
					'title'             => '',
					'disabled'          => FALSE,
					'class'             => '',
					'css'               => '',
					'placeholder'       => '',
					'type'              => 'text',
					'desc_tip'          => FALSE,
					'description'       => '',
					'custom_attributes' => array(),
					'options'           => array(),
				);

				$data  = wp_parse_args( $data, $defaults );
				$value = (array) $this->get_option( $key, array() );

				ob_start(); ?>

				<tr valign="top">
					<th scope="row" class="titledesc">
						<label for="<?php echo esc_attr( $field ); ?>">
							<?php echo wp_kses_post( $data['title'] ); ?>
						</label>
						<?php echo $this->get_tooltip_html( $data ); ?>
					</th>
					<td class="forminp">
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php echo wp_kses_post( $data['title'] ); ?></span>
							</legend>
							<?php foreach ( (array) $data['options'] as $option_key => $option_value ) : ?>
								<input
									type="checkbox" class="multicheckbox"
									name="<?php echo esc_attr( $field ) . '[' . esc_attr( $option_key ) . ']'; ?>"
									id="<?php echo esc_attr( $field ) . '_' . esc_attr( $option_key ); ?>"
									style="<?php echo esc_attr( $data['css'] ); ?>"
									<?php disabled( $data['disabled'],
									                TRUE ); ?>
									<?php checked( $value[ $option_key ],
									               'yes' ); ?>
									<?php echo $this->get_custom_attribute_html( $data ); ?>
									value="1"
								/>
								<label for="<?php echo esc_attr( $field ) . '_' . esc_attr( $option_key ); ?>">
									<?php echo wp_kses_post( $option_value ); ?>
								</label>
								<br/>
							<?php endforeach; ?>

							<?php echo $this->get_description_html( $data ); ?>
						</fieldset>
					</td>
				</tr>

				<?php return ob_get_clean();
			}

			public function validate_multicheckbox_field( $key ) {

				$field  = $this->get_field_key( $key );
				$status = array();

				if ( isset( $_POST[ $field ] ) ) {
					foreach ( (array) $_POST[ $field ] as $k => $v ) {
						if ( $v == 1 ) {
							$status[ stripslashes( $k ) ] = 'yes';
						}
					}
				}

				return $status;
			}

			protected function get_order_name( \WC_Order $order ) {

				$item_count = $order->get_item_count();

				if ( ! $item_count ) {
					throw new \LogicException( 'items are empty!' );
				}

				$items      = $order->get_items();
				$first_item = reset( $items );

				if ( $item_count == 1 ) {
					$order_name = $first_item['name'];
				} else {
					$fmt        = _n( '외 %d개 상품',
					                  '외 %d개 상품들',
					                  $item_count - 1,
					                  'wskl' );
					$order_name = $first_item['name'] . sprintf( $fmt,
					                                             $item_count - 1 );
				}

				return $order_name;
			}

			private function iamport_payment_info( $order_id ) {

				$order = wc_get_order( $order_id );

				$order_name = $this->get_order_name( $order );

				$redirect_url = add_query_arg( array(
					                               'order_id' => $order_id,
					                               'wc-api'   => 'wskl_iamport',
				                               ),
				                               $order->get_checkout_payment_url() );

				// from wskl's pay slugs to payapp's pay type
				if ( $this->checkout_method == 'kakao_pay' ) {

					$pay_method = 'card';
					$pg         = 'kakao';

				} else {

					$idx              = array_search( $this->checkout_method,
					                                  array_keys( WSKL_Payment_Gates::get_checkout_methods( 'iamport' ) ) );
					$payapp_pay_types = array(
						'card',
						'trans',
						'vbank',
						'phone',
					);
					$pay_method       = $payapp_pay_types[ $idx ];
				}

				$response = array(
					'user_code'      => $this->get_option( 'iamport_user_code' ),
					'name'           => $order_name,
					'merchant_uid'   => $order->order_key,
					'amount'         => $order->order_total,
					//amount
					'buyer_name'     => $order->billing_first_name . $order->billing_last_name,
					//name
					'buyer_email'    => $order->billing_email,
					//email
					'buyer_tel'      => $order->billing_phone,
					//tel
					'buyer_addr'     => strip_tags( $order->get_formatted_shipping_address() ),
					//address
					'buyer_postcode' => $order->shipping_postcode,
					'vbank_due'      => date( 'Ymd', strtotime( "+1 day" ) ),
					'm_redirect_url' => $redirect_url,
					'pay_method'     => $pay_method,
				);

				if ( isset( $pg ) ) {
					$response['pg'] = $pg;
				}

				return $response;
			}

			protected function variate( $slug ) {

				$guided_methods     = WSKL_Payment_Gates::get_checkout_methods( 'iamport' );
				$payment_gate_names = WSKL_Payment_Gates::get_pay_gates();

				// ID
				$this->id = 'wskl_iamport_' . $slug;

				// checkout method and description.
				$this->checkout_method             = $slug;
				$this->checkout_method_description = $guided_methods[ $slug ];

				// $this->checkout_method_description 변수를 사용하므로 뒤에 위치할 것.
				$this->init_form_fields();
				$this->init_settings();

				$this->title       = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );

				$this->enabled = $this->get_option( 'enabled' );

				// 다보리 설정 URL
				$tab_href = add_query_arg( array(
					'page' => WSKL_MENU_SLUG,
					'tab'  => 'checkout-payment-gates',
				                           ),
				                           admin_url( 'admin.php ' ) );

				// Method title: 우커머스 > 설정 > 결제 (checkout) 탭에서 확인
				$this->method_title = $payment_gate_names['iamport'] . " - {$guided_methods[ $slug ]}";

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

				$enabled_methods   = wskl_get_option( 'checkout_methods' );
				$available_methods = array_keys( WSKL_Payment_Gates::get_checkout_methods() );
				$result            = array();

				$checkout_methods = array_intersect( $enabled_methods,
				                                     $available_methods );

				foreach ( $checkout_methods as $key ) {

					$class_name = 'WC_Gateway_WSKL_Iamport_' . ucfirst( $key );

					if ( class_exists( $class_name ) ) {
						$result[] = $class_name;
					}
				}

				return $result;
			}
		}    // class WC_Gateway_WSKL_Iamport ...


		class WC_Gateway_WSKL_Iamport_Credit extends WC_Gateway_WSKL_Iamport {

			function __construct() {

				parent::__construct();

				$this->variate( 'credit' );
			}
		}


		class WC_Gateway_WSKL_Iamport_Remit extends WC_Gateway_WSKL_Iamport {

			function __construct() {

				parent::__construct();

				$this->variate( 'remit' );
			}
		}


		class WC_Gateway_WSKL_Iamport_Virtual extends WC_Gateway_WSKL_Iamport {

			function __construct() {

				parent::__construct();

				$this->variate( 'virtual' );
			}
		}


		class WC_Gateway_WSKL_Iamport_Mobile extends WC_Gateway_WSKL_Iamport {

			function __construct() {

				parent::__construct();

				$this->variate( 'mobile' );
			}
		}


		class WC_Gateway_WSKL_Iamport_Kakao_pay extends WC_Gateway_WSKL_Iamport {

			function __construct() {

				parent::__construct();

				$this->variate( 'kakao_pay' );
			}
		}
	}        // if ( class_exists( 'WC_Payment_Gateway' ...
}            // function init_wc_gateway_wskl_iamport()