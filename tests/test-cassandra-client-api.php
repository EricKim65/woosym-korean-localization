<?php

include_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );
include_once( 'api-setting.php' );

use wskl\lib\cassandra;
use wskl\lib\cassandra\ClientAPI;
use wskl\lib\cassandra\OrderItemRelation;
use wskl\lib\cassandra\Rest_Api_Helper;


class CassandraClientAPITest extends WP_UnitTestCase {

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
		$this->assertTrue(
			$oir instanceof OrderItemRelation,
			'$oir is not instance of OrderItemRelation'
		);

		// company name
		$this->assertEquals(
			$oir->get_domain()->get_company_name(),
			$company_name
		);

		// API key
		$this->assertEquals(
			$oir->get_key()->get_key(),
			$this->paymentApiKey,
			'key mismatch'
		);

		// activation
		$this->assertTrue( $oir->get_key()->is_active(), 'key not activated' );
	}

	public function test_deactivatePaymentAPIKey() {

		$company_name = 'MY COMPANY';

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

	public function test_verifyActivation() {

		// activate && verify --> get an oir
		ClientAPI::activate(
			'payment',
			$this->paymentApiKey,
			site_url(),
			'',
			TRUE
		);

		$oir = ClientAPI::verify( 'payment', $this->paymentApiKey, site_url() );

		$this->assertTrue( $oir instanceof OrderItemRelation );
		$this->assertTrue( $oir->get_key()->is_active() );
	}

	public function test_verifyDeactivation() {

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
	}
}