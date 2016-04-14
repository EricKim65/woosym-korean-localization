<?php
wskl_check_abspath();


/**
 * Class WSKL_Dabory_Members
 */
class WSKL_Dabory_Members {

	const WP_MEMBERS = 'wp-members/wp-members.php';

	/**
	 * initialization
	 */
	public static function init() {

		if ( wskl_is_plugin_inactive( self::WP_MEMBERS ) ) {
			return;
		}

		if ( is_admin() ) {
			wskl_load_module(
				'/includes/admin/dabory-members/class-wskl-dabory-members-admin.php',
				'enable_dabory_members'
			);
		}

		// 회원 등록 서브모듈
		wskl_load_module( '/includes/dabory-members/class-wskl-dabory-members-registration.php' );

		// 회원 탈퇴 서브모듈
		wskl_load_module(
			'/includes/dabory-members/class-wskl-dabory-members-withdrawal.php',
			'members_enable_withdrawal_shortcode'
		);

		// tinymce 버튼 삽입 서브모듈
		wskl_load_module( '/includes/dabory-members/class-wskl-dabory-members-tinymce-buttons.php' );

		// 탈퇴한 회원의 로그인 방지
		add_filter( 'authenticate', array( __CLASS__, 'filter_authentication' ), 100, 1 );

		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'delivery_refund' ) );

		add_shortcode( 'dabory-members', array( __CLASS__, 'shortcode_dabory_members' ) );
	}

	public static function delivery_refund( array $tabs ) {

		if ( wskl_is_option_enabled( 'members_show_delivery' ) ) {
			$tabs['delivery_terms'] = array(
				'title'    => __( '배송 정보', 'wskl' ),
				'priority' => 20,
				'callback' => array( __CLASS__, 'output_delivery_terms' ),
			);
		}

		if ( wskl_is_option_enabled( 'members_show_refund' ) ) {
			$tabs['refund_terms'] = array(
				'title'    => __( '환불 정보', 'wskl' ),
				'priority' => 20,
				'callback' => array( __CLASS__, 'output_refund_terms' ),
			);
		}

		return $tabs;
	}

	public static function shortcode_dabory_members( $attrs, $content ) {

		$param  = wskl_get_from_assoc( $attrs, 0 );
		$output = '';

		switch ( $param ) {
			case 'withdrawal':
				if ( wskl_is_option_enabled( 'members_enable_withdrawal_shortcode' ) ) {
					$output = WSKL_Dabory_Members_Withdrawal::output_form( $content );
				}
				break;

			default:
				_doing_it_wrong( __FUNCTION__, "parameter '$param' is not recognized.", WSKL_VERSION );
		}

		return $output;
	}

	/**
	 * @callback
	 * @filter      authentication
	 * @used-by     WSKL_Dabory_Members::init()
	 *
	 * @param $user
	 *
	 * @return null
	 */
	public static function filter_authentication( $user ) {

		if ( $user instanceof WP_User && in_array( 'withdrawer', $user->roles ) ) {
			return NULL;
		}

		return $user;
	}

	public static function output_delivery_terms() {

		self::output_term( 'delivery', __( '배송 약관 페이지가 설정되어 있지 않습니다.', 'wskl' ) );
	}

	private static function output_term( $term_slug, $fallback_text = '' ) {

		$post = WP_Post::get_instance( wskl_get_option( 'members_page_' . $term_slug ) );

		if ( ! $post ) {
			echo $fallback_text;
		} else {
			echo '<h3>' . esc_html( $post->post_title ) . '</h3>';
			echo wpautop( wptexturize( $post->post_content ) );
		}
	}

	public static function output_refund_terms() {

		self::output_term( 'refund', __( '환불 약관 페이지가 설정되어 있지 않습니다.', 'wskl' ) );
	}
}


WSKL_Dabory_Members::init();
