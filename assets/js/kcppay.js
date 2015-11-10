    /* 플러그인 설치(확인) */

    window.onload = function(e) { onload_pay(); }

    StartSmartUpdate();

/*  해당 스크립트는 타브라우져에서 적용이 되지 않습니다.
 if( document.Payplus.object == null )
 {
 openwin = window.open( "chk_plugin.html", "chk_plugin", "width=420, height=100, top=300, left=300" );
 }
 */

/* Payplus Plug-in 실행 */
function  jsf__pay( form )
{
    var RetVal = false;

    /* Payplus Plugin 실행 */
    if ( MakePayMessage( form ) == true )
    {
        openwin = window.open( "proc_win.html", "proc_win", "width=449, height=209, top=300, left=300" );
        RetVal = true ;
    }

    else
    {
        /*  res_cd와 res_msg변수에 해당 오류코드와 오류메시지가 설정됩니다.
         ex) 고객이 Payplus Plugin에서 취소 버튼 클릭시 res_cd=3001, res_msg=사용자 취소
         값이 설정됩니다.
         */
        res_cd  = document.order_info.res_cd.value ;
        res_msg = document.order_info.res_msg.value ;

    }

    return RetVal ;
}

// Payplus Plug-in 설치 안내
function init_pay_button()
{
    if ((navigator.userAgent.indexOf('MSIE') > 0) || (navigator.userAgent.indexOf('Trident/7.0') > 0))
    {
        try
        {
            if( document.Payplus.object == null )
            {
                document.getElementById("display_setup_message").style.display = "block" ;
            }
            else{
                document.getElementById("display_pay_button").style.display = "block" ;
            }
        }
        catch (e)
        {
            document.getElementById("display_setup_message").style.display = "block" ;
        }
    }
    else
    {
        try
        {
            if( Payplus == null )
            {
                document.getElementById("display_setup_message").style.display = "block" ;
            }
            else{
                document.getElementById("display_pay_button").style.display = "block" ;
            }
        }
        catch (e)
        {
            document.getElementById("display_setup_message").style.display = "block" ;
        }
    }
}

/* 주문번호 생성 예제 */
function init_orderid()
{
    var today = new Date();
    var year  = today.getFullYear();
    var month = today.getMonth() + 1;
    var date  = today.getDate();
    var time  = today.getTime();

    if(parseInt(month) < 10) {
        month = "0" + month;
    }

    if(parseInt(date) < 10) {
        date = "0" + date;
    }

    var order_idxx = "TEST" + year + "" + month + "" + date + "" + time;

    document.order_info.ordr_idxx.value = order_idxx;

    /*
     * 인터넷 익스플로러와 파이어폭스(사파리, 크롬.. 등등)는 javascript 파싱법이 틀리기 때문에 object 가 인식 전에 실행 되는 문제
     * 기존에는 onload 부분에 추가를 했지만 setTimeout 부분에 추가
     * setTimeout 300의 의미는 플러그인 인식속도에 따른 여유시간 설정
     * - 20101018 -
     */
    setTimeout("init_pay_button();",300);
}

/* onLoad 이벤트 시 Payplus Plug-in이 실행되도록 구성하시려면 다음의 구문을 onLoad 이벤트에 넣어주시기 바랍니다. */
function onload_pay()
{
    if( jsf__pay(document.order_info) )
        document.order_info.submit();
}
