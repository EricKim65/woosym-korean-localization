<?php


class InactiveAccountTest extends WP_UnitTestCase {

	private $user;

	public function setUp() {

		$this->user = $this->factory->user->create_and_get(
			array(
				'user_login'   => 'inactive_account_test',
				'user_email'   => 'inactive_account_test@changwoo.pe.kr',
				'display_name' => 'test user',
				'role'         => 'customer',
			)
		);
	}

	public function test() {

		$this->assertTrue( TRUE );
	}
}