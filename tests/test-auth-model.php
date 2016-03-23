<?php

include_once( 'api-setting.php' );
include_once( WSKL_PATH . '/includes/lib/auth/class-wskl-auth-info.php' );
include_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );

use wskl\lib\cassandra\ClientAPI;
use wskl\lib\cassandra\OrderItemRelation;


class AuthModelTest extends WP_UnitTestCase {

	protected $paymentApiKey = PAYMENT_API_KEY;

	protected $oir;

	public function setUp() {

		update_test_cassandra_url();

		$this->oir = ClientAPI::activate(
			'payment',
			$this->paymentApiKey,
			site_url(),
			'',
			TRUE
		);

		$this->assertTrue( $this->oir instanceof OrderItemRelation );
	}

	public function test_modelOir() {

		$auth = new \WSKL_Auth_Info( 'payment' );

		$auth->set_oir( $this->oir );

		$this->assertSame( $auth->get_oir(), $this->oir );
	}
}