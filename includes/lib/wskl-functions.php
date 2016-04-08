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
 * prefix 붙인 옵션 값 가져오는 래퍼 함수
 *
 * @param        $option_name
 * @param string $default
 * @param bool   $use_native_prefix
 *
 * @since 3.2.3-r2
 *
 * @return mixed|void
 */
function wskl_get_option(
	$option_name,
	$default = '',
	$use_native_prefix = TRUE
) {

	return $use_native_prefix ? get_option(
		wskl_get_option_name( $option_name ),
		$default
	) : get_option( $option_name, $default );
}


/**
 * @param      $option_name
 * @param      $option_value
 * @param null $autoload
 * @param bool $use_native_prefix
 *
 * @return bool
 */
function wskl_update_option(
	$option_name,
	$option_value,
	$autoload = NULL,
	$use_native_prefix = TRUE
) {

	return $use_native_prefix ?
		update_option( wskl_get_option_name( $option_name ), $option_value, $autoload ) :
		update_option( $option_name, $option_value, $autoload );
}


function wskl_yes_or_no( $expression ) {

	return $expression ? 'yes' : 'no';
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
function wskl_get_from_assoc(
	&$assoc_var,
	$key_name,
	$sanitize = '',
	$default = ''
) {

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


function wskl_verify_nonce( $nonce_action, $nonce_value ) {

	class NonceVerificationFailureException extends Exception {

		public function __construct() {

			$this->message = 'Nonce verification failed.';
		}
	}


	if ( ! wp_verify_nonce( $nonce_value, $nonce_action ) ) {
		throw new NonceVerificationFailureException();
	}
}

function wskl_setting_tab_url( $tab_name ) {

	return add_query_arg(
		array( 'page' => WSKL_MENU_SLUG, 'tab' => $tab_name, ),
		admin_url( 'admin.php' )
	);
}

function wskl_plugin_url( $path ) {

	if ( $path[0] == '/' ) {
		$path = substr( $path, 1 );
	}

	return plugin_dir_url( WSKL_MAIN_FILE ) . $path;
}

function wskl_wp_members_url( $tab = 'dabory-members' ) {

	return add_query_arg(
		array(
			'page' => 'wpmem-settings',
			'tab'  => $tab,
		),
		admin_url( 'options-general.php' )
	);
}


/**
 * shortcut of enqueuing scripts
 *
 * @param string $handle
 * @param string $asset_path  스크립트 경로. 상대 경로일 경우, 항상 우리 플러그인의 루트 디렉토리를 기준으로
 *                            계산한다.
 * @param array  $depends
 * @param string $ver
 * @param bool   $in_footer
 * @param string $object_name 로컬라이징, 혹은 별도의 파라미터를 전달할 경우 입력한다.
 * @param array  $i10n        $object_name 과 같은 용도로 입력한다.
 */
function wskl_enqueue_script(
	$handle,
	$asset_path,
	$depends = array(),
	$ver = WSKL_VERSION,
	$in_footer = FALSE,
	$object_name = '',
	$i10n = array()
) {

	if ( ! preg_match( '/^https?:\/\//', $asset_path ) ) {
		$asset_path = wskl_plugin_url( $asset_path );
	}

	wp_register_script( $handle, $asset_path, $depends, $ver, $in_footer );

	if ( ! empty( $object_name ) && ! empty( $i10n ) ) {
		wp_localize_script( $handle, $object_name, $i10n );
	}

	wp_enqueue_script( $handle );
}


function wskl_debug_enabled() {

	return WP_DEBUG && defined( 'WSKL_DEBUG' ) && WSKL_DEBUG;
}


function wskl_lab_enabled() {

	return defined( 'WSKL_LAB_FEATURES' ) && WSKL_LAB_FEATURES;
}


function wskl_license_authorized( $license_type ) {

	$info = new WSKL_Auth_Info( $license_type );

	return $info->is_available() && $info->is_verified();
}


function wskl_get_setting_tab_url( $tab ) {

	return add_query_arg(
		array(
			'page' => WSKL_MENU_SLUG,
			'tab'  => $tab,
		),
		admin_url( 'admin.php' )
	);
}

function wskl_get_template(
	$template_name,
	array $args = array(),
	$default_path = ''
) {

	if ( ! is_string( $template_name ) || empty( $template_name ) ) {
		return;
	}

	if ( empty( $default_path ) ) {
		$default_path = WSKL_PATH . '/includes/templates';
	}

	if ( substr( $template_name, 0, 1 ) !== '/' ) {
		$template_name = '/' . $template_name;
	}

	wc_get_template( $template_name, $args, '', $default_path );
}

function wskl_enqueue_daum_postcode_scripts() {

	/** @noinspection PhpUndefinedConstantInspection */
	$api_uri = is_ssl() ? DAUM_POSTCODE_HTTPS : DAUM_POSTCODE_HTTP;

	wp_enqueue_script(
		'daum-postcode-v2',
		$api_uri,
		NULL,
		NULL,
		FALSE
	);  // in the header area
}

function wskl_check_abspath() {

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
}

function wskl_dump( $obj ) {

	echo '<p><pre>' . print_r( $obj, TRUE ) . ' </pre>';
}