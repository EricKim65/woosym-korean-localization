//<body onload="javascript:enable_click()" onFocus="javascript:focus_control()"> 를 아래와 같이 처리함.
window.onload = function () {
    enable_click();
    if (pay(ini)) {
        document.ini.submit();
    }
};

window.onfocus = function () {
    focus_control();
};

StartSmartUpdate();

var openwin;

function pay(frm) {
    // MakePayMessage()를 호출함으로써 플러그인이 화면에 나타나며, Hidden Field
    // 에 값들이 채워지게 됩니다. 일반적인 경우, 플러그인은 결제처리를 직접하는 것이
    // 아니라, 중요한 정보를 암호화 하여 Hidden Field의 값들을 채우고 종료하며,
    // 다음 페이지인 INIsecureresult.php로 데이터가 포스트 되어 결제 처리됨을 유의하시기 바랍니다.

    if (document.ini.clickcontrol.value == "enable") {

        if (document.ini.goodname.value == "")  // 필수항목 체크 (상품명, 상품가격, 구매자명, 구매자 이메일주소, 구매자 전화번호)
        {
            alert("상품명이 빠졌습니다. 필수항목입니다.");
            return false;
        }
        else if (document.ini.buyername.value == "") {
            alert("구매자명이 빠졌습니다. 필수항목입니다.");
            return false;
        }
        else if (document.ini.buyeremail.value == "") {
            alert("구매자 이메일주소가 빠졌습니다. 필수항목입니다.");
            return false;
        }
        else if (document.ini.buyertel.value == "") {
            alert("구매자 전화번호가 빠졌습니다. 필수항목입니다.");
            return false;
        }
        else if (ini_IsInstalledPlugin() == false) //플러그인 설치유무 체크
        {
            alert("\n이니페이 플러그인 128이 설치되지 않았습니다. \n\n안전한 결제를 위하여 이니페이 플러그인 128의 설치가 필요합니다. \n\n다시 설치하시려면 Ctrl + F5키를 누르시거나 메뉴의 [보기/새로고침]을 선택하여 주십시오.");
            return false;
        }
        else {
            if (MakePayMessage(frm)) {
                disable_click();
                //alert(1);
                //openwin = window.open("childwin.html", "childwin", "width=299,height=149");
                return true;
            } else {
                if (IsPluginModule()) {//plugin타입 체크
                    alert("결제를 취소하셨습니다.");
                }
                return false;
            }
        }
    }
    else {
        return false;
    }
}


function enable_click() {
    document.ini.clickcontrol.value = "enable"
}

function disable_click() {
    document.ini.clickcontrol.value = "disable"
}

function focus_control() {
    if (document.ini.clickcontrol.value == "disable")
        openwin.focus();
}

