<?php

include_once( WSKL_PATH . '/includes/lib/cassandra-php/class-api-handler.php' );
include_once( 'api-setting.php' );

use wskl\lib\cassandra;
use wskl\lib\cassandra\ClientAPI;


class CassandraClientAPIWrongAddressTest extends WP_UnitTestCase {

	protected $paymentApiKey = '';

	public function setUp() {

		$this->paymentApiKey = PAYMENT_API_KEY;  // key for local cassandra!

		// test with wrong api url
		// 서버 자체가 연결되지 않음은 서버가 아운되거나
		// 서버가 500 에러를 내는 경우와 유사하다.
		update_option(
			wskl_get_option_name( 'develop_cassandra_url' ),
			'http://localhost/wrong/api/url/wrong'
		);
	}

	public function tearDown() {

		update_test_cassandra_url();
	}

	public function test_url_is_expected() {

		$opt = get_option( wskl_get_option_name( 'develop_cassandra_url' ) );

		$this->assertEquals( $opt, 'http://localhost/wrong/api/url/wrong' );
	}

	public function test_verifyWrongAddress() {


		$oir = ClientAPI::verify( 'payment', $this->paymentApiKey, site_url() );

		$this->assertFalse( $oir );
	}
}