<?php

return array(
	'title'       => __( '개발자용', 'wskl' ),
	'description' => __( '개발자용 탭 WP_DEBUG = TRUE 면 나오는 탭', 'wskl' ),
	'fields'      => array(
		array(
			'id'          => 'enable_debugging',
			'label'       => __( 'Enable DEBUGGING MODE', 'wskl' ),
			'description' => __( '', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'develop_xdebug_always_on',
			'label'       => __( 'Always Enable XDEBUG', 'wskl' ),
			'description' => __( '', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'develop_xdebug_session_id',
			'label'       => __( 'XDEBUG SESSION ID', 'wskl' ),
			'description' => __( 'Value will set by cookie!', 'wskl' ),
			'type'        => 'text',
			'default'     => '',
		),
		array(
			'id'          => 'develop_casper_url',
			'label'       => __( 'CASPER URL OVERRIDE', 'wskl' ),
			'description' => __( '', 'wskl' ),
			'type'        => 'text',
			'default'     => '',
		),
	),
);