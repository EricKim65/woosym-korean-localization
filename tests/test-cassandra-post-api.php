<?php

include_once( WSKL_PATH . '/includes/lib/cassandra-php/api-handler.php' );
include_once( 'api-setting.php' );

use wskl\lib\cassandra;
use wskl\lib\cassandra\ClientAPI;
use wskl\lib\cassandra\PostAPI;


class CassandraPostAPITest extends WP_UnitTestCase {

	protected $marketingApiKey = MARKETING_API_KEY;

	protected $testPost = NULL;

	public function setUp() {

		update_test_cassandra_url();

		ClientAPI::activate(
			'marketing',
			$this->marketingApiKey,
			site_url(),
			'',
			TRUE
		);

		$this->testPost = $this->factory->post->create(
			array(
				'post_title' => 'POST TITLE',
				'post_content' => 'TEST POST',
				'post_author' => '1',
			)
		);
	}

	public function test_sendPost() {

		PostAPI::send_post(
			'marketing',
			$this->marketingApiKey,
			site_url(),
			'1',
			$this->testPost
		);
	}
}