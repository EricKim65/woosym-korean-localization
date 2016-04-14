<?php

wskl_check_abspath();


class WSKL_Admin_Dabory_SMS {

	public static function init() {

		add_filter( 'woocommerce_get_settings_pages', array( __CLASS__, 'add_settings_pages' ) );

		add_action( 'wp_ajax_dabory-sms-tester', array( __CLASS__, 'do_message_testing' ) );
	}

	public static function add_settings_pages( array $settings ) {

		wskl_load_module( '/includes/admin/settings/class-wskl-settings-dabory-sms.php' );

		$settings[] = new WSKL_Settings_Dabory_SMS();

		return $settings;
	}

	public static function do_message_testing() {

		if ( ! wp_verify_nonce( $_POST['dabory-sms-tester-nonce'], 'dabory-sms-tester-nonce' ) ) {
			wp_send_json_error( 'Nonce verification failed' );
			die();
		}

		wskl_load_module( '/includes/libraries/dabory-sms/provider/mdalin/class-wskl-dabory-sms-provider-mdalin.php' );

		try {

			$dalin = WSKL_Dabory_SMS_Provider_MDalin::factory();
			$dalin->send_message(
				array(
					'remote_msg'   => '[웹발신]테스트 문자입니다. ' . site_url(),
					'remote_phone' => wskl_get_option( 'dabory_sms_sender_phone' ),
				)
			);

			wp_send_json_success( __( '성공적으로 메시지를 보냈습니다.', 'wskl' ) );

		} catch( Exception $e ) {
			
			wp_send_json_error( $e->getMessage() );
		}

		die();
	}
}


WSKL_Admin_Dabory_SMS::init();
