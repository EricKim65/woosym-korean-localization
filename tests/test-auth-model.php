<?php

include_once( 'api-setting.php' );
include_once( WSKL_PATH . '/includes/lib/auth/class-auth-model.php' );
include_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );

use wskl\lib\auth\Auth_Model;
use wskl\lib\cassandra\ClientAPI;
use wskl\lib\cassandra\OrderItemRelation;


class AuthModelTest extends WP_UnitTestCase {

	protected $paymentApiKey = '';

	protected $oir;

	public function setUp() {

		$this->paymentApiKey = PAYMENT_API_KEY;  // key for local cassandra!

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

	public function test_construct() {

		$auth = new Auth_Model( 'payment' );
		$auth->set_oir( $this->oir );

		$this->assertSame( $auth->get_oir(), $this->oir );
	}
}