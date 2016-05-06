<?php


class WSKL_Inactive_Accounts_Email {

	public static function init() {

		add_action( 'wp_mail_failed', array( __CLASS__, 'handle_email_error' ) );
	}

	public static function handle_email_error( WP_Error $wp_error ) {

		$message = sprintf(
			'wp_mail failed: %s %s. Error data: %s',
			$wp_error->get_error_code(),
			$wp_error->get_error_message(),
			print_r( $wp_error->get_error_data(), TRUE )
		);

		error_log( $message );
	}

	public static function send_email( array &$recipients, $post_id, WSKL_Inactive_Accounts_Shortcodes $shortcodes ) {

		$post = WP_Post::get_instance( $post_id );

		if ( ! $post ) {
			error_log( "\$post (ID: $post_id) returned false. Notification halted." );

			return;
		}

		$success_count = 0;
		$failure_count = 0;

		/** @var WP_User|array|string $recipient if it is an array, 'name', and 'addr' keys are set. */
		foreach ( $recipients as $recipient ) {

			$shortcodes->set_recipient( $recipient );

			$address = self::get_recipient_address( $recipient );
			$subject = do_shortcode( $post->post_title );
			$message = wptexturize( wpautop( do_shortcode( $post->post_content ) ) );

			if ( wp_mail( $address, $subject, $message ) ) {
				++ $success_count;
			} else {
				++ $failure_count;
			}
		}

		error_log( "Message sent to $success_count users. $failure_count failed." );
	}

	public static function get_recipient_address( $recipient ) {

		if ( $recipient instanceof WP_User ) {

			return "\"{$recipient->display_name}\" <{$recipient->user_email}>";

		} else if ( is_array( $recipient ) ) {

			$name = wskl_get_from_assoc( $recipient, 'name' );
			$addr = wskl_get_from_assoc( $recipient, 'addr' );

			if ( $name ) {
				return "\"{$name}\" <$addr>";
			} else {
				return $addr;
			}
		}

		return $recipient;
	}
}


WSKL_Inactive_Accounts_Email::init();
