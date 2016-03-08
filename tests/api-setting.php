<?php

define( 'DEFAULT_CASSANDRA_URL', 'http://localhost:8000/api/v1' );
define( 'PAYMENT_API_KEY', 'payment-c9b661e154521514adecd81c792bf342' );
define( 'MARKETING_API_KEY', 'marketing-a13a26f54ab984047e9dcd8e4b8bfdf2' );


function update_test_cassandra_url() {

	update_option(
		wskl_get_option_name( 'develop_cassandra_url' ),
		DEFAULT_CASSANDRA_URL
	);
}