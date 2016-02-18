<?php
$methods        = WSKL_Payment_Gates::get_checkout_methods();
$credit_default = $methods['credit'] . WSKL_Payment_Gates::get_checkout_method_postfix() . <<<EOD
<br>
1.페이앱 결제는 1,2번째 칸은 ActiveX 인증 방식이 아니므로 결제창에서 신용카드 번호만 입력하면 신속하게 결제됩니다.<br>
2. 페이앱 결제창의 3번째 칸에서는 기존의 ActiveX 방식이 지원되므로 기존의 방식으로 결제가 가능합니다.<br>
EOD;

return array(
	array(
		'id'          => 'payapp_user_id',
		'label'       => __( '판매자 아이디', 'wskl' ),
		'description' => __( '페이앱 판매자 아이디를 입력해주십시오', 'wskl' ),
		'type'        => 'text',
		'default'     => '',
		'placeholder' => '',
	),
	array(
		'id'          => 'payapp_link_key',
		'label'       => __( '연동 Key', 'wskl' ),
		'description' => __( '페이앱 연동 KEY를 입력해주십시오(중요)', 'wskl' ),
		'type'        => 'longtext',
		'default'     => '',
		'placeholder' => '',
	),
	array(
		'id'          => 'payapp_link_val',
		'label'       => __( '연동 Value', 'wskl' ),
		'description' => __( '페이앱 연동 VALUE를 입력해주십시오(중요)', 'wskl' ),
		'type'        => 'longtext',
		'default'     => '',
		'placeholder' => '',
	),
	array(
		'id'          => 'payapp_checkout_description_credit',
		'label'       => $methods['credit'] . __( ' 메시지', 'wskl' ),
		'description' => $methods['credit'] . __( ' 메시지', 'wskl' ),
		'type'        => 'textarea',
		'default'     => $credit_default,
		'placeholder' => '',
	),
	array(
		'id'          => 'payapp_checkout_description_remit',
		'label'       => $methods['remit'] . __( ' 메시지', 'wskl' ),
		'description' => $methods['remit'] . __( ' 메시지', 'wskl' ),
		'type'        => 'textarea',
		'default'     => $methods['remit'] . WSKL_Payment_Gates::get_checkout_method_postfix(),
		'placeholder' => '',
	),
	array(
		'id'          => 'payapp_checkout_description_virtual',
		'label'       => $methods['virtual'] . __( ' 메시지', 'wskl' ),
		'description' => $methods['virtual'] . __( ' 메시지', 'wskl' ),
		'type'        => 'textarea',
		'default'     => $methods['virtual'] . WSKL_Payment_Gates::get_checkout_method_postfix(),
		'placeholder' => '',
	),
	array(
		'id'          => 'payapp_checkout_description_mobile',
		'label'       => $methods['mobile'] . __( ' 메시지', 'wskl' ),
		'description' => $methods['mobile'] . __( ' 메시지', 'wskl' ),
		'type'        => 'textarea',
		'default'     => $methods['mobile'] . WSKL_Payment_Gates::get_checkout_method_postfix(),
		'placeholder' => '',
	),
	array(
		'id'          => 'dummy_34',
		'label'       => __( '판매자 등록', 'wskl' ),
		'description' => __(
			'
						<span class="wskl-notice">판매자 등록 과정은 매우 중요한 사항이므로  정확히 숙지하고 실행해주셔야 합니다. </span></br>
						1. 다보리와 계약시 메일로 전달된 판매자 아이디를 입력합니다.</br>
                        &nbsp;&nbsp;PayApp 판매자로 로그인 한 후 확인한 연동 KEY 와 연동 VALUE를 입력하고 저장합니다.</br><a href="https://seller.payapp.kr/c/apiconnect_info.html" target="_blank">https://seller.payapp.kr/c/apiconnect_info.html  연동정보 확인하러 가기</a></br>
 						<span class="wskl-info">페이앱에서는 테스트 모드가 제공되지 않습니다. 대신 결제 실패의 경우가 발생하지 않습니다.<br>불편하시겠지만 결제 테스트 후 판매자 관리자로 로그인하여 해당 결제를 취소하여 주십시오.</span></br>
  					', 'wskl'
		),
		'type'        => 'caption',
		'default'     => '',
	),
);
