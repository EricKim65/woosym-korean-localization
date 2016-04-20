<?php


/**
 * Class WSKL_Dabory_SMS_Provider_Loading
 *
 * 문자 서비스 제공자에 대한 훅 콜백 부분을 별도로 구성. 이 클래스를 샘플로 하면 문자 서비스 확장 가능.
 *
 * @since 3.3.0
 */
class WSKL_Dabory_SMS_Provider_Loading {

	public static $provider = 'mdalin';

	public static function get_auth_section_settings() {

		$section = array(
			array(
				'type'  => 'title',
				'title' => __( '서비스 제공자 설정', 'wskl' ),
				'id'    => 'provider_options',
			),
			array(
				'id'      => wskl_get_option_name( 'sms_provider_id' ),
				'type'    => 'text',
				'title'   => '아이디',
				'desc'    => '',
				'default' => '',
			),
			array(
				'id'      => wskl_get_option_name( 'sms_provider_password' ),
				'type'    => 'password',
				'title'   => '패스워드',
				'desc'    => '',
				'default' => '',
			),
			array(
				'type' => 'sms_provider_additional',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'provider_options',
			),
		);

		return apply_filters( 'dabory_sms_auth_section_settings', $section );
	}

	public static function get_provider_class() {

		return apply_filters( 'dabory_sms_provider_class', 'WSKL_Dabory_SMS_Provider_MDalin' );
	}

	public static function load_provider_module() {

		wskl_load_module( '/includes/dabory-sms/providers/mdalin/class-wskl-dabory-sms-provider-mdalin.php' );
	}

	public static function output_provider_additional() { ?>
		<tr valign="top">
			<td colspan="2" class="forminp forminp-provider-information">
				<p>
					문자 메시지는 '<a href="http://www.mdalin.co.kr" target="_blank">문자달인</a>'을 통해 제공됩니다.
					<a href="https://www.mdalin.co.kr:444/callback_mgr/callback_manager.php" target="_blank">발신자
						등록</a> |
					<a href="http://www.mdalin.co.kr/pay/pay.html" target="_blank">포인트 충전</a> |
					<a href="http://www.mdalin.co.kr/member/member_join.php" target="_blank">회원가입</a>
				</p>
			</td>
		</tr>
		<?php
	}
}