<?php

namespace wskl\lib\auth;

require_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );
require_once( 'class-auth-model.php' );

use wskl\lib\cassandra\ClientAPI;
use wskl\lib\cassandra\OrderItemRelation;


class Auth {

	/** @var \Woosym_Korean_Localization_Settings */
	private $wskl_setting;

	static private $nonce_action = 'activation_nonce_wskl_e9celjs&32n';

	public function __construct( \Woosym_Korean_Localization_Settings $wskl_setting ) {

		$this->wskl_setting = $wskl_setting;

		$this->initialize();
	}

	public function initialize() {

		// 관리자 화면에서 JS/CSS 스크립트 로드
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_activate_action', array( $this, 'callback_activation' ) );
	}

	/**
	 * 스크립트 로드 콜백
	 *
	 * @action admin_enqueue_scripts
	 */
	public function admin_enqueue_scripts() {

		$screen = get_current_screen();

		if ( $screen->id != $this->wskl_setting->setting_menu_hook ) {
			return;
		}

		// 세팅 페이지에 필요한 스크립트 첨부.

		//인증 관련.
		if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'authentication' ) {

			wp_register_script( 'license_activation', plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/js/license-activation.js', array( 'jquery', ), NULL, TRUE );
			wp_localize_script(
				'license_activation',
				'activation_object',
				array(
					'site_url'         => site_url(),
					'ajax_url'         => admin_url( 'admin-ajax.php' ),
					'activation_nonce' => wp_create_nonce( static::$nonce_action ),
				)
			);
			wp_enqueue_script( 'license_activation' );
		}
	}

	/**
	 * @action wp_ajax_activate_action
	 */
	public function callback_activation() {

		if ( ! wp_verify_nonce( $_POST['activation_nonce'], static::$nonce_action ) ) {
			die();
		}

		$key_type     = sanitize_text_field( $_POST['key_type'] );
		$key_value    = sanitize_text_field( $_POST['key_value'] );
		$site_url     = sanitize_text_field( $_POST['site_url'] );
		$company_name = get_bloginfo( 'name' );

		$activation = ClientAPI::activate( $key_type, $key_value, $site_url, $company_name, TRUE );
		$info_model = new Auth_Model( $key_type );

		if ( $activation instanceof OrderItemRelation ) {

			$info_model->set_oir( $activation );
			$info_model->save();

			wp_send_json_success();
			die();
		}

		$info_model->reset();
		wp_send_json_error();
		die();
	}


	/** @noinspection PhpInconsistentReturnPointsInspection */
	/**
	 * @param $key_type
	 * @param $echo
	 *
	 * @return string|void
	 */
	public static function get_license_duration_string( $key_type, $echo = FALSE ) {

		$info = new Auth_Model( $key_type );

		if ( $info->is_available() ) {

			if( $info->is_verified() ) {

				$days_left = $info->get_oir()->get_key()->get_days_left();

				$text = '<span class="wskl-notice">'. sprintf(
						'%s: %s, %s: %s, %s: %s %s',
						__( '발급일', 'wskl' ),
						static::to_date_string( $info->get_oir()->get_key()->get_issue_date() ),
						__( '만료일', 'wskl' ),
						static::to_date_string( $info->get_oir()->get_key()->get_expire_date() ),
						__( '남은 기간', 'wskl' ),
						( $info->is_expired() ? __( '만료됨', 'wskl-') : $days_left ),
						_n( '일', '일', $days_left, 'wskl' )
					). '</span>';

			} else {

				$text = '<span class="wskl-notice">' . __( '활성화키가 인증되지 않아 기능이 실행되지 않습니다.', 'wskl' ) . '</span>';
			}


		} else {

			if( empty( $key_type ) ) {
				$text = __( '키를 입력하지 않았습니다.', 'wskl' );
			} else {
				$text = '<span class="wskl-notice">' . __( '활성화키가 인증되지 않아 기능이 실행되지 않습니다.', 'wskl' ) . '</span>';
			}
		}

		if( !$echo ) {
			return $text;
		}

		echo $text;
	}

	private static function to_date_string( \DateTime $datetime ) {

		return $datetime->format( 'Y-m-d' );
	}
}

