<?php
$convenience_features = array(
	'title'       => __( '편의기능(C)', 'wskl' ),
	'description' => __(
		'배송회사와의 송장번호를 통하여 연결을 할 수 있도록 합니다.</br>아래에서 사용 택배회사가 없을 경우 service@econoq.co.kr 로 메일 주시면 추가해드리겠습니다.',
		'wskl'
	),
	'fields'      => array(
		array(
			'id'          => 'enable_ship_track',
			'label'       => __( '배송 추적 기능 활성화', 'wskl' ),
			'description' => __( '배송 추적 기능 사용여부를 설정합니다.', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'shipping_companies',
			'label'       => __( '배송회사 지정', 'wskl' ),
			'description' => __( '사용중인 배송회사를 지정해 주십시오.', 'wskl' ),
			'type'        => 'checkbox_multi',
			'options'     => $agents,
			'data'        => get_option( 'shipping_companies' ),
			'default'     => $agents[ $agent_default ],
		),
		array(
			'id'          => 'enable_direct_purchase',
			'label'       => __( '바로구매 버튼 사용', 'wskl' ),
			'description' => __( '바로구매 버튼을 사용합니다.', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'enable_dabory_members',
			'label'       => __( '다보리 멤버스 활성화', 'wskl' ),
			'description' => $members_description,
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'hide_product_review_tab',
			'label'       => __( '상품 리뷰 숨김', 'wskl' ),
			'description' => __( '상품 페이지의 리뷰 탭을 보이지 않게 합니다.', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
	),
);

return $convenience_features;