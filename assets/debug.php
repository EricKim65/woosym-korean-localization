<?php
header('Content-type: text/plain');

//if ( is_super_admin() ) {
	if ( $_GET['sw'] != 'symlog') {
		print "== Debug Log ==\r\n";
		print file_get_contents("debug.log");
	}

	if ( $_GET['sw'] != 'debug') {
		print "\r\n== Sym Log ==\r\n";
		print file_get_contents("sym__log.log");
	}

//} else { 
//	print "==Sorry, Please login as admin ==" ;
//}

?>