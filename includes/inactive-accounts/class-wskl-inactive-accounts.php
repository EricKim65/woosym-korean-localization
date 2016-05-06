<?php

wskl_check_abspath();

require_once( WSKL_PATH . '/includes/inactive-accounts/functions.php' );


/**
 * 휴면 계정 처리 모듈
 *
 * WP-Members 플러그인에 의존한다.
 *
 * 로그인 하지 않은 지 N 일이 지난 회훤을 휴면 처리 한다. 또한 D 일이 지난 회원은 알림 처리를 한다.
 * 알림을 보내고도 로그인하지 않았다면, 회원의 메타 키를 지우고 휴면 처리한다.
 *
 * 휴면 처리는 메타 키를 삭제하는 것으로 하며, 복구는 WP-Members 의 패스워드 초기화 개능을 활용한다.
 *
 * 휴면 계정 파악은 CRON 을 이용하므로 만일 CRON 이 사용되지 않는다면 이 모듈을 사용할 수 없다.
 *
 * Class WSKL_Inactive_Accounts
 */
class WSKL_Inactive_Accounts {

	public $admin = NULL;

	public $shortcodes = NULL;

	public function __construct() {

		/**
		 * 어드민 화면에서 통지하는 기능은 WSKL_Plugins_React 를 참고.
		 *
		 * @see WSKL_Plugins_React::wp_members()
		 */
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			return;
		}

		if ( wskl_is_plugin_inactive( WP_MEMBERS_PLUGIN ) ) {
			return;
		}

		// 관리자 모듈
		if ( WSKL()->is_request( 'admin' ) ) {
			wskl_load_module( '/includes/inactive-accounts/admin/class-wskl-inactive-accounts-admin.php' );
			$this->admin = new WSKL_Inactive_Accounts_Admin();
		}

		/**
		 * 사용자 로그인 시, 로그인 시각 기록
		 *
		 * @see wp-includes/user.php
		 * @see wp_signon()
		 */
		add_action( 'wp_login', array( $this, 'set_login_data' ), 10, 2 );

		/**
		 * 크론 스케쥴 확장 작업
		 */
		add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );
		if ( wskl_debug_enabled() ) {
			// 디버그 전용...
			add_filter( 'cron_request', array( $this, 'add_xdebug_cookie_value' ) );
		}

		/**
		 * 크론 스케쥴로 새성된 훅
		 */
		add_action( 'wskl_inactive_accounts_check', array( $this, 'do_interval_jobs' ) );

		/**
		 * WP-Members 패스워드 초기화
		 */
		add_action( 'wpmem_pwd_reset', array( $this, 'recover_user_role' ), 10, 1 );

		// 나머지 모듈들
		wskl_load_module( '/includes/inactive-accounts/class-wskl-inactive-accounts-email.php' );
		wskl_load_module( '/includes/inactive-accounts/admin/class-wskl-inactive-accounts-tinymce-buttons.php' );
		wskl_load_module( '/includes/inactive-accounts/class-wskl-inactive-accounts-shortcodes.php' );
		$this->shortcodes = new WSKL_Inactive_Accounts_Shortcodes();

		if ( wskl_debug_enabled() ) {
			add_action( 'wp_ajax_inactive-accounts_test', array( $this, 'test' ) );
		}
	}

	public function test() {

		if ( ! current_user_can( 'manage_options' ) ) {
			die();
		}

		$active_span = wskl_get_option( 'inactive-accounts_active_span' );
		$alert       = wskl_get_option( 'inactive-accounts_alert' );
		$target_role = wskl_get_option( 'inactive-accounts_target_role' );

		if ( ! $active_span || ! $alert || ! $target_role ) {
			return;
		}

		$guests = array(
			get_user_by( 'login', 'guest' ),
			get_user_by( 'login', 'guest2' ),
			get_user_by( 'login', 'guest3' ),
		);

		foreach ( $guests as $guest ) {
			$guest->remove_role( 'deactivated' );
			$guest->add_role( $target_role );
		}

		$recent        = time() - DAY_IN_SECONDS;
		$alert_ts      = $recent - ( $active_span * DAY_IN_SECONDS ) + ( $alert * DAY_IN_SECONDS ) - DAY_IN_SECONDS;
		$deactivate_ts = $recent - ( $active_span * DAY_IN_SECONDS ) - ( MONTH_IN_SECONDS );

		error_log( 'Recent: ' . $recent );
		error_log( 'Alert: ' . $alert_ts );
		error_log( 'Deactivate: ' . $deactivate_ts );

		wskl_set_user_last_login( $guests[0]->ID, $recent );
		wskl_set_user_last_login( $guests[1]->ID, $alert_ts );
		wskl_set_user_last_login( $guests[2]->ID, $deactivate_ts );

		wskl_delete_user_alerted( $guests[1]->ID );
		wskl_set_user_alerted( $guests[2]->ID, $alert_ts - WEEK_IN_SECONDS );

		wskl_load_module( '/includes/inactive-accounts/class-wskl-inactive-accounts-cron-jobs.php' );
		$job = new WSKL_Inactive_Accounts_Cron_Jobs();
		$job->do_inactive_account_filtering();

		die();
	}

	/**
	 * 디버그를 위해 XDEBUG 세션 값을 추가.
	 *
	 * @callback
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function add_xdebug_cookie_value( $params ) {

		$xdebug_session_id = wskl_get_option( 'develop_xdebug_session_id' );
		$url               = $params['url'];
		$params['url']     = add_query_arg( 'XDEBUG_SESSION_START', $xdebug_session_id, $url );

		return $params;
	}

	/**
	 * 패스워드 복구 기능을 이용하여 계정을 복구한 경우, 현재 유저가 휴면 계정일 경우에는 다시 유저를 고객으로 복구한다.
	 *
	 * @callback
	 * @action    wpmem_pwd_reset
	 * @see       wp-members/inc/core.php
	 * @see       wpmem_reset_password()
	 *
	 * @param $user_id
	 */
	public function recover_user_role( $user_id /* , $new_pass */ ) {

		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return;
		}

		$user->remove_role( 'deactivated' );
		$user->add_role( wskl_get_option( 'inactive-accounts_target_role' ) );

		wskl_delete_user_alerted( $user_id );
		wskl_delete_user_deactivated( $user_id );
	}

	/**
	 * @callback
	 * @filter    cron_schedules
	 *
	 * @param array $schedules
	 *
	 * @return array
	 */
	public function add_cron_schedule( $schedules ) {

		/** @var WSKL_Inactive_Accounts_Admin $admin */
		$interval = wskl_get_option( 'inactive-accounts_interval' );

		if ( ! $interval ) {
			return $schedules;
		}

		$schedules['wskl_inactive_accounts_check_interval'] = array(
			'interval' => $interval * HOUR_IN_SECONDS,
			'display'  => sprintf( _n( '휴면계정: 매 %d 시간 마다.', '휴면계정: 매 %d 시간 마다.', 'wskl' ), $interval ),
		);

		$schedules['wskl_inactive_accounts_TEST_interval'] = array(
			'interval' => 20,
			'display'  => '테스트: 매 20초마다.',
		);

		return $schedules;
	}

	/**
	 * @callback
	 * @action    wp_login
	 *
	 * @param $user_login
	 * @param $user
	 */
	public function set_login_data(
		/** @noinspection PhpUnusedParameterInspection */
		$user_login, $user
	) {

		wskl_set_user_last_login( $user->ID );
		wskl_delete_user_alerted( $user->ID );
	}

	/**
	 * 크론에 의해 실행되는 콜백
	 *
	 * @callback
	 * @action     wskl_inactive_accounts_check
	 */
	public function do_interval_jobs() {

		wskl_load_module( '/includes/inactive-accounts/class-wskl-inactive-accounts-cron-jobs.php' );
		$job = new WSKL_Inactive_Accounts_Cron_Jobs();

		$job->fill_user_login_field();
		$job->do_inactive_account_filtering();
	}
}


if ( ! WSKL()->submodules()->has_module( 'inactive-accounts' ) ) {
	WSKL()->submodules()->add_submodule( 'inactive-accounts', new WSKL_Inactive_Accounts() );
}
