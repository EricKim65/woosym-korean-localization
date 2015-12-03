<?php

if ( ! class_exists( 'wskl\delivery_tracking\agents\Agent_Helper' ) ) {
  require_once( WSKL_PATH . '/includes/lib/delivery-tracking/agents.php' );
}

use wskl\delivery_tracking\agents\Agent_Helper;


define( 'SDURL', WP_PLUGIN_URL . "/" . dirname( plugin_basename( __FILE__ ) ) );

if ( ! class_exists( 'Sym_Shipping_Tracking' ) ) {

  load_plugin_textdomain( 'sym-shipping-tracking', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang' );


  class Sym_Shipping_Tracking {

    function __construct() {

      /** 관리자 주문 페이지에 메타 박스 추가 */
      add_action( 'add_meta_boxes', array( &$this, 'woocommerce_metaboxes' ) );

      /** 사용자가 보는 주문 페이지에 배송 상태 정보 보여 주기 */
      add_action( 'woocommerce_order_items_table', array( &$this, 'track_page_shipping_details' ) );

      add_action( 'woocommerce_process_shop_order_meta', array( &$this, 'woocommerce_process_shop_ordermeta' ), 10 );

      //add_action( 'woocommerce_email_before_order_table', array( &$this, 'email_shipping_details' ) );

      //add_action( 'admin_menu', array( &$this, 'ship_select_menu'));

      //add_action( 'admin_init', array( &$this, 'ship_register_settings'));

      add_action( 'manage_edit-shop_order_columns', array( &$this, 'add_shipping_column' ) );

      add_action( 'manage_shop_order_posts_custom_column', array( &$this, 'add_shipping_column_details' ) );

    }

    function add_shipping_column_details( $column ) {

      global $post, $woocommerce, $the_order;

      if ( empty( $the_order ) || $the_order->id != $post->ID ) {
        $the_order = new WC_Order( $post->ID );
      }

      switch ( $column ) {
        case "tracking_number" :

          $order_meta = get_post_custom( $the_order->id );

          for ( $i = 0; $i <= 4; $i ++ ) {
            if ( $i == 0 ) {
              if ( isset( $order_meta['_order_trackno'] ) && isset( $order_meta['_order_trackurl'] ) ) {
                $this->admin_shipping_details( $order_meta['_order_trackno'], $order_meta['_order_trackurl'], $order );
              }
            } else {
              if ( isset( $order_meta[ '_order_trackno' . $i ] ) && isset( $order_meta[ '_order_trackurl' . $i ] ) ) {
                $this->admin_shipping_details( $order_meta[ '_order_trackno' . $i ], $order_meta[ '_order_trackurl' . $i ], $order );
              }
            }
          }
          break;
      }

    }

    function add_shipping_column( $columns ) {

      $columns["tracking_number"] = __( 'Tracking Number', 'sym-shipping-tracking' );

      return $columns;

    }

    function shipping_details_options( $data, $options, $part ) {

      if ( $part == '0' ) {
        $part = '';
      }

      /*if ($part == '0' || $part == '' ) {
				$part = '';
			}*/

      //$shipping_companies = $this->get_shipping_list();
      foreach ( $options as $key => $value ) {
        echo '<option value="' . $value . '" ';
        if ( isset( $data[ '_order_trackurl' . $part ][0] ) && $data[ '_order_trackurl' . $part ][0] == $value ) {
          echo 'selected="selected"';
        }
        echo '>' . $value . '</option>';
      }

      /*            foreach( $shipping_companies as $k => $v ){
							if (isset($options[$k]) == '1') {
								echo '<option value="'.$k.'" ';
								if (isset($data['_order_trackurl'.$part][0]) && $data['_order_trackurl'.$part][0] == $k) {
									echo 'selected="selected"';
								}
								echo '>'.$v.'</option>';
							}

						}*/

    }

    /**
     * 메타 박스 렌더링 콜백
     *
     * @param $post
     */
    function woocommerce_order_shippingdetails( $post ) {

      $agent_list = Agent_Helper::get_agent_list();
      $options    = get_option( wskl_get_option_name( 'shipping_companies' ) );
      $agents     = array();

      $current_agent           = esc_html( get_post_meta( $post->ID, 'wskl-delivery-agent', TRUE ) );
      $current_tracking_number = esc_html( get_post_meta( $post->ID, 'wskl-tracking-number', TRUE ) );

      foreach ( $options as $o ) {
        $agents[ $o ] = $agent_list[ $o ];
      }
      ?>
      <ul class="totals">
        <li>
          <label for="wskl-delivery-agent"><?php _e( '배송업체:', 'sym-shipping-tracking' ); ?></label>
          <select id="wskl-delivery-agent" name="wskl-delivery-agent">
            <option value="not-available">지정 안됨</option>
            <?php foreach ( $agents as $k => $v ) { ?>
              <option value="<?php echo $k; ?>" <?php echo ( $current_agent == $k ) ? 'selected' : ''; ?>><?php echo $v; ?></option>
            <?php } ?>
          </select>
        </li>
        <li>
          <label for="wskl-tracking-number"><?php _e( '송장번호:', 'sym-shipping-tracking' ); ?></label>
          <input type="text" id="wskl-tracking-number" name="wskl-tracking-number" placeholder="운송장 번호 입력" value="<?php echo $current_tracking_number; ?>">
        </li>
      </ul>
      <?php
    }

    /**
     * Save Order 버튼을 누르면 별도로 우리가 지정한 데이터를 처리하기 위한 콜백을 준다.
     * 여기서 배송업체와 송장번호를 메타 정보에 기록한다.
     *
     * @param $post_id
     */
    function woocommerce_process_shop_ordermeta( $post_id ) {

      $delivery_agent  = sanitize_text_field( $_POST['wskl-delivery-agent'] );
      $tracking_number = sanitize_text_field( $_POST['wskl-tracking-number'] );

      update_post_meta( $post_id, 'wskl-delivery-agent', $delivery_agent );
      update_post_meta( $post_id, 'wskl-tracking-number', $tracking_number );
    }

    function woocommerce_metaboxes() {

      add_meta_box( 'woocommerce-order-ship', __( '배송 정보', 'sym-shipping-tracking' ), array(
          &$this,
          'woocommerce_order_shippingdetails',
      ), 'shop_order', 'side', 'high' );

    }

    function ship_register_settings() {

      register_setting( 'woo_ship_group', 'woo_ship_options' );
      wp_enqueue_script( 'shippingdetails-js', SDURL . '/js/shippingdetails.js', array( 'jquery' ) );
    }

    /*function ship_select_menu(){
			
			if (!function_exists('current_user_can') || !current_user_can('manage_options') )
			return;
				
			if ( function_exists( 'add_options_page' ) )
			{
				add_options_page(
					__('Shipping Details Settings', 'sym-shipping-tracking'),
					__('Shipping Details', 'sym-shipping-tracking'),
					'manage_options',
					'woo_ship_buttons',
					array( &$this, 'admin_options' ) );
			}
		}
			
			
		public function admin_options() {
			$options = get_option( 'woo_ship_options' );
			ob_start();
			   ?>
			<div class="wrap">
				<?php screen_icon("options-general"); ?>
				<h2>Shipping Details Settings</h2>
				<br>
				<h3><b>Select Shipping Company that you will be using to ship the Products.</b></h3>
				<form action="options.php" method="post"  style="padding-left:20px">
				<?php settings_fields('woo_ship_group');
					if( isset($options['CANPAR'] )){ ?>
					<br>
					<b>Canpar Shipper Code : </b>
					<input type="text" name="woo_ship_options[CANPARSCODE]" id="CANPARSCODE" value="<?php if(isset($options['CANPARSCODE'])) echo $options['CANPARSCODE']; ?>" />
					<br>
					<br>
					<?php } ?>
					<table cellpadding="10px">
					<?php

						$shipping_companies = $this->get_shipping_list();

						$i = 0;
						foreach( $shipping_companies as $k => $v ){

							if($i%5==0){
								echo '<tr>';
							}

							$checked = '';

							if(1 == isset($options[$k])){
								$checked = "checked='checked'";
							}

							echo "<td><td class='forminp'>
									<input type='checkbox' name='woo_ship_options[$k]' id='$k' value='1' $checked />
								</td>
								<td scope='row'><label for='$k' >$v</label></td>
								</td>";

							$i++;
							if($i%5==0){
								echo '</tr>';
							}
						}
						if($i%5!=0){
							echo '</tr>';
						}

					?>
					</table>
					<p class="submit">
						<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'sym-shipping-tracking'); ?>" />
					</p>
				</form>
			</div>
			<?php
			echo ob_get_clean();
		}*/

    function track_page_shipping_details( $order ) {

      $current_agent_slug      = esc_html( get_post_meta( $order->id, 'wskl-delivery-agent', TRUE ) );
      $current_tracking_number = esc_html( get_post_meta( $order->id, 'wskl-tracking-number', TRUE ) );

      $agent = Agent_Helper::get_tracking_number_agent_by_slug( $current_agent_slug );

      if ( $agent ) {

        $tracking_url = esc_attr( sprintf( $agent->get_query_url_template(), $current_tracking_number ) );
        printf( '<h3>%s%s</h3>', $agent->get_name(), __( "로 배송이 되었습니다.", 'sym-shipping-tracking' ) );
        printf(
            '<p class="order-again"><strong>송장번호: %s</strong><a href="%s" class="button" target="_blank">배송확인</a></p>',
            $current_tracking_number, $tracking_url
        );
      }
    }


    function email_shipping_details( $order ) {

      $order_meta = get_post_custom( $order->id );

      for ( $i = 0; $i <= 4; $i ++ ) {
        if ( $i == 0 ) {
          if ( isset( $order_meta['_order_trackno'] ) && isset( $order_meta['_order_trackurl'] ) ) {
            $this->shipping_details( $order_meta['_order_trackno'], $order_meta['_order_trackurl'], $order );
          }
        } else {
          if ( isset( $order_meta[ '_order_trackno' . $i ] ) && isset( $order_meta[ '_order_trackurl' . $i ] ) ) {
            $this->shipping_details( $order_meta[ '_order_trackno' . $i ], $order_meta[ '_order_trackurl' . $i ], $order );
          }
        }
      }
    }

    function shipping_details( $trackno, $trackurl, $order ) {

//			$options = get_option( 'woo_ship_options' );
//
//
//
//			include 'shipping-url-list.php';
//
//			if ( $trackno[0] != NULL && $trackurl[0] != NULL && $trackurl[0] != 'NOTRACK' ) { ?>
      <!--				<h3>[--><?php //echo $shipping_companies[ $trackurl[0] ]; ?>
      <!--					]--><?php //_e( '로 배송이 되었습니다.', 'sym-shipping-tracking' ); ?><!--</h3>-->
      <!--				<p class="order-again">-->
      <!--					<STRONG>송장번호 : --><?php //echo $trackno[0]; ?><!--</STRONG>-->
      <!--					<a href="--><?php //echo $urltrack; ?><!--" class="button">배송확인</a></p>-->
      <!--				<!-- <br/> -->-->
      <!---->
      <!--			--><?php //}


    }

    function admin_shipping_details( $trackno, $trackurl, $order ) {

      $options = get_option( 'woo_ship_options' );

      $shipping_companies = $this->get_shipping_list();

      include 'shipping-url-list.php';

      if ( $trackno[0] != NULL && $trackurl[0] != NULL && $trackurl[0] != 'NOTRACK' ) { ?>
        <STRONG><?php echo $shipping_companies[ $trackurl[0] ]; ?></STRONG><br/>
        <?php if ( $trackurl[0] == 'POSTNLL' ) { ?>
          <STRONG><?php _e( 'Tracking #', 'sym-shipping-tracking' ); ?> </STRONG><?php echo $track[0]; ?><br/>
          <STRONG><?php _e( 'Postal Code', 'sym-shipping-tracking' ); ?> </STRONG><?php echo $track[1]; ?>
        <?php } else if ( $trackurl[0] == 'APCOVERNIGHT' ) { ?>
          <STRONG><?php _e( 'Consignment #', 'sym-shipping-tracking' ); ?> </STRONG><?php echo $track[1]; ?>
          <br/>
          <STRONG><?php _e( 'Postal Code', 'sym-shipping-tracking' ); ?> </STRONG><?php echo $track[0]; ?>
        <?php } else { ?>
          <STRONG><?php _e( 'Tracking #', 'sym-shipping-tracking' ); ?></STRONG><?php echo $trackno[0]; ?>
        <?php } ?>
        <br/><br/>
      <?php }

    }

    function get_shipping_list() {

      include 'shipping-company-list.php';

      ksort( $shipping_companies );

      return $shipping_companies;

    }

  }
}
$GLOBALS['wooshippinginfo'] = new Sym_Shipping_Tracking();

