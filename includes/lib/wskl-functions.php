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
 * @param string $relative_path WSKL_PATH 로부터 상대적인 경로. 앞에 '/' 가 안 붙었으면 자동으로 붙임.
 * @param string $option_name   prefix 문자열을 붙이지 않은 옵션 이름. 빈문자열인 경우는 옵션을 체크하지 않음.
 */
function wskl_load_module( $relative_path, $option_name = '' ) {

	if ( empty( $option_name ) || wskl_is_option_enabled( $option_name ) ) {

		if ( $relative_path[0] != '/' ) {
			$relative_path = '/' . $relative_path;
		}

		/** @noinspection PhpIncludeInspection */
		require_once( WSKL_PATH . $relative_path );
	}
}


/**
 * associative array 로부터 값을 추출.
 *
 * @param array           $assoc_var
 * @param string          $key_name
 * @param callable|string $sanitize
 * @param mixed           $default
 *
 * @return mixed
 */
function wskl_get_from_assoc( &$assoc_var, $key_name, $sanitize = '', $default = '' ) {

	$v = $default;

	if ( isset( $assoc_var[ $key_name ] ) ) {
		$v = $assoc_var[ $key_name ];
	}

	if ( is_callable( $sanitize ) ) {
		$v = $sanitize( $v );
	}

	return $v;
}


function wskl_GET( $key_name, $sanitize = '', $default = '' ) {

	return wskl_get_from_assoc( $_GET, $key_name, $sanitize, $default );
}


function wskl_POST( $key_name, $sanitize = '', $default = '' ) {

	return wskl_get_from_assoc( $_POST, $key_name, $sanitize, $default );
}


function wskl_REQUEST( $key_name, $sanitize = '', $default = '' ) {

	return wskl_get_from_assoc( $_REQUEST, $key_name, $sanitize, $default );
}


function wskl_plugin_url( $path ) {

	if( $path[0] == '/' ) {
		$path = substr( $path, 1 );
	}

	return plugin_dir_url( WSKL_MAIN_FILE ) . $path;
}


/**
 * shortcut of enqueuing scripts
 *
 * @param string $handle
 * @param string $asset_path 스크립트 경로. 상대 경로일 경우, 항상 우리 플러그인의 루트 디렉토리를 기준으로 계산한다.
 * @param array  $depends
 * @param string $ver
 * @param bool   $in_footer
 * @param string $object_name 로컬라이징, 혹은 별도의 파라미터를 전달할 경우 입력한다.
 * @param array  $i10n        $object_name 과 같은 용도로 입력한다.
 */
function wskl_enqueue_script( $handle, $asset_path, $depends = array(), $ver = WSKL_VERSION, $in_footer = false, $object_name = '', $i10n = array() ) {

	if( !preg_match( '/^https?:\/\//', $asset_path ) ) {
		$asset_path = wskl_plugin_url( $asset_path );
	}

	wp_register_script( $handle, $asset_path, $depends, $ver, $in_footer );

	if( !empty( $object_name ) && !empty( $i10n ) ) {
		wp_localize_script( $handle, $object_name, $i10n );
	}

	wp_enqueue_script( $handle );
}