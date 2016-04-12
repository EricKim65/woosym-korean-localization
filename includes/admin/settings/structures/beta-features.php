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
	),
);

return $beta_features;