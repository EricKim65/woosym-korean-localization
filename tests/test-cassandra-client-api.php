<?php

include_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );
include_once( 'api-setting.php' );

use wskl\lib\cassandra;
use wskl\lib\cassandra\ClientAPI;
use wskl\lib\cassandra\OrderItemRelation;
use wskl\lib\cassandra\Rest_Api_Helper;


class CassandraAPITest extends WP_UnitTestCase {

	protected $paymentApiKey = '';

	public function setUp() {

		$this->paymentApiKey = PAYMENT_API_KEY;  // key for local cassandra!

		update_test_cassandra_url();
	}

	// WSKL_DEBUG = TRUE 확인
	public function test_wskl_debug() {

		$this->assertTrue( wskl_debug_enabled() );
	}

	// CASSANDRA (LOCAL) 과 연결 체크
	public function test_connection() {

		$url = \wskl\lib\cassandra\wskl_get_host_api_url(
		       ) . '/auth/hello'; // use easter egg.

		$response = Rest_Api_Helper::request( $url, 'GET' );

		$this->assertTrue( $response['code'] == 200 );
		$this->assertTrue( isset( $response['body']->message ) );
	}

	// activate api 확인
	public function test_activatePaymentAPIKey() {

		$company_name = 'MY COMPANY';

		$oir = ClientAPI::activate(
			'payment',
			$this->paymentApiKey,
			site_url(),
			$company_name,
			TRUE
		);

		// is this really an OIR?
		$this->assertTrue( $oir instanceof OrderItemRelation );

		// company name
		$this->assertEquals(
			$oir->get_domain()->get_company_name(),
			$company_name
		);

		// API key
		$this->assertEquals( $oir->get_key()->get_key(), $this->paymentApiKey );

		// activation
		$this->assertTrue( $oir->get_key()->is_active() );

		$oir = ClientAPI::activate(
			'payment',
			$this->paymentApiKey,
			site_url(),
			$company_name,
			FALSE
		);

		$this->assertTrue( $oir instanceof cassandra\OrderItemRelation );

		$this->assertFalse( $oir->get_key()->is_active() );
	}

	public function test_verifyPaymentAPIKey() {

		// activate && verify --> get an oir
		ClientAPI::activate(
			'payment',
			$this->paymentApiKey,
			site_url(),
			'',
			TRUE
		);

		$oir = ClientAPI::verify( 'payment', $this->paymentApiKey, site_url() );

		// var_dump( 'oir: ' . print_r( $oir, true ) );

		$this->assertTrue( $oir instanceof OrderItemRelation );
		$this->assertTrue( $oir->get_key()->is_active() );

		// deactivation && verify --> get null
		ClientAPI::activate(
			'payment',
			$this->paymentApiKey,
			site_url(),
			'',
			FALSE
		);

		$oir = ClientAPI::verify( 'payment', $this->paymentApiKey, site_url() );

		$this->assertNull( $oir );

		// test with wrong api url
		// 서버 자체가 연결되지 않음은 서버가 아운되거나
		// 서버가 500 에러를 내는 경우와 유사하다.
		update_option(
			wskl_get_option_name( 'develop_cassandra_url' ),
			'http://localhost/wrongapiurl/wrong'
		);

		$oir = ClientAPI::verify( 'payment', $this->paymentApiKey, site_url() );

		$this->assertFalse( $oir );

		update_test_cassandra_url();
	}
}