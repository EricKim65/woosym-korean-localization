<?php
$diff = array(
	'nav_menu_item',
	'revision',
	'shop_coupon',
	'shop_order',
	'shop_order_refund',
	'shop_webhook',
	'wooframework',
);

$post_types = array_diff( get_post_types(), $diff );
ksort( $post_types );

return array(
	'title'       => __( '마케팅자동화기능(D) - 베타', 'wskl' ),
	'description' => __( '독립 웹사이트에서 마케팅 자동화 서버로 관련 데이타가 연동됩니다. 베타테스팅 중입니다.', 'wskl' ),
	'fields'      => array(
		array(
			'id'          => 'enable_sales_log',
			'label'       => __( '판매 로그 사용', 'wskl' ),
			'description' => __( '결제 완료시 판매 로그가 연동됩니다.', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'enable_add_to_cart_log',
			'label'       => __( '장바구니 로그 사용', 'wskl' ),
			'description' => __( '장바구니 로그가 연동됩니다.', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'enable_wish_list_log',
			'label'       => __( '위시리스트 로그 사용', 'wskl' ),
			'description' => __( '위시리스트 로그가 연동됩니다.', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'enable_today_seen_log',
			'label'       => __( '오늘 본 상품 로그 사용', 'wskl' ),
			'description' => __( '오늘 본 상품 로그가 연동됩니다.', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'enable_page_seen_log',
			'label'       => __( '오늘 본 페이지 로그 사용', 'wskl' ),
			'description' => __( '오늘본 페이지 로그가 연동됩니다.', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'enable_post_export',
			'label'       => __( '블로그 자동 포스팅 기능 활성화', 'wskl' ),
			'description' => __( '블로그/상품/이벤트를 네이버/다음/티스토리에 자동으로 포스팅하도록 설정합니다.', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'post_export_types',
			'label'       => __( '포스팅할 포스트 타입', 'wskl' ),
			'description' => __( '', 'wskl' ),
			'type'        => 'checkbox_multi',
			'options'     => $post_types,
			'default'     => '',
		),
	),
);