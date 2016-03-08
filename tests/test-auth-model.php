<?php

include_once( 'api-setting.php' );
include_once( WSKL_PATH . '/includes/lib/auth/class-auth-model.php' );
include_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );


class AuthModelTest extends WP_UnitTestCase {

	//	protected $paymentApiKey = PAYMENT_API_KEY;
	//
	//	protected $oir;
	//
	//	public function setUp() {
	//
	//		update_test_cassandra_url();
	//
	//		$this->oir = ClientAPI::activate(
	//			'payment',
	//			$this->paymentApiKey,
	//			site_url(),
	//			'',
	//			TRUE
	//		);
	//
	//		$this->assertTrue( $this->oir instanceof OrderItemRelation );
	//	}
	//
	//	public function test_modelOir() {
	//
	//		$auth = new Auth_Model( 'payment' );
	//
	//		print_r( 'oir is null? ' . print_r( is_null( $this->oir ), true ) );
	//
	//		$auth->set_oir( $this->oir );
	//
	//		$this->assertSame( $auth->get_oir(), $this->oir );
	//	}

	public function test_dummy() {

		$this->assertTrue( TRUE );
	}
}