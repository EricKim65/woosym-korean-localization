<?php

wskl_check_abspath();

require_once( WSKL_PATH . '/includes/dabory-sms/providers/class-wskl-dabory-sms-provider-loading.php' );


/**
 * Class WSKL_Dabory_SMS_Admin
 *
 * @since 3.3.0
 */
class WSKL_Dabory_SMS_Admin {

	public static function init() {

		/**
		 * Loading SMS setting page.
		 */
		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'add_settings_pages' ) );

		/**
		 * Handling dabory-sms-tester ajax request.
		 */
		add_action( 'wp_ajax_dabory-sms-tester', array( __CLASS__, 'do_message_testing' ) );

		/**
		 * Handling dabory-sms-point ajax request.
		 */
		add_action( 'wp_ajax_dabory-sms-point', array( __CLASS__, 'do_message_point' ) );
	}

	/**
	 * 우커머스 설정 - 다보리 SMS 탭과 설정 삽입
	 *
	 * @filter   woocommerce_get_settings_pages
	 * @callback
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function add_settings_pages( array $settings ) {

		wskl_load_module( '/includes/dabory-sms/admin/class-wskl-dabory-sms-settings-page.php' );

		$settings[] = new WSKL_Dabory_SMS_Settings_Page();

		return $settings;
	}

	/**
	 * 문자 메시지 테스트 ajax 요청 응답
	 *
	 * @callback
	 * @action       wp_ajax_dabory-sms-tester
	 *
	 * @throws \Exception
	 */
	public static function do_message_testing() {

		wskl_verify_nonce( 'dabory-sms-tester-nonce', $_POST['dabory-sms-tester-nonce'] );

		do_action( 'dabory_sms_load_provider_module' );

		$dalin  = WSKL_Dabory_SMS_Provider_MDalin::factory();
		$result = $dalin->send_message(
			array(
				'remote_msg'   => '[웹발신]테스트 문자입니다. ' . site_url(),
				'remote_phone' => wskl_get_option( 'sms_sender_phone' ),
			)
		);

		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo wp_json_encode( array( 'success' => TRUE, 'data' => $result ) );
		die();
	}

	/**
	 * 포인트 조회 ajax 요청 응답.
	 *
	 * @callback
	 * @action     wp_ajax_dabory-sms-point
	 */
	public static function do_message_point() {

		if ( ! wp_verify_nonce( $_POST['dabory-sms-point-nonce'], 'dabory-sms-point-nonce' ) ) {
			wp_send_json_error( 'Nonce verification failed' );
			die();
		}

		do_action( 'dabory_sms_load_provider_module' );

		try {

			$dalin     = WSKL_Dabory_SMS_Provider_MDalin::factory();
			$sms_point = $dalin->point_check(
				array(
					'remote_request' => 'sms',
				)
			);

			wp_send_json_success( array( 'sms' => $sms_point ) );

		} catch( Exception $e ) {

			wp_send_json_error( $e->getMessage() );
		}

		die();
	}
}


WSKL_Dabory_SMS_Admin::init();
