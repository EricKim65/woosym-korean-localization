<?php


class WSKL_Dabory_Members_Admin_Settings {

	const PASSWORD_MIN_LENGTH = 8;

	/**
	 * @action  load-settings_page_{$page_name}
	 * @used-by WSKL_Dabory_Members_Admin::init()
	 *
	 * @uses    wskl_GET, wskl_POST, wskl_verify_nonce, add_settings_error
	 * @uses    extract_option_values()
	 * @uses    validate_option_values()
	 * @throws \NonceVerificationFailureException
	 */
	public static function update_dabory_members() {

		if ( wskl_GET( 'tab' ) != 'dabory-members' ||
		     wskl_POST( 'action' ) != 'update_dabory_members'
		) {
			return;
		}

		wskl_verify_nonce(
			'wskl_83830_nonce',
			wskl_POST( 'wskl_members_nonce' )
		);

		$options = self::extract_option_values();

		if ( self::validate_option_values( $options ) ) {

			foreach ( $options as $key => $option_value ) {
				self::update_option( $key, $option_value );
			}

			/** success notice */
			add_settings_error(
				'dabory-members',
				'settings_updated',
				__( 'Settings saved.' ),  // use wordpress default text
				'updated'
			);
		}
	}

	/**
	 * POST 데이터에서 값을 추출
	 *
	 * @return array
	 */
	private static function extract_option_values() {

		$output = array();

		// ranged numeric values
		$options = array(
			// 페이지 post ID
			array( 'page_tos', 'intval', 0 ),
			array( 'page_privacy', 'intval', 0 ),
			array( 'page_3rd_party', 'intval', 0 ),
			array( 'page_delivery', 'intval', 0 ),
			array( 'page_registration', 'intval', 0 ),
			array( 'page_registration_complete', 'intval', 0 ),
			array( 'page_withdrawal', 'intval', 0 ),

			// 약관 동의 출력 (checkbox)
			array( 'show_terms', 'sanitize_text_field', 'no' ),

			// 주소검색 (checkbox)
			array( 'enable_postcode_button', 'sanitize_text_field', 'no' ),

			// 비밀번호 문자조합 (checkbox)
			array( 'password_mixed_chars', 'sanitize_text_field', 'no' ),

			// 등록 완료 페이지 보이기 (checkbox)
			array( 'show_registration_complete', 'sanitize_text_field', 'no' ),

			// 탈퇴 페이지 쇼트코드 사용 (checkbox)
			array( 'enable_withdrawal_shortcode', 'sanitize_text_field', 'no' ),

			// 배송 약관 보이기 (checkbox)
			array( 'show_delivery', 'sanitize_text_field', 'no' ),

			// 환불 약관 보이기 (checkbox)
			array( 'show_refund', 'sanitize_text_field', 'no' ),

			// 패스워드 길이 설정 (checkbox)
			array( 'enable_password_length', 'sanitize_text_field', 'no' ),

			// 비밀번호 최소 길이 (number)
			array( 'password_min_length', 'intval', 0 ),

			// 비밀번호 강도 사용 (checkbox)
			array( 'password_strength_meter', 'sanitize_text_field', 'no' ),

			// 가입 후 로그인 (checkbox)
			array( 'logged_in_after_registration', 'sanitize_text_field', 'no' ),

			// 탈퇴 회원의 즉시 삭제 (checkbox)
			array( 'delete_after_withdrawal', 'sanitize_text_field', 'no' ),
		);

		foreach ( $options as $elem ) {
			$key      = $elem[0];
			$sanitize = $elem[1];
			$fallback = $elem[2];

			$option_name = self::get_option_name( $key );
			$val         = wskl_POST( $option_name, $sanitize, $fallback );

			$output[ $key ] = $val;
		}

		return $output;
	}

	public static function get_option_name( $key ) {

		return WSKL_PREFIX . 'members_' . $key;
	}

	/**
	 * 옵션 값을 검증.
	 *
	 * @param array $options
	 *
	 * @return bool
	 */
	private static function validate_option_values( array &$options ) {

		if ( $options['password_min_length'] < self::PASSWORD_MIN_LENGTH ) {
			add_settings_error(
				'dabory-members',
				'password_min_length',
				sprintf(
					__( '비밀번호는 최소 길이는 %d자 입니다.', 'wskl' ),
					self::PASSWORD_MIN_LENGTH
				)
			);

			return FALSE;
		}

		return TRUE;
	}

	public static function update_option( $key, $value, $autoload = NULL ) {

		return update_option(
			self::get_option_name( $key ),
			$value,
			$autoload
		);
	}

	/**
	 * poge 타입 포스트를 선택할 수 있도록 select 태그 생성
	 *
	 * @uses output_page_option_tags()
	 * @uses output_closed_context_button()
	 * @uses output_opened_context()
	 *
	 * @param        $key
	 * @param string $label
	 * @param string $desc
	 */
	public static function output_page_select_tag( $key, $label = '', $desc = '' ) {

		$value       = self::get_option( $key );
		$select_name = esc_attr( self::get_option_name( $key ) );

		echo '<li><label>' . esc_html( $label ) . '</label>';
		echo "<select class=\"dabory-page-select\" name=\"{$select_name}\">";
		echo self::output_page_option_tags( $value );
		echo '</select>';

		self::output_closed_context_button( absint( $value ) > 0 );
		self::output_opened_context();

		echo '</span>';
		echo '<span class="description">' . esc_html( $desc ) . '</span></li>';
	}

	public static function get_option( $key, $fallback = FALSE ) {

		return get_option( self::get_option_name( $key ), $fallback );
	}

	/**
	 * page 타입 포스트를 선택할 수 있도록 option 태그 생성
	 *
	 * @param bool $chosen_id
	 *
	 * @used-by output_page_select_tag
	 * @return string
	 */
	public static function output_page_option_tags( $chosen_id = FALSE ) {

		$pages    = get_pages();
		$selected = $chosen_id ? 'selected' : '';
		$buffer   = array(
			"<option value=\"-1\" {$selected}>" .
			esc_html( __( '페이지를 선택하세요', 'wskl' ) ) .
			'</option>',
		);

		foreach ( $pages as $page ) {
			$selected = $page->ID == $chosen_id ? 'selected' : '';
			$url      = get_page_link( $page );

			$buffer[] = "<option value=\"{$page->ID}\" {$selected} data-url=\"{$url}\">";
			$buffer[] = esc_html( $page->post_title );
			$buffer[] = "</option>";
		}

		$output = implode( '', $buffer );

		return $output;
	}

	/**
	 * 페이지가 선택되었을 때 셀렉트 박스 오른편에 닫힌 컨텍스트 링크가 보임.
	 *
	 * @used-by output_page_select_tag()
	 *
	 * @param $display
	 */
	private static function output_closed_context_button( $display ) {

		echo '<span class="context-closed" ';
		echo ( $display ) ? '>' : 'style="display:none;">';
		echo '<a href="#" class="arrow-right"> &rtrif; </a>';
		echo '</span>';
	}

	/**
	 * 페이지 셀렉트 박스의 열린 컨텍스트 출력
	 *
	 * @used-by output_page_select_tag()
	 */
	private static function output_opened_context() {

		echo '<span class="context-opened">';
		echo '<a href="" class="context-edit" target="_blank">' . __(
				'편집',
				'wskl'
			) . '</a> | ';
		echo '<a href="" class="context-view" target="_blank">' . __(
				'보기',
				'wskl'
			) . '</a> | ';
		echo '<a href="#" class="arrow-left">' . __( '숨김', 'wskl' ) . '</a>';
		echo '</span>';
	}

	/**
	 * input 체크박스 출력
	 *
	 * @param        $key
	 * @param string $label
	 * @param string $desc
	 */
	public static function output_checkbox( $key, $label = '', $desc = '' ) {

		$value = self::get_option( $key );

		$fmt = '<li><label for="%2$s">%1$s</label>
				<input id="%2$s" name="%2$s" type="checkbox" value="yes" %3$s />
				<span class="description">%4$s</span></li>';

		$checked = $value == 'yes' ? 'checked' : '';
		printf(
			$fmt,
			esc_html( $label ),                              // 1 label
			esc_attr( self::get_option_name( $key ) ),       // 2 id, name
			$checked,                                        // 3 checked
			esc_html( $desc )                                // 4 description
		);
	}

	/**
	 * 일반적인 형태의 인풋 태그 출력
	 *
	 * @param        $key
	 * @param string $label
	 * @param string $desc
	 * @param array  $attributes    별도의 프로퍼티. DB 에 저장된 값을 불러올 때는 여기에 'value' 키를
	 *                              넣지 말 것.
	 * @param mixed  $default_value 기본 value 프로퍼티 값.
	 */
	public static function output_input( $key, $label = '', $desc = '', $attributes = array(), $default_value = FALSE ) {

		$value = self::get_option( $key, $default_value );
		if ( ! isset( $attributes['value'] ) ) {
			$attributes['value'] = $value;
		}

		$buffer = array();
		foreach ( $attributes as $k => $v ) {
			$buffer[] = esc_attr( $k ) . '="' . esc_attr( $v ) . '"';;
		}
		$attr = implode( ' ', $buffer );

		$fmt = '<li><label for="%2$s">%1$s</label><input id="%2$s" name="%2$s" %5$s /><span class="description">%4$s</span></li>';

		printf(
			$fmt,
			esc_html( $label ),
			esc_attr( self::get_option_name( $key ) ),
			esc_attr( $value ),
			esc_html( $desc ),
			$attr
		);
	}

	public static function get_password_min_length() {

		return apply_filters( 'dabory_members_password_min_length', self::PASSWORD_MIN_LENGTH );
	}
}