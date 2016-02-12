<?php


class WC_Ags_Common extends WC_Payment_Gateway {

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

  //ags 일반 option
  public $ags_storeid;
  public $ags_storenm;
  public $ags_mallurl;

  //ags 모바일option
  public $ags_hp_id;
  public $ags_hp_pwd;
  public $ags_hp_subid;
  public $ags_prodcode;
  public $ags_unittype;

  public $is_mobile;

  public $_prefix;
  public $_folder;
  public $assets_url;

  public function __construct() {

    $this->local_settings();

    //////////////////////Don't  Change Here////////////////////////
    $this->id                 = $this->_pg_agency . '_' . $this->method;
    $this->method_title       = __( $this->admin_title, $this->_folder );
    $this->method_description = '';

    $this->init_form_fields();
    $this->init_settings();

    $this->enabled     = $this->get_option( 'enabled' );
    $this->title       = $this->get_option( 'title' );
    $this->description = $this->get_option( 'description' );
    ////////////////////////////////////////////////////////////////

    add_action( 'wp_enqueue_scripts', array( $this, 'js_and_css' ) );
    add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'order_pay_page' ) );
    add_filter( 'woocommerce_thankyou_order_received_text', array( $this, 'order_received_text' ), 10, 0 );

    //add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

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

    $this->ags_hp_id    = get_option( $this->_prefix . 'ags_hp_id' );//CPID(발급된ID)-10
    $this->ags_hp_pwd   = get_option( $this->_prefix . 'ags_hp_pwd' ); //CP비밀번호-10
    $this->ags_hp_subid = get_option( $this->_prefix . 'ags_hp_subid' );//실거래 전화후 받은 상점만
    $this->ags_prodcode = get_option( $this->_prefix . 'ags_prodcode' ); //발급받은 상품코드
    $this->ags_unittype = get_option( $this->_prefix . 'ags_unittype' );//상품종류 디지털-1 실물-2

    $this->enable_showinputs = get_option( $this->_prefix . 'enable_showinputs' );
    $this->is_mobile         = $this->mobile_ok();


    /*Local*/
    //$this->kcp_hp_id        = get_option( $this->_prefix .'ags_hp_id' );//CPID(발급된ID)-10

    if ( $this->enable_testmode != 'on' ) {
      $this->ags_storeid = get_option( $this->_prefix . 'ags_storeid' );
      $this->ags_storenm = get_option( $this->_prefix . 'ags_storenm' );
      $this->ags_mallurl = get_option( $this->_prefix . 'ags_mallurl' );
    } else {
      $this->ags_storeid = 'aegis';
      $this->ags_storenm = '테스트상점';
      $this->ags_mallurl = 'http://www.allthegate.com';
    }

    if ( $this->enable_escrow != 'on' ) { //에스크로 아닌 일반
      if ( $this->method == 'credit' ) {
        $this->method_code = 'onlycard';
      } else if ( $this->method == 'remit' ) {
        $this->method_code = 'onlyiche';
      } else if ( $this->method == 'virtual' ) {
        $this->method_code = 'onlyvirtual';
      } else if ( $this->method == 'mobile' ) {
        $this->method_code = 'onlyhp';
      }

    } else {  //에스크로 시
      if ( $this->method == 'credit' ) {
        $this->method_code = 'onlycardselfescrow';
      } else if ( $this->method == 'remit' ) {
        $this->method_code = 'onlyicheselfescrow';
      } else if ( $this->method == 'virtual' ) {
        $this->method_code = 'onlyvirtualselfescrow';
      } else if ( $this->method == 'mobile' ) {  //바꿀것 없슴
        if ( count( $sym_checkout_methods ) == 1 ) {
          $this->method_code = 'onlyhp';
        } else {
          $this->method_code = 'hp';
        }
      }
    }

    /*if ( $this->enable_escrow != 'on' ) { //에스크로 아닌 일반
		if ($this->method == 'credit') {
			if (count($sym_checkout_methods) == 1) {
				$this->method_code = 'onlycard';
			} else {
				$this->method_code = 'card';
			}
		} else if ($this->method == 'remit') {
			if (count($sym_checkout_methods) == 1) {
				$this->method_code = 'onlyiche';
			} else {
				$this->method_code = 'iche';
			}
		} else if ($this->method == 'virtual') {
			if (count($sym_checkout_methods) == 1) {
				$this->method_code = 'onlyvirtual';
			} else {
				$this->method_code = 'virtual';
			}
		} else if ($this->method == 'mobile') {
			if (count($sym_checkout_methods) == 1) {
				$this->method_code = 'onlyhp';
			} else {
				$this->method_code = 'hp';
			}
		}

	} else {  //에스크로 시
		if ($this->method == 'credit') {
			$this->method_code = 'onlycardselfescrow';
		} else if ($this->method == 'remit') {
			$this->method_code = 'onlyicheselfescrow';
		} else if ($this->method == 'virtual') {
			$this->method_code = 'onlyvirtualselfescrow';
		} else if ($this->method == 'mobile') {  //바꿀것 없슴
			if (count($sym_checkout_methods) == 1) {
				$this->method_code = 'onlyhp';
			} else {
				$this->method_code = 'hp';
			}
		}
	}*/
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

  function order_pay_form( $order_id ) {

    global $woocommerce;

    $order = new WC_Order( $order_id );

    require_once dirname( __FILE__ ) . '/noedit_agspay.php';
    $ret_url  = home_url( '/?wc-api=ags_pay_callback' );
    $buf_form = '<form name="frmAGS_pay" method="post" action="' . $ret_url . '" >';

    $pay_form_args = $this->pay_form_input_args( $order );

    $pay_form_inputs = '';
    foreach ( $pay_form_args as $key => $value ) {
      if ( $this->enable_showinputs != 'on' ) {
        $pay_form_inputs .= '<input type="hidden" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
      } else {
        $pay_form_inputs .= '<table><tr><td>' . esc_attr( $key ) . '</td><td><input type="text" style="width:300px;" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" /></td></tr></table>';
      }
    }

    $buf_form .= $pay_form_inputs;
    $buf_form .= $ags_noedit;
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

    $pay_form_args =
        array(  // 공통 변수
            'Job'        => $this->method_code,   //지불방법 코드
            'StoreId'    => $this->ags_storeid,  //상점 아이디-20
            'OrdNo'      => $order->id,  // 주문번호-40
            'Amt'        => (int) $order->order_total,  //금액-12
            'StoreNm'    => $this->ags_storenm,  //상점명-50
            'ProdNm'     => sanitize_text_field( $item_name ),  // 상품명
            'MallUrl'    => $this->ags_mallurl,  //상점 URL
            'UserEmail'  => $order->billing_email,  //주문자 이메일
            'UserId'     => get_current_user_id(),  //회원 아이디
            'OrdNm'      => $order->billing_first_name . $order->billing_last_name,  //주문자명
            'OrdPhone'   => $order->billing_phone,  //주문자 전화번호
            'OrdAddr'    => $order->billing_address, // 주문자주소
            'RcpNm'      => $order->billing_email,  //수신자명
            'RcpPhone'   => $order->billing_phone,  //수신자 전화번호
            'DlvAddr'    => $order->shipping_address, // 배송지 주소
            'Remark'     => '',
            'CardSelect' => '',
        );

    if ( $this->method_code == 'onlyhp' || $this->method_code == 'hp' ) {
      $pay_form_args = array_merge( $pay_form_args,
          array( //핸드폰 소액결제 변수
              'HP_ID'       => $this->ags_hp_id, //CPID(발급된ID)-10
              'HP_PWD'      => $this->ags_hp_pwd,  //CP비밀번호-10
              'HP_SUBID'    => $this->ags_hp_subid, //실거래 전화후 받은 상점만
              'ProdCode'    => $this->ags_prodcode,  //발급받은 상품코드
              'HP_UNITType' => $this->ags_unittype //상품종류 디지털-1 실물-2
          )
      );
    }

    if ( $this->method_code == 'onlyvirtual' || $this->method_code == 'virtual' ) {
      $pay_form_args = array_merge( $pay_form_args,
          array( //가상계좌
              'MallPage'       => '/?wc-api=ags_pay_callback_virtual',
            // 입/출금 통보를 위한 필수, 페이지주소는 도메인주소를 제외한 '/'이후 주소-100
              'VIRTUAL_DEPODT' => '',
            //입금가능한 기한을 지정하는 기능, 발급일자로부터 최대 15일 이내로만 설정, Default 발급일자로부터 5일 이후로 설정
          )
      );
    }

    $pay_form_inputs = apply_filters( 'woocommerce_pay_form_args', $pay_form_args );

    return $pay_form_inputs;
  }

  function js_and_css() {

    if ( $this->enable_https != 'on' ) {
      wp_enqueue_script( 'ags_wallet', 'http://www.allthegate.com/plugin/AGSWallet_utf8.js' );  //맨앞에 넣음
    } else {
      wp_enqueue_script( 'ags_wallet', 'https://www.allthegate.com/plugin/AGSWallet_ssl.js' );  //맨앞에 넣음
    }
    wp_enqueue_script( 'ags_pay', $this->assets_url . 'js/agspay.js' );  //맨앞에 넣음

    /*
	<script language=javascript src="http://www.allthegate.com/plugin/AGSWallet.js"></script>
	<!-- ※ UTF8 언어 형식으로 페이지 제작시 아래 경로의 js 파일을 사용할 것!! -->
	<!-- script language=javascript src="http://www.allthegate.com/plugin/AGSWallet_utf8.js"></script -->
	<!-- Euc-kr 이 아닌 다른 charset 을 이용할 경우에는 AGS_pay_ing(결제처리페이지) 상단의
		[ AGS_pay.html 로 부터 넘겨받을 데이터파라미터 ] 선언부에서 파라미터 값들을 euc-kr로
		인코딩 변환을 해주시기 바랍니다.
	<!-- ※ SSL 보안을 이용할 경우 아래 경로의 js 파일을 사용할 것!! -->
	<!-- script language=javascript src="https://www.allthegate.com/plugin/AGSWallet_ssl.js"></script -->
	*/
  }

  // 이걸 빼면 안됨
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

    if ( isset( $_POST['AuthTy'] ) ) {  // PG가 연결되었을때만 (무통장입금이 아닐때)

      /**********************************************************************************************
       *
       * 파일명 : AGS_pay_result.php
       * 작성일자 : 2012/04/30
       *
       * 소켓결제결과를 처리합니다.
       *
       * Copyright AEGIS ENTERPRISE.Co.,Ltd. All rights reserved.
       *
       **********************************************************************************************/

      //공통사용
      $AuthTy   = trim( $_POST["AuthTy"] );        //결제형태
      $SubTy    = trim( $_POST["SubTy"] );        //서브결제형태
      $rStoreId = trim( $_POST["rStoreId"] );      //업체ID
      $rAmt     = trim( $_POST["rAmt"] );        //거래금액
      $rOrdNo   = trim( $_POST["rOrdNo"] );        //주문번호
      $rProdNm  = trim( $_POST["rProdNm"] );      //상품명
      $rOrdNm   = trim( $_POST["rOrdNm"] );        //주문자명

      //소켓통신결제(신용카드,핸드폰,일반가상계좌)시 사용
      $rSuccYn = trim( $_POST["rSuccYn"] );      //성공여부
      $rResMsg = trim( $_POST["rResMsg"] );      //실패사유
      $rApprTm = trim( $_POST["rApprTm"] );      //승인시각

      //신용카드공통
      $rBusiCd = trim( $_POST["rBusiCd"] );      //전문코드
      $rApprNo = trim( $_POST["rApprNo"] );      //승인번호
      $rCardCd = trim( $_POST["rCardCd"] );      //카드사코드
      $rDealNo = trim( $_POST["rDealNo"] );      //거래고유번호

      //신용카드(안심,일반)
      $rCardNm = trim( $_POST["rCardNm"] );      //카드사명
      $rMembNo = trim( $_POST["rMembNo"] );      //가맹점번호
      $rAquiCd = trim( $_POST["rAquiCd"] );      //매입사코드
      $rAquiNm = trim( $_POST["rAquiNm"] );      //매입사명


      //계좌이체
      $ICHE_OUTBANKNAME = trim( $_POST["ICHE_OUTBANKNAME"] );    //이체계좌은행명
      //$ICHE_OUTACCTNO 	= trim( $_POST["ICHE_OUTACCTNO"] );			//이체계좌번호 - 안넘어오고 있슴
      $ICHE_OUTBANKMASTER = trim( $_POST["ICHE_OUTBANKMASTER"] );    //이체계좌소유주
      $ICHE_AMOUNT        = trim( $_POST["ICHE_AMOUNT"] );      //이체금액

      //핸드폰
      $rHP_TID       = trim( $_POST["rHP_TID"] );      //핸드폰결제TID
      $rHP_DATE      = trim( $_POST["rHP_DATE"] );      //핸드폰결제날짜
      $rHP_HANDPHONE = trim( $_POST["rHP_HANDPHONE"] );    //핸드폰결제핸드폰번호
      $rHP_COMPANY   = trim( $_POST["rHP_COMPANY"] );    //핸드폰결제통신사명(SKT,KTF,LGT)

      //ARS
      $rARS_PHONE = trim( $_POST["rARS_PHONE"] );        //ARS결제전화번호

      //가상계좌
      $rVirNo           = trim( $_POST["rVirNo"] );        //가상계좌번호 가상계좌추가
      $VIRTUAL_CENTERCD = trim( $_POST["VIRTUAL_CENTERCD"] );  //가상계좌 입금은행코드

      //이지스에스크로
      $ES_SENDNO = trim( $_POST["ES_SENDNO"] );        //이지스에스크로(전문번호)

      //*******************************************************************************
      //* MD5 결제 데이터 정상여부 확인
      //* 결제전 AGS_HASHDATA 값과 결제 후 rAGS_HASHDATA의 일치 여부 확인
      //* 형태 : 상점아이디(StoreId) + 주문번호(OrdNo) + 결제금액(Amt)
      //*******************************************************************************

      $AGS_HASHDATA  = trim( $_POST["AGS_HASHDATA"] );
      $rAGS_HASHDATA = md5( $rStoreId . $rOrdNo . (int) $rAmt );

      if ( $AGS_HASHDATA == $rAGS_HASHDATA ) {
        $errResMsg = "";
      } else {
        $errResMsg = "결재금액 변조 발생. 확인 바람";
      }

      $order_id = $rOrdNo;
      $order    = new WC_Order( $order_id );

      Sym_Custom_Data::extend( $order );

      $order->custom->order_receipt_data = array(
          'ags_retailer_id' => $rStoreId,
          'ags_approve'     => $rApprNo,
          'ags_send_no'     => $rDealNo,
          'ags_send_dt'     => $rApprTm,
      );
      $order->custom->save();

      if ( isset( $order->custom->order_receipt_data ) ) {
        $custom_data = $order->custom->order_receipt_data;
      }

      $ags_retailer_id = $custom_data['ags_retailer_id'];
      $ags_approve     = $custom_data['ags_approve'];
      $ags_send_no     = $custom_data['ags_send_no'];
      $ags_send_dt     = $custom_data['ags_send_dt'];

      $html = '주문에 감사드립니다. [신용카드] 결제시에만 <input type="button" value="영수증 인쇄" onclick="javascript:show_receipt();"> 가 발행 가능합니다.';

      $html .= '
	<script language=javascript>
	<!--
	/***********************************************************************************
	* ◈ 영수증 출력을 위한 자바스크립트
	*
	*	영수증 출력은 [카드결제]시에만 사용하실 수 있습니다.
	*
	*   ※당일 결제건에 한해서 영수증 출력이 가능합니다.
	*     당일 이후에는 아래의 주소를 팝업(630X510)으로 띄워 내역 조회 후 출력하시기 바랍니다.
	*	  ▷ 팝업용 결제내역조회 패이지 주소 :
	*	     	 http://www.allthegate.com/support/card_search.html
	*		(반드시 스크롤바를 "yes" 상태로 하여 팝업을 띄우시기 바랍니다.)*
	***********************************************************************************/
	function show_receipt()
	{
		if("' . $rSuccYn . '"== "y" ) {
			var send_dt = "' . $ags_send_dt . '";

			url="http://www.allthegate.com/customer/receiptLast3.jsp";
			url=url+"?sRetailer_id=' . $ags_retailer_id . '";
			url=url+"&approve=' . $ags_approve . '";
			url=url+"&send_no=' . $ags_send_no . '";
			url=url+"&send_dt="+send_dt.substring(0,8);
			window.open(url, "window","toolbar=no,location=no,directories=no,status=,menubar=no,scrollbars=no,resizable=no,width=420,height=700,top=0,left=150");
		} else  {
			alert("해당하는 결제내역이 없습니다");
		}

	}
	-->
	</script>
			';

      return $html;
    }
  }

}


class Ags_Pay_Callback extends WC_Payment_Gateway {

  public $_prefix;
  public $_folder;
  public $assets_url;
  public $method = "신용카드";

  public function __construct() {

    $this->_prefix = WSKL_PREFIX;

    $tmp_arr          = explode( "/", dirname( __FILE__ ) );
    $this->_folder    = $tmp_arr[ count( $tmp_arr ) - 3 ]; //_folder = plugin folder name
    $this->assets_url = plugins_url( $this->_folder . '/assets/' );

    add_action( 'woocommerce_api_ags_pay_callback', array( $this, 'pay_callback' ) );
  }

  function pay_callback() {

    global $woocommerce;

    global $woocommerce_ver21_less;
    @ob_clean();
    /*결제준비AGS_pay.html (UTF-8) 에서 결제실행 AGS_pay_ing.html (EUC-KR)로 보내야되므로
		POST값들을 아래와 같이 컨버젼해주어야 한다 */
    /////////////////////////////////////////////////////////////////////////////
    header( 'Content-type: text/html; charset=euc-kr' );
    foreach ( $_POST as $key => $row ) {
      $_POST[ $key ] = iconv( "UTF-8", "EUC-KR", $row );
    }

    ///////////////////////////////////////////////////////////////////////////////

    $order_id = $_POST["OrdNo"];
    $order    = new WC_Order( $order_id );

    require_once dirname( __FILE__ ) . '/homeags/AGSLib.php';
    /********************************************************************************
     *
     * 파일명 : AGS_pay_ing.php
     * 최종수정일자 : 2012/04/30
     *
     * 올더게이트 플러그인에서 리턴된 데이타를 받아서 소켓결제요청을 합니다.
     *
     * Copyright AEGIS ENTERPRISE.Co.,Ltd. All rights reserved.
     *
     *
     *  ※ 유의사항 ※
     *  1.  "|"(파이프) 값은 결제처리 중 구분자로 사용하는 문자이므로 결제 데이터에 "|"이 있을경우
     *   결제가 정상적으로 처리되지 않습니다.(수신 데이터 길이 에러 등의 사유)
     ********************************************************************************/


    /****************************************************************************
     *
     * [1] 라이브러리(AGSLib.php)를 인클루드 합니다.
     *
     ****************************************************************************/

    /****************************************************************************
     *
     * [2]. agspay4.0 클래스의 인스턴스를 생성합니다.
     *
     ****************************************************************************/
    $agspay = new agspay40;


    /****************************************************************************
     *
     * [3] AGS_pay.html 로 부터 넘겨받을 데이타
     *
     ****************************************************************************/

    /*공통사용*/
    //$agspay->SetValue("AgsPayHome","C:/htdocs/agspay");			//올더게이트 결제설치 디렉토리 (상점에 맞게 수정)
    $agspay->SetValue( "AgsPayHome", dirname( __FILE__ ) . '/homeags' );      //올더게이트 결제설치 디렉토리 (상점에 맞게 수정)
    $agspay->SetValue( "StoreId", trim( $_POST["StoreId"] ) );    //상점아이디
    $agspay->SetValue( "log", "false" );              //true : 로그기록, false : 로그기록안함.
    $agspay->SetValue( "logLevel", "INFO" );            //로그레벨 : DEBUG, INFO, WARN, ERROR, FATAL (해당 레벨이상의 로그만 기록됨)
    $agspay->SetValue( "UseNetCancel", "true" );          //true : 망취소 사용. false: 망취소 미사용
    $agspay->SetValue( "Type", "Pay" );              //고정값(수정불가)
    $agspay->SetValue( "RecvLen", 7 );              //수신 데이터(길이) 체크 에러시 6 또는 7 설정.


    $agspay->SetValue( "AuthTy", trim( $_POST["AuthTy"] ) );      //결제형태
    $agspay->SetValue( "SubTy", trim( $_POST["SubTy"] ) );      //서브결제형태
    $agspay->SetValue( "OrdNo", trim( $_POST["OrdNo"] ) );      //주문번호
    $agspay->SetValue( "Amt", trim( $_POST["Amt"] ) );        //금액
    $agspay->SetValue( "UserEmail", trim( $_POST["UserEmail"] ) );  //주문자이메일
    $agspay->SetValue( "ProdNm", trim( $_POST["ProdNm"] ) );      //상품명
    $AGS_HASHDATA = trim( $_POST["AGS_HASHDATA"] );    //암호화 HASHDATA

    /*신용카드&가상계좌사용*/
    $agspay->SetValue( "MallUrl", trim( $_POST["MallUrl"] ) );    //MallUrl(무통장입금) - 상점 도메인 가상계좌추가
    $agspay->SetValue( "UserId", trim( $_POST["UserId"] ) );      //회원아이디


    /*신용카드사용*/
    $agspay->SetValue( "OrdNm", trim( $_POST["OrdNm"] ) );      //주문자명
    $agspay->SetValue( "OrdPhone", trim( $_POST["OrdPhone"] ) );    //주문자연락처
    $agspay->SetValue( "OrdAddr", trim( $_POST["OrdAddr"] ) );    //주문자주소 가상계좌추가
    $agspay->SetValue( "RcpNm", trim( $_POST["RcpNm"] ) );      //수신자명
    $agspay->SetValue( "RcpPhone", trim( $_POST["RcpPhone"] ) );    //수신자연락처
    $agspay->SetValue( "DlvAddr", trim( $_POST["DlvAddr"] ) );    //배송지주소
    $agspay->SetValue( "Remark", trim( $_POST["Remark"] ) );      //비고
    $agspay->SetValue( "DeviId", trim( $_POST["DeviId"] ) );      //단말기아이디
    $agspay->SetValue( "AuthYn", trim( $_POST["AuthYn"] ) );      //인증여부
    $agspay->SetValue( "Instmt", trim( $_POST["Instmt"] ) );      //할부개월수
    $agspay->SetValue( "UserIp", $_SERVER["REMOTE_ADDR"] );    //회원 IP

    /*신용카드(ISP)*/
    $agspay->SetValue( "partial_mm", trim( $_POST["partial_mm"] ) );    //일반할부기간
    $agspay->SetValue( "noIntMonth", trim( $_POST["noIntMonth"] ) );    //무이자할부기간
    $agspay->SetValue( "KVP_CURRENCY", trim( $_POST["KVP_CURRENCY"] ) );  //KVP_통화코드
    $agspay->SetValue( "KVP_CARDCODE", trim( $_POST["KVP_CARDCODE"] ) );  //KVP_카드사코드
    $agspay->SetValue( "KVP_SESSIONKEY", $_POST["KVP_SESSIONKEY"] );  //KVP_SESSIONKEY
    $agspay->SetValue( "KVP_ENCDATA", $_POST["KVP_ENCDATA"] );      //KVP_ENCDATA
    $agspay->SetValue( "KVP_CONAME", trim( $_POST["KVP_CONAME"] ) );    //KVP_카드명
    $agspay->SetValue( "KVP_NOINT", trim( $_POST["KVP_NOINT"] ) );    //KVP_무이자=1 일반=0
    $agspay->SetValue( "KVP_QUOTA", trim( $_POST["KVP_QUOTA"] ) );    //KVP_할부개월

    /*신용카드(안심)*/
    $agspay->SetValue( "CardNo", trim( $_POST["CardNo"] ) );      //카드번호
    $agspay->SetValue( "MPI_CAVV", $_POST["MPI_CAVV"] );      //MPI_CAVV
    $agspay->SetValue( "MPI_ECI", $_POST["MPI_ECI"] );        //MPI_ECI
    $agspay->SetValue( "MPI_MD64", $_POST["MPI_MD64"] );      //MPI_MD64

    /*신용카드(일반)*/
    $agspay->SetValue( "ExpMon", trim( $_POST["ExpMon"] ) );        //유효기간(월)
    $agspay->SetValue( "ExpYear", trim( $_POST["ExpYear"] ) );      //유효기간(년)
    $agspay->SetValue( "Passwd", trim( $_POST["Passwd"] ) );        //비밀번호
    $agspay->SetValue( "SocId", trim( $_POST["SocId"] ) );        //주민등록번호/사업자등록번호

    /*계좌이체사용*/
    $agspay->SetValue( "ICHE_OUTBANKNAME", trim( $_POST["ICHE_OUTBANKNAME"] ) );    //이체은행명
    $agspay->SetValue( "ICHE_OUTACCTNO", trim( $_POST["ICHE_OUTACCTNO"] ) );      //이체계좌번호
    $agspay->SetValue( "ICHE_OUTBANKMASTER", trim( $_POST["ICHE_OUTBANKMASTER"] ) );  //이체계좌소유주
    $agspay->SetValue( "ICHE_AMOUNT", trim( $_POST["ICHE_AMOUNT"] ) );        //이체금액

    /*핸드폰사용*/
    $agspay->SetValue( "HP_SERVERINFO", trim( $_POST["HP_SERVERINFO"] ) );  //SERVER_INFO(핸드폰결제)
    $agspay->SetValue( "HP_HANDPHONE", trim( $_POST["HP_HANDPHONE"] ) );    //HANDPHONE(핸드폰결제)
    $agspay->SetValue( "HP_COMPANY", trim( $_POST["HP_COMPANY"] ) );      //COMPANY(핸드폰결제)
    $agspay->SetValue( "HP_ID", trim( $_POST["HP_ID"] ) );          //HP_ID(핸드폰결제)
    $agspay->SetValue( "HP_SUBID", trim( $_POST["HP_SUBID"] ) );        //HP_SUBID(핸드폰결제)
    $agspay->SetValue( "HP_UNITType", trim( $_POST["HP_UNITType"] ) );    //HP_UNITType(핸드폰결제)
    $agspay->SetValue( "HP_IDEN", trim( $_POST["HP_IDEN"] ) );        //HP_IDEN(핸드폰결제)
    $agspay->SetValue( "HP_IPADDR", trim( $_POST["HP_IPADDR"] ) );      //HP_IPADDR(핸드폰결제)

    /*ARS사용*/
    $agspay->SetValue( "ARS_NAME", trim( $_POST["ARS_NAME"] ) );        //ARS_NAME(ARS결제)
    $agspay->SetValue( "ARS_PHONE", trim( $_POST["ARS_PHONE"] ) );      //ARS_PHONE(ARS결제)

    /*가상계좌사용*/
    $agspay->SetValue( "VIRTUAL_CENTERCD", trim( $_POST["VIRTUAL_CENTERCD"] ) );  //은행코드(가상계좌)
    $agspay->SetValue( "VIRTUAL_DEPODT", trim( $_POST["VIRTUAL_DEPODT"] ) );    //입금예정일(가상계좌)
    $agspay->SetValue( "ZuminCode", trim( $_POST["ZuminCode"] ) );        //주민번호(가상계좌)
    $agspay->SetValue( "MallPage", trim( $_POST["MallPage"] ) );          //상점 입/출금 통보 페이지(가상계좌)
    $agspay->SetValue( "VIRTUAL_NO", trim( $_POST["VIRTUAL_NO"] ) );        //가상계좌번호(가상계좌)

    /*에스크로사용*/
    $agspay->SetValue( "ES_SENDNO", trim( $_POST["ES_SENDNO"] ) );        //에스크로전문번호

    /*계좌이체(소켓) 결제 사용 변수*/
    $agspay->SetValue( "ICHE_SOCKETYN", trim( $_POST["ICHE_SOCKETYN"] ) );      //계좌이체(소켓) 사용 여부
    $agspay->SetValue( "ICHE_POSMTID", trim( $_POST["ICHE_POSMTID"] ) );        //계좌이체(소켓) 이용기관주문번호
    $agspay->SetValue( "ICHE_FNBCMTID", trim( $_POST["ICHE_FNBCMTID"] ) );      //계좌이체(소켓) FNBC거래번호
    $agspay->SetValue( "ICHE_APTRTS", trim( $_POST["ICHE_APTRTS"] ) );        //계좌이체(소켓) 이체 시각
    $agspay->SetValue( "ICHE_REMARK1", trim( $_POST["ICHE_REMARK1"] ) );        //계좌이체(소켓) 기타사항1
    $agspay->SetValue( "ICHE_REMARK2", trim( $_POST["ICHE_REMARK2"] ) );        //계좌이체(소켓) 기타사항2
    $agspay->SetValue( "ICHE_ECWYN", trim( $_POST["ICHE_ECWYN"] ) );          //계좌이체(소켓) 에스크로여부
    $agspay->SetValue( "ICHE_ECWID", trim( $_POST["ICHE_ECWID"] ) );          //계좌이체(소켓) 에스크로ID
    $agspay->SetValue( "ICHE_ECWAMT1", trim( $_POST["ICHE_ECWAMT1"] ) );        //계좌이체(소켓) 에스크로결제금액1
    $agspay->SetValue( "ICHE_ECWAMT2", trim( $_POST["ICHE_ECWAMT2"] ) );        //계좌이체(소켓) 에스크로결제금액2
    $agspay->SetValue( "ICHE_CASHYN", trim( $_POST["ICHE_CASHYN"] ) );        //계좌이체(소켓) 현금영수증발행여부
    $agspay->SetValue( "ICHE_CASHGUBUN_CD", trim( $_POST["ICHE_CASHGUBUN_CD"] ) );  //계좌이체(소켓) 현금영수증구분
    $agspay->SetValue( "ICHE_CASHID_NO", trim( $_POST["ICHE_CASHID_NO"] ) );      //계좌이체(소켓) 현금영수증신분확인번호

    /*계좌이체-텔래뱅킹(소켓) 결제 사용 변수*/
    $agspay->SetValue( "ICHEARS_SOCKETYN", trim( $_POST["ICHEARS_SOCKETYN"] ) );  //텔레뱅킹계좌이체(소켓) 사용 여부
    $agspay->SetValue( "ICHEARS_ADMNO", trim( $_POST["ICHEARS_ADMNO"] ) );      //텔레뱅킹계좌이체 승인번호
    $agspay->SetValue( "ICHEARS_POSMTID", trim( $_POST["ICHEARS_POSMTID"] ) );    //텔레뱅킹계좌이체 이용기관주문번호
    $agspay->SetValue( "ICHEARS_CENTERCD", trim( $_POST["ICHEARS_CENTERCD"] ) );  //텔레뱅킹계좌이체 은행코드
    $agspay->SetValue( "ICHEARS_HPNO", trim( $_POST["ICHEARS_HPNO"] ) );      //텔레뱅킹계좌이체 휴대폰번호

    /****************************************************************************
     *
     * [4] 올더게이트 결제서버로 결제를 요청합니다.
     *
     ****************************************************************************/
    $agspay->startPay();


    /****************************************************************************
     *
     * [5] 결제결과에 따른 상점DB 저장 및 기타 필요한 처리작업을 수행하는 부분입니다.
     *
     *  아래의 결과값들을 통하여 각 결제수단별 결제결과값을 사용하실 수 있습니다.
     *
     *  -- 공통사용 --
     *  업체ID : $agspay->GetResult("rStoreId")
     *  주문번호 : $agspay->GetResult("rOrdNo")
     *  상품명 : $agspay->GetResult("rProdNm")
     *  거래금액 : $agspay->GetResult("rAmt")
     *  성공여부 : $agspay->GetResult("rSuccYn") (성공:y 실패:n)
     *  결과메시지 : $agspay->GetResult("rResMsg")
     *
     *  1. 신용카드
     *
     *  전문코드 : $agspay->GetResult("rBusiCd")
     *  거래번호 : $agspay->GetResult("rDealNo")
     *  승인번호 : $agspay->GetResult("rApprNo")
     *  할부개월 : $agspay->GetResult("rInstmt")
     *  승인시각 : $agspay->GetResult("rApprTm")
     *  카드사코드 : $agspay->GetResult("rCardCd")
     *
     *  2.계좌이체(인터넷뱅킹/텔레뱅킹)
     *  에스크로주문번호 : $agspay->GetResult("ES_SENDNO") (에스크로 결제시)
     *
     *  3.가상계좌
     *  가상계좌의 결제성공은 가상계좌발급의 성공만을 의미하며 입금대기상태로 실제 고객이 입금을 완료한 것은 아닙니다.
     *  따라서 가상계좌 결제완료시 결제완료로 처리하여 상품을 배송하시면 안됩니다.
     *  결제후 고객이 발급받은 계좌로 입금이 완료되면 MallPage(상점 입금통보 페이지(가상계좌))로 입금결과가 전송되며
     *  이때 비로소 결제가 완료되게 되므로 결제완료에 대한 처리(배송요청 등)은  MallPage에 작업해주셔야 합니다.
     *  결제종류 : $agspay->GetResult("rAuthTy") (가상계좌 일반 : vir_n 유클릭 : vir_u 에스크로 : vir_s)
     *  승인일자 : $agspay->GetResult("rApprTm")
     *  가상계좌번호 : $agspay->GetResult("rVirNo")
     *
     *  4.핸드폰결제
     *  핸드폰결제일 : $agspay->GetResult("rHP_DATE")
     *  핸드폰결제 TID : $agspay->GetResult("rHP_TID")
     *
     *  5.ARS결제
     *  ARS결제일 : $agspay->GetResult("rHP_DATE")
     *  ARS결제 TID : $agspay->GetResult("rHP_TID")
     *
     ****************************************************************************/
    // "지불처리중" 팝업창 닫기

    if ( $agspay->GetResult( "rSuccYn" ) == "y" ) {
      if ( $agspay->GetResult( "AuthTy" ) == "virtual" ) {
        //가상계좌결제의 경우 입금이 완료되지 않은 입금대기상태(가상계좌 발급성공)이므로 상품을 배송하시면 안됩니다.

      } else {
        // 결제성공에 따른 상점처리부분
        //echo ("결제가 성공처리되었습니다. [" . $agspay->GetResult("rSuccYn")."]". $agspay->GetResult("rResMsg").". " );

        if ( $woocommerce_ver21_less ) {
          $return_url = add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order_id, get_permalink( woocommerce_get_page_id( 'thanks' ) ) ) );
        } else {
          $return_url = $this->get_return_url( $order );
        }

        $order->add_order_note( sprintf( __( '결제가 성공적으로 처리됨. 결제방법: %s. 올더게이트 TID: %s. 발생시각: %s.', $this->_folder ), $this->method, '111', date( 'YmdHis' ) ) );

        // Complete payment, reduce stock levels & remove cart
        $order->payment_complete();
        $order->reduce_order_stock();
        $woocommerce->cart->empty_cart();

        ?>
        <html>
        <head>
        </head>
        <body onload="javascript:frmAGS_pay_ing.submit();">
        <form name=frmAGS_pay_ing method=post action="<?= $return_url ?>">

          <!-- 각 결제 공통 사용 변수 -->
          <input type=hidden name=AuthTy value="<?= $agspay->GetResult( "AuthTy" ) ?>">    <!-- 결제형태 -->
          <input type=hidden name=SubTy value="<?= $agspay->GetResult( "SubTy" ) ?>">      <!-- 서브결제형태 -->
          <input type=hidden name=rStoreId value="<?= $agspay->GetResult( "rStoreId" ) ?>">  <!-- 상점아이디 -->
          <input type=hidden name=rOrdNo value="<?= $agspay->GetResult( "rOrdNo" ) ?>">    <!-- 주문번호 -->
          <input type=hidden name=rProdNm value="<?= $agspay->GetResult( "ProdNm" ) ?>">    <!-- 상품명 -->
          <input type=hidden name=rAmt value="<?= $agspay->GetResult( "rAmt" ) ?>">      <!-- 결제금액 -->
          <input type=hidden name=rOrdNm value="<?= $agspay->GetResult( "OrdNm" ) ?>">    <!-- 주문자명 -->
          <input type=hidden name=AGS_HASHDATA value="<?= $AGS_HASHDATA ?>">        <!-- 암호화 HASHDATA -->

          <input type=hidden name=rSuccYn value="<?= $agspay->GetResult( "rSuccYn" ) ?>">  <!-- 성공여부 -->
          <input type=hidden name=rResMsg value="<?= $agspay->GetResult( "rResMsg" ) ?>">  <!-- 결과메시지 -->
          <input type=hidden name=rApprTm value="<?= $agspay->GetResult( "rApprTm" ) ?>">  <!-- 결제시간 -->

          <!-- 신용카드 결제 사용 변수 -->
          <input type=hidden name=rBusiCd value="<?= $agspay->GetResult( "rBusiCd" ) ?>">    <!-- (신용카드공통)전문코드 -->
          <input type=hidden name=rApprNo value="<?= $agspay->GetResult( "rApprNo" ) ?>">    <!-- (신용카드공통)승인번호 -->
          <input type=hidden name=rCardCd value="<?= $agspay->GetResult( "rCardCd" ) ?>">  <!-- (신용카드공통)카드사코드 -->
          <input type=hidden name=rDealNo value="<?= $agspay->GetResult( "rDealNo" ) ?>">      <!-- (신용카드공통)거래번호 -->

          <input type=hidden name=rCardNm value="<?= $agspay->GetResult( "rCardNm" ) ?>">  <!-- (안심클릭,일반사용)카드사명 -->
          <input type=hidden name=rMembNo value="<?= $agspay->GetResult( "rMembNo" ) ?>">  <!-- (안심클릭,일반사용)가맹점번호 -->
          <input type=hidden name=rAquiCd value="<?= $agspay->GetResult( "rAquiCd" ) ?>">    <!-- (안심클릭,일반사용)매입사코드 -->
          <input type=hidden name=rAquiNm value="<?= $agspay->GetResult( "rAquiNm" ) ?>">  <!-- (안심클릭,일반사용)매입사명 -->

          <!-- 계좌이체 결제 사용 변수 -->
          <input type=hidden name=ICHE_OUTBANKNAME value="<?= $agspay->GetResult( "ICHE_OUTBANKNAME" ) ?>">
          <!-- 이체은행명 -->
          <input type=hidden name=ICHE_OUTBANKMASTER value="<?= $agspay->GetResult( "ICHE_OUTBANKMASTER" ) ?>">
          <!-- 이체계좌예금주 -->
          <input type=hidden name=ICHE_AMOUNT value="<?= $agspay->GetResult( "ICHE_AMOUNT" ) ?>">          <!-- 이체금액 -->

          <!-- 핸드폰 결제 사용 변수 -->
          <input type=hidden name=rHP_HANDPHONE value="<?= $agspay->GetResult( "HP_HANDPHONE" ) ?>">    <!-- 핸드폰번호 -->
          <input type=hidden name=rHP_COMPANY value="<?= $agspay->GetResult( "HP_COMPANY" ) ?>">
          <!-- 통신사명(SKT,KTF,LGT) -->
          <input type=hidden name=rHP_TID value="<?= $agspay->GetResult( "rHP_TID" ) ?>">          <!-- 결제TID -->
          <input type=hidden name=rHP_DATE value="<?= $agspay->GetResult( "rHP_DATE" ) ?>">        <!-- 결제일자 -->

          <!-- ARS 결제 사용 변수 -->
          <input type=hidden name=rARS_PHONE value="<?= $agspay->GetResult( "ARS_PHONE" ) ?>">      <!-- ARS번호 -->

          <!-- 가상계좌 결제 사용 변수 -->
          <input type=hidden name=rVirNo value="<?= $agspay->GetResult( "rVirNo" ) ?>">          <!-- 가상계좌번호 -->
          <input type=hidden name=VIRTUAL_CENTERCD value="<?= $agspay->GetResult( "VIRTUAL_CENTERCD" ) ?>">
          <!--입금가상계좌은행코드(우리은행:20) -->

          <!-- 이지스에스크로 결제 사용 변수 -->
          <input type=hidden name=ES_SENDNO value="<?= $agspay->GetResult( "ES_SENDNO" ) ?>">
          <!-- 이지스에스크로(전문번호) -->

        </form>
        </body>
        </html>
        <?php
        ///wp_redirect( $return_url);
        exit;
      }
    } else {
      // 결제실패에 따른 상점처리부분
      //echo ("결제가 실패처리되었습니다. [" . $agspay->GetResult("rSuccYn")."]". $agspay->GetResult("rResMsg").". " );
      $res_msg = $agspay->GetResult('rResMsg');

      $order->update_status( 'failed', sprintf( __( '결제가 실패했습니다.<br/>에러메시지 : %s.<br/>발생시각: %s.', 'wskl' ), $res_msg, date( 'Y-m-d H:i:s' ) ) );
      $cart_url = $woocommerce->cart->get_cart_url();
      wp_redirect( $cart_url );
      exit;
    }

    /*******************************************************************
     * [6] 결제가 정상처리되지 못했을 경우 $agspay->GetResult("NetCancID") 값을 이용하여
     * 결제결과에 대한 재확인요청을 할 수 있습니다.
     *
     * 추가 데이터송수신이 발생하므로 결제가 정상처리되지 않았을 경우에만 사용하시기 바랍니다.
     *
     * 사용방법 :
     * $agspay->checkPayResult($agspay->GetResult("NetCancID"));
     *
     *******************************************************************/

    /*
	$agspay->SetValue("Type", "Pay"); // 고정
	$agspay->checkPayResult($agspay->GetResult("NetCancID"));
	*/

    /*******************************************************************
     * [7] 상점DB 저장 및 기타 처리작업 수행실패시 강제취소
     *
     * $cancelReq : "true" 강제취소실행, "false" 강제취소실행안함.
     *
     * 결제결과에 따른 상점처리부분 수행 중 실패하는 경우
     * 아래의 코드를 참조하여 거래를 취소할 수 있습니다.
     *  취소성공여부 : $agspay->GetResult("rCancelSuccYn") (성공:y 실패:n)
     *  취소결과메시지 : $agspay->GetResult("rCancelResMsg")
     *
     * 유의사항 :
     * 가상계좌(virtual)는 강제취소 기능이 지원되지 않습니다.
     *******************************************************************/

    // 상점처리부분 수행실패시 $cancelReq를 "true"로 변경하여
    // 결제취소를 수행되도록 할 수 있습니다.
    // $cancelReq의 "true"값으로 변경조건은 상점에서 판단하셔야 합니다.

    /*
	$cancelReq = "false";

	if($cancelReq == "true")
	{
		$agspay->SetValue("Type", "Cancel"); // 고정
		$agspay->SetValue("CancelMsg", "DB FAIL"); // 취소사유
		$agspay->startPay();
	}
	*/
  }
}


?>