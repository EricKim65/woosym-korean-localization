<?php


class WSKL_Inactive_Accounts_Cron_Jobs {

	public function __construct() {

	}

	public function do_inactive_account_filtering() {

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
		error_log( sprintf( 'Alert job finished. Execution time: %.04fms', ( $finish - $start ) * 1000 ) );

		$start = microtime( TRUE );
		$this->process_deactivation( $to_disabled, $target_role );
		$finish = microtime( TRUE );
		error_log( sprintf( 'Deactivate job finished. Execution time: %.04fms', ( $finish - $start ) * 1000 ) );
	}

	public function process_alert( array &$to_notified ) {

		$post_id    = wskl_get_option( 'inactive-accounts_post_alert' );
		$shortcodes = WSKL()->submodules()->get_submodule( 'inactive-accounts' )->shortcodes;

		$now = time();
		foreach ( $to_notified as $user ) {
			wskl_set_user_alerted( $user->ID, $now );
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
			wskl_deactivate_account( $user, $keys_to_preserve, $target_role );
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