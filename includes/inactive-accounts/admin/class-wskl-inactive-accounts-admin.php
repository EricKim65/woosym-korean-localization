<?php

require_once( WSKL_PATH . '/includes/lib/wp-members/class-wskl-wp-members-settings.php' );


/**
 * 휴면계정 관리 관리자 제어
 *
 * Class WSKL_Inactive_Accounts_Admin
 *
 */
class WSKL_Inactive_Accounts_Admin extends WSKL_WP_Members_Settings {

	public $id = 'inactive-accounts';

	public $tab_priority = 40;

	public $nonce_action = 'inactive-accounts-nonce';

	public $nonce_param = 'inactive-accounts-nonce';

	public $action = 'update-inactive-accounts';

	private $gmt_offset = 0;

	public function __construct() {

		parent::__construct();

		if ( $this->is_option_enabled( 'display_last_login' ) ) {
			$this->last_login_column_hooks();
		}

		/** 설정 페이지의 푸터 영역에 안내 메시지 삽입 */
		add_action( 'wskl_wp_members_section_footer_footer_area', array( $this, 'section_footer_area' ), 10, 0 );

		/** 테스트 메일 송신 버튼 삽입 */
		add_action( 'wskl_wp_members_field_test_email', array( $this, 'field_test_email' ), 10, 0 );

		/** 테스트 메일 AJAX 처리 */
		add_action( 'wp_ajax_wskl_inactive-accounts_test_email', array( $this, 'send_test_email' ) );

		/** 수동 크론 콜백 동작 */
		add_action( 'wp_ajax_manual_cron_job', array( $this, 'do_manual_cron_job' ) );
	}

	/**
	 * 로그인 정보 출력 칼럼 관련 작업
	 */
	private function last_login_column_hooks() {

		$this->gmt_offset = wskl_get_gmt_offset() * HOUR_IN_SECONDS;

		/**
		 * @see wp-admin/includes/screens.php
		 * @see get_column_headers()
		 */
		add_filter( 'manage_users_columns', array( $this, 'add_columns' ) );

		/**
		 * @see wp-admin/includes/class-wp-list-table.php
		 * @see WP_List_Table::get_column_info()
		 */
		add_filter( 'manage_users_sortable_columns', array( $this, 'sortable_columns' ) );

		/**
		 * @see wp-admin/includes/class-wp-users-list-table.php
		 * @see WP_Users_List_Table::single_row()
		 */
		add_filter( 'manage_users_custom_column', array( $this, 'display_custom_columns' ), 10, 3 );
	}

	/**
	 * @callback
	 * @filter       manage_users_columns
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function add_columns( $columns ) {

		$columns['wskl_last_login']  = __( '마지막 로그인', 'wskl' );
		$columns['wskl_alerted']     = __( '휴면 통지', 'wskl' );
		$columns['wskl_deactivated'] = __( '휴면 처리', 'wskl' );

		return $columns;
	}

	/**
	 * @callback
	 * @filter      manage_users_sortable_columns
	 *
	 * @param $sortable_columns
	 *
	 * @return mixed
	 */
	public function sortable_columns( $sortable_columns ) {

		$sortable_columns['wskl_last_login']  = 'wskl_last_login';
		$sortable_columns['wskl_alerted']     = 'wskl_alerted';
		$sortable_columns['wskl_deactivated'] = 'wskl_deactivated';

		return $sortable_columns;
	}

	/**
	 * @callback
	 * @filter      manage_users_custom_column
	 *
	 * @param $value
	 * @param $column_name
	 * @param $user_id
	 *
	 * @return string|void
	 */
	public function display_custom_columns( $value, $column_name, $user_id ) {

		switch ( $column_name ) {

			case 'wskl_last_login':
				$timestamp = wskl_get_last_login( $user_id );
				break;

			case 'wskl_alerted':
				$timestamp = wskl_get_user_alerted( $user_id );
				break;

			case 'wskl_deactivated':
				$timestamp = wskl_get_user_deactivated( $user_id );
				break;

			default:
				return $value;
				break;
		}

		return $this->format_datetime( $timestamp );
	}

	private function format_datetime( $timestamp ) {

		if ( ! $timestamp ) {
			return __( '기록 없음', 'wskl' );
		}

		return wskl_datetime_string( $timestamp, TRUE );
	}

	/** 페이지의 세팅 요소를 출력하기 위한 구조 서술. */
	public function get_fields() {

		return array(
			'tab_name' => __( '휴면계정', 'wskl' ),
			'title'    => __( '휴면계정 설정', 'wskl' ),
			'desc'     => sprintf(
				_x( '%s 에서 제공하는 휴면 계정 관리 기능입니다.', '메뉴 설정 부제목. %s 에 플러그인 이름', 'wskl' ),
				WSKL_NAME
			),
			'class'    => $this->id,
			'sections' => array(
				array(
					'id'     => 'general',
					'title'  => '',
					'fields' => array(
						array(
							'type'     => 'input',
							'key'      => 'interval',
							'label'    => __( '검사 주기', 'wskl' ),
							'desc'     => __( '시간 단위로 입력하세요.', 'wskl' ),
							'attrs'    => array(
								'type' => 'number',
							),
							'sanitize' => 'intval',
							'validate' => array( $this, 'validate_positive_integer' ),
							'default'  => '24',
						),
						array(
							'type'     => 'input',
							'key'      => 'active_span',
							'label'    => __( '휴면 기한', 'wskl' ),
							'desc'     => __( '일 동안 로그인하지 않은 계정을 휴면 처리합니다.', 'wskl' ),
							'attrs'    => array(
								'type' => 'number',
							),
							'sanitize' => 'intval',
							'validate' => array( $this, 'validate_positive_integer' ),
							'default'  => '365',
						),
						array(
							'type'     => 'input',
							'key'      => 'alert',
							'label'    => __( '휴면 통지 일자', 'wskl' ),
							'desc'     => __( '일 전에 휴면 처리 통지를 보냅니다.', 'wskl' ),
							'attrs'    => array(
								'type' => 'number',
							),
							'sanitize' => 'intval',
							'validate' => array( $this, 'validate_positive_integer' ),
							'default'  => '30',
						),
						array(
							'type'     => 'page_select',
							'key'      => 'post_alert',
							'label'    => __( '휴면 사전 알림 메일', 'wskl' ),
							'desc'     => __( '메일 본문으로 쓸 페이지를 선택하세요.', 'wskl' ),
							'sanitize' => 'intval',
							'validate' => array( $this, 'validate_positive_integer' ),
						),
						array(
							'type'     => 'page_select',
							'key'      => 'post_deactivation',
							'label'    => __( '휴면 처리 후 알림', 'wskl' ),
							'desc'     => __( '메일 본문으로 쓸 페이지를 선택하세요.', 'wskl' ),
							'sanitize' => 'intval',
							'validate' => array( $this, 'validate_positive_integer' ),
						),
					),
					'footer' => array(
						'type' => 'new_page',
					),
				),
				array(
					'title'  => '',
					'id'     => 'admin',
					'fields' => array(
						array(
							'type'    => 'checkbox',
							'key'     => 'display_last_login',
							'label'   => __( '사용자 컬럼 확장', 'wskl' ),
							'desc'    => __( '모든 사용자 목록에 최근 로그인 일자를 표시합니다.', 'wskl' ),
							'default' => '',
						),
						array(
							'type'         => 'role_checkbox',
							'key'          => 'target_role',
							'label'        => __( '대상 역할', 'wskl' ),
							'desc'         => __( '선택한 사용자 역할에만 휴면 계정 관리를 합니다.', 'wskl' ),
							'default'      => '',
							'role_exclude' => array( 'administrator', 'wskl_deactivated' ),
						),
					),
				),
				array(
					'title'  => '',
					'id'     => 'email',
					'fields' => array(
						array(
							'type'     => 'input',
							'key'      => 'sender_address',
							'label'    => __( '발신 이메일', 'wskl' ),
							'desc'     => sprintf(
								__( '발신자 주소를 별도로 설정할 수 있습니다. 비워두면 사이트 관리자의 이메일(%s)을 씁니다.', 'wskl' ),
								get_bloginfo( 'admin_email' )
							),
							'attrs'    => array(
								'type'  => 'text',
								'class' => 'text',
							),
							'default'  => '',
							'sanitize' => 'sanitize_email',
						),
						array(
							'type'     => 'input',
							'key'      => 'sender_name',
							'label'    => __( '발신자 이름', 'wskl' ),
							'desc'     => sprintf(
								__( '발신자 이름을 별도로 설정할 수 있습니다. 비워두면 사이트 제목(%s)을 씁니다.', 'wskl' ),
								get_bloginfo( 'name' )
							),
							'attrs'    => array(
								'type'  => 'text',
								'class' => 'text',
							),
							'default'  => '',
							'sanitize' => 'sanitize_text_field',
						),
						array(
							'type' => 'test_email',
						),
					),
					'footer' => array(
						'type' => 'footer_area',
					),
				),
			),
		);
	}

	public function update_settings() {

		parent::update_settings();

		if ( wskl_GET( 'tab' ) !== $this->id || wskl_POST( 'action' ) != $this->action ) {
			return;
		}

		$interval = $this->get_option( 'interval' );

		if ( $interval ) {
			$this->schedule_event();
		} else {
			$this->cancel_event();
		}
	}

	public function schedule_event() {

		$this->cancel_event();

		$next_scheduled = wskl_get_midnight_timestamp() + DAY_IN_SECONDS;

		wp_schedule_event( $next_scheduled, 'wskl_inactive_accounts_check_interval', 'wskl_inactive_accounts_check' );
	}

	public function cancel_event() {

		wp_clear_scheduled_hook( 'wskl_inactive_accounts_check' );
	}

	/**
	 * @callback
	 * @action    wskl_wp_members_section_footer_footer_area
	 */
	public function section_footer_area() {

		echo '<br/>';
		echo '<h4>' . __( '최근 휴면 계정 작업 기록', 'wskl' ) . '</h4>';

		$recent_jobs = wskl_get_option( 'inactive-accounts_recent_jobs' );

		if ( ! $recent_jobs ) {
			echo __( '작업 기록이 없습니다.' );
		} else {
			krsort( $recent_jobs );
			include 'recent-jobs-code.php';
		}

		echo '<br/>';
		echo '<h4>' . __( '휴면 계정을 처음 사용하는 분께 알림', 'wskl' ) . '</h4>';
		echo '<p>' . __(
				'워드프레스와 우커머스는 \'마지막 로그인\' 시각을 기록하지 않습니다. 그러므로 본 모듈을 처음 작동시킨 시점에는 마지막 로그인 시각 데이터가 존재하지 않습니다.<br/>로그인 시각 데이터는 모듈 작동 중 각기 회원이 로그인한 시점에 매번 갱신됩니다.',
				'wskl'
			) . '</p>';
		echo '<p>' . __(
				'또한 본 모듈은 정상적인 휴면 관리를 위해 검사 주기마다 회원의 누락된 마지막 로그인 시간을 채워 넣습니다.<br/>이 때 마지막 로그인 시각은 검사 당시 시간으로 간주됩니다.',
				'wskl'
			) . '</p>';
		echo '<p>' . __(
				'모든 설정값이 제대로 채워져 있어야 정상 동작합니다. 그리고 설정 저장 버튼을 누르면 검사 시간이 재편성됩니다.<br/>최초 검사 시간은 다음날 자정인 00시 00분부터 실행되며 매 검사 주기마다 반복됩니다. 단, 워드프레스의 크론은 사용자 방문에 의해 동작하므로 약간씩 오차가 발생할 수도 있습니다.',
				'wskl'
			) . '</p>';

		echo '<h4>' . __( '계정 복구에 대해', 'wskl' ) . '</h4>';
		echo '<p>' . __( '휴면 처리된 계정 복구는 WP-Members 플러그인의 패스워드 복구 기능을 이용해 진행할 수 있습니다.', 'wskl' )
		     . '</p>';
	}

	/**
	 * @callback
	 * @action    wskl_wp_members_field_test_email
	 */
	public function field_test_email() {

		include 'test-email-code.php';
	}

	/**
	 * @callback
	 * @action    wp_ajax_wskl_inactive-accounts_test_email
	 */
	public function send_test_email() {

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], '_wpnonce' ) ) {
			die();
		}

		add_action(
			'wp_mail_failed',
			function ( WP_Error $wp_error ) {

				$message = sprintf(
					'wp_mail failed: %s %s. Error data: %s',
					$wp_error->get_error_code(),
					$wp_error->get_error_message(),
					print_r( $wp_error->get_error_data(), TRUE )
				);
				error_log( $message );
			}
		);

		add_filter( 'wp_mail_content_type', 'wskl_email_content_type', 10, 0 );
		add_filter( 'wp_mail_from', 'wskl_email_from' );
		add_filter( 'wp_mail_from_name', 'wskl_email_from_name' );

		$mail_address = wskl_get_option( 'inactive-accounts_sender_address' );
		if ( ! is_email( $mail_address ) ) {
			_e( '메일 주소가 정확하지 않습니다.', 'wskl' );
		}

		if ( wp_mail( $mail_address, __( '휴면 계정 테스트 메일입니다.', 'wskl' ), __( '휴면 계정 테스트 메일입니다.', 'wskl' ) ) ) {
			_e( '테스트 메일을 보냈습니다. 스팸 메일함 확인도 잊지 마세요.', 'wskl' );
		} else {
			_e( '메일 발송 에러. 로그를 확인하세요.', 'wskl' );
		}

		remove_filter( 'wp_mail_content_type', 'wskl_email_content_type' );
		remove_filter( 'wp_mail_from', 'wskl_email_from' );
		remove_filter( 'wp_mail_from_name', 'wskl_email_from_name' );

		die();
	}

	/**
	 * @callback
	 * @action    wp_ajax_manual_cron_job
	 */
	public function do_manual_cron_job() {

		wskl_verify_nonce( 'manual_cron_job_nonce', $_POST['manual_cron_job_nonce'] );

		do_action( 'wskl_inactive_accounts_check' );

		die();
	}
}
