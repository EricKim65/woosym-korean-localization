<?php


class WSKL_Dabory_Members_Withdrawal {

	public static function init() {

		add_action( 'wp_loaded', array( __CLASS__, 'process_withdrawal' ), 20 );
	}

	public static function output_form( $completed_message ) {

		ob_start();

		$status = wskl_GET( 'status' );

		// user is logged out when the withdrawal process is completed.
		if ( $status == 'complete' ) {
			$context = array(
				'status'  => 'complete',
				'message' => $completed_message,
			);
			wskl_get_template( 'dabory-members-withdrawal.php', $context );

			return ob_get_clean();
		}

		if ( ! is_user_logged_in() ) {
			wskl_get_template(
				'dabory-members-withdrawal.php',
				array(
					'status'  => 'failure',
					'message' => __( '먼저 로그인 하세요', 'wskl' ),
				)
			);

			return ob_get_clean();
		}

		if ( $status == 'failure' ) {
			$context = array(
				'status'  => 'failure',
				'message' => wskl_GET( 'message' ),
			);
			wskl_get_template( 'dabory-members-withdrawal.php', $context );

			return ob_get_clean();
		}


		wskl_get_template( 'dabory-members-withdrawal.php' );

		return ob_get_clean();
	}

	public static function process_withdrawal() {

		$action = wskl_POST( 'action' );

		if ( $action != 'dabory_members_withdrawal' ) {
			return;
		}

		self::evaluate_and_redirect_if_failed( is_user_logged_in(), __( '먼저 로그인 하세요', 'wskl' ) );

		self::evaluate_and_redirect_if_failed(
			wp_verify_nonce( $_POST['dabory_members_withdrawal'], 'dabory_members_withdrawal' ),
			__( 'Nonce 인증에 실패했습니다.', 'wskl' )
		);

		$user     = wp_get_current_user();
		$password = wskl_POST( 'password' );
		$reason   = wskl_POST( 'reason', 'sanitize_text_field' );

		self::evaluate_and_redirect_if_failed(
			wp_check_password( $password, $user->user_pass, $user->ID ),
			__( '패스워드가 일치하지 않습니다.', 'wskl' )
		);

		if ( wskl_is_option_enabled( 'members_delete_after_withdrawal' ) ) {
			if ( ! function_exists( 'wp_delete_user' ) ) {
				include_once( ABSPATH . 'wp-admin/includes/user.php' );
			}
			// 멤버 정말로 삭제
			wp_logout();
			wp_delete_user( $user->ID );
		} else {
			// 역할을 바꿔 탈퇴 회원으로 간주
			update_user_meta( $user->ID, 'withdrawal_reason', $reason );
			$user->set_role( 'withdrawer' );
			wp_logout();
		}

		// 탈퇴 완료 메시지
		wp_redirect(
			add_query_arg(
				array( 'status' => 'complete' ),
				$_SERVER['REQUEST_URI']
			)
		);
		exit;
	}

	private static function evaluate_and_redirect_if_failed( $expr, $message ) {

		if ( ! $expr ) {
			wp_redirect(
				add_query_arg(
					array(
						'status'  => 'failure',
						'message' => urlencode( $message ),
					),
					$_SERVER['REQUEST_URI']
				)
			);
			exit;
		}
	}
}


WSKL_Dabory_Members_Withdrawal::init();