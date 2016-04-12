<?php
return array(
	array(
		'id'          => 'enable_testmode',
		'label'       => __( '테스트 모드로 설정', 'wskl' ),
		'description' => __( '결제 테스트 모드로 설정되어 실제적인 결제는 되지 않습니다.',
		                     'wskl' ),
		'type'        => 'checkbox',
		'default'     => '',
	), array(
		'id'          => 'enable_showinputs',
		'label'       => __( '필드보임 설정', 'wskl' ),
		'description' => __( '테스트용으로 사용되므로 일반적인경우 비활성화 해주세요.',
		                     'wskl' ),
		'type'        => 'checkbox',
		'default'     => '',
	), array(
		'id'          => 'checkout_methods',
		'label'       => __( '결제방식 지정', 'wskl' ),
		'description' => __( '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;사용하실 결제방식을 지정해 주십시오.',
		                     'wskl' ),
		'type'        => 'checkbox_multi',
		'options'     => WSKL_Payment_Gates::get_checkout_methods(),
		'default'     => array( 'credit', '신용카드' ),
	), array(
		'id'          => 'enable_https',
		'label'       => __( 'HTTPS 사용', 'wskl' ),
		'description' => __( '결제페이지 스크립트을 HTTPS(보안모드)방식으로 호출합니다. 홈페이지 보안 인증 서비스를 받지 않는 사이트의 경우 무시하셔도 됩니다.', 'wskl' ),
		'type'        => 'checkbox',
		'default'     => '',
	), array(
		'id'          => 'enable_escrow',
		'label'       => __( '에스크로 설정', 'wskl' ),
		'description' => __( '에스크로 방식으로 결제를 진행합니다.', 'wskl' ),
		'type'        => 'checkbox',
		'default'     => '',
	), array(
		'id'          => 'escrow_delivery',
		'label'       => __( '에스크로 예상 배송일', 'wskl' ),
		'description' => __( '에스크로 설정시 배송소요기간(일). (에스크로아닌 경우 해당사항 없음)', 'wskl' ),
		'type'        => 'shorttext',
		'default'     => '',
		'placeholder' => '',
	)
);