<?php


class WC_Inicis_Common extends WC_Payment_Gateway {

  // title과 desc 관련 option
  public $admin_title;
  public $frontend_title;
  public $frontend_desc;
  public $_pg_agency;

  // pg 일반 옵션
  public $method_code;
  public $enable_testmode;
  public $enable_https;
  public $enable_escrow;

  //inicis 일반 option
  public $inicis_admin;  //키패스워드
  public $inicis_mid;     //상점아이디
  public $inicis_url;

  //inicis 모바일option


  public $is_mobile;

  public $_prefix;
  public $_folder;
  public $assets_url;

  public function __construct() {

    $this->local_settings();

    //////////////////////Don't  Change Here////////////////////////
    $this->id                 = $this->_pg_agency . '_' . $this->method;

    $guided_methods     = WSKL_Payment_Gates::get_checkout_methods();
    $payment_gate_names = WSKL_Payment_Gates::get_pay_gates();

    $this->method_title       = $payment_gate_names[ 'inicis' ] . " - {$guided_methods[ $this->method ]}";
    $this->method_description = '';

    $this->init_form_fields();
    $this->init_settings();

    $this->enabled     = $this->get_option( 'enabled' );
    $this->title       = $this->get_option( 'title' );
    $this->description = $this->get_option( 'description' );
    ////////////////////////////////////////////////////////////////

    //add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

    add_action( 'wp_enqueue_scripts', array( $this, 'js_and_css' ) );
    add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'order_pay_page' ) );

    //Woocommerce 2.1 이전 버젼은 아래 내용이 접수가 안됨. www.biozoa.co.kr의 thankyou.php 형태로 바꿈.
    add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'order_received_text' ), 1, 0 );

  }

  function local_settings() {

    global $sym_checkout_methods;
    global $sym_pg_title;
    global $sym_checkout_titles;
    global $sym_checkout_desc;
    global $pay_gate_agency;

    /*Common*/
    $this->frontend_title = $sym_checkout_titles[ $this->method ];
    $this->frontend_desc  = $sym_checkout_titles[ $this->method ] . $sym_checkout_desc;

    $this->_prefix    = WSKL_PREFIX;
    $this->_pg_agency = $pay_gate_agency;

    $tmp_arr           = explode( '/', dirname( __FILE__ ) );
    $this->_folder     = $tmp_arr[ count( $tmp_arr ) - 3 ]; //_folder = plugin folder name
    $this->assets_url  = plugins_url( $this->_folder . '/assets/' );
    $this->admin_title = $sym_pg_title . ' ' . $sym_checkout_titles[ $this->method ];

    $this->enable_testmode = get_option( $this->_prefix . 'enable_testmode' );
    $this->enable_https    = get_option( $this->_prefix . 'enable_https' );
    $this->enable_escrow   = get_option( $this->_prefix . 'enable_escrow' );

    $this->enable_showinputs = get_option( $this->_prefix . 'enable_showinputs' );
    $this->is_mobile         = $this->mobile_ok();

    /*Local*/

    if ( $this->enable_testmode != 'on' ) {
      $this->inicis_admin = get_option( $this->_prefix . 'inicis_admin' );
      $this->inicis_mid   = get_option( $this->_prefix . 'inicis_mid' );
      $this->inicis_url   = get_option( $this->_prefix . 'inicis_url' );
    } else {
      $this->inicis_admin = '1111';
      $this->inicis_mid   = 'INIpayTest';
      $this->inicis_url   = 'http://www.your_domain.co.kr';
    }
    //$pay_methods = array();
    /*foreach ($sym_checkout_methods as $key => $value) {
		echo 'key='. $key. '---value='. $value. '<br>';
	 }*/

    if ( $this->method == 'credit' ) {
      $this->method_code = 'onlycard';
    } else if ( $this->method == 'remit' ) {
      $this->method_code = 'onlydbank';
    } else if ( $this->method == 'virtual' ) {
      $this->method_code = 'onlyvbank';
    } else if ( $this->method == 'mobile' ) {
      $this->method_code = 'onlyhpp';
    }

  }

  function init_form_fields() {  // action에 포함되는 것이므로 include로 뺄수 없슴.
    $this->form_fields = array(
        'enabled'     => array(
            'title'   => __( '활성화/비활성화', $this->_folder ),
            'type'    => 'checkbox',
            'label'   => __( '해당 결제방법을 활성화합니다.<br/> 활성화 후에 상단에 있는 [결제 옵션]-[지불게이트 웨이]메뉴에서 [회원 결제 페이지]의 표시 순서를 조정하십시요. ', $this->_folder ),
            'default' => 'yes',
        ),
        'title'       => array(
            'title'       => __( '제목', $this->_folder ),
            'type'        => 'text',
            'description' => __( ' [회원 결제 페이지]에 보여지는 제목입니다.', $this->_folder ),
            'default'     => __( $this->frontend_title, $this->_folder ),
        ),
        'description' => array(
            'title'       => __( '설명', $this->_folder ),
            'type'        => 'textarea',
            'description' => __( ' [회원 결제 페이지]에 보여지는 설명입니다.', $this->_folder ),
            'default'     => __( $this->frontend_desc, $this->_folder ),
        ),
    );
  }


  function order_pay_page( $order_id ) {

    $order = new WC_Order( $order_id );
    echo __( '<p><h4><font color="red">결제 처리가 완료될때까지 기다려 주시기 바랍니다. </font></h4>  </p>', $this->_folder );
    echo $this->order_pay_form( $order_id );
  }

  /**
   * @param $order_id
   *
   * @return string
   */
  function order_pay_form( $order_id ) {

    global $woocommerce;

    $order = new WC_Order( $order_id );

    /* * **************************
	* 0. 세션 시작				*
	* ************************** */
    //session_start();      //주의:파일 최상단에 위치시켜주세요!!

    /* * ************************
	 * 1. 라이브러리 인클루드 *
	 * ************************ */
    require_once dirname( __FILE__ ) . '/homeinicis/libs/INILib.php';

    /* * *************************************
	 * 2. INIpay50 클래스의 인스턴스 생성  *
	 * ************************************* */
    $inipay = new INIpay50;

    /* * ************************
	 * 3. 암호화 대상/값 설정 *
	 * ************************ */
    $inipay->SetField( "inipayhome", dirname( __FILE__ ) . '/homeinicis/' );       // 이니페이 홈디렉터리(상점수정 필요)
    $inipay->SetField( "type", "chkfake" );      // 고정 (절대 수정 불가)
    $inipay->SetField( "debug", "true" );        // 로그모드("true"로 설정하면 상세로그가 생성됨.)
    $inipay->SetField( "enctype", "asym" );    //asym:비대칭, symm:대칭(현재 asym으로 고정)
    /* * ************************************************************************************************
	 * admin 은 키패스워드 변수명입니다. 수정하시면 안됩니다. 1111의 부분만 수정해서 사용하시기 바랍니다.
	 * 키패스워드는 상점관리자 페이지(https://iniweb.inicis.com)의 비밀번호가 아닙니다. 주의해 주시기 바랍니다.
	 * 키패스워드는 숫자 4자리로만 구성됩니다. 이 값은 키파일 발급시 결정됩니다.
	 * 키패스워드 값을 확인하시려면 상점측에 발급된 키파일 안의 readme.txt 파일을 참조해 주십시오.
	 * ************************************************************************************************ */
    $inipay->SetField( "admin", $this->inicis_admin );     // 키패스워드(키발급시 생성, 상점관리자 패스워드와 상관없음)
    $inipay->SetField( "checkopt", "false" );   //base64함:false, base64안함:true(현재 false로 고정)
    //필수항목 : mid, price, nointerest, quotabase
    //추가가능 : INIregno, oid
    //*주의* : 	추가가능한 항목중 암호화 대상항목에 추가한 필드는 반드시 hidden 필드에선 제거하고
    //          SESSION이나 DB를 이용해 다음페이지(INIsecureresult.php)로 전달/셋팅되어야 합니다.
    $inipay->SetField( "mid", $this->inicis_mid );            // 상점아이디
    //(int)$order->order_total
    $inipay->SetField( "price", $order->order_total );                // 가격
    //$inipay->SetField("price", "1000");                // 가격
    $inipay->SetField( "nointerest", "no" );             //무이자여부(no:일반, yes:무이자)
    $inipay->SetField( "quotabase", "선택:일시불:2개월:3개월:6개월" ); //할부기간
    $inipay->SetField( "log", "false" );

    /* * ******************************
	 * 4. 암호화 대상/값을 암호화함 *
	 * ****************************** */
    $inipay->startAction();

    /* * *******************
	 * 5. 암호화 결과  *
	 * ******************* */
    if ( $inipay->GetResult( "ResultCode" ) != "00" ) {
      echo $inipay->GetResult( "ResultMsg" );
      exit( 0 );
    }

    /* * *******************
	 * 6. 세션정보 저장  *
	 * ******************* */
    $_SESSION['INI_MID']     = $this->inicis_mid; //상점ID
    $_SESSION['INI_ADMIN']   = $this->inicis_admin;   // 키패스워드(키발급시 생성, 상점관리자 패스워드와 상관없음)
    $_SESSION['INI_PRICE']   = $order->order_total;     //가격
    $_SESSION['INI_RN']      = $inipay->GetResult( "rn" ); //고정 (절대 수정 불가)
    $_SESSION['INI_ENCTYPE'] = $inipay->GetResult( "enctype" ); //고정 (절대 수정 불가)

    $_SESSION['inicis_url'] = $this->inicis_url;   // 셋업 정보를 pay-callback으로 넘기기위해서


    $buf_form = '
            <!-- 아래의 meta tag 4가지 항목을 반드시 추가 하시기 바랍니다. -->
            <meta http-equiv="Cache-Control" content="no-cache"/>
            <meta http-equiv="Expires" content="0"/>
            <meta http-equiv="Pragma" content="no-cache"/>
            <meta http-equiv="X-UA-Compatible" content="requiresActiveX=true" />
            <!-- 끝  -->
        ';

    $ret_url = home_url( '/?wc-api=inicis_pay_callback' );
    $buf_form .= '<form name="ini" method="post" action="' . $ret_url . '" >';

    $pay_form_args = $this->pay_form_input_args( $order );

    $pay_form_inputs = '';
    foreach ( $pay_form_args as $key => $value ) {
      if ( $this->enable_showinputs != 'on' ) {
        $pay_form_inputs .= '<input type="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
      } else {
        $pay_form_inputs .= '<table><tr><td>' . esc_attr( $key ) . '</td><td><input type="text" style="width:300px;" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" /></td></tr></table>';
      }
    }

    require_once dirname( __FILE__ ) . '/noedit_inicispay.php';

    $buf_form .= $pay_form_inputs;
    $buf_form .= $inicis_noedit;

    $buf_form .= '</form>';

    if ( ! $this->is_mobile ) {  //PC 결제
      $buf_form = $buf_form . "
			";
    } else {  //모바일 결제
      $buf_form = $buf_form . "
			";
    }

    return $buf_form;
  }

  function pay_form_input_args( $order ) {

    global $woocommerce;

    $order_id = $order->id;

    //$this->billing_phone = $order->billing_phone;

    if ( sizeof( $order->get_items() ) > 0 ) {
      foreach ( $order->get_items() as $item ) {
        $item_name = $item['name'];
        break;
        // if ( $item[ 'qty' ] ) {
        //    $item_name = $item[ 'name' ];
        // }
      }
    }

    $pay_form_args = array(  // 공통 변수
      // 주문정보 입력
        'oid'          => $order_id,
        'gopaymethod'  => $this->method_code,
        'goodname'     => $item_name,  // 상품명
        'buyername'    => $order->billing_first_name . $order->billing_last_name, //주문자 성명
        'buyeremail'   => $order->billing_email,  // 주문자 이메일
        'parentemail'  => $order->billing_email,  // 주문자 이메일 - 정통부 권고 사항
        'buyertel'     => $order->billing_phone, //휴대폰번호, - 포함

      //가맹점 필수정보
        'acceptmethod' => 'HPP(1):Card(0):OCB:receipt:cardpoint',
        'currency'     => 'WON',

    );

    $pay_form_inputs = apply_filters( 'woocommerce_pay_form_args', $pay_form_args );

    return $pay_form_inputs;
  }

  function js_and_css() {

    if ( $this->enable_https != 'on' ) {
      wp_enqueue_script( 'inicis_wallet', 'http://plugin.inicis.com/pay61_secuni_cross.js' );
    } else {
      wp_enqueue_script( 'inicis_wallet', 'https://plugin.inicis.com/pay61_secunissl_cross.js' );
    }

    wp_enqueue_script( 'inicis_pay', $this->assets_url . 'js/inicispay.js' );  //맨앞에 넣음
    // 이걸 빼면 안됨
  }

  /**
   * @param $order_id
   *
   * @return array
   */
  function process_payment( $order_id ) {

    //global $woocommerce;
    global $woocommerce_ver21_less;

    $order = new WC_Order( $order_id );

    if ( $woocommerce_ver21_less ) {

      $return_array = array(
          'result'   => 'success',
          'redirect' => add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order_id, get_permalink( woocommerce_get_page_id( 'pay' ) ) ) ),
      );

    } else {
      $return_array = array(
          'result'   => 'success',
          'redirect' => $order->get_checkout_payment_url( true ),
      );
    }

    return $return_array;

  }

  function mobile_ok() {

    $device = $_SERVER['HTTP_USER_AGENT'];
    if ( stripos( $device, "Android" ) || stripos( $device, "iPhone" ) || stripos( $device, "iPod" ) || stripos( $device, "iPad" ) ) {
      return true;
    } else {
      return false;
    }
  }

  function order_received_text() {

    /* = -------------------------------------------------------------------------- = */
    /* =   가맹점 측 DB 처리 실패시 상세 결과 메시지 설정 끝                    = */
    /* =========================================== */

    if ( isset( $_POST['TID'] ) ) {  // PG가 연결되었을때만 (무통장입금이 아닐때)

      $html = '
				<script type="text/javascript">
						function show_receipt(tid) // 영수증 출력
						{
								 var receiptUrl = "https://iniweb.inicis.com/DefaultWebApp/mall/cr/cm/mCmReceipt_head.jsp?noTid=" + tid + "&noMethod=1";
								window.open(receiptUrl,"receipt","width=430,height=700");
						 }

						function errhelp() // 상세 에러내역 출력
						{
							var errhelpUrl = "http://www.inicis.com/ErrCode/Error.jsp?result_err_code=" + "' . $_POST['ResultErrorCode'] . '" + "&mid=" + "' . $_POST['MID'] . '" + "&tid=' . $_POST['TID'] . '" + "&goodname=" + "' . $_POST['GoodName'] . '" + "&price=" + "' . $_POST['TotPrice'] . '" + "&paymethod=" + "' . $_POST['PayMethod'] . '" + "&buyername=" + "' . $_POST['BuyerName'] . '" + "&buyertel=" + "' . $_POST['BuyerTel'] . '" + "&buyeremail=" + "' . $_POST['BuyerEmail'] . '" + "&codegw=" + "' . $_POST['HPP_GWCode'] . '";
							window.open(errhelpUrl,"errhelp","width=520,height=150, scrollbars=yes,resizable=yes");
						}

				</script>
			';


      $order_id = $_POST['order_id'];
      $order    = new WC_Order( $order_id );

      Sym_Custom_Data::extend( $order );
      $order->custom->order_receipt_data = array(
          'inicis_tid' => $_POST['TID'],
      );

      $order->custom->save();

      if ( isset( $order->custom->order_receipt_data ) ) {
        $custom_data = $order->custom->order_receipt_data;
      }

      if ( $_POST['ResultCode'] == "00" ) {  // 결제 성공
        if ( $_POST['PayMethod'] == "Card" || $_POST['PayMethod'] == "VCard" ) {  // 신용카드
          $html .= '주문에 감사드립니다. [신용카드] 결제시에만 <input type="button" value="신용 카드 영수증 인쇄" onclick="javascript:show_receipt(\'' . $custom_data['inicis_tid'] . '\')" > 가 발행 가능합니다.';
        } else {
          $html .= '에러가 발생했습니다.  <input type="button" value="에러내용보기"  onclick="javascript:errhelp()" >';
        }
      }

      return $html;
    }

  }
}


class Inicis_Pay_Callback extends WC_Payment_Gateway {

  public $_prefix;
  public $_folder;
  public $assets_url;

  //public $method = "신용카드";

  public function __construct() {

    $this->_prefix = WSKL_PREFIX;

    $tmp_arr          = explode( "/", dirname( __FILE__ ) );
    $this->_folder    = $tmp_arr[ count( $tmp_arr ) - 3 ]; //_folder = plugin folder name
    $this->assets_url = plugins_url( $this->_folder . '/assets/' );

    add_action( 'woocommerce_api_inicis_pay_callback', array( $this, 'pay_callback' ) );
  }

  function pay_callback() {

    global $woocommerce;
    global $woocommerce_ver21_less;
    @ob_clean();

    header( 'Content-type: text/html; charset=euc-kr' );
    foreach ( $_POST as $key => $row ) {
      $_POST[ $key ] = iconv( "UTF-8", "EUC-KR", $row );
    }


    $order_id = $_POST["oid"];
    $order    = new WC_Order( $order_id );

    /* INIsecurepay.php
	*
	* 이니페이 플러그인을 통해 요청된 지불을 처리한다.
	* 지불 요청을 처리한다.
	* 코드에 대한 자세한 설명은 매뉴얼을 참조하십시오.
	* <주의> 구매자의 세션을 반드시 체크하도록하여 부정거래를 방지하여 주십시요.
	*
	* http://www.inicis.com
	* Copyright (C) 2006 Inicis Co., Ltd. All rights reserved.
	*/

    /****************************
     * 0. 세션 시작             *
     ****************************/
    // session_start();								//주의:파일 최상단에 위치시켜주세요!!

    /**************************
     * 1. 라이브러리 인클루드 *
     **************************/
    require_once dirname( __FILE__ ) . '/homeinicis/libs/INILib.php';

    /***************************************
     * 2. INIpay50 클래스의 인스턴스 생성 *
     ***************************************/
    $inipay = new INIpay50;

    /*********************
     * 3. 지불 정보 설정 *
     *********************/
    //$inipay->SetField("inipayhome", "/home/ts/www/INIpay50"); // 이니페이 홈디렉터리(상점수정 필요)
    $inipay->SetField( "inipayhome", dirname( __FILE__ ) . '/homeinicis' );       // 이니페이 홈디렉터리(상점수정 필요)
    $inipay->SetField( "type", "securepay" );                         // 고정 (절대 수정 불가)
    $inipay->SetField( "pgid", "INIphp" . $pgid );                      // 고정 (절대 수정 불가)
    $inipay->SetField( "subpgip", "203.238.3.10" );                    // 고정 (절대 수정 불가)
    $inipay->SetField( "admin", $_SESSION['INI_ADMIN'] );    // 키패스워드(상점아이디에 따라 변경)
    $inipay->SetField( "debug", "true" );                             // 로그모드("true"로 설정하면 상세로그가 생성됨.)
    $inipay->SetField( "uid", $uid );                                 // INIpay User ID (절대 수정 불가)
    $inipay->SetField( "goodname", $goodname );                       // 상품명
    $inipay->SetField( "currency", $currency );                       // 화폐단위

    $inipay->SetField( "mid", $_SESSION['INI_MID'] );        // 상점아이디
    $inipay->SetField( "rn", $_SESSION['INI_RN'] );          // 웹페이지 위변조용 RN값
    $inipay->SetField( "price", $_SESSION['INI_PRICE'] );    // 가격
    $inipay->SetField( "enctype", $_SESSION['INI_ENCTYPE'] );// 고정 (절대 수정 불가)


    /*----------------------------------------------------------------------------------------
	  price 등의 중요데이터는
	  브라우저상의 위변조여부를 반드시 확인하셔야 합니다.

	  결제 요청페이지에서 요청된 금액과
	  실제 결제가 이루어질 금액을 반드시 비교하여 처리하십시오.

	  설치 메뉴얼 2장의 결제 처리페이지 작성부분의 보안경고 부분을 확인하시기 바랍니다.
	  적용참조문서: 이니시스홈페이지->가맹점기술지원자료실->기타자료실 의
					 '결제 처리 페이지 상에 결제 금액 변조 유무에 대한 체크' 문서를 참조하시기 바랍니다.
	  예제)
	  원 상품 가격 변수를 OriginalPrice 하고  원 가격 정보를 리턴하는 함수를 Return_OrgPrice()라 가정하면
	  다음 같이 적용하여 원가격과 웹브라우저에서 Post되어 넘어온 가격을 비교 한다.

	   $OriginalPrice = Return_OrgPrice();
	   $PostPrice = $_SESSION['INI_PRICE'];
	   if ( $OriginalPrice != $PostPrice )
	   {
		   //결제 진행을 중단하고  금액 변경 가능성에 대한 메시지 출력 처리
		   //처리 종료
	   }

	 ----------------------------------------------------------------------------------------*/
    $inipay->SetField( "buyername", $buyername );       // 구매자 명
    $inipay->SetField( "buyertel", $buyertel );        // 구매자 연락처(휴대폰 번호 또는 유선전화번호)
    $inipay->SetField( "buyeremail", $buyeremail );      // 구매자 이메일 주소
    $inipay->SetField( "paymethod", $paymethod );       // 지불방법 (절대 수정 불가)
    $inipay->SetField( "encrypted", $encrypted );       // 암호문
    $inipay->SetField( "sessionkey", $sessionkey );      // 암호문
    $inipay->SetField( "url", $_SESSION['inicis_url'] ); // 실제 서비스되는 상점 SITE URL로 변경할것
    $inipay->SetField( "cardcode", $cardcode );         // 카드코드 리턴
    $inipay->SetField( "parentemail", $parentemail );   // 보호자 이메일 주소(핸드폰 , 전화결제시에 14세 미만의 고객이 결제하면  부모 이메일로 결제 내용통보 의무, 다른결제 수단 사용시에 삭제 가능)

    /*-----------------------------------------------------------------*
	 * 수취인 정보 *                                                   *
	 *-----------------------------------------------------------------*
	 * 실물배송을 하는 상점의 경우에 사용되는 필드들이며               *
	 * 아래의 값들은 INIsecurepay.html 페이지에서 포스트 되도록        *
	 * 필드를 만들어 주도록 하십시요.                                  *
	 * 컨텐츠 제공업체의 경우 삭제하셔도 무방합니다.                   *
	 *-----------------------------------------------------------------*/
    $inipay->SetField( "recvname", $recvname );  // 수취인 명
    $inipay->SetField( "recvtel", $recvtel );    // 수취인 연락처
    $inipay->SetField( "recvaddr", $recvaddr );  // 수취인 주소
    $inipay->SetField( "recvpostnum", $recvpostnum );  // 수취인 우편번호
    $inipay->SetField( "recvmsg", $recvmsg );    // 전달 메세지

    $inipay->SetField( "joincard", $joincard );  // 제휴카드코드
    $inipay->SetField( "joinexpire", $joinexpire );    // 제휴카드유효기간
    $inipay->SetField( "id_customer", $id_customer );    //user_id

    $inipay->SetField( "log", "false" ); // 로깅은 생략합니다.

    /****************
     * 4. 지불 요청 *
     ****************/
    $inipay->startAction();

    /****************************************************************************************************************
     * 5. 결제  결과
     *
     *  1 모든 결제 수단에 공통되는 결제 결과 데이터
     *  거래번호 : $inipay->GetResult('TID')
     *  결과코드 : $inipay->GetResult('ResultCode') ("00"이면 지불 성공)
     *  결과내용 : $inipay->GetResult('ResultMsg') (지불결과에 대한 설명)
     *  지불방법 : $inipay->GetResult('PayMethod') (매뉴얼 참조)
     *  상점주문번호 : $inipay->GetResult('MOID')
     *  결제완료금액 : $inipay->GetResult('TotPrice')
     *
     * 결제 되는 금액 =>원상품가격과  결제결과금액과 비교하여 금액이 동일하지 않다면
     * 결제 금액의 위변조가 의심됨으로 정상적인 처리가 되지않도록 처리 바랍니다. (해당 거래 취소 처리)
     *
     *
     *  2. 신용카드,ISP,핸드폰, 전화 결제, 은행계좌이체, OK CASH BAG Point 결제 결과 데이터
     *      (무통장입금 , 문화 상품권 포함)
     *  이니시스 승인날짜 : $inipay->GetResult('ApplDate') (YYYYMMDD)
     *  이니시스 승인시각 : $inipay->GetResult('ApplTime') (HHMMSS)
     *
     *  3. 신용카드 결제 결과 데이터
     *
     *  신용카드 승인번호 : $inipay->GetResult('ApplNum')
     *  할부기간 : $inipay->GetResult('CARD_Quota')
     *  무이자할부 여부 : $inipay->GetResult('CARD_Interest') ("1"이면 무이자할부)
     *  신용카드사 코드 : $inipay->GetResult('CARD_Code') (매뉴얼 참조)
     *  카드발급사 코드 : $inipay->GetResult('CARD_BankCode') (매뉴얼 참조)
     *  본인인증 수행여부 : $inipay->GetResult('CARD_AuthType') ("00"이면 수행)
     *      각종 이벤트 적용 여부 : $inipay->GetResult('EventCode')
     *
     *      ** 달러결제 시 통화코드와  환률 정보 **
     *  해당 통화코드 : $inipay->GetResult('OrgCurrency')
     *  환율 : $inipay->GetResult('ExchangeRate')
     *
     *      아래는 "신용카드 및 OK CASH BAG 복합결제" 또는"신용카드 지불시에 OK CASH BAG적립"시에 추가되는 데이터
     *  OK Cashbag 적립 승인번호 : $inipay->GetResult('OCB_SaveApplNum')
     *  OK Cashbag 사용 승인번호 : $inipay->GetResult('OCB_PayApplNum')
     *  OK Cashbag 승인일시 : $inipay->GetResult('OCB_ApplDate') (YYYYMMDDHHMMSS)
     *  OCB 카드번호 : $inipay->GetResult('OCB_Num')
     *  OK Cashbag 복합결재시 신용카드 지불금액 : $inipay->GetResult('CARD_ApplPrice')
     *  OK Cashbag 복합결재시 포인트 지불금액 : $inipay->GetResult('OCB_PayPrice')
     *
     * 4. 실시간 계좌이체 결제 결과 데이터
     *
     *  은행코드 : $inipay->GetResult('ACCT_BankCode')
     *  현금영수증 발행결과코드 : $inipay->GetResult('CSHR_ResultCode')
     *  현금영수증 발행구분코드 : $inipay->GetResult('CSHR_Type')
     *                            *
     * 5. OK CASH BAG 결제수단을 이용시에만  결제 결과 데이터
     *  OK Cashbag 적립 승인번호 : $inipay->GetResult('OCB_SaveApplNum')
     *  OK Cashbag 사용 승인번호 : $inipay->GetResult('OCB_PayApplNum')
     *  OK Cashbag 승인일시 : $inipay->GetResult('OCB_ApplDate') (YYYYMMDDHHMMSS)
     *  OCB 카드번호 : $inipay->GetResult('OCB_Num')
     *
     * 6. 무통장 입금 결제 결과 데이터                                      *
     *  가상계좌 채번에 사용된 주민번호 : $inipay->GetResult('VACT_RegNum')                        *
     *  가상계좌 번호 : $inipay->GetResult('VACT_Num')                                          *
     *  입금할 은행 코드 : $inipay->GetResult('VACT_BankCode')                                    *
     *  입금예정일 : $inipay->GetResult('VACT_Date') (YYYYMMDD)                                *
     *  송금자 명 : $inipay->GetResult('VACT_InputName')                                            *
     *  예금주 명 : $inipay->GetResult('VACT_Name')                                            *
     *                            *
     * 7. 핸드폰, 전화 결제 결과 데이터( "실패 내역 자세히 보기"에서 필요 , 상점에서는 필요없는 정보임)             *
     *  전화결제 사업자 코드 : $inipay->GetResult('HPP_GWCode')                                  *
     *                            *
     * 8. 핸드폰 결제 결과 데이터                                        *
     *  휴대폰 번호 : $inipay->GetResult('HPP_Num') (핸드폰 결제에 사용된 휴대폰번호)                *
     *                            *
     * 9. 전화 결제 결과 데이터                                        *
     *  전화번호 : $inipay->GetResult('ARSB_Num') (전화결제에  사용된 전화번호)                  *
     *                            *
     * 10. 문화 상품권 결제 결과 데이터                                      *
     *  컬쳐 랜드 ID : $inipay->GetResult('CULT_UserID')                                      *
     *                            *
     * 11. K-merce 상품권 결제 결과 데이터 (K-merce ID, 틴캐시 아이디 공통사용)                                     *
     *      K-merce ID : $inipay->GetResult('CULT_UserID')                                                                       *
     *                                                                                                              *
     * 12. 모든 결제 수단에 대해 결제 실패시에만 결제 결과 데이터              *
     *  에러코드 : $inipay->GetResult('ResultErrorCode')                                      *
     *                            *
     * 13.현금영수증 발급 결과코드 (은행계좌이체시에만 리턴)              *
     *    $inipay->GetResult('CSHR_ResultCode')                                                                                     *
     *                                                                                                              *
     * 14.틴캐시 잔액 데이터                                              *
     *    $inipay->GetResult('TEEN_Remains')                                                                            *
     *  틴캐시 ID : $inipay->GetResult('CULT_UserID')                          *
     * 15.게임문화 상품권              *
     *  사용 카드 갯수 : $inipay->GetResult('GAMG_Cnt')                                  *
     *                            *
     ****************************************************************************************************************/


    /*******************************************************************
     * 7. DB연동 실패 시 강제취소                                      *
     *                                                                 *
     * 지불 결과를 DB 등에 저장하거나 기타 작업을 수행하다가 실패하는  *
     * 경우, 아래의 코드를 참조하여 이미 지불된 거래를 취소하는 코드를 *
     * 작성합니다.                                                     *
     *******************************************************************/
    /*
	$cancelFlag = "false";

	// $cancelFlag를 "ture"로 변경하는 condition 판단은 개별적으로
	// 수행하여 주십시오.

	if($cancelFlag == "true")
	{
		$TID = $inipay->GetResult("TID");
		$inipay->SetField("type", "cancel"); // 고정
		$inipay->SetField("tid", $TID); // 고정
		$inipay->SetField("cancelmsg", "DB FAIL"); // 취소사유
		$inipay->startAction();
		if($inipay->GetResult('ResultCode') == "00")
		{
	  $inipay->MakeTXErrMsg(MERCHANT_DB_ERR,"Merchant DB FAIL");
		}
	}
	*/

    //print_r($inipay->GetResult('ResultCode'));

    if ( $woocommerce_ver21_less ) {
      $return_url = add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order_id, get_permalink( woocommerce_get_page_id( 'thanks' ) ) ) );
    } else {
      $return_url = $this->get_return_url( $order );
    }

    if ( $inipay->GetResult( 'ResultCode' ) == "00" ) {
      $order->add_order_note( sprintf( __( '결제가 성공적으로 처리됨.<br/>결제방법: %s<br/>이니시스 TID: %s. 발생시각: %s.', 'wskl' ), $this->method, '111', date( 'Y-m-d H:i:s' ) ) );
      // Complete payment, reduce stock levels & remove cart
      $order->payment_complete();
      $order->reduce_order_stock();
      $woocommerce->cart->empty_cart();
    } else {  // 결제실패에 따른 상점처리부분
      $res_msg = iconv( 'euc-kr', 'utf-8', $inipay->GetResult('ResultMsg') );
      $order->update_status( 'failed', sprintf( __( '결제처리 안됨.<br/>-에러메시지 : %s<br/>-발생시각: %s.', 'wskl' ), $res_msg, date( 'Y-m-d H-i-s' ) ) );
      //$cart_url = $woocommerce->cart->get_cart_url();
      // wp_redirect($cart_url);
    }
    ?>

    <body onload="javascript:pay_info.submit();">
    <form name="pay_info" method="post" action="<?php echo $return_url; ?>">
      <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
      <input type="hidden" name="TID" value="<?php echo $inipay->GetResult( 'TID' ); ?>">
      <input type="hidden" name="ResultCode" value="<?php echo $inipay->GetResult( 'ResultCode' ); ?>">
      <input type="hidden" name="ResultMsg" value="<?php echo $inipay->GetResult( 'ResultMsg' ); ?>">
      <input type="hidden" name="PayMethod" value="<?php echo $inipay->GetResult( 'PayMethod' ); ?>">
      <input type="hidden" name="MOID" value="<?php echo $inipay->GetResult( 'MOID' ); ?>">
      <input type="hidden" name="TotPrice" value="<?php echo $inipay->GetResult( 'TotPrice' ); ?>">
      <input type="hidden" name="MID" value="<?php echo $inipay->GetResult( 'MID' ); ?>">

      <!-- 아래는 프로그램에서 값이$inipay에서 값이 넘어오지 않으므로 무시해도 됨-->
      <input type="hidden" name="ResultErrorCode" value="<?php echo $inipay->GetResult( 'ResultErrorCode' ); ?>">
      <input type="hidden" name="GoodName" value="<?php echo $inipay->GetResult( 'GoodName' ); ?>">
      <input type="hidden" name="BuyerName" value="<?php echo $inipay->GetResult( 'BuyerName' ); ?>">
      <input type="hidden" name="BuyerTel" value="<?php echo $inipay->GetResult( 'BuyerTel' ); ?>">
      <input type="hidden" name="BuyerEmail" value="<?php echo $inipay->GetResult( 'BuyerEmail' ); ?>">
      <input type="hidden" name="HPP_GWCode" value="<?php echo $inipay->GetResult( 'HPP_GWCode' ); ?>">

    </form>
    </body>
    <?php
    ///wp_redirect( $return_url);
    exit;
  }
}


?>