<?php

require_once WSKL_PATH . '/includes/lib/shipping-tracking/class-wskl-agent-helper.php'; // 배송정보 치환을 위해.


/**
 * Class WSKL_SMS_Text_Substitution
 *
 * 문자 메시지 본문과 제목의 치환 문자열 처리
 *
 * @since 3.3.0
 */
class WSKL_SMS_Text_Substitution {

	private $order_magic_texts = array();

	private $user_magic_texts = array();

	private $find = array();

	private $replace = array();

	private $order = NULL;

	private $user = NULL;

	public function __construct() {

		$blog_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$this->order_magic_texts = array(
			'blog-name'       => array(
				'find'    => '{blog_name}',
				'replace' => $blog_name,
				'desc'    => __( '상점 이름.', 'wskl' ) . ' ' . esc_html( $blog_name ),
			),
			'order-content'   => array(
				'find' => '{order_content}',
				'desc' => __( '주문 내역. \'상품명\', 혹은 \'상품명 외 N건\'으로 표시됩니다.', 'wskl' ),
			),
			'order-date'      => array(
				'find' => '{order_date}',
				'desc' => __( '주문 일시', 'wskl' ),
			),
			'order-id'        => array(
				'find' => '{order_id}',
				'desc' => __( '주문 ID', 'wskl' ),
			),
			'order-number'    => array(
				'find' => '{order_number}',
				'desc' => __( '주문 번호', 'wskl' ),
			),
			'order-total'     => array(
				'find' => '{order_total}',
				'desc' => __( '주문 총액', 'wskl' ),
			),
			'order-status'    => array(
				'find' => '{order_status}',
				'desc' => __( '주문 상태', 'wskl' ),
			),
			'site-title'      => array(
				'find'    => '{site_title}',
				'replace' => $blog_name,
				'desc'    => __( '상점 이름.', 'wskl' ) . ' ' . esc_html( $blog_name ),
			),
			'tracking-number' => array(
				'find'    => '{tracking_number}',
				'replace' => '',
				'desc'    => __( '배송정보가 기록된 경우 "배송정보: &lt;택배회사&gt; &lt;배송번호&gt;"로 표시됩니다.', 'wskl' ),
			),
			'order:custom'    => array(
				'find'    => '{order:&lt;meta_key&gt;}',
				'replace' => '',
				'desc'    => __( '주문 정보의 메타 키 값.', 'wskl' ),
			),
		);

		$this->user_magic_texts = array(
			'user-email'  => array(
				'find' => '{user_email}',
				'desc' => __( '유저 이메일', 'wskl' ),
			),
			'user-id'     => array(
				'find' => '{user_id}',
				'desc' => __( '유저 ID (숫자)', 'wskl' ),
			),
			'user-login'  => array(
				'find' => '{user_login}',
				'desc' => __( '로그인 아이디', 'wskl' ),
			),
			'user:custom' => array(
				'find' => '{user:&lt;meta_key&gt;}',
				'desc' => __( '사용자의 메타 키 값.', 'wskl' ),
			),
		);
	}

	public function init_substitute( WC_Order $order = NULL, WP_User $user = NULL ) {

		if ( ! $order && ! $user ) {
			throw new LogicException( 'Either $order, or $user should not be null!' );
		}

		$this->order = $order;
		$this->user  = $user;

		$order_magic_texts = $this->get_order_magic_texts();

		foreach ( $order_magic_texts as $key => $item ) {

			$this->find[ $key ] = $item['find'];

			switch ( $key ) {
				case 'blog-name':
				case 'site-title':
					$this->replace[ $key ] = $item['replace'];
					break;

				case 'order-content':
					$this->replace[ $key ] = $order ? wskl_get_order_item_description( $order, 10 ) : '';
					break;

				case 'order-date':
					$this->replace[ $key ] = $order ? date_format(
						date_create( $order->order_date ),
						'Y-m-d H:i:s'
					) : '';
					break;

				case 'order-id':
					$this->replace[ $key ] = $order ? $order->id : '';
					break;

				case 'order-number':
					$this->replace[ $key ] = $order ? $order->get_order_number() : '';
					break;

				case 'order-total':
					$this->replace[ $key ] = $order ? sprintf(
						get_woocommerce_price_format(),
						get_woocommerce_currency_symbol( '' ),
						$order->order_total
					) : '';
					break;

				case 'tracking-number':

					$delivery_agent  = get_post_meta( $order->id, 'wskl-delivery-agent', TRUE );
					$tracking_number = get_post_meta( $order->id, 'wskl-tracking-number', TRUE );

					if ( $delivery_agent && ! empty( $delivery_agent ) && $tracking_number && ! empty( $tracking_number ) ) {
						$agent = WSKL_Agent_Helper::get_tracking_number_agent_by_slug( $delivery_agent );
						if ( $agent ) {
							$this->replace[ $key ] = __( '배송정보', 'wskl' )
							                         . ': ' . "{$agent->get_name()} {$tracking_number}";
						}
					}
					break;

				case 'order-status':
					$this->replace[ $key ] = $order ? $order->status : '';
					break;
			}
		}

		if ( isset( $this->find['order:custom'] ) ) {
			unset( $this->find['order:custom'] );
		}

		$user_magic_texts = $this->get_user_magic_texts();

		foreach ( $user_magic_texts as $key => $item ) {

			$this->find[ $key ] = $item['find'];

			switch ( $key ) {
				case 'user-email':
					$this->replace[ $key ] = $user ? $user->user_email : '';
					break;

				case 'user-id':
					$this->replace[ $key ] = $user ? $user->ID : '';
					break;

				case 'user-login':
					$this->replace[ $key ] = $user ? $user->user_login : '';
					break;
			}
		}

		if ( isset( $this->find['user:custom'] ) ) {
			unset( $this->find['user:custom'] );
		}
	}

	public function get_order_magic_texts() {

		return $this->order_magic_texts;
	}

	public function get_user_magic_texts() {

		return $this->user_magic_texts;
	}

	public function substitute( $template ) {

		if ( ! $this->order && ! $this->user ) {
			throw new LogicException( 'Please call init_substitute() before substitute()' );
		}

		$output_text = $template;

		$output_text = preg_replace_callback(
			'/{order:(.+?)}/',
			array( $this, 'order_custom_replace' ),
			$output_text
		);

		$output_text = preg_replace_callback(
			'/{user:(.+?)}/',
			array( $this, 'user_custom_replace' ),
			$output_text
		);

		assert( count( $this->find ) == count( $this->replace ) );

		$output_text = str_replace( $this->find, $this->replace, $output_text );

		return $output_text;
	}

	public function user_custom_replace( $matches ) {

		$meta_key = $matches[1];

		return $this->user->{$meta_key};
	}

	public function order_custom_replace( $matches ) {

		$meta_key = $matches[1];

		return $this->order->{$meta_key};
	}
}


