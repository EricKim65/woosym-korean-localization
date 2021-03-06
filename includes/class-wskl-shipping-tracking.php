<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( WSKL_PATH . '/includes/lib/shipping-tracking/class-wskl-agent-helper.php' );


define( 'SDURL', WP_PLUGIN_URL . "/" . dirname( plugin_basename( __FILE__ ) ) );

if ( ! class_exists( 'WSKL_Shipping_Tracking' ) ) :

	class WSKL_Shipping_Tracking {

		public static function init() {

			/** 관리자 주문 페이지에 메타 박스 추가 */
			add_action(
				'add_meta_boxes',
				array( __CLASS__, 'woocommerce_meta_boxes' )
			);

			/** 사용자가 보는 주문 페이지에 배송 상태 정보 보여 주기 */
			add_action(
				'woocommerce_order_items_table',
				array( __CLASS__, 'track_page_shipping_details' )
			);

			add_action(
				'woocommerce_process_shop_order_meta',
				array( __CLASS__, 'woocommerce_process_shop_order_meta', )
			);

			add_action(
				'manage_edit-shop_order_columns',
				array( __CLASS__, 'add_shipping_column' )
			);

			add_action(
				'manage_shop_order_posts_custom_column',
				array( __CLASS__, 'add_shipping_column_details' ),
				10,
				2
			);

			add_shortcode(
				'wskl_shipping_tracking',
				array( __CLASS__, 'shortcode_shipping_tracking' )
			);

		}

		function add_shipping_column_details( $column, $post_id ) {

			if ( $column == 'tracking_number' ) {
				$current_agent           = esc_html(
					get_post_meta( $post_id, 'wskl-delivery-agent', TRUE )
				);
				$current_tracking_number = esc_html(
					get_post_meta( $post_id, 'wskl-tracking-number', TRUE )
				);

				if ( $current_agent && $current_tracking_number ) {

					$agent = WSKL_Agent_Helper::get_tracking_number_agent_by_slug(
						$current_agent
					);

					printf(
						'%s / <a href="%s" target="_blank">%s</a>',
						$agent->get_name(),
						esc_attr(
							$agent->get_url_by_tracking_number(
								$current_tracking_number
							)
						),
						$current_tracking_number
					);
				}
			}
		}

		function add_shipping_column( $columns ) {

			$pos = array_search( 'order_actions', array_keys( $columns ) );

			$before_actions = array_slice( $columns, 0, $pos );
			$after_actions  = array_slice( $columns, $pos );
			$columns        = array_merge(
				$before_actions,
				array(
					"tracking_number" => __(
						'택배 추적',
						'wskl'
					),
				),
				$after_actions
			);

			return $columns;

		}

		/**
		 * 메타 박스 렌더링 콜백
		 *
		 * @param $post
		 */
		function woocommerce_order_shipping_details( $post ) {

			$agent_list = WSKL_Agent_Helper::get_agent_list();
			$options    = (array) get_option(
				wskl_get_option_name( 'shipping_companies' )
			);
			$agents     = array(
				'not-available' => __( '지정 안됨', 'wskl' ),
			);

			$current_agent           = esc_html(
				get_post_meta( $post->ID, 'wskl-delivery-agent', TRUE )
			);
			$current_tracking_number = esc_html(
				get_post_meta( $post->ID, 'wskl-tracking-number', TRUE )
			);

			foreach ( $options as $o ) {
				if ( $o ) {
					$agents[ $o ] = $agent_list[ $o ];
				}
			}
			?>
			<ul class="totals">
				<li>
					<label for="wskl-delivery-agent"><?php _e(
							'배송업체:',
							'sym-shipping-tracking'
						); ?></label>
					<select id="wskl-delivery-agent" name="wskl-delivery-agent">
						<?php foreach ( $agents as $k => $v ) { ?>
							<option value="<?php echo $k; ?>" <?php echo ( $current_agent == $k ) ? 'selected' : ''; ?>><?php echo $v; ?></option>
						<?php } ?>
					</select>
				</li>
				<li>
					<label for="wskl-tracking-number"><?php _e(
							'송장번호:',
							'sym-shipping-tracking'
						); ?></label>
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
		function woocommerce_process_shop_order_meta( $post_id ) {

			$delivery_agent  = sanitize_text_field(
				$_POST['wskl-delivery-agent']
			);
			$tracking_number = sanitize_text_field(
				$_POST['wskl-tracking-number']
			);

			update_post_meta(
				$post_id,
				'wskl-delivery-agent',
				$delivery_agent
			);
			update_post_meta(
				$post_id,
				'wskl-tracking-number',
				$tracking_number
			);
		}

		/** 메타 박스에 배송 정보 추가 */
		function woocommerce_meta_boxes() {

			add_meta_box(
				'wskl-shipping-tracking',
				__( '배송 정보', 'wskl' ),
				array( __CLASS__, 'woocommerce_order_shipping_details' ),
				'shop_order',
				'side',
				'high'
			);
		}

		function track_page_shipping_details( $order ) {

			$current_agent_slug      = esc_html(
				get_post_meta( $order->id, 'wskl-delivery-agent', TRUE )
			);
			$current_tracking_number = esc_html(
				get_post_meta( $order->id, 'wskl-tracking-number', TRUE )
			);

			$agent = WSKL_Agent_Helper::get_tracking_number_agent_by_slug(
				$current_agent_slug
			);

			if ( $agent ) {

				$tracking_url = esc_attr(
					sprintf(
						$agent->get_query_url_template(),
						$current_tracking_number
					)
				);
				printf(
					'<h3>%s%s</h3>',
					$agent->get_name(),
					__( "로 배송이 되었습니다.", 'sym-shipping-tracking' )
				);
				printf(
					'<p class="order-again"><strong>송장번호: %s</strong><a href="%s" class="button" target="_blank">배송확인</a></p>',
					$current_tracking_number,
					$tracking_url
				);
			}
		}

		function shortcode_shipping_tracking( $args ) {

			$sample_file = WSKL_PATH . '/tests/shipping-tracking/sample.json';
			if ( ! file_exists( $sample_file ) ) {
				return 'sample file not found!';
			}

			/**
			 * @var array $context 파일 형식 예
			 *                     {
			 *                      "agents": {
			 *                        "slug1": ["track01", "track02"],
			 *                        "slug2": ["track01"]
			 *                      }
			 *                     }
			 */
			$context = json_decode( file_get_contents( $sample_file ), TRUE );

			ob_start();
			wc_get_template(
				'shipping-tracking-template.php',
				$context,
				'',
				WSKL_PATH . '/includes/lib/shipping-tracking/'
			);

			return ob_get_clean();
		}
	}

endif;

WSKL_Shipping_Tracking::init();

