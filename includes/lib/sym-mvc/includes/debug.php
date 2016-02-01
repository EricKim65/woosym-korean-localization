<?php

if ( $_GET["phpinfo"] == 'y' ) {
	echo '<a href="'. $_SERVER['PHP_SELF']. '">Do Not see phpinfo </a><br>';
	phpinfo();
} else {
	echo '<a href="?phpinfo=y">phpinfo 보기</a></br>';
    header('Content-type: text/utf-8');
	$tmp_arr = explode("/",$_SERVER['PHP_SELF']);

	$prefix = '';
	foreach ($tmp_arr as $value) {

		if ($value != '') {
			$prefix .= '/'. $value;
		}

		if ($value == 'wp-content') {
			break;
		 }
	}

	//if ( is_super_admin() ) {
		make_empty_logfiles();
		if ( $_GET['sw'] != 'symlog') {
			//print "== Debug Log ==\r\n";
			print "== Debug Log ==\r\n";
			/*php.ini 에서 allow_url_fopen = on 이 되어 있지 않으면 file_get_contents 이 실행되지 않으므로
			file_get_contents를 curl_get_contents  로 바꿈.*/
			//print file_get_contents( 'http://'. $_SERVER[HTTP_HOST]. $prefix. '/debug.log');
			print  curl_get_contents('http://'. $_SERVER[HTTP_HOST]. $prefix. '/debug.log');
		}

		if ( $_GET['sw'] != 'debug') {
			print "\r\n== Sym Log ==\r\n";
			//print file_get_contents('http://'. $_SERVER[HTTP_HOST]. $prefix. 'sym__log.log');
			//sym__log('empty');
			print  curl_get_contents('http://'. $_SERVER[HTTP_HOST]. $prefix. '/sym__log.log');
		}

	//} else { 
	//	print "==Sorry, Please login as admin ==" ;
	//}
}


function curl_get_contents($url)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

function make_empty_logfiles($message, $showpage=false ) {

	$log_dir = "wp-content";
	$time_msg = "[" . date_format(date_create(), 'Y-m-d-H-i-s') . "] " ;

	$log_file = $_SERVER['DOCUMENT_ROOT']. '/'. $log_dir ."/";
	$log_file .= "sym__log.log";

	if (is_array($message) || is_object($message)) {
		$str_msg = print_r($message, true);
	} else {
		$str_msg = $message;
	}
	//file_put_contents($log_file, $message, FILE_APPEND | LOCK_EX);
	error_log($time_msg . "=============================================\r\n", 3, $log_file);
	error_log($time_msg . "=============================================\r\n", 3, str_replace("sym__log.log", "debug.log", $log_file) );

}


?>