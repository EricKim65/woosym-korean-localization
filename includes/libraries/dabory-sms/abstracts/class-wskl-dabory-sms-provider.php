<?php

wskl_check_abspath();


abstract class WSKL_Dabory_SMS_Provider {

	protected $host = '';

	abstract public function send_message( array $args );

	abstract public function point_check( array $args );

	abstract protected function check_response( $result );

	protected function send_request( $url, $method = 'GET', array $body = array() ) {

		$response = wp_remote_post(
			$url,
			array(
				'method' => $method,
				'body'   => &$body,
			)
		);

		if ( is_wp_error( $response ) ) {
			$msg = 'Response is WP_Error object: ' . $response->get_error_message();
			error_log( $msg );
			throw new Exception( $msg );
		}

		return wp_remote_retrieve_body( $response );
	}
}
