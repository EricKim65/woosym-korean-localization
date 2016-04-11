<?php

include_once( WSKL_PATH . '/includes/admin/class-wskl-dabory-members-admin-settings.php' );

use WSKL_Dabory_Members_Admin_Settings as Settings;


class WSKL_Dabory_Members_Registration {

	/**
	 * @var array 우리가 만든 폼의 validation error 항목을 저장. 키는 해당 폼 id, 값은 메시지.
	 * @used-by validate_agreements()
	 * @used-by get_tos_page_text()
	 *
	 * @see     wp-members/inc/forms.php
	 * @see     wpmem_inc_registration()
	 * @see     validate_agreements()
	 */
	private static $validation_errors = array();

	/**
	 * @var array 보여 줘야 할 약관의 종류
	 *
	 * @filter dabory_members_agreement_keys
	 */
	private static $agreement_keys = array( 'tos', 'privacy', '3rd_party' );

	public static function init() {

		self::$agreement_keys = apply_filters( 'dabory_members_agreement_keys', self::$agreement_keys );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'add_registration_scripts' ) );

		// 약관 출력
		if ( wskl_is_option_enabled( 'members_show_terms' ) ) {
			add_action( 'wpmem_pre_register_data', array( __CLASS__, 'validate_agreements' ) );
			add_filter( 'wpmem_register_form_rows', array( __CLASS__, 'include_terms' ), 10, 2 );
		}

		// 주소 찾기 기능
		if ( wskl_is_option_enabled( 'members_enable_postcode_button' ) ) {
			add_filter( 'wpmem_register_form_rows', array( __CLASS__, 'include_postcode_button' ), 10, 2 );
		}

		// 패스워드 강도 미터기 기능
		if ( wskl_is_option_enabled( 'members_password_strength_meter' ) ) {
			add_filter( 'wpmem_register_form_rows', array( __CLASS__, 'include_password_strength_meter' ), 10, 1 );
		}

		// 패스워드 관련 validation check
		add_action( 'wpmem_pre_register_data', array( __CLASS__, 'validate_password' ) );

		// 등록 완료 후 로그인 처리
		if ( wskl_is_option_enabled( 'members_logged_in_after_registration' ) ) {
			add_action( 'wpmem_post_register_data', array( __CLASS__, 'let_registered_user_logged_in' ) );
		}

		// 등록 완료 페이지 설정
		if ( wskl_is_option_enabled( 'members_show_registration_complete' ) ) {
			add_action( 'wpmem_register_redirect', array( __CLASS__, 'redirect_to_welcome_page' ), 9 );
		}
	}

	/**
	 * 주소찾기 버튼 삽입
	 *
	 * @callback
	 * @filter     wpmem_register_form_rows
	 * @used-by    WSKL_Dabory_Members_Registration::init()
	 *
	 * @param $rows
	 * @param $toggle
	 *
	 * @return mixed
	 */
	public static function include_postcode_button(
		$rows,
		/** @noinspection PhpUnusedParameterInspection */
		$toggle
	) {

		$zip_fields = apply_filters(
			'dabory_members_postcode_zip_fields',
			array( 'zip', )
		);

		$readonly_fields = apply_filters(
			'dabory_members_postcode_readonly_fields',
			array( 'zip', 'addr1', 'billing_postcode', 'billing_address_1' )
		);

		$field_to_include_postcode_button = apply_filters(
			'dabory_members_postcode_field_to_include_postcode_button',
			array( 'zip', 'billing_postcode' )
		);

		foreach ( $rows as &$row ) {

			/** 우편번호 버튼의 자리를 위해 zip input 길이를 조정 */
			if ( in_array( $row['meta'], $zip_fields ) ) {
				$row['field'] = preg_replace( '/class="(.+?)"/', 'class="$1 width-auto"', $row['field'] );
			}

			/** 읽기 전용 속성 부여 */
			if ( in_array( $row['meta'], $readonly_fields ) ) {
				$row['field'] = preg_replace( '/(<input.+?)\/?>/', '$1 readonly />', $row['field'] );
			}

			/** 버튼 삽입 */
			if ( in_array( $row['meta'], $field_to_include_postcode_button ) ) {
				$row['field'] .= '<button id="dabory-postcode-button" type="button" class="button clear" >' .
				                 __( '우편번호 찾기', 'wskl' ) . '</button>';
			}
		}

		return $rows;
	}

	/**
	 * @callback
	 * @filter    wpmem_register_form_rows
	 * @used-by   WSKL_Dabory_Members_Registration::init()
	 *
	 * @param  array $rows
	 *
	 * @return array
	 */
	public static function include_password_strength_meter( $rows ) {

		foreach ( $rows as &$row ) {
			if ( $row['meta'] == 'password' ) {
				$row['field_after'] = '<span class="password-strength-meter">' . __(
						'패스워드를 입력하세요.',
						'wskl'
					) . '</span>' . $row['field_after'];
			}
		}

		return $rows;
	}

	/**
	 * @callback
	 * @action    wp_enqueue_scripts
	 */
	public static function add_registration_scripts() {

		if ( ! self::is_wp_members_register_page() ) {
			return;
		}

		wp_enqueue_style(
			'dabory-members-registration',
			plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/css/dabory-members-registration.css'
		);

		if ( wskl_is_option_enabled( 'members_enable_postcode_button' ) ) {

			wskl_enqueue_daum_postcode_scripts();

			wskl_enqueue_script(
				'dabory-members-postcode',
				'assets/js/dabory-members-postcode.js',
				array( 'jquery', 'daum-postcode-v2' ),
				WSKL_VERSION,
				TRUE
			);
		}

		// password strength meter. Since WP Version 2.8 (https://codex.wordpress.org/Version_2.8)
		if ( wskl_is_option_enabled( 'members_password_strength_meter' ) ) {
			wskl_enqueue_script(
				'dabory-members-registration',
				'assets/js/dabory-members-registration.js',
				array( 'jquery', 'password-strength-meter' ),
				WSKL_VERSION,
				TRUE,
				'passwordMeterObj',
				array(
					'passwordEmpty' => __( '패스워드를 입력하세요.', 'wskl' ),
				)
			);
		}
	}

	/**
	 * 글로벌 변수 $post 때문에 콜백 함수에서만 사용해야 한다.
	 * 현재 페이지가 registration 쇼트코드를 사용한 가입 페이지인지 확인.
	 *
	 * @return bool
	 */
	public static function is_wp_members_register_page() {

		global $post;

		if ( $post ) {
			return (bool) preg_match( '/\[wpmem_form\s+register\s+\/\]/', $post->post_content );
		}

		return FALSE;
	}

	/**
	 * 약관에 모두 동의했는지 검사하는 로직
	 *
	 * @callback
	 * @action  wpmem_pre_register_data
	 * @used-by WSKL_Dabory_Members_Registration::init()
	 *
	 * @param $fields
	 */
	public static function validate_agreements(
		/** @noinspection PhpUnusedParameterInspection */
		$fields
	) {

		/**
		 * @var string $wpmem_themsg validation string
		 *
		 * @see wp-members/inc/register.php
		 * @see wpmem_registration
		 */
		global $wpmem_themsg;

		foreach ( self::$agreement_keys as $key ) {

			$agreed  = wskl_POST( 'checkbox-' . $key ) == 'yes';
			$post_id = intval( wskl_get_option( 'members_page_' . $key ) );

			if ( $post_id && ! $agreed ) {
				$post                            = WP_Post::get_instance( $post_id );
				$message                         = apply_filters(
					'dabory_members_validate_agreements_message',
					sprintf( esc_html__( '%s에 동의 체크해 주세요', 'wskl' ), $post->post_title )
				);
				self::$validation_errors[ $key ] = $message;
			}
		}

		if ( count( self::$validation_errors ) ) {
			$validation_messages = '<ul class="validation_error_list">';
			foreach ( self::$validation_errors as $error ) {
				$validation_messages .= '<li>' . $error . '</li>';
			}
			$validation_messages .= '</ul>';
			$wpmem_themsg = $validation_messages;
		}
	}

	/**
	 * 약관 항목을 사삽입
	 *
	 * @callback
	 * @filter   wpmem_register_form_rows
	 * @used-by  WSKL_Dabory_Members_Registration::init()
	 * @uses     WSKL_Dabory_Members_Registration::get_tos_page_text()
	 *
	 * @see      wp-members/inc/forms.php
	 * @see      wpmem_inc_registration()
	 *
	 * @param array  $rows   form rows
	 * @param string $toggle new|edit
	 *
	 * @return array
	 */
	public static function include_terms(
		$rows,
		/** @noinspection PhpUnusedParameterInspection */
		$toggle
	) {

		wp_enqueue_style(
			'dabory-members-registration',
			plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/css/dabory-members-registration.css'
		);

		$terms_rows = array(
			array(
				'order' => 0,
				'field' => '<h2 class="terms-title">' . __( '다보리 약관 동의', 'wskl' ) . '</h2>',
			),
		);

		$idx = 1;
		$cnt = count( self::$agreement_keys );

		if ( $cnt > 1 ) {
			$terms_rows[] = array(
				'field' => '<div id="check-all-wrapper"><label for="checkbox-all">'
				           . '<input type="checkbox" id="checkbox-all" class="checkbox-agreement">'
				           . __( '아래 약관에 모두 동의합니다.', 'wskl' ) . '</label></div>',
			);
		}

		foreach ( self::$agreement_keys as $key ) {
			$field = self::get_tos_page_text( $key, $idx == $cnt );
			if ( ! empty( $field ) ) {
				$terms_rows[] = array(
					'field' => $field,
				);
				++ $idx;
			}
		}

		return array_merge( $terms_rows, $rows );
	}

	/**
	 * 약관 페이지 필드 작성
	 *
	 * @used-by WSKL_Dabory_Members_Registration::include_terms()
	 *
	 * @param string $key
	 * @param bool   $is_last
	 *
	 * @return string
	 */
	private static function get_tos_page_text( $key, $is_last ) {

		$post_id = intval( wskl_get_option( 'members_page_' . $key ) );

		if ( ! $post_id ) {
			return '';
		}

		$post           = WP_Post::get_instance( $post_id );
		$title          = esc_html( $post->post_title );
		$content        = wpautop( wptexturize( esc_html( $post->post_content ) ) );
		$agreement_text = __( '약관에 동의합니다.', 'wskl' );
		$last           = $is_last ? 'last' : '';
		$checked        = ( wskl_POST( 'checkbox-' . $key ) == 'yes' ) ? 'checked' : '';

		if ( isset( self::$validation_errors[ $key ] ) ) {
			$validation_error_css_class = 'validation_error';
		} else {
			$validation_error_css_class = '';
		}

		$output = <<< PHP_EOD
<div class="tos-wrapper $last $validation_error_css_class">
	<h3 class="tos-title">$title</h3>
	<div class="text tos-content tos-content-$key">
		$content
	</div>
	<label for="checkbox-$key">
		<input type="checkbox" id="checkbox-$key" class="checkbox-agreement" name="checkbox-$key" value="yes" $checked />
		$agreement_text
	</label>
	<span class="req">*</span>
</div>
PHP_EOD;

		return $output;
	}

	/**
	 * @callback
	 * @action    wpmem_pre_register_data
	 *
	 * @param array $fields
	 */
	public static function validate_password( array $fields ) {

		global $wpmem_themsg;

		$min_length = intval( wskl_get_option( 'members_password_min_length' ), Settings::get_password_min_length() );

		// 패스워드 최소 길이 설정
		if ( wskl_is_option_enabled( 'members_enable_password_length' ) ) {
			if ( isset( $fields['password'] ) && $min_length ) {
				if ( strlen( $fields['password'] ) < $min_length ) {
					$wpmem_themsg = _n( '비밀번호는 %d자 이상으로 작성해 주세요.', '비밀번호는 %d자 이상으로 작성해 주세요.', $min_length, 'wskl' );
				}
			}
		}

		// 패스워드 문자 조합 설정
		if ( wskl_is_option_enabled( 'members_password_mixed_chars' ) ) {
			if ( ! self::check_password_mixed_chars( $fields['password'] ) ) {
				$wpmem_themsg = __( '비밀번호에는 특수문자와 숫자가 각각 1글자 이상씩 포함되어야 합니다.', 'wskl' );
			}
		}
	}

	public static function check_password_mixed_chars( $password ) {

		return preg_match(
			apply_filters( 'dabory_members_mixed_chars', '/^(?=.*[0-9])(?=.*[\W])(.+)/' ),
			$password
		);
	}

	public static function redirect_to_welcome_page() {

		$page_id = intval( wskl_get_option( 'members_page_registration_complete' ) );

		if ( $page_id ) {
			wp_redirect( get_page_link( $page_id ) );
			exit;
		}
	}

	public static function let_registered_user_logged_in( $fields ) {

		$username = $fields['username'];
		$password = $fields['password'];

		if ( ! empty( $username ) && ! empty( $password ) ) {
			$user = wp_signon(
				array(
					'user_login'    => $username,
					'user_password' => $password,
				)
			);

			if ( is_wp_error( $user ) ) {
				wp_die( $user->get_error_message() );
			}
		}
	}
}


WSKL_Dabory_Members_Registration::init();