<?php


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
			add_filter( 'wpmem_pre_register_data', array( __CLASS__, 'validate_agreements' ) );
			add_filter( 'wpmem_register_form_rows', array( __CLASS__, 'include_terms' ), 10, 2 );
		}

		// 주소 찾기 기능
		if ( wskl_is_option_enabled( 'members_enable_postcode_button' ) ) {
			add_filter( 'wpmem_register_form_rows', array( __CLASS__, 'include_postcode_button' ), 10, 2 );
		}

		// 패스워드 최소 길이 설정

		// 패스워드 문자 조합 설정

		// 등록 완료 페이지 설정
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

		$readonly_fields = apply_filters(
			'dabory_members_postcode_readonly_fields',
			array( 'zip', 'addr1', 'billing_postcode', 'billing_address_1' )
		);

		$field_to_include_postcode_button = apply_filters(
			'dabory_members_postcode_field_to_include_postcode_button',
			array( 'zip', 'billing_postcode' )
		);

		foreach ( $rows as &$row ) {

			if ( in_array( $row['meta'], $readonly_fields ) ) {
				$row['field'] = preg_replace( '/(<input.+?)\/?>/', '$1 readonly />', $row['field'] );
			}

			if ( in_array( $row['meta'], $field_to_include_postcode_button ) ) {
				$row['field'] .= '<button id="dabory-postcode-button" type="button" class="button" >' .
				                 __( '우편번호 찾기', 'wskl' ) . '</button>';
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
	 * @filter  wpmem_pre_register_data
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

		foreach ( self::$agreement_keys as $key ) {
			$field = self::get_tos_page_text( $key );
			if ( ! empty( $field ) ) {
				$terms_rows[] = array(
					'field' => $field,
				);
			}
		}

		return array_merge( $terms_rows, $rows );
	}

	/**
	 * 약관 페이지 필드 작성
	 *
	 * @used-by WSKL_Dabory_Members_Registration::include_terms()
	 *
	 * @param $key
	 *
	 * @return string
	 */
	private static function get_tos_page_text( $key ) {

		$post_id = intval( wskl_get_option( 'members_page_' . $key ) );

		if ( ! $post_id ) {
			return '';
		}

		$post           = WP_Post::get_instance( $post_id );
		$title          = esc_html( $post->post_title );
		$content        = wpautop( wptexturize( esc_html( $post->post_content ) ) );
		$agreement_text = __( '약관에 동의합니다.', 'wskl' );
		$checked        = ( wskl_POST( 'checkbox-' . $key ) == 'yes' ) ? 'checked' : '';

		if ( isset( self::$validation_errors[ $key ] ) ) {
			$validation_error_css_class = 'validation_error';
		} else {
			$validation_error_css_class = '';
		}

		$output = <<< PHP_EOD
<div class="tos-wrapper $validation_error_css_class">
	<h3 class="tos-title">$title</h3>
	<div class="text tos-content tos-content-$key">
		$content
	</div>
	<label for="checkbox-$key">
		<input type="checkbox" id="checkbox-$key" class="text tos-agreement" name="checkbox-$key" value="yes" $checked />
		$agreement_text
	</label>
	<span class="req">*</span>
</div>
PHP_EOD;

		return $output;
	}
}


WSKL_Dabory_Members_Registration::init();