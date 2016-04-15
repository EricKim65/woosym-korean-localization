<?php

require_once( WSKL_PATH . '/includes/libraries/dabory-sms/abstracts/class-wskl-dabory-sms-provider.php' );


final class WSKL_Dabory_SMS_Provider_MDalin extends WSKL_Dabory_SMS_Provider {

	private $remote_id;
	private $remote_pass;
	private $sender_phone;

	public function __construct( $remote_id, $remote_pass, $sender_phone ) {

		$this->host = 'http://www.mdalin.co.kr';

		$this->remote_id    = $remote_id;
		$this->remote_pass  = $remote_pass;
		$this->sender_phone = $sender_phone;
	}

	public static function factory() {

		$id       = wskl_get_option( 'sms_provider_id' );
		$password = wskl_get_option( 'sms_provider_password' );
		$sender   = wskl_get_option( 'sms_sender_phone' );

		return new static( $id, $password, $sender );
	}

	/**
	 * @param array  $args
	 * @param string $message_type
	 *
	 * @throws Exception
	 *
	 * @return array 서버 리턴 값
	 *               0: 결과 코드
	 *               1: 결과 메시지
	 *               2: 잔여 건수
	 *               3: etc1
	 *               4: etc2
	 */
	public function send_message( array $args, $message_type = 'sms' ) {

		$args = wp_parse_args(
			$args,
			array(
				'remote_id'       => $this->remote_id,
				'remote_pass'     => $this->remote_pass,
				// 'remote_returnurl' => '',
				'remote_callback' => $this->sender_phone,
				'remote_msg'      => '',
			)
		);

		if ( strtolower( $message_type ) == 'sms' ) {
			$url = $this->host . '/Remote/RemoteSms.html';
		} else {
			$url = $this->host . '/Remote/RemoteMms.html';
		}

		$response = $this->send_request( $url, 'POST', $args );

		$result = explode( '|', $response );
		if ( count( $result ) != 5 ) {
			$message = 'Result string could not be converted into an array that length is 5. Response body: ' . $response;
			error_log( $message );
			throw new Exception( $message );
		}

		$this->check_response( $result );

		return $result;
	}

	protected function check_response( $result ) {

		if ( trim( $result[0] ) !== '0000' ) {
			$message = 'Request failure. Code: ' . $result[0] . ' Message: ' . $result[1];
			error_log( $message );
			throw new Exception( $message );
		}
	}

	/**
	 * @param array $args
	 *
	 * @throws BadMethodCallException
	 * @throws Exception
	 *
	 * @return int
	 */
	public function point_check( array $args ) {

		$url            = $this->host . '/Remote/RemoteCheck.html';
		$remote_request = strtolower( $args['remote_request'] );

		if ( ! in_array( $remote_request, array( 'sms', 'lms', 'mms' ) ) ) {
			throw new BadMethodCallException( 'type parameter should be one of these: sms, lms, mms' );
		}

		$response = $this->send_request(
			$url,
			'POST',
			array(
				'remote_id'      => $this->remote_id,
				'remote_pass'    => $this->remote_pass,
				'remote_request' => $remote_request,
			)
		);

		$result = explode( '|', $response );
		if ( count( $result ) != 3 ) {
			$message = 'Result string could not be converted into an array that length is 3. Response body: ' . $response;
			error_log( $message );
			throw new Exception( $message );
		}

		$this->check_response( $result );

		// $result_code    = $result[0];
		// $result_message = $result[1];
		$rest_point = intval( $result[2] );

		return $rest_point;
	}
}