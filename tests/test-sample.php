<?php


class SampleTest extends WP_UnitTestCase {

	function test_sample() {

		// replace this with some actual testing code
		$this->assertTrue( TRUE );
	}

	function test_prefix_defined() {

		$this->assertTrue( defined( 'WSKL_PREFIX' ) );
	}

	function test_wskl_get_option_name() {

		$option_name = 'test_option';

		$prefixed_options_name = wskl_get_option_name( $option_name );

		$this->assertTrue(
			$prefixed_options_name === WSKL_PREFIX . $option_name
		);
	}

	function test_wskl_is_option_enabled() {

		$option_name          = 'test_option';
		$prefixed_option_name = wskl_get_option_name( $option_name );

		update_option( $prefixed_option_name, 'yes' );
		$this->assertTrue( get_option( $prefixed_option_name ) === 'yes' );
		$this->assertTrue( TRUE === wskl_is_option_enabled( $option_name ) );

		update_option( $prefixed_option_name, '1' );
		$this->assertTrue( get_option( $prefixed_option_name ) === '1' );
		$this->assertTrue( TRUE === wskl_is_option_enabled( $option_name ) );

		update_option( $prefixed_option_name, 'true' );
		$this->assertTrue( get_option( $prefixed_option_name ) === 'true' );
		$this->assertTrue( TRUE === wskl_is_option_enabled( $option_name ) );

		update_option( $prefixed_option_name, 'no' );
		$this->assertTrue( get_option( $prefixed_option_name ) === 'no' );
		$this->assertTrue( FALSE === wskl_is_option_enabled( $option_name ) );

		update_option( $prefixed_option_name, '0' );
		$this->assertTrue( get_option( $prefixed_option_name ) === '0' );
		$this->assertTrue( FALSE === wskl_is_option_enabled( $option_name ) );

		update_option( $prefixed_option_name, 'false' );
		$this->assertTrue( get_option( $prefixed_option_name ) === 'false' );
		$this->assertTrue( FALSE === wskl_is_option_enabled( $option_name ) );
	}

	function test_wskl_get_option() {

		$option_name          = 'test_option';
		$prefixed_option_name = wskl_get_option_name( $option_name );

		update_option( $prefixed_option_name, 'yes' );
		$this->assertTrue( get_option( $prefixed_option_name ) === 'yes' );
		$this->assertTrue( 'yes' === wskl_get_option( $option_name ) );

		update_option( $prefixed_option_name, 'no' );
		$this->assertTrue( get_option( $prefixed_option_name ) === 'no' );
		$this->assertTrue( 'no' === wskl_get_option( $option_name ) );

		update_option( $prefixed_option_name, '0' );
		$this->assertTrue( get_option( $prefixed_option_name ) === '0' );
		$this->assertTrue( '0' === wskl_get_option( $option_name ) );

		update_option( $prefixed_option_name, 'test-value' );
		$this->assertTrue(
			get_option( $prefixed_option_name ) === 'test-value'
		);
		$this->assertTrue( 'test-value' === wskl_get_option( $option_name ) );
	}

	function test_wskl_get_from_assoc() {

		$assoc_var = array(
			'key01' => 'Espresso',
			'key02' => '1503',
			'key03' => 'http://www.example.com/?topping=cream',
		);

		$this->assertEquals(
			wskl_get_from_assoc( $assoc_var, 'key01' ),
			'Espresso'
		);
		$this->assertEquals(
			wskl_get_from_assoc( $assoc_var, 'key01', 'absint' ),
			0
		);

		$this->assertEquals(
			wskl_get_from_assoc( $assoc_var, 'key02', 'absint' ),
			1503
		);

		$this->assertEquals(
			wskl_get_from_assoc( $assoc_var, 'key03', 'esc_url' ),
			esc_url( $assoc_var['key03'] )
		);

		$this->assertEquals(
			wskl_get_from_assoc( $assoc_var, 'key999', '', 'default-value' ),
			'default-value'
		);
	}
}

