<?php


class WSKL_Inactive_Accounts_Cron_Jobs {

	private $cron_job_id = NULL;

	public function __construct() {

		$this->cron_job_id = time();
	}

	public static function content_type() {

		return 'text/html';
	}

	public static function mail_from( $from_email ) {

		$mail_address = wskl_get_option( 'inactive-accounts_sender_address' );
		if ( $mail_address && is_email( $mail_address ) ) {
			return $mail_address;
		}

		return $from_email;
	}

	public static function mail_from_name( $from_name ) {

		$name = wskl_get_option( 'inactive-accounts_sender_name' );
		if ( $name ) {
			return $name;
		}

		return $from_name;
	}

	public function __destruct() {
	}

	public function do_inactive_account_filtering() {

		add_filter( 'wp_mail_content_type', array( __CLASS__, 'content_type' ), 10, 0 );
		add_filter( 'wp_mail_from', array( __CLASS__, 'mail_from' ) );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'mail_from_name' ) );

		$post_alert        = wskl_get_option( 'inactive-accounts_post_alert' );
		$post_deactivation = wskl_get_option( 'inactive-accounts_post_deactivation' );
		$active_span       = wskl_get_option( 'inactive-accounts_active_span' );
		$alert             = wskl_get_option( 'inactive-accounts_alert' );
		$target_role       = wskl_get_option( 'inactive-accounts_target_role' );

		if ( $post_alert < 1 || $post_deactivation < 1 || ! $active_span || ! $alert || ! $target_role ) {
			$message = __METHOD__ . ": Values are net properly set.\n";
			$message .= "  post_alert=$post_alert,\n";
			$message .= "  post_deactivation=$post_deactivation,\n";
			$message .= "  active_span=$active_span,\n";
			$message .= "  alert=$alert\n";
			$message .= "  target_role=$target_role";
			error_log( $message );

			return;
		}

		$now           = time();
		$alert_ts      = $now - ( $active_span * DAY_IN_SECONDS ) + ( $alert * DAY_IN_SECONDS );
		$deactivate_ts = $now - ( $active_span * DAY_IN_SECONDS );

		$to_notified = wskl_get_alert_staged_users( $deactivate_ts, $alert_ts, $target_role );
		$to_disabled = wskl_get_deactivation_staged_users( $deactivate_ts, $target_role );

		$message = "Before inactive-accounts email notification.";
		$message .= count( $to_notified ) . " users will be alerted. ";
		$message .= count( $to_disabled ) . " users will be disabled. ";
		$message .= "Total " . ( count( $to_notified ) + count( $to_disabled ) ) . " users.";
		error_log( $message );

		$start = microtime( TRUE );
		$this->process_alert( $to_notified );
		$finish = microtime( TRUE );

		$notification_spent = $finish - $start;
		error_log( sprintf( 'Alert job finished. Execution time: %.04fms', $notification_spent * 1000 ) );

		$start = microtime( TRUE );
		$this->process_deactivation( $to_disabled, $target_role );
		$finish = microtime( TRUE );

		$deactivation_spent = $finish - $start;
		error_log( sprintf( 'Deactivate job finished. Execution time: %.04fms', ( $deactivation_spent ) * 1000 ) );

		remove_filter( 'wp_mail_content_type', array( __CLASS__, 'content_type' ) );
		remove_filter( 'wp_mail_from', array( __CLASS__, 'mail_from' ) );
		remove_filter( 'wp_mail_from_name', array( __CLASS__, 'mail_from_name' ) );

		$recent_jobs                       = wskl_get_option( 'inactive-accounts_recent_jobs', array() );
		$recent_jobs[ $this->cron_job_id ] = array(
			'timestamp'          => $this->cron_job_id,
			'total_notified'     => count( $to_notified ),
			'total_disabled'     => count( $to_disabled ),
			'notification_spent' => $notification_spent,  // microsecond
			'deactivation_spent' => $deactivation_spent,  // microsecond
		);

		$cnt = count( $recent_jobs );
		if ( $cnt > 7 ) {
			$recent_jobs = array_slice( $recent_jobs, - 7, 7 );
		}
		ksort( $recent_jobs );
		wskl_update_option( 'inactive-accounts_recent_jobs', $recent_jobs );
	}

	public function process_alert( array &$to_notified ) {

		$post_id    = wskl_get_option( 'inactive-accounts_post_alert' );
		$shortcodes = WSKL()->submodules()->get_submodule( 'inactive-accounts' )->shortcodes;

		foreach ( $to_notified as $user ) {
			wskl_set_user_alerted( $user->ID, $this->cron_job_id );
		}

		WSKL_Inactive_Accounts_Email::send_email( $to_notified, $post_id, $shortcodes );
	}

	public function process_deactivation( array &$to_disabled, $target_role ) {

		$post_id    = wskl_get_option( 'inactive-accounts_post_deactivation' );
		$shortcodes = WSKL()->submodules()->get_submodule( 'inactive-accounts' )->shortcodes;

		$keys_to_preserve = array(
			wskl_get_option_name( 'inactive-accounts_alerted' ),
		);

		foreach ( $to_disabled as $user ) {
			wskl_deactivate_account( $user, $this->cron_job_id, $keys_to_preserve, $target_role );
		}

		WSKL_Inactive_Accounts_Email::send_email( $to_disabled, $post_id, $shortcodes );
	}

	public function fill_user_login_field() {

		$target_role = wskl_get_option( 'inactive-accounts_target_role' );

		if ( ! $target_role ) {
			$message = __METHOD__ . ": Values are net properly set.\n";
			$message .= "  target_role=$target_role";
			error_log( $message );

			return;
		}

		$key   = wskl_get_option_name( 'last_login' );
		$users = wskl_get_users_with_missing_meta_key( $key, $target_role );
		$now   = time();

		/** @var WP_User $user */
		foreach ( $users as $user ) {
			update_user_meta( $user->ID, $key, $now );
		}
	}
}