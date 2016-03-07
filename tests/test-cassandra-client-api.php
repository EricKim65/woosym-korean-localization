<?php

include_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );

use wskl\lib\cassandra;
use wskl\lib\cassandra\ClientAPI;


class CassandraAPITest extends WP_UnitTestCase {

	protected $paymentApiKey = '';

	public function setUp() {

		$this->paymentApiKey = 'payment-c9b661e154521514adecd81c792bf342';  // key for local cassandra!

		// update casper local site. Note that WSKL_DEBUG must be true.
		update_option(
			wskl_get_option_name( 'develop_cassandra_url' ),
			'http://localhost:8000/api/v1'
		);
	}

	// WSKL_DEBUG = TRUE 확인
	public function test_wskl_debug() {

		$this->assertTrue( wskl_debug_enabled() );
	}

	// CASSANDRA (LOCAL) 과 연결 체크
	public function test_connection() {

		$url = \wskl\lib\cassandra\wskl_get_host_api_url(
		       ) . '/auth/hello'; // use easter egg.

		$response = \wskl\lib\cassandra\Rest_Api_Helper::request( $url, 'GET' );

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
		$this->assertTrue( $oir instanceof cassandra\OrderItemRelation );

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
		$oir = ClientAPI::activate(
			'payment',
			$this->paymentApiKey,
			site_url(),
			'',
			TRUE
		);

		$oir = ClientAPI::verify( 'payment', $this->paymentApiKey, site_url() );

		// var_dump( 'oir: ' . print_r( $oir, true ) );

		$this->assertTrue( $oir instanceof cassandra\OrderItemRelation );
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
	}
}