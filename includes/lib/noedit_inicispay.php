<?php
$inicis_noedit = '
    <!--
    플러그인에 의해서 값이 채워지거나, 플러그인이 참조하는 필드들
    삭제/수정 불가
    uid 필드에 절대로 임의의 값을 넣지 않도록 하시기 바랍니다.
    -->
    <input type=hidden name=ini_encfield value="'. $inipay->GetResult("encfield"). '">
    <input type=hidden name=ini_certid value="'. $inipay->GetResult("certid"). '">
    <input type=hidden name=quotainterest value="">
    <input type=hidden name=paymethod value="">
    <input type=hidden name=cardcode value="">
    <input type=hidden name=cardquota value="">
    <input type=hidden name=rbankcode value="">
    <input type=hidden name=reqsign value="DONE">
    <input type=hidden name=encrypted value="">
    <input type=hidden name=sessionkey value="">
    <input type=hidden name=uid value=""> 
    <input type=hidden name=sid value="">
    <input type=hidden name=version value=4000>
    <input type=hidden name=clickcontrol value="">
';

?>
