<?php

function init_wc_gateway_wskl_iamport() {

  if ( class_exists( 'WC_Payment_Gateway' ) && ! class_exists( 'WC_Gateway_WSKL_Iamport' ) ) {

    class WC_Gateway_WSKL_Iamport extends WC_Payment_Gateway {

      public $checkout_method = '';

      public function __construct() {

        $this->id         = 'wskl_iamport';
        $this->icon       = null;
        $this->has_fields = false;

        $this->init_form_fields();
        $this->init_settings();

        $this->method_title = __( '다보리 아임포트', 'wskl' );

        $link                     = wskl_html_anchor( __( '다보리 &gt; 지불기능', 'wskl' ), array( 'href' => wskl_setting_tab_url( 'checkout-payment-gate' ) ), true );
        $this->method_description = $link . ' ' . __( '메뉴에서 설정하세요', 'wskl' );

        //        add_action(
        //            'woocommerce_update_options_payment_gateways_' . $this->id,
        //            array( $this, 'process_admin_options', )
        //        );

        add_action( 'woocommerce_api_' . strtolower( __CLASS__ ), array( $this, 'check_payment_response' ) );

        /**
         * 체크아웃 페이지의 자바스크립트 로드 - 아임포트 플러그인의 스크립트를 검사하므로 약간 순위를 낮춤.
         */
        add_action( 'wp_enqueue_scripts', array( $this, 'callback_wp_enqueue_scripts' ), 20 );
      }

      public function callback_wp_enqueue_scripts() {

        // 스크립트 핸들 'iamport_script' (아임포트 우커머스 플러그인이 쓰는 핸들)
        if ( ! wp_script_is( 'iamport_script' ) ) {
          wp_enqueue_script( 'wskl_iamport-payment-js', plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/iamport.payment-1.1.0.js' );
        }
        wp_enqueue_script( 'wskl_iamport-checkout-js', plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/iamport-checkout.js' );
      }

      public function init_form_fields() {

        //        $description_text = __( '<a href="https://admin.iamport.kr" target="_blank">아임포트 홈페이지</a>에서 회원가입 후, <a href="https://admin.iamport.kr/settings" target="_blank">"시스템설정 > 내정보"</a>에서 확인하실 수 있습니다.', 'wskl' );

        /**
         * @see woocommerce/includes/admin/settings/class-wc-settings-checkout.php
         * @see woocommerce/includes/abstracts/abstract-wc-settings-api.php
         * @see WC_Settings_Payment_Gateways::output()
         * @see WC_Settings_API::admin_options()
         * @see WC_Settings_API::generate_settings_html()
         */
        $this->form_fields = array(

          //            'enabled'     => array(
          //                'title'   => __( 'Enable/Disable', 'woocommerce' ),
          //                'type'    => 'checkbox',
          //                'label'   => __( '아임포트 결제 모듈 활성화', 'wskl' ),
          //                'default' => 'yes',
          //            ),
          //            'title'       => array(
          //                'title'       => __( 'Title', 'woocommerce' ),
          //                'type'        => 'text',
          //                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
          //                'default'     => __( '%METHOD% 결제 (다보리 아임포트)', 'wskl' ),
          //                'desc_tip'    => true,
          //            ),
          //            'description' => array(
          //                'title'       => __( 'Description', 'woocommerce' ),
          //                'type'        => 'textarea',
          //                'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
          //                'default'     => __( '%METHOD% 결제 (다보리 아임포트)', 'wskl' ),
          //                'desc_tip'    => true,
          //            ),
          //            'user_code'   => array(
          //                'title'       => __( '가맹점 식별코드', 'wskl' ),
          //                'type'        => 'text',
          //                'description' => $description_text,
          //                'label'       => __( '가맹점 식별코드', 'wskl' ),
          //                'default'     => '',
          //            ),
          //            'rest_key'    => array(
          //                'title'       => __( 'REST API 키', 'wskl' ),
          //                'type'        => 'text',
          //                'description' => $description_text,
          //                'label'       => __( 'REST API 키', 'wskl' ),
          //                'default'     => '',
          //            ),
          //            'rest_secret' => array(
          //                'title'       => __( 'REST API Secret', 'wskl' ),
          //                'type'        => 'text',
          //                'description' => $description_text,
          //                'label'       => __( 'REST API Secret', 'wskl' ),
          //                'default'     => '',
          //            ),
          //            'pay_gates'   => array(
          //                'title'       => __( '결제방식 선택', 'wskl' ),
          //                'type'        => 'multicheckbox',
          //                'description' => __( '원하는 결제 방식을 선택해 주세요.', 'wskl' ),
          //                'options'     => array(
          //                    'card'  => __( '신용카드', 'wskl' ),
          //                    'trans' => __( '실시간 계좌이체', 'wskl' ),
          //                    'vbank' => __( '가상계좌', 'wskl' ),
          //                    'phone' => __( '휴대폰 소액결제', 'wskl' ),
          //                    'kakao' => __( '카카오페이', 'wskl' ),
          //                ),
          //            ),
        );
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
          $this->settings[ $key ] = get_option( wskl_get_option_name( $key ) );
        }

        $enable_sym_pg = wskl_is_option_enabled( 'enable_sym_pg' );
        $pg_agency     = WSKL_PREFIX . get_option( wskl_get_option_name( 'pg_agency' ) );

        if ( $enable_sym_pg && $pg_agency == $this->id ) {
          $this->settings['enabled'] = 'yes';
        } else {
          $this->settings['enabled'] = 'no';
        }
      }

      public function process_payment( $order_id ) {

        $order = wc_get_order( $order_id );

        if ( $order->has_status( array( 'processing', 'completed' ) ) ) {
          $redirect_url = $order->get_checkout_order_received_url();
        } else {
          $redirect_url = $order->get_checkout_payment_url( true );
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

      public function check_payment_response() {

        if ( ! empty( $_REQUEST['imp_uid'] ) ) {

          //결제승인 결과조회
          require_once( WSKL_PATH . '/includes/lib/iamport/iamport.php' );

          $imp_uid     = $_REQUEST['imp_uid'];
          $rest_key    = $this->get_option( 'iamport_rest_key' );
          $rest_secret = $this->get_option( 'iamport_rest_secret' );

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

            update_post_meta( $order_id, '_iamport_provider', $payment_data->pg_provider );
            update_post_meta( $order_id, '_iamport_paymethod', $payment_data->pay_method );
            update_post_meta( $order_id, '_iamport_receipt_url', $payment_data->receipt_url );

            if ( $payment_data->status == 'paid' ) {

              if ( $order->order_total == $payment_data->amount ) {
                if ( ! $order->has_status( array( 'processing', 'completed' ) ) ) {
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
                update_post_meta( $order_id, '_iamport_vbank_name', $vbank_name );
                update_post_meta( $order_id, '_iamport_vbank_num', $vbank_num );
                update_post_meta( $order_id, '_iamport_vbank_date', $vbank_date );

                //가상계좌 입금대기 중
                $order->update_status( 'awaiting-vbank', __( '가상계좌 입금대기 중', 'iamport' ) );
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

              wc_add_notice( $payment_data->error['message'], 'error' );
              $redirect_url = $order->get_checkout_payment_url( true );

              wp_redirect( $redirect_url );
            }
          }
        }
      }

      public function generate_multicheckbox_html( $key, $data ) {

        $field    = $this->get_field_key( $key );
        $defaults = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
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
                    <?php disabled( $data['disabled'], true ); ?>
                    <?php checked( $value[ $option_key ], 'yes' ); ?>
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
          $fmt        = _n( '외 %d개 상품', '외 %d개 상품들', $item_count - 1, 'wskl' );
          $order_name = $first_item['name'] . sprintf( $fmt, $item_count - 1 );
        }

        return $order_name;
      }

      private function iamport_payment_info( $order_id ) {

        $order = wc_get_order( $order_id );

        $order_name = $this->get_order_name( $order );

        $redirect_url = add_query_arg( array(
            'order_id' => $order_id,
            'wc-api'   => strtolower( __CLASS__ ),
        ), $order->get_checkout_payment_url() );

        // from wskl's pay slugs to payapp's pay type
        if ( $this->checkout_method == 'kakao_pay' ) {

          $pay_method = 'card';
          $pg         = 'kakao';

        } else {

          $idx              = array_search( $this->checkout_method, array_keys( WSKL_Payment_Gates::get_checkout_methods( 'iamport' ) ) );
          $payapp_pay_types = array( 'card', 'trans', 'vbank', 'phone' );
          $pay_method       = $payapp_pay_types[ $idx ];
        }

        $response = array(
            'user_code'      => $this->get_option( 'iamport_user_code' ),
            'name'           => $order_name,
            'merchant_uid'   => $order->order_key,
            'amount'         => $order->order_total, //amount
            'buyer_name'     => $order->billing_first_name . $order->billing_last_name, //name
            'buyer_email'    => $order->billing_email, //email
            'buyer_tel'      => $order->billing_phone, //tel
            'buyer_addr'     => strip_tags( $order->get_formatted_shipping_address() ), //address
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

      private function variate( $slug ) {

        $guided_methods  = WSKL_Payment_Gates::get_checkout_methods( 'iamport' );
        $checkout_method = $guided_methods[ $slug ];

        $this->id              = 'wskl_iamport_' . $slug;
        $this->checkout_method = $slug;
        $this->title           = $this->method_title . " - {$checkout_method}";
        $this->description     = $checkout_method . WSKL_Payment_Gates::get_checkout_method_postfix();

        $this->enabled = $this->settings['enabled'];
      }

      public static function get_gateway_methods() {

        $checkout_methods  = get_option( wskl_get_option_name( 'checkout_methods' ) );
        $available_methods = array();

        if ( is_array( $checkout_methods ) && ! empty( $checkout_methods ) ) {

          $instance = new static();

          foreach ( $checkout_methods as $slug ) {

            // clone. constructor will not be called again.
            $method = clone $instance;
            $method->variate( $slug );
            $available_methods[] = $method;
          }

          unset( $instance );
        }

        return $available_methods;
      }
    }    // class WC_Gateway_WSKL_Iamport ...

  }      // if ( class_exists( 'WC_Payment_Gateway' ...
}        // function init_wc_gateway_wskl_iamport()