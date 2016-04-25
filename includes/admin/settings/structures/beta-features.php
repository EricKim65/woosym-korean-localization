<?php

$beta_features = array(
	'title'       => __( '다보리 베타', 'wskl' ),
	'description' => __( '특정 기능을 위한 베타 기능', 'wskl' ),
	'fields'      => array(
		array(
			'id'          => 'enable_combined_tax_kcp',
			'label'       => __( 'KCP 복합과세', 'wskl' ),
			'description' => __( 'KCP 복합과세용 포맷으로 결제 가능', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'bacs_callback_uid',
			'label'       => __( 'APIBOX 아이디', 'wskl' ),
			'description' => __( '무통장입금 자동통보 콜백정보를 확인하기 위해 필요.', 'wskl' ),
			'type'        => 'text',
			'default'     => '',
		),
	),
);

return $beta_features;