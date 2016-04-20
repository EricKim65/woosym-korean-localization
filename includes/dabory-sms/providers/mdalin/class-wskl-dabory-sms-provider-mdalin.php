<?php

require_once( WSKL_PATH . '/includes/dabory-sms/abstracts/class-wskl-dabory-sms-provider.php' );


/**
 * 문자달인 ( http://www.mdalin.co.kr )의 SMS API.
 *
 * Class WSKL_Dabory_SMS_Provider_MDalin
 */
final class WSKL_Dabory_SMS_Provider_MDalin extends WSKL_Dabory_SMS_Provider {

	private $remote_id;
	private $remote_pass;
	private $sender_phone;
	private $return_url;

	public function __construct( $remote_id, $remote_pass, $sender_phone ) {

		$this->host = 'http://www.mdalin.co.kr';

		$this->remote_id    = $remote_id;
		$this->remote_pass  = $remote_pass;
		$this->sender_phone = $sender_phone;

		$this->return_url = add_query_arg(
			array( 'wc-ajax' => 'mdalin_return' ),
			site_url()
		);
	}

	public static function factory() {

		$id       = wskl_get_option( 'sms_provider_id' );
		$password = wskl_get_option( 'sms_provider_password' );
		$sender   = wskl_get_option( 'sms_sender_phone' );

		return new static( $id, $password, $sender );
	}

	/**
	 * @param array  $args
	 *                - remote_id:           (required) 회원 아이디
	 *                - remote_pass:         (required) 회원 패스워드
	 *                - remote_phone:        (required) 수신번호
	 *                - remote_msg:          (required) 메시지 본문 (sms: max 90bytes, lms: 2000bytes) URLEncode
	 *
	 *                - remote_returnurl:    (optional) 콜백 URL
	 *                                                   - POST['code']: 리턴 코드
	 *                                                   - POST['msg']:  리턴 메시지
	 *                                                   - POST['nums']: 전송된 개수
	 *                                                   - POST['cols']: 잔여 콜 수
	 *                                                   - POST['etc1']: remote_etc1
	 *                                                   - POST['etc2']: remote_etc2
	 *                                                  이 URL을 쓰지 않으면 콘텐트에 | 로 나뉘는 문자열 (크기 5)을 보냄.
	 *                                                  이 값이 사용되면 콜백할 HTML 소스 코드가 전달됨. 성공때만 콜백되는 듯.
	 *
	 *                - remote_num:          (optional) 문자메시지 전송 개수
	 *                - remote_reserve:      (optional) 예약 전송 체크 (1: 예약, 0: 즉시)
	 *                - remote_reservetime:  (optional) 예약시간 (YYYY-MM-DD HH:II:SS)
	 *                - remote_name:         (optional) 문자 수신번호의 이름
	 *                - remote_callback:     (optional) 발신번호
	 *                - remote_subject:      (optional) 문자 제목
	 *                - remote_contents:     (optional) MMS 전송시 이미지 파일명. 미리 문자달인에 업로드된 이미지만 가능.
	 *                - remote_etc1:         (optional) 사용자 정의
	 *                - remote_etc2:         (optional) 사용자 정의
	 *
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
				// 'remote_returnurl' => $this->return_url,
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

		return $result;
	}

	/**
	 * @param array $args
	 *               - remote_request: sms/lms/mms
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

	protected function check_response( $result ) {

		if ( trim( $result[0] ) !== '0000' ) {
			$message = 'Request failure. Code: ' . $result[0] . ' Message: ' . $result[1];
			error_log( $message );
			throw new Exception( $message );
		}
	}
}