<?php
/**
 * prefix 를 붙인 옵션 이름을 리턴
 *
 * @author changwoo
 *
 * @param $option_name string prefix 문자열을 붙이지 않은 옵션 이름.
 *
 * @return string prefix 문자열을 붙인 옵션 이름
 */
function wskl_get_option_name( $option_name ) {

	return WSKL_PREFIX . $option_name;
}


/**
 * 해당 옵션을 boolean 으로 해석해 true, false 로 리턴
 *
 * @author changwoo
 *
 * @param $option_name string prefix 문자열을 붙이지 않은 옵션 이름.
 *
 * @return boolean 해당 옵션
 */
function wskl_is_option_enabled( $option_name ) {

	$value = get_option( wskl_get_option_name( $option_name ) );

	return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}


/**
 * 또다른 helper function
 *
 * @param string $relative_path  WSKL_PATH 로부터 상대적인 경로. 앞에 '/' 가 안 붙었으면 자동으로 붙임.
 * @param string $option_name    prefix 문자열을 붙이지 않은 옵션 이름. 빈문자열인 경우는 옵션을 체크하지 않음.
 */
function wskl_load_module( $relative_path, $option_name = '' ) {

	if( empty( $option_name ) || wskl_is_option_enabled( $option_name ) ) {

		if( $relative_path[0] != '/' ) {
			$relative_path = '/' . $relative_path;
		}

		/** @noinspection PhpIncludeInspection */
		require_once( WSKL_PATH . $relative_path );
	}
}