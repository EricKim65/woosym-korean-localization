<?php
require_once( WSKL_PATH . '/includes/dabory-sms/providers/mdalin/class-wskl-dabory-sms-provider-mdalin.php' );
require_once( WSKL_PATH . '/includes/dabory-sms/class-wskl-sms-text-substitution.php' );
require_once( WSKL_PATH . '/includes/dabory-sms/class-wskl-dabory-sms-trigger.php' );


/**
 * Class WSKL_Dabory_SMS
 *
 * 우커머스 특정 이벤트에 SMS 발송
 *
 * 노트
 * =====
 * 특이한 경우에 의해 문자 전송 제공자(provider)를 교체할 수 있다. 다음 액션과 핉터를 삭제하고 자신의 것으로 변경하면된다.
 * 해당 콜백은 WSKL_Dabory_SMS_Provider_Loading 클래스를 참고.
 *
 * * 액션
 *   - dabory_sms_load_provider_module: 원하는 문자 제공 전송자를 include 한다. WSKL_Dabory_SMS_Provider 클래스를 상속하라.
 *   - dabory_sms_provider_additional:  다보리 SMS > 서비스 제공자 설정 섹션의 마지막에 적절히 넣고 싶은 내용을 출력한다.
 *                                      해당 문자 제공자의 정보를 출력하는 용도로 적절하다.
 * * 필터
 *   - dabory_sms_auth_section_settings: 원하는 문자 제공 전송자의 설정을 제안한다. WSKL_Dabory_SMS_Provider::get_auth_section_settings()
 *   을 참고하라.
 *   - dabory_sms_provider_class:        원하는 문자 제공 전송자의 클래스 이름을 반환한다.
 *
 *   - dabory_sms_customer_new_account_user_role: 문자를 보낼 사용자 역할 목록. 기본은 'customer' 문자열 하나만 가진 array.
 *
 * @see   WSKL_Dabory_SMS_Provider_Loading
 * @since 3.3.0
 */
class WSKL_Dabory_SMS {

	public static function init() {

		/**
		 * 문자 전송 제공자 모듈 파일 include 를 위한 action
		 *
		 * @see WSKL_Dabory_SMS_Trigger::init()
		 * @see WSKL_Dabory_SMS_Admin::do_message_testing()
		 * @see WSKL_Dabory_SMS_Admin::do_message_point()
		 */
		add_action(
			'dabory_sms_load_provider_module',
			array( 'WSKL_Dabory_SMS_Provider_Loading', 'load_provider_module' )
		);

		if ( WSKL()->is_request( 'admin' ) ) {
			wskl_load_module( '/includes/dabory-sms/admin/class-wskl-dabory-sms-admin.php' );
		}

		WSKL_Dabory_SMS_Trigger::init();
	}
}


WSKL_Dabory_SMS::init();