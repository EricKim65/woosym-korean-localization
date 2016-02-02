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
