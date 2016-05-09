<?php


class WSKL_Inactive_Accounts_Shortcodes {

	/** @var WP_User|null */
	public $recipient_user = NULL;

	/** @var int|null 활동 기간. 일 단위로. */
	public $active_span = NULL;

	public function __construct() {

		add_action( 'wp_loaded', array( $this, 'shortcode_processing' ) );
	}

	public function shortcode_processing() {

		$active_span       = wskl_get_option( 'inactive-accounts_active_span' );
		$post_alert        = wskl_get_option( 'inactive-accounts_post_alert' );
		$post_deactivation = wskl_get_option( 'inactive-accounts_post_deactivation' );

		if ( ! $active_span || $post_alert < 1 || $post_deactivation < 1 ) {

			$error_message = "Shortcode processing halted due to invalid setting values."
			                 . "\$active_span=$active_span, "
			                 . "\$post_alert=$post_alert, "
			                 . "\$post_deactivation=$post_deactivation. ";
			error_log( $error_message );

			return;
		}

		$this->active_span = $active_span;
		$this->add_shortcodes();
	}

	private function add_shortcodes() {

		foreach ( $this->get_shortcodes() as $tag => $data ) {
			add_shortcode( $tag, $data['callback'] );
		}
	}

	public function get_shortcodes() {

		$common_callback = array( $this, 'handle_shortcodes' );

		return apply_filters(
			'wskl_inactive_accounts_email_shortcodes',
			array(
				'active_span'       => array(
					'title'    => __( '휴면 기간(일)', 'wskl' ),
					'callback' => $common_callback,
				),
				'blog_name'         => array(
					'title'    => __( '사이트 이름', 'wskl' ),
					'callback' => $common_callback,
				),
				'deactivation_date' => array(
					'title'    => __( '휴면 전환일', 'wskl' ),
					'callback' => $common_callback,
				),
				'today'             => array(
					'title'    => __( '오늘 날짜', 'wskl' ),
					'callback' => $common_callback,
				),
				'user_login'        => array(
					'title'    => __( '사용자 이름', 'wskl' ),
					'callback' => $common_callback,
				),
				'site_name'         => array(
					'title'    => __( '사이트 이름', 'wskl' ),
					'callback' => $common_callback,
				),
			)
		);
	}

	public function set_recipient( WP_User $recipient ) {

		$this->recipient_user = $recipient;
	}

	public function handle_shortcodes(
		/** @noinspection PhpUnusedParameterInspection */
		$args,
		/** @noinspection PhpUnusedParameterInspection */
		$text,
		$tag
	) {

		if ( ! $this->recipient_user ) {
			$user = wp_get_current_user();
		} else {
			$user = $this->recipient_user;
		}

		$return_text = '';

		switch ( $tag ) {
			case 'blog_name':
			case 'site_name':
				$return_text = wp_specialchars_decode( get_bloginfo( 'blogname' ) );
				break;

			// 회원 활동 기간
			case 'active_span':
				$return_text = $this->active_span;
				break;

			// 휴면 계정 전환일
			case 'deactivation_date':
				if ( $user->ID ) {
					$last_login = wskl_get_last_login( $user->ID );
					assert( $last_login > 0 );
				} else {
					$last_login = time();
				}
				$return_text = wskl_date_string( $last_login + ( $this->active_span * DAY_IN_SECONDS ) );
				break;

			// 오늘 날짜
			case 'today':
				$return_text = wskl_date_string();
				break;

			// 회원 로그인 이름
			case 'user_login':
				$return_text = $user->ID ? $user->user_login : 'anonymous-user';
				break;
		}

		return $return_text;
	}
}
