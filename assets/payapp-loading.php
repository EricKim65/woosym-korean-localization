<!DOCTYPE html>
<html>
<!--
페이앱에서 "주문 확정" 버튼을 누르면 "사용자의 요청에 의해" 팝업을 띄운다. 이렇게 하지 않고 자바스크립트가 어떤 이벤트 콜백에 의해
팝업창을 띄우면 웹브라우저는 이를 안전하지 않은 동작으로 간주하고 팝업 블록을 하고, 사용자가 직접 차단을 해제하도록 요구한다.
이런 동작은 결제에 있어 유연하지 않은 동작이다. 그러므로 이를 우회하기 위해 먼저 팝업을 띄우고, 이 팝업을 결제 주소로 이동한다.
이 때 갑자기 빈 화면에 뜨는 것은 보기 좋지 않으므로 필요한 대기 화면으로 사용자와 타협한다.
-->
<head>
  <meta charset="UTF-8">
  <title></title>
  <style type="text/css">
    p {
      vertical-align: text-top;
      font-size: 36px;
    }
  </style>
</head>
<body>
<p>
  <img src="image/payapp/spin_light-2x.gif">
  처리중입니다.
</p>
</body>
</html>