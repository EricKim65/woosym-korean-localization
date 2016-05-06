<?php

$inactive_account_description = __( '휴면계정 관리 기능을 활성화합니다.', 'wskl' ) . '<br />'
                                . wskl_inform_plugin_dependency(
	                                'enable_inactive_accounts',
	                                __( 'WP-Members 플러그인(무료)', 'wskl' ),
	                                'https://wordpress.org/plugins/wp-members/',
	                                __( '휴면계정 관리 설정으로 이동', 'wskl' ),
	                                wskl_wp_members_url( 'inactive-accounts' )
                                );

$essential_features = array(
	'title'       => __( '핵심기능(B)', 'wskl' ),
	'description' => __(
		'여기서 결제 페이지의 배송정보 입력 방법 배송 방법등을 설정한다.',
		'wskl'
	),
	'fields'      => array(
		array(
			'id'          => 'enable_sym_checkout',
			'label'       => __( '한국형 주소 찾기', 'wskl' ),
			'description' => __( '한국형 주소 찾기와 결제 페이지를 활성화합니다.', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'enable_inactive_accounts',
			'label'       => __( '휴면계정 관리', 'wskl' ),
			'description' => $inactive_account_description,
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'enable_bacs_payer_name',
			'label'       => __( '입금인 지정 기능', 'wskl' ),
			'description' => __(
				'\'직접 은행 이체 (BACS)\' 결제 방법에서 고객이 입금인 이름을 별도로 지정하게 할 수 있습니다.',
				'wskl'
			),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'bacs_payer_name_in_own_column',
			'label'       => __( '입금자 별도 행 출력', 'wskl' ),
			'description' => __(
				'활성화 시 주문 페이지에서 \'주문\' 열에 출력되는 입금인 이름을 별도의 열로 옮겨 출력합니다.',
				'wskl'
			),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'enable_combined_tax',
			'label'       => __( '복합과세 활성화', 'wskl' ),
			'description' => __(
				'복합과세 설정을 합니다. 신용카드 결제 적용시 플러그인 제작사와 상의해주십시요.',
				'wskl'
			),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'hide_display_cart_tax',
			'label'       => __( '결제페이지의 세금액 숨기기', 'wskl' ),
			'description' => __(
				'복합과세 활성화시 결제 페이지에 표시되는 세금 항목을 숨깁니다.',
				'wskl'
			),
			'type'        => 'checkbox',
			'default'     => '',
		),

		array(
			'id'          => 'korean_won',
			'label'       => __( '한국 원화 표시 설정', 'wskl' ),
			'description' => __(
				'우커머스->설정->일반->통화에서 대한민국(원)표시가 나오도록 합니다.<br/>국내용 우커머스 쇼핑몰은 반드시 <a href="http://www.symphonysoft.co.kr/cs/activity/">우커머스-설정-일반</a> 에서 통화->대한민국(KRW), 통화 기호 위치->오른쪽으로 세팅하여 주십시오 !</br> <span class="wskl-notice">그렇지 않으면 고객의 결제  진행이 되지 않습니다. </span>',
				'wskl'
			),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'disable_sku',
			'label'       => __( 'SKU(상품코드) 사용해제', 'wskl' ),
			'description' => __( 'SKU(상품코드) 사용을 해제합니다.', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'disable_returntoshop',
			'label'       => __( '상점으로 돌아가기 버튼해제 ', 'wskl' ),
			'description' => __(
				'상점으로 돌아가기 버튼클릭 시 메인 홈으로 가게 합니다.',
				'wskl'
			),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'related_products_count',
			'label'       => __( '관련상품표시 갯수', 'wskl' ),
			'description' => __(
				'관련상품에 표시되는 갯수를 결정합니다.<span class="wskl-info">(테마에 따라 적용되지 않는 경우도 있으므로 유의하세요.)</span>',
				'wskl'
			),
			'type'        => 'shorttext',
			'default'     => '4',
			'placeholder' => __( '4', 'wskl' ),
		),
		array(
			'id'          => 'related_products_columns',
			'label'       => __( '관련상품표시 칸 수', 'wskl' ),
			'description' => __( '관련상품 칸 수를 결정합니다.', 'wskl' ),
			'type'        => 'shorttext',
			'default'     => '4',
			'placeholder' => __( '4', 'wskl' ),
		),
		array(
			'id'          => 'related_products_priority',
			'label'       => __( '관련상품 필터 우선순위' ),
			'description' => __(
				'테마나 타 플러그인에서 관련상품 값을 덮어쓸 수 있습니다. 만약 원하는 결과가 나오지 않으면 이 숫자를 늘려서 우선 순위를 낮춰 보세요',
				'wskl'
			),
			'type'        => 'shorttext',
			'default'     => '99',
			'placeholder' => __( '99', 'wskl' ),
		),
		//array(
		//	'id'          => 'vat',
		//	'label'       => __( '세금계산서 정보 입력 설정', 'wskl' ),
		//	'description' => __( '세금계산서 발급용 사업자 번호를 입력 여부를 설정합니다.(미적용)', 'wskl' ),
		//	'type'        => 'checkbox',
		//	'default'     => ''
		//),
	),
);

return $essential_features;