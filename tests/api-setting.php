<?php

define( 'PAYMENT_API_KEY', 'payment-c9b661e154521514adecd81c792bf342' );

function update_test_cassandra_url() {

	update_option(
		wskl_get_option_name( 'develop_cassandra_url' ),
		'http://localhost:8000/api/v1'
	);
}