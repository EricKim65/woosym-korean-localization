<?php

class WC_Kcp_Common extends WC_Payment_Gateway {

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
    public $kcp_sitecd;
    public $kcp_sitename;
    public $kcp_sitekey;

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
        $this->id = $this->_pg_agency . '_' . $this->method;
        $this->method_title = __($this->admin_title, $this->_folder);
        $this->method_description = '';

        $this->init_form_fields();
        $this->init_settings();

        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        ////////////////////////////////////////////////////////////////

        //add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        add_action('wp_enqueue_scripts', array($this, 'js_and_css'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'order_pay_page'));

        //Woocommerce 2.1 이전 버젼은 아래 내용이 접수가 안됨. www.biozoa.co.kr의 thankyou.php 형태로 바꿈.
        add_filter('woocommerce_thankyou_order_received_text', array($this, 'order_received_text'), 1, 0);

    }
    function local_settings() {
        global $sym_checkout_methods;
        global $sym_pg_title;
        global $sym_checkout_titles;
        global $sym_checkout_desc;
        global $pay_gate_agency;

        /*Common*/
        $this->frontend_title = $sym_checkout_titles[$this->method];
        $this->frontend_desc = $sym_checkout_titles[$this->method] . $sym_checkout_desc;

        $this->_prefix = WSKL_PREFIX;
        $this->_pg_agency = $pay_gate_agency;

        $tmp_arr = explode('/', dirname(__FILE__));
        $this->_folder = $tmp_arr[count($tmp_arr) - 3]; //_folder = plugin folder name
        $this->assets_url = plugins_url($this->_folder . '/assets/');
        $this->admin_title = $sym_pg_title . ' ' . $sym_checkout_titles[$this->method];

        $this->enable_testmode = get_option($this->_prefix . 'enable_testmode');
        $this->enable_https = get_option($this->_prefix . 'enable_https');
        $this->enable_escrow = get_option($this->_prefix . 'enable_escrow');

        $this->enable_showinputs = get_option($this->_prefix . 'enable_showinputs');
        $this->is_mobile = $this->mobile_ok();

        /*Local*/

        if ($this->enable_testmode != 'on') {
            $this->kcp_sitecd = get_option($this->_prefix . 'kcp_sitecd');
            $this->kcp_sitename = get_option($this->_prefix . 'kcp_sitename');
            $this->kcp_sitekey = get_option($this->_prefix . 'kcp_sitekey');
        }
        else {
            $this->kcp_sitecd = 'T0000';
            $this->kcp_sitename = 'KCP TEST SHOP';
            $this->kcp_sitekey = '3grptw1.zW0GSo4PQdaGvsF__';
        }

        //$pay_methods = array();
        /*foreach ($sym_checkout_methods as $key => $value) {
            echo 'key='. $key. '---value='. $value. '<br>';
         }*/

        if ($this->method == 'credit') {
            $this->method_code = '100000000000';
        }
        else if ($this->method == 'remit') {
            $this->method_code = '010000000000';
        }
        else if ($this->method == 'virtual') {
            $this->method_code = '001000000000';
        }
        else if ($this->method == 'mobile') {
            $this->method_code = '000010000000';
        }

        /* if ($this->method == 'credit') {
             if (count($sym_checkout_methods) == 1) {
                 $this->method_code = 'onlyiche';
             } else {
                 $this->method_code = 'iche';
             }
         }*/

    }

    function init_form_fields() {  // action에 포함되는 것이므로 include로 뺄수 없슴.
        $this->form_fields = array('enabled' => array('title' => __('활성화/비활성화', $this->_folder), 'type' => 'checkbox', 'label' => __('해당 결제방법을 활성화합니다.<br/> 활성화 후에 상단에 있는 [결제 옵션]-[지불게이트 웨이]메뉴에서 [회원 결제 페이지]의 표시 순서를 조정하십시요. ', $this->_folder), 'default' => 'yes'), 'title' => array('title' => __('제목', $this->_folder), 'type' => 'text', 'description' => __(' [회원 결제 페이지]에 보여지는 제목입니다.', $this->_folder), 'default' => __($this->frontend_title, $this->_folder),), 'description' => array('title' => __('설명', $this->_folder), 'type' => 'textarea', 'description' => __(' [회원 결제 페이지]에 보여지는 설명입니다.', $this->_folder), 'default' => __($this->frontend_desc, $this->_folder)),);
    }


    function order_pay_page($order_id) {
        $order = new WC_Order($order_id);
        echo __('<p><h4><font color="red">결제 처리가 완료될때까지 기다려 주시기 바랍니다. </font></h4>  </p>', $this->_folder);
        echo $this->order_pay_form($order_id);
    }

    function order_pay_form($order_id) {
        global $woocommerce;

        $order = new WC_Order($order_id);

        require_once dirname(__FILE__) . '/kcppay_noedit.php';
        $ret_url = home_url('/?wc-api=kcp_pay_callback');
        $buf_form = '<form name="order_info" method="post" action="' . $ret_url . '" >';

        $pay_form_args = $this->pay_form_input_args($order);

        $pay_form_inputs = '';
        foreach ($pay_form_args as $key => $value) {
            if ($this->enable_showinputs != 'on') {
                $pay_form_inputs .= '<input type="hidden" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
            }
            else {
                $pay_form_inputs .= '<table><tr><td>' . esc_attr($key) . '</td><td><input type="text" style="width:300px;" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" /></td></tr></table>';
            }
        }

        $buf_form .= $pay_form_inputs;
        $buf_form .= $kcp_noedit;
        $buf_form .= '</form>';

        if (!$this->is_mobile) {  //PC 결제
            $buf_form = $buf_form . "
			";
        }
        else {  //모바일 결제
            $buf_form = $buf_form . "
			";
        }

        return $buf_form;
    }

    function pay_form_input_args($order) {
        global $woocommerce;

        $order_id = $order->id;

        //$this->billing_phone = $order->billing_phone;

        if (sizeof($order->get_items()) > 0) {
            foreach ($order->get_items() as $item) {
                $item_name = $item['name'];
                break;
                // if ( $item[ 'qty' ] ) {
                //    $item_name = $item[ 'name' ];
                // }
            }
        }

        $pay_form_args = array(  // 공통 변수
            // 주문정보 입력
            'ordr_idxx' => $order_id, 'pay_method' => $this->method_code, 'good_name' => $item_name,  // 상품명
            'good_mny' => (int)$order->order_total,  // 결제금액 ,(콤마)를 제외한 숫자만 입력
            'buyr_name' => $order->billing_first_name . $order->billing_last_name, //주문자 성명
            'buyr_mail' => $order->billing_email,  // 주문자 이메일
            'buyr_tel1' => $order->billing_phone,  //전화번호,  - 포함
            'buyr_tel2' => $order->billing_mobile, //휴대폰번호, - 포함

            //가맹점 필수정보
            'req_tx' => 'pay', 'site_cd' => $this->kcp_sitecd, 'site_name' => $this->kcp_sitename, 'quotaopt' => '12', 'currency' => 'WON',

            //옵션 정보
            //'used_card_YN'					=> 'Y',
            //'used_card'					=> 'CCBC:CCKM:CCSS',
            //'used_card_CCXX'			=> 'Y',
            //'save_ocb'					=> 'Y',
            //'fix_inst'					=> '07',
            //'kcp_noint'					=> '',
            //'kcp_noint_quota'			=> 'CCBC-02:03:06,CCKM-03:06,CCSS-03:06:09',
            //'wish_vbank_list'			=> '05:03:04:07:11:23:26:32:34:81:71',
            'vcnt_expire_term' => '3', //가상계좌 입금 시한 3일
            'vcnt_expire_term_time' => '120000', //가상계좌 입금 시간 설정
            //'complex_pnt_yn'			=> 'N',
            //'disp_tax_yn'				=> 'Y',
            //'site_logo'					=> '',
            //'eng_flag'					=> 'Y',
            //'tax_flag'					=> '',
            //'comm_tax_mny'				=> '',
            //'comm_vat_mny'				=> '',
            //'comm_free_mny'				=> '',
            //'skin_indx'					=> '1',
            //'good_cd'					=> '',
        );

        /*if ( $this->method_code == 'onlyhp' || $this->method_code == 'hp' ) {
            $pay_form_args = array_merge($pay_form_args,
                array( //핸드폰 소액결제 변수
                    'HP_ID' => $this->ags_hp_id, //CPID(발급된ID)-10
                    'HP_PWD' => $this->ags_hp_pwd,  //CP비밀번호-10
                    'HP_SUBID' => $this->ags_hp_subid, //실거래 전화후 받은 상점만
                    'ProdCode' => $this->ags_prodcode,  //발급받은 상품코드
                    'HP_UNITType' => $this->ags_unittype //상품종류 디지털-1 실물-2
                )
            );
        }

        if ( $this->method_code == 'onlyvirtual' || $this->method_code == 'virtual' ) {
            $pay_form_args = array_merge($pay_form_args,
                array( //가상계좌
                    'MallPage' => '/?wc-api=ags_pay_callback_virtual',   // 입/출금 통보를 위한 필수, 페이지주소는 도메인주소를 제외한 '/'이후 주소-100
                    'VIRTUAL_DEPODT' => '',  //입금가능한 기한을 지정하는 기능, 발급일자로부터 최대 15일 이내로만 설정, Default 발급일자로부터 5일 이후로 설정
                )
            );
        }*/

        $pay_form_inputs = apply_filters('woocommerce_pay_form_args', $pay_form_args);

        return $pay_form_inputs;
    }


    function js_and_css() {

        if ($this->enable_testmode != 'on') {
            if ($this->enable_https != 'on') {
                wp_enqueue_script('kcp_wallet', 'http://pay.kcp.co.kr/plugin/payplus_un.js');
            }
            else {
                wp_enqueue_script('kcp_wallet', 'https://pay.kcp.co.kr/plugin/payplus_un.js');
            }
        }
        else {
            if ($this->enable_https != 'on') {
                wp_enqueue_script('kcp_wallet', 'http://pay.kcp.co.kr/plugin/payplus_test_un.js');
            }
            else {
                wp_enqueue_script('kcp_wallet', 'https://pay.kcp.co.kr/plugin/payplus_test_un.js');
            }
        }

        wp_enqueue_script('kcp_pay', $this->assets_url . 'js/kcppay.js');  //맨앞에 넣음

        /* =----------------------------------------------------------------------------= */
        /* = 테스트 시 : src="http://pay.kcp.co.kr/plugin/payplus_test.js"              = */
        /* =             src="https://pay.kcp.co.kr/plugin/payplus_test.js"             = */
        /* = 실결제 시 : src="http://pay.kcp.co.kr/plugin/payplus.js"                   = */
        /* =             src="https://pay.kcp.co.kr/plugin/payplus.js"                  = */
        /* =                                                                            = */
        /* = 테스트 시(UTF-8) : src="http://pay.kcp.co.kr/plugin/payplus_test_un.js"    = */
        /* =                    src="https://pay.kcp.co.kr/plugin/payplus_test_un.js"   = */
        /* = 실결제 시(UTF-8) : src="http://pay.kcp.co.kr/plugin/payplus_un.js"         = */
        /* =                    src="https://pay.kcp.co.kr/plugin/payplus_un.js"        = */
        /* ============================================================================== */

        // 이걸 빼면 안됨
    }

    function process_payment($order_id) {
        //global $woocommerce;
        global $woocommerce_ver21_less;

        $order = new WC_Order($order_id);

        if ($woocommerce_ver21_less) {
            $return_array = array('result' => 'success', 'redirect' => add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('pay')))));
        }
        else {
            $return_array = array('result' => 'success', 'redirect' => $order->get_checkout_payment_url(true));
        }
        return $return_array;

    }

    function mobile_ok() {
        $device = $_SERVER['HTTP_USER_AGENT'];
        if (stripos($device, "Android") || stripos($device, "iPhone") || stripos($device, "iPod") || stripos($device, "iPad")) {
            return true;
        }
        else {
            return false;
        }
    }

    function order_received_text() {

         /* = -------------------------------------------------------------------------- = */
        /* =   PAGE : 결과 처리 PAGE                                                    = */
        /* = -------------------------------------------------------------------------- = */
        /* =   pp_ax_hub.php 파일에서 처리된 결과값을 출력하는 페이지입니다.            = */
        /* = -------------------------------------------------------------------------- = */
        /* =   연동시 오류가 발생하는 경우 아래의 주소로 접속하셔서 확인하시기 바랍니다.= */
        /* =   접속 주소 : http://kcp.co.kr/technique.requestcode.do			        = */
        /* = -------------------------------------------------------------------------- = */
        /* =   Copyright (c)  2013   KCP Inc.   All Rights Reserverd.                   = */
        /* ============================================================================== */
        ?>
        <?php
        /* ============================================================================== */
        /* =   지불 결과                                                                = */
        /* = -------------------------------------------------------------------------- = */
        $site_cd = $_POST["site_cd"];      // 사이트코드
        $req_tx = $_POST["req_tx"];      // 요청 구분(승인/취소)
        $use_pay_method = $_POST["use_pay_method"];      // 사용 결제 수단
        $bSucc = $_POST["bSucc"];      // 업체 DB 정상처리 완료 여부
        /* = -------------------------------------------------------------------------- = */
        $res_cd = $_POST["res_cd"];      // 결과코드
        $res_msg = $_POST["res_msg"];      // 결과메시지
        $res_msg_bsucc = "";
        /* = -------------------------------------------------------------------------- = */
        $amount = $_POST["amount"];      // 금액
        $ordr_idxx = $_POST["ordr_idxx"];      // 주문번호
        $tno = $_POST["tno"];      // KCP 거래번호
        $good_mny = $_POST["good_mny"];      // 결제금액
        $good_name = $_POST["good_name"];      // 상품명
        $buyr_name = $_POST["buyr_name"];      // 구매자명
        $buyr_tel1 = $_POST["buyr_tel1"];      // 구매자 전화번호
        $buyr_tel2 = $_POST["buyr_tel2"];      // 구매자 휴대폰번호
        $buyr_mail = $_POST["buyr_mail"];      // 구매자 E-Mail
        /* = -------------------------------------------------------------------------- = */
        // 공통
        $pnt_issue = $_POST["pnt_issue"];      // 포인트 서비스사
        $app_time = $_POST["app_time"];      // 승인시간 (공통)
        /* = -------------------------------------------------------------------------- = */
        // 신용카드
        $card_cd = $_POST["card_cd"];      // 카드코드
        $card_name = $_POST["card_name"];      // 카드명
        $noinf = $_POST["noinf"];      // 무이자 여부
        $quota = $_POST["quota"];      // 할부개월
        $app_no = $_POST["app_no"];      // 승인번호
        /* = -------------------------------------------------------------------------- = */
        // 계좌이체
        $bank_name = $_POST["bank_name"];      // 은행명
        $bank_code = $_POST["bank_code"];      // 은행코드
        /* = -------------------------------------------------------------------------- = */
        // 가상계좌
        $bankname = $_POST["bankname"];      // 입금할 은행
        $depositor = $_POST["depositor"];      // 입금할 계좌 예금주
        $account = $_POST["account"];      // 입금할 계좌 번호
        $va_date = $_POST["va_date"];      // 가상계좌 입금마감시간
        /* = -------------------------------------------------------------------------- = */
        // 포인트
        $add_pnt = $_POST["add_pnt"];      // 발생 포인트
        $use_pnt = $_POST["use_pnt"];      // 사용가능 포인트
        $rsv_pnt = $_POST["rsv_pnt"];      // 총 누적 포인트
        $pnt_app_time = $_POST["pnt_app_time"];      // 승인시간
        $pnt_app_no = $_POST["pnt_app_no"];      // 승인번호
        $pnt_amount = $_POST["pnt_amount"];      // 적립금액 or 사용금액
        /* = -------------------------------------------------------------------------- = */
        //상품권
        $tk_van_code = $_POST["tk_van_code"];      // 발급사 코드
        $tk_app_no = $_POST["tk_app_no"];      // 승인 번호
        /* = -------------------------------------------------------------------------- = */
        //휴대폰
        $commid = $_POST["commid"];      // 통신사 코드
        $mobile_no = $_POST["mobile_no"];      // 휴대폰 번호
        /* = -------------------------------------------------------------------------- = */
        // 현금영수증
        $cash_yn = $_POST["cash_yn"];      //현금영수증 등록 여부
        $cash_authno = $_POST["cash_authno"];      //현금영수증 승인 번호
        $cash_tr_code = $_POST["cash_tr_code"];      //현금영수증 발행 구분
        $cash_id_info = $_POST["cash_id_info"];      //현금영수증 등록 번호
        /* = -------------------------------------------------------------------------- = */

        $req_tx_name = "";

        if ($req_tx == "pay") {
            $req_tx_name = "지불";
        }

        /* ============================================================================== */
        /* =   가맹점 측 DB 처리 실패시 상세 결과 메시지 설정                           = */
        /* = -------------------------------------------------------------------------- = */

        if ($req_tx == "pay") {
            //업체 DB 처리 실패
            if ($bSucc == "false") {
                if ($res_cd == "0000") {
                    $res_msg_bsucc = "결제는 정상적으로 이루어졌지만 업체에서 결제 결과를 처리하는 중 오류가 발생하여 시스템에서 자동으로 취소 요청을 하였습니다. <br> 업체로 문의하여 확인하시기 바랍니다.";
                }
                else {
                    $res_msg_bsucc = "결제는 정상적으로 이루어졌지만 업체에서 결제 결과를 처리하는 중 오류가 발생하여 시스템에서 자동으로 취소 요청을 하였으나, <br> <b>취소가 실패 되었습니다.</b><br> 업체로 문의하여 확인하시기 바랍니다.";
                }
            }
        }

        /* = -------------------------------------------------------------------------- = */
        /* =   가맹점 측 DB 처리 실패시 상세 결과 메시지 설정 끝                        = */
        /* ============================================================================== */

        $html = '
            <script type="text/javascript">
                /* 신용카드 영수증 */
                /* 실결제시 : "https://admin8.kcp.co.kr/assist/bill.BillAction.do?cmd=card_bill&tno=" */
                /* 테스트시 : "https://testadmin8.kcp.co.kr/assist/bill.BillAction.do?cmd=card_bill&tno=" */
                 function receiptView( receipt_url, tno, ordr_idxx, amount )
                {
                    receiptWin = receipt_url;
                    receiptWin += tno + "&";
                    receiptWin += "order_no=" + ordr_idxx + "&";
                    receiptWin += "trade_mony=" + amount ;

                    window.open(receiptWin, "", "width=455, height=815, scrollbars=yes ");
                }

                /* 현금 영수증 */
                /* 실결제시 : "https://admin.kcp.co.kr/Modules/Service/Cash/Cash_Bill_Common_View.jsp" */
                /* 테스트시 : "https://testadmin8.kcp.co.kr/Modules/Service/Cash/Cash_Bill_Common_View.jsp" */
                function receiptView2( receipt_url, site_cd, order_id, bill_yn, auth_no )
                {
                    receiptWin2 = receipt_url;
                    receiptWin2 += "?";
                    receiptWin2 += "term_id=PGNW" + site_cd + "&";
                    receiptWin2 += "orderid=" + order_id + "&";
                    receiptWin2 += "bill_yn=" + bill_yn + "&";
                    receiptWin2 += "authno=" + auth_no ;

                    window.open(receiptWin2, "", "width=370, height=625, scrollbars=yes ");
                }
                /* 가상 계좌 모의입금 페이지 호출 */
                /* 테스트시에만 사용가능 */
                /* 실결제시 해당 스크립트 주석처리 */
                function receiptView3()
                {
                    receiptWin3 = "http://devadmin.kcp.co.kr/Modules/Noti/TEST_Vcnt_Noti.jsp";
                    window.open(receiptWin3, "", "width=520, height=300, scrollbars=yes ");
                }
            </script>
        ';


        $order_id = $ordr_idxx;
        $order = new WC_Order($order_id);

        Sym_Custom_Data::extend($order);
        $order->custom->order_receipt_data =  array(
            'kcp_tno' => $tno,
            'kcp_amount' => $amount,
            'kcp_cash_yn' => $cash_yn,
            'kcp_cash_authno' => $cash_authno
        );
        $order->custom->save();

        if (isset($order->custom->order_receipt_data)) {
            $custom_data = $order->custom->order_receipt_data;
        }

        $kcp_site_cd = get_option($this->_prefix.'kcp_sitecd');
        $kcp_tno = $custom_data['kcp_tno'];
        $kcp_amount = $custom_data['kcp_amount'];
        $kcp_cash_yn = $custom_data['kcp_cash_yn'];
        $kcp_cash_authno = $custom_data['kcp_cash_authno'];

        if ( $use_pay_method == "100000000000" ) {  // 신용카드

            if ($this->enable_testmode != 'on') {
                $receipt_url = 'https://admin8.kcp.co.kr/assist/bill.BillAction.do?cmd=card_bill&tno=';
            }
            else {
                $receipt_url = 'https://testadmin8.kcp.co.kr/assist/bill.BillAction.do?cmd=card_bill&tno=';
            }

            $html .= '주문에 감사드립니다. [신용카드] 결제시에만 <input type="button" value="신용 카드 영수증 인쇄" onclick="javascript:receiptView(\''.$receipt_url. '\', \''.$kcp_tno. '\', \''. $ordr_idxx. '\', \''. $kcp_amount. '\')" > 가 발행 가능합니다.';

      } else if ( $use_pay_method == "010000000000" ) {  //가상계좌
            $html .= '주문에 감사드립니다.  [계좌이체] 가 완료되었습니다. <br/>';

        } else if ( $use_pay_method == "001000000000" ) {
            $html .= '주문에 감사드립니다. <br/>';
            $html .= '!!!중요정보입니다.!! '. $va_date. '까지 '. $bankname. '의 예금주 '. $depositor. '의 '. $account. '로  [ 입금을 완료하여 주시기 바랍니다. <br/>';
            $html .= '<a href="javascript:receiptView3()">모의입금 페이지로 이동합니다.</a><br/>';
        }

        if ( $cash_yn != "" ) {

            if ($this->enable_testmode != 'on') {
                $receipt_url = 'https://admin.kcp.co.kr/Modules/Service/Cash/Cash_Bill_Common_View.jsp';
            }
            else {
                $receipt_url = 'https://testadmin8.kcp.co.kr/Modules/Service/Cash/Cash_Bill_Common_View.jsp';
            }

            $html .= ' [신용카드] 결제시에만 <input type="button" value="현금 영수증 인쇄" onclick="javascript:receiptView2(\''.$receipt_url. '\', \''.$kcp_site_cd. '\', \''. $ordr_idxx. '\', \''. $kcp_cash_yn. '\', \''. $kcp_cash_authno. '\')" > 가 발행 가능합니다.';

        }

        return $html;
    }
}
class Kcp_Pay_Callback extends WC_Payment_Gateway {

    public $_prefix;
    public $_folder;
    public $assets_url;
    //public $method = "신용카드";

    public function __construct() {

        $this->_prefix = WSKL_PREFIX;

        $tmp_arr = explode("/", dirname( __FILE__ ));
        $this->_folder = $tmp_arr[count($tmp_arr)-3]; //_folder = plugin folder name
        $this->assets_url = plugins_url( $this->_folder.'/assets/' ) ;

        add_action( 'woocommerce_api_kcp_pay_callback', array( $this, 'pay_callback' ) );
    }
    function pay_callback() {
        global $woocommerce;
        global $woocommerce_ver21_less;
        @ob_clean();

        //////// 던져야될 변수는 모두 7개 ////////////////////////////

        $g_conf_log_level = "3";
        $g_conf_gw_port   = "8090";        // 포트번호(변경불가)

        $g_conf_home_dir  = dirname(__FILE__) ;        // BIN 절대경로 입력 (bin전까지)
        $g_conf_log_path = dirname(__FILE__). '/log';

        if (get_option($this->_prefix.'enable_testmode') != 'on') {
            $g_conf_gw_url    = "paygw.kcp.co.kr";
            $g_conf_site_cd  = get_option($this->_prefix.'kcp_sitecd');
            $g_conf_site_key = get_option($this->_prefix.'kcp_sitekey');
        } else {
            $g_conf_gw_url    = "testpaygw.kcp.co.kr";
            $g_conf_site_cd = 'T0000';
            $g_conf_site_key = '3grptw1.zW0GSo4PQdaGvsF__';
        }

        //sym__log ( $this->assets_url  ) ;
        ///////////////////////////////////////////////////////////////////////////////

        $order_id = $_POST[ "ordr_idxx"      ];
        $order = new WC_Order( $order_id );

        require_once dirname( __FILE__ ) . '/pp_ax_hub_lib.php';

        /* ============================================================================== */
        /* =   01. 지불 요청 정보 설정                                                  = */
        /* = -------------------------------------------------------------------------- = */
        $req_tx         = $_POST[ "req_tx"         ]; // 요청 종류
        $tran_cd        = $_POST[ "tran_cd"        ]; // 처리 종류
        /* = -------------------------------------------------------------------------- = */
        $cust_ip        = getenv( "REMOTE_ADDR"    ); // 요청 IP
        $ordr_idxx      = $_POST[ "ordr_idxx"      ]; // 쇼핑몰 주문번호
        $good_name      = $_POST[ "good_name"      ]; // 상품명
        $good_mny       = $_POST[ "good_mny"       ]; // 결제 총금액
        /* = -------------------------------------------------------------------------- = */
        $res_cd         = "";                         // 응답코드
        $res_msg        = "";                         // 응답메시지
        $res_en_msg     = "";                         // 응답 영문 메세지
        $tno            = $_POST[ "tno"            ]; // KCP 거래 고유 번호
        /* = -------------------------------------------------------------------------- = */
        $buyr_name      = $_POST[ "buyr_name"      ]; // 주문자명
        $buyr_tel1      = $_POST[ "buyr_tel1"      ]; // 주문자 전화번호
        $buyr_tel2      = $_POST[ "buyr_tel2"      ]; // 주문자 핸드폰 번호
        $buyr_mail      = $_POST[ "buyr_mail"      ]; // 주문자 E-mail 주소
        /* = -------------------------------------------------------------------------- = */
        $use_pay_method = $_POST[ "use_pay_method" ]; // 결제 방법
        $bSucc          = "";                         // 업체 DB 처리 성공 여부
        /* = -------------------------------------------------------------------------- = */
        $app_time       = "";                         // 승인시간 (모든 결제 수단 공통)
        $amount         = "";                         // KCP 실제 거래 금액
        $total_amount   = 0;                          // 복합결제시 총 거래금액
        $coupon_mny     = "";                         // 쿠폰금액
        /* = -------------------------------------------------------------------------- = */
        $card_cd        = "";                         // 신용카드 코드
        $card_name      = "";                         // 신용카드 명
        $app_no         = "";                         // 신용카드 승인번호
        $noinf          = "";                         // 신용카드 무이자 여부
        $quota          = "";                         // 신용카드 할부개월
        $partcanc_yn    = "";                         // 부분취소 가능유무
        $card_bin_type_01 = "";                       // 카드구분1
        $card_bin_type_02 = "";                       // 카드구분2
        $card_mny       = "";                         // 카드결제금액
        /* = -------------------------------------------------------------------------- = */
        $bank_name      = "";                         // 은행명
        $bank_code      = "";                         // 은행코드
        $bk_mny         = "";                         // 계좌이체결제금액
        /* = -------------------------------------------------------------------------- = */
        $bankname       = "";                         // 입금할 은행명
        $depositor      = "";                         // 입금할 계좌 예금주 성명
        $account        = "";                         // 입금할 계좌 번호
        $va_date        = "";                         // 가상계좌 입금마감시간
        /* = -------------------------------------------------------------------------- = */
        $pnt_issue      = "";                         // 결제 포인트사 코드
        $pnt_amount     = "";                         // 적립금액 or 사용금액
        $pnt_app_time   = "";                         // 승인시간
        $pnt_app_no     = "";                         // 승인번호
        $add_pnt        = "";                         // 발생 포인트
        $use_pnt        = "";                         // 사용가능 포인트
        $rsv_pnt        = "";                         // 총 누적 포인트
        /* = -------------------------------------------------------------------------- = */
        $commid         = "";                         // 통신사 코드
        $mobile_no      = "";                         // 휴대폰 번호
        /* = -------------------------------------------------------------------------- = */
        $shop_user_id   = $_POST[ "shop_user_id"   ]; // 가맹점 고객 아이디
        $tk_van_code    = "";                         // 발급사 코드
        $tk_app_no      = "";                         // 상품권 승인 번호
        /* = -------------------------------------------------------------------------- = */
        $cash_yn        = $_POST[ "cash_yn"        ]; // 현금영수증 등록 여부
        $cash_authno    = "";                         // 현금 영수증 승인 번호
        $cash_tr_code   = $_POST[ "cash_tr_code"   ]; // 현금 영수증 발행 구분
        $cash_id_info   = $_POST[ "cash_id_info"   ]; // 현금 영수증 등록 번호

        /* ============================================================================== */

        /* ============================================================================== */
        /* =   02. 인스턴스 생성 및 초기화                                              = */
        /* = -------------------------------------------------------------------------- = */
        /* =       결제에 필요한 인스턴스를 생성하고 초기화 합니다.                     = */
        /* = -------------------------------------------------------------------------- = */
        $c_PayPlus = new C_PP_CLI;

        $c_PayPlus->mf_clear();
        /* ------------------------------------------------------------------------------ */
        /* =   02. 인스턴스 생성 및 초기화 END                                          = */
        /* ============================================================================== */


        /* ============================================================================== */
        /* =   03. 처리 요청 정보 설정                                                  = */
        /* = -------------------------------------------------------------------------- = */

        /* = -------------------------------------------------------------------------- = */
        /* =   03-1. 승인 요청                                                          = */
        /* = -------------------------------------------------------------------------- = */
        if ( $req_tx == "pay" )
        {
            /* 1004원은 실제로 업체에서 결제하셔야 될 원 금액을 넣어주셔야 합니다. 결제금액 유효성 검증 */
            /* $c_PayPlus->mf_set_ordr_data( "ordr_mony",  "1004" );                                    */
            $c_PayPlus->mf_set_ordr_data( "ordr_mony",  (int)$order->order_total );

            $c_PayPlus->mf_set_encx_data( $_POST[ "enc_data" ], $_POST[ "enc_info" ] );
        }

        /* ------------------------------------------------------------------------------ */
        /* =   03.  처리 요청 정보 설정 END                                             = */
        /* ============================================================================== */


        /* ============================================================================== */
        /* =   04. 실행                                                                 = */
        /* = -------------------------------------------------------------------------- = */
        if ( $tran_cd != "" )
        {
            $c_PayPlus->mf_do_tx( $trace_no, $g_conf_home_dir, $g_conf_site_cd, $g_conf_site_key, $tran_cd, "",
                $g_conf_gw_url, $g_conf_gw_port, "payplus_cli_slib", $ordr_idxx,
                $cust_ip, $g_conf_log_level, 0, 0, $g_conf_log_path ); // 응답 전문 처리

            $res_cd  = $c_PayPlus->m_res_cd;  // 결과 코드
            $res_msg = $c_PayPlus->m_res_msg; // 결과 메시지
            /* $res_en_msg = $c_PayPlus->mf_get_res_data( "res_en_msg" );  // 결과 영문 메세지 */
        }
        else
        {
            $c_PayPlus->m_res_cd  = "9562";
            $c_PayPlus->m_res_msg = "연동 오류|Payplus Plugin이 설치되지 않았거나 tran_cd값이 설정되지 않았습니다.";
        }


        /* = -------------------------------------------------------------------------- = */
        /* =   04. 실행 END                                                             = */
        /* ============================================================================== */


        /* ============================================================================== */
        /* =   05. 승인 결과 값 추출                                                    = */
        /* = -------------------------------------------------------------------------- = */
        if ( $req_tx == "pay" )
        {
            if( $res_cd == "0000" )
            {
                $tno       = $c_PayPlus->mf_get_res_data( "tno"       ); // KCP 거래 고유 번호
                $amount    = $c_PayPlus->mf_get_res_data( "amount"    ); // KCP 실제 거래 금액
                $pnt_issue = $c_PayPlus->mf_get_res_data( "pnt_issue" ); // 결제 포인트사 코드
                $coupon_mny = $c_PayPlus->mf_get_res_data( "coupon_mny" ); // 쿠폰금액

                /* = -------------------------------------------------------------------------- = */
                /* =   05-1. 신용카드 승인 결과 처리                                            = */
                /* = -------------------------------------------------------------------------- = */
                if ( $use_pay_method == "100000000000" )
                {
                    $card_cd   = $c_PayPlus->mf_get_res_data( "card_cd"   ); // 카드사 코드
                    $card_name = $c_PayPlus->mf_get_res_data( "card_name" ); // 카드 종류
                    $app_time  = $c_PayPlus->mf_get_res_data( "app_time"  ); // 승인 시간
                    $app_no    = $c_PayPlus->mf_get_res_data( "app_no"    ); // 승인 번호
                    $noinf     = $c_PayPlus->mf_get_res_data( "noinf"     ); // 무이자 여부 ( 'Y' : 무이자 )
                    $quota     = $c_PayPlus->mf_get_res_data( "quota"     ); // 할부 개월 수
                    $partcanc_yn = $c_PayPlus->mf_get_res_data( "partcanc_yn" ); // 부분취소 가능유무
                    $card_bin_type_01 = $c_PayPlus->mf_get_res_data( "card_bin_type_01" ); // 카드구분1
                    $card_bin_type_02 = $c_PayPlus->mf_get_res_data( "card_bin_type_02" ); // 카드구분2
                    $card_mny = $c_PayPlus->mf_get_res_data( "card_mny" ); // 카드결제금액

                    /* = -------------------------------------------------------------- = */
                    /* =   05-1.1. 복합결제(포인트+신용카드) 승인 결과 처리               = */
                    /* = -------------------------------------------------------------- = */
                    if ( $pnt_issue == "SCSK" || $pnt_issue == "SCWB" )
                    {
                        $pnt_amount   = $c_PayPlus->mf_get_res_data ( "pnt_amount"   ); // 적립금액 or 사용금액
                        $pnt_app_time = $c_PayPlus->mf_get_res_data ( "pnt_app_time" ); // 승인시간
                        $pnt_app_no   = $c_PayPlus->mf_get_res_data ( "pnt_app_no"   ); // 승인번호
                        $add_pnt      = $c_PayPlus->mf_get_res_data ( "add_pnt"      ); // 발생 포인트
                        $use_pnt      = $c_PayPlus->mf_get_res_data ( "use_pnt"      ); // 사용가능 포인트
                        $rsv_pnt      = $c_PayPlus->mf_get_res_data ( "rsv_pnt"      ); // 총 누적 포인트
                        $total_amount = $amount + $pnt_amount;                          // 복합결제시 총 거래금액
                    }
                }

                /* = -------------------------------------------------------------------------- = */
                /* =   05-2. 계좌이체 승인 결과 처리                                            = */
                /* = -------------------------------------------------------------------------- = */
                if ( $use_pay_method == "010000000000" )
                {
                    $app_time  = $c_PayPlus->mf_get_res_data( "app_time"   );  // 승인 시간
                    $bank_name = $c_PayPlus->mf_get_res_data( "bank_name"  );  // 은행명
                    $bank_code = $c_PayPlus->mf_get_res_data( "bank_code"  );  // 은행코드
                    $bk_mny = $c_PayPlus->mf_get_res_data( "bk_mny" ); // 계좌이체결제금액
                }

                /* = -------------------------------------------------------------------------- = */
                /* =   05-3. 가상계좌 승인 결과 처리                                            = */
                /* = -------------------------------------------------------------------------- = */
                if ( $use_pay_method == "001000000000" )
                {
                    $bankname  = $c_PayPlus->mf_get_res_data( "bankname"  ); // 입금할 은행 이름
                    $depositor = $c_PayPlus->mf_get_res_data( "depositor" ); // 입금할 계좌 예금주
                    $account   = $c_PayPlus->mf_get_res_data( "account"   ); // 입금할 계좌 번호
                    $va_date   = $c_PayPlus->mf_get_res_data( "va_date"   ); // 가상계좌 입금마감시간
                }

                /* = -------------------------------------------------------------------------- = */
                /* =   05-4. 포인트 승인 결과 처리                                               = */
                /* = -------------------------------------------------------------------------- = */
                if ( $use_pay_method == "000100000000" )
                {
                    $pnt_amount   = $c_PayPlus->mf_get_res_data( "pnt_amount"   ); // 적립금액 or 사용금액
                    $pnt_app_time = $c_PayPlus->mf_get_res_data( "pnt_app_time" ); // 승인시간
                    $pnt_app_no   = $c_PayPlus->mf_get_res_data( "pnt_app_no"   ); // 승인번호
                    $add_pnt      = $c_PayPlus->mf_get_res_data( "add_pnt"      ); // 발생 포인트
                    $use_pnt      = $c_PayPlus->mf_get_res_data( "use_pnt"      ); // 사용가능 포인트
                    $rsv_pnt      = $c_PayPlus->mf_get_res_data( "rsv_pnt"      ); // 적립 포인트
                }

                /* = -------------------------------------------------------------------------- = */
                /* =   05-5. 휴대폰 승인 결과 처리                                              = */
                /* = -------------------------------------------------------------------------- = */
                if ( $use_pay_method == "000010000000" )
                {
                    $app_time  = $c_PayPlus->mf_get_res_data( "hp_app_time"  ); // 승인 시간
                    $commid    = $c_PayPlus->mf_get_res_data( "commid"	     ); // 통신사 코드
                    $mobile_no = $c_PayPlus->mf_get_res_data( "mobile_no"	 ); // 휴대폰 번호
                }

                /* = -------------------------------------------------------------------------- = */
                /* =   05-6. 상품권 승인 결과 처리                                              = */
                /* = -------------------------------------------------------------------------- = */
                if ( $use_pay_method == "000000001000" )
                {
                    $app_time    = $c_PayPlus->mf_get_res_data( "tk_app_time"  ); // 승인 시간
                    $tk_van_code = $c_PayPlus->mf_get_res_data( "tk_van_code"  ); // 발급사 코드
                    $tk_app_no   = $c_PayPlus->mf_get_res_data( "tk_app_no"    ); // 승인 번호
                }

                /* = -------------------------------------------------------------------------- = */
                /* =   05-7. 현금영수증 결과 처리                                               = */
                /* = -------------------------------------------------------------------------- = */
                $cash_authno  = $c_PayPlus->mf_get_res_data( "cash_authno"  ); // 현금 영수증 승인 번호

            }
        }

        /* = -------------------------------------------------------------------------- = */
        /* =   05. 승인 결과 처리 END                                                   = */
        /* ============================================================================== */

        /* ============================================================================== */
        /* =   06. 승인 및 실패 결과 DB처리                                             = */
        /* = -------------------------------------------------------------------------- = */
        /* =       결과를 업체 자체적으로 DB처리 작업하시는 부분입니다.                 = */
        /* = -------------------------------------------------------------------------- = */


        if( $res_cd == "9502" ) {
            sym__log( plugins_url( '/bin/pp_cli', __FILE__ ). ' 화일의 실행권한을 755로 바꾸어 주세요 !');
            sym__alert( plugins_url( '/bin/pp_cli', __FILE__ ). ' 화일의 실행권한을 755로 바꾸어 주세요 !');
            exit;
        }

        if ( $req_tx == "pay" )
        {
            if( $res_cd == "0000" )
            {
              // 결제성공에 따른 상점처리부분
                //echo ("결제가 성공처리되었습니다. [" . $agspay->GetResult("rSuccYn")."]". $agspay->GetResult("rResMsg").". " );

                if ( $woocommerce_ver21_less ) {
                    $return_url =  add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))));
                } else {
                    $return_url = $this->get_return_url( $order );
                }

                $order->add_order_note( sprintf( __( '결제가 성공적으로 처리됨. 결제방법: %s. 올더게이트 TID: %s. 발생시각: %s.', $this->_folder ), $this->method, '111', date('YmdHis') ) );

                // Complete payment, reduce stock levels & remove cart
                $order->payment_complete();
                $order->reduce_order_stock();
                $woocommerce->cart->empty_cart();

                //$lib_url = "'.plugins_url( 'AGS_progress.html', __FILE__ ).
                ?>
<html>
<head>
    <title>*** KCP [AX-HUB Version] ***</title>
    <script type="text/javascript">
        function goResult()
        {
            var openwin = window.open( '<?=plugins_url( 'proc_win.html', __FILE__ )?>', 'proc_win', '' );
            document.pay_info.submit();
            openwin.close();
        }

        // 결제 중 새로고침 방지 샘플 스크립트 (중복결제 방지)
        function noRefresh()
        {
            /* CTRL + N키 막음. */
            if ((event.keyCode == 78) && (event.ctrlKey == true))
            {
                event.keyCode = 0;
                return false;
            }
            /* F5 번키 막음. */
            if(event.keyCode == 116)
            {
                event.keyCode = 0;
                return false;
            }
        }
        document.onkeydown = noRefresh ;
    </script>
</head>

<body onload="goResult()">
<form name="pay_info" method="post" action="<?=$return_url ?>">
    <input type="hidden" name="site_cd"           value="<?=$g_conf_site_cd ?>">    <!-- 사이트코드 -->
    <input type="hidden" name="req_tx"            value="<?=$req_tx         ?>">    <!-- 요청 구분 -->
    <input type="hidden" name="use_pay_method"    value="<?=$use_pay_method ?>">    <!-- 사용한 결제 수단 -->
    <input type="hidden" name="bSucc"             value="<?=$bSucc          ?>">    <!-- 쇼핑몰 DB 처리 성공 여부 -->

    <input type="hidden" name="amount"            value="<?=$amount		    ?>">	<!-- 금액 -->
    <input type="hidden" name="res_cd"            value="<?=$res_cd         ?>">    <!-- 결과 코드 -->
    <input type="hidden" name="res_msg"           value="<?=$res_msg        ?>">    <!-- 결과 메세지 -->
    <input type="hidden" name="res_en_msg"        value="<?=$res_en_msg     ?>">    <!-- 결과 영문 메세지 -->
    <input type="hidden" name="ordr_idxx"         value="<?=$ordr_idxx      ?>">    <!-- 주문번호 -->
    <input type="hidden" name="tno"               value="<?=$tno            ?>">    <!-- KCP 거래번호 -->
    <input type="hidden" name="good_mny"          value="<?=$good_mny       ?>">    <!-- 결제금액 -->
    <input type="hidden" name="good_name"         value="<?=$good_name      ?>">    <!-- 상품명 -->
    <input type="hidden" name="buyr_name"         value="<?=$buyr_name      ?>">    <!-- 주문자명 -->
    <input type="hidden" name="buyr_tel1"         value="<?=$buyr_tel1      ?>">    <!-- 주문자 전화번호 -->
    <input type="hidden" name="buyr_tel2"         value="<?=$buyr_tel2      ?>">    <!-- 주문자 휴대폰번호 -->
    <input type="hidden" name="buyr_mail"         value="<?=$buyr_mail      ?>">    <!-- 주문자 E-mail -->

    <input type="hidden" name="card_cd"           value="<?=$card_cd        ?>">    <!-- 카드코드 -->
    <input type="hidden" name="card_name"         value="<?=$card_name      ?>">    <!-- 카드명 -->
    <input type="hidden" name="app_time"          value="<?=$app_time       ?>">    <!-- 승인시간 -->
    <input type="hidden" name="app_no"            value="<?=$app_no         ?>">    <!-- 승인번호 -->
    <input type="hidden" name="quota"             value="<?=$quota          ?>">    <!-- 할부개월 -->
    <input type="hidden" name="noinf"             value="<?=$noinf          ?>">    <!-- 무이자여부 -->
    <input type="hidden" name="partcanc_yn"       value="<?=$partcanc_yn    ?>">    <!-- 부분취소가능유무 -->
    <input type="hidden" name="card_bin_type_01"  value="<?=$card_bin_type_01 ?>">  <!-- 카드구분1 -->
    <input type="hidden" name="card_bin_type_02"  value="<?=$card_bin_type_02 ?>">  <!-- 카드구분2 -->

    <input type="hidden" name="bank_name"         value="<?=$bank_name      ?>">    <!-- 은행명 -->
    <input type="hidden" name="bank_code"         value="<?=$bank_code      ?>">    <!-- 은행코드 -->

    <input type="hidden" name="bankname"          value="<?=$bankname       ?>">    <!-- 입금할 은행 -->
    <input type="hidden" name="depositor"         value="<?=$depositor      ?>">    <!-- 입금할 계좌 예금주 -->
    <input type="hidden" name="account"           value="<?=$account        ?>">    <!-- 입금할 계좌 번호 -->
    <input type="hidden" name="va_date"           value="<?=$va_date        ?>">    <!-- 가상계좌 입금마감시간 -->

    <input type="hidden" name="pnt_issue"         value="<?=$pnt_issue      ?>">    <!-- 포인트 서비스사 -->
    <input type="hidden" name="pnt_app_time"      value="<?=$pnt_app_time   ?>">    <!-- 승인시간 -->
    <input type="hidden" name="pnt_app_no"        value="<?=$pnt_app_no     ?>">    <!-- 승인번호 -->
    <input type="hidden" name="pnt_amount"        value="<?=$pnt_amount     ?>">    <!-- 적립금액 or 사용금액 -->
    <input type="hidden" name="add_pnt"           value="<?=$add_pnt        ?>">    <!-- 발생 포인트 -->
    <input type="hidden" name="use_pnt"           value="<?=$use_pnt        ?>">    <!-- 사용가능 포인트 -->
    <input type="hidden" name="rsv_pnt"           value="<?=$rsv_pnt        ?>">    <!-- 적립 포인트 -->

    <input type="hidden" name="commid"            value="<?=$commid         ?>">    <!-- 통신사 코드 -->
    <input type="hidden" name="mobile_no"         value="<?=$mobile_no      ?>">    <!-- 휴대폰 번호 -->

    <input type="hidden" name="tk_van_code"       value="<?=$tk_van_code    ?>">    <!-- 발급사 코드 -->
    <input type="hidden" name="tk_app_time"       value="<?=$tk_app_time    ?>">    <!-- 승인 시간 -->
    <input type="hidden" name="tk_app_no"         value="<?=$tk_app_no      ?>">    <!-- 승인 번호 -->

    <input type="hidden" name="cash_yn"           value="<?=$cash_yn        ?>">    <!-- 현금영수증 등록 여부 -->
    <input type="hidden" name="cash_authno"       value="<?=$cash_authno    ?>">    <!-- 현금 영수증 승인 번호 -->
    <input type="hidden" name="cash_tr_code"      value="<?=$cash_tr_code   ?>">    <!-- 현금 영수증 발행 구분 -->
    <input type="hidden" name="cash_id_info"      value="<?=$cash_id_info   ?>">    <!-- 현금 영수증 등록 번호 -->
</form>
</body>
</html>
                <?php
                ///wp_redirect( $return_url);
                exit;

              // 06-1-1. 신용카드
                if ( $use_pay_method == "100000000000" )
                {
                    // 06-1-1-1. 복합결제(신용카드 + 포인트)
                    if ( $pnt_issue == "SCSK" || $pnt_issue == "SCWB" )
                    {
                    }
                }
                // 06-1-2. 계좌이체
                if ( $use_pay_method == "010000000000" )
                {
                }
                // 06-1-3. 가상계좌
                if ( $use_pay_method == "001000000000" )
                {
                }
                // 06-1-4. 포인트
                if ( $use_pay_method == "000100000000" )
                {
                }
                // 06-1-5. 휴대폰
                if ( $use_pay_method == "000010000000" )
                {
                }
                // 06-1-6. 상품권
                if ( $use_pay_method == "000000001000" )
                {
                }
            }

            /* = -------------------------------------------------------------------------- = */
            /* =   06. 승인 및 실패 결과 DB처리                                             = */
            /* ============================================================================== */
            else if ( $res_cd != "0000" )
            {
                // 결제실패에 따른 상점처리부분
                //echo ("결제가 실패처리되었습니다. [" . $agspay->GetResult("rSuccYn")."]". $agspay->GetResult("rResMsg").". " );

                $order->update_status( 'failed', sprintf( __( '결제처리 안됨.  에러메시지 : %s. 발생시각: %s.', $this->_folder ), $res_msg, date('YmdHis') ) );
                $cart_url = $woocommerce->cart->get_cart_url();
                wp_redirect($cart_url);
                exit;
            }
        }

        /* ============================================================================== */
        /* =   07. 승인 결과 DB처리 실패시 : 자동취소                                   = */
        /* = -------------------------------------------------------------------------- = */
        /* =         승인 결과를 DB 작업 하는 과정에서 정상적으로 승인된 건에 대해      = */
        /* =         DB 작업을 실패하여 DB update 가 완료되지 않은 경우, 자동으로       = */
        /* =         승인 취소 요청을 하는 프로세스가 구성되어 있습니다.                = */
        /* =                                                                            = */
        /* =         DB 작업이 실패 한 경우, bSucc 라는 변수(String)의 값을 "false"     = */
        /* =         로 설정해 주시기 바랍니다. (DB 작업 성공의 경우에는 "false" 이외의 = */
        /* =         값을 설정하시면 됩니다.)                                           = */
        /* = -------------------------------------------------------------------------- = */

        $bSucc = ""; // DB 작업 실패 또는 금액 불일치의 경우 "false" 로 세팅

        /* = -------------------------------------------------------------------------- = */
        /* =   07-1. DB 작업 실패일 경우 자동 승인 취소                                 = */
        /* = -------------------------------------------------------------------------- = */
        if ( $req_tx == "pay" )
        {
            if( $res_cd == "0000" )
            {
                if ( $bSucc == "false" )
                {
                    $c_PayPlus->mf_clear();

                    $tran_cd = "00200000";

                    $c_PayPlus->mf_set_modx_data( "tno",      $tno                         );  // KCP 원거래 거래번호
                    $c_PayPlus->mf_set_modx_data( "mod_type", "STSC"                       );  // 원거래 변경 요청 종류
                    $c_PayPlus->mf_set_modx_data( "mod_ip",   $cust_ip                     );  // 변경 요청자 IP
                    $c_PayPlus->mf_set_modx_data( "mod_desc", "결과 처리 오류 - 자동 취소" );  // 변경 사유

                    $c_PayPlus->mf_do_tx( $trace_no, $g_conf_home_dir, $g_conf_site_cd, $g_conf_site_key, $tran_cd, "",
                        $g_conf_gw_url, $g_conf_gw_port, "payplus_cli_slib", $ordr_idxx,
                        $cust_ip, $g_conf_log_level, 0, 0, $g_conf_log_path ); // 응답 전문 처리

                    $res_cd  = $c_PayPlus->m_res_cd;
                    $res_msg = $c_PayPlus->m_res_msg;
                }
            }
        } // End of [res_cd = "0000"]
        /* ============================================================================== */


        /* ============================================================================== */
        /* =   08. 폼 구성 및 결과페이지 호출                                           = */
        /* ============================================================================== */

    }
}
?>