<?php
/*
 * Plugin Name: 우커머스-심포니 통합 플러그인
 * Version: 3.2.0 - 최종 15/11/08
 * Plugin URI:  http://www.symphonysoft.co.kr
 * Description: 우커머스를 카페24 같이 편리하게 만들어주는 한국 쇼핑몰 환경 표준 플러그인. 
 * Author: (주)심포니소프트 - Dabory
 * Author URI: http://www.symphonysoft.co.kr
 * Requires at least: 4.1
 * Tested up to: 4.0004
 *
 * Text Domain: wskl
 * Domain Path: /lang/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Sym_Mvc_Main' ) ) {
	add_action( 'admin_notices', 'wskl_install_sym_mvc_notice' );	
	return;  
}

function wskl_install_sym_mvc_notice() {  // Symphony MVC Framework 가 실행될 때만
   echo ' <div class="error">
				 <p><font color="red">우커머스-심포니는 Symphony MVC Framework 플러그인이 활성화된 상태에서만 동작됩니다. Symphony MVC Framework를 설치/활성화하여 주십시요 ! </font></p>
		  </div>';
}

if ( ! class_exists( 'WooCommerce' ) ) { // 우커머스가 실행될 때만
	add_action( 'admin_notices', 'wskl_install_woocommerce_notice' );	
	return;   
}

function wskl_install_woocommerce_notice() {  
   echo ' <div class="error">
				 <p><font color="red">우커머스-심포니는 우커머스 플러그인이 활성화된 상태에서만 동작됩니다. 우커머스를 설치/활성화하여 주십시요 ! </font></p>
		  </div>';
}

// plugin's defines
define( 'WSKL_PATH',      __DIR__ );
define( 'WSKL_MAIN_FILE', WSKL_PATH . '/woosym-korean-localization.php');
define( 'WSKL_PREFIX',    'wskl_' );
define( 'WSKL_VERSION',   '3.2.0' );
define( 'SYM_MVC_FRAMEWORK_PATH', WP_PLUGIN_DIR . '/sym-mvc-framework' );

/**
 * prefix 를 붙인 옵션 이름을 리턴
 *
 * @author changwoo
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
 * @param $option_name string prefix 문자열을 붙이지 않은 옵션 이름.
 *
 * @return boolean 해당 옵션
 */
function wskl_is_option_enabled( $option_name ) {
	$value = get_option( wskl_get_option_name( $option_name ) );
	return filter_var( $value, FILTER_VALIDATE_BOOLEAN ) ;
}


if ( !class_exists( 'Sym_Mvc_Main' ) ) {
	$sym_ins_msg =  '"우커머스-심포니 통합 플러그인"을 사용하시려면 먼저 Symphony-MVC-Framework Plugin 을 설치하여 주십시요 !';
	echo "<script>alert('".$sym_ins_msg."');</script>";
	return;
}

if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<' ) ) {
	$woocommerce_ver21_less = true ;
} else {
	$woocommerce_ver21_less = false ;
}

if( !function_exists('wskl_plugin_add_settings_link')) {

	function wskl_plugin_add_settings_link( $links ) {

		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=woosym_korean_localization_checkout_settings' ),
			__( 'Settings' )
		);

		if( isset( $links['0'] ) &&  FALSE !== strstr( $links[0], 'Settings') )  {
			unset( $links[0] );
		}

		$links['settings'] = $settings_link;
		return $links;
	}
}

$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'wskl_plugin_add_settings_link', 99 );


$woo_sym_prefix = 'wskl_';

// 관련상품 표시 갯수
if ( get_option( $woo_sym_prefix . 'related_products_count' ) != '' ) {
	add_filter( 'woocommerce_output_related_products_args', 'sym_related_products_args' );
	  function sym_related_products_args( $args ) {
		 global $woo_sym_prefix;
		$args['posts_per_page'] = get_option( $woo_sym_prefix . 'related_products_count'); // 4 related products
		$args['columns'] = 1; // arranged in 2 columns
		return $args;
	}
}

$sym_checkout_titles = array('credit' => '신용카드', 'remit' => '실시간 계좌이체', 'virtual' => '가상계좌 이체', 'mobile' => '모바일소액결제');
$sym_checkout_desc = '로 결제합니다.';
$sym_pg_agency = get_option( $woo_sym_prefix . 'pg_agency');



if ( wskl_is_option_enabled( 'enable_sym_pg' ) ) {

	// NOTE: 옵션 켜져 있는 것과 pay_gate_agency 찾을 수 있는 것은 별개의 사항으므로 에러 체크가 필요함.
	$pay_gate_agency = get_option( wskl_get_option_name( 'pg_agency' ) );

	/** @noinspection PhpIncludeInspection*/
	require_once( 'includes/lib/class-pg-' . $pay_gate_agency . '-main.php' );

	/** @noinspection PhpIncludeInspection*/
	require_once( 'includes/lib/class-pg-' . $pay_gate_agency . '-common.php' );
}

/**
 * 모듈 배송추적
 */
if ( wskl_is_option_enabled( 'enable_ship_track' ) ) {
	require_once( WSKL_PATH . '/includes/lib/class-shipping-tracking.php' );
}

/**
 * 모듈 소셜 로그인
 */
if ( wskl_is_option_enabled( 'enable_social_login' ) ) {
	require_once( WSKL_PATH . '/includes/lib/class-social-login.php' );
}

if ( wskl_is_option_enabled( 'enable_direct_purchase' ) ) {
	require_once( WSKL_PATH . '/includes/lib/class-direct-purchase.php' );
}


if ( wskl_is_option_enabled( 'enable_countryip_block' ) ) {
	require_once( WSKL_PATH . '/includes/lib/geoip/geoip.inc' );
	if ( ! is_admin() )  add_action( 'plugins_loaded', 'wskl_country_ipblock' );
}

function wskl_country_ipblock() {
	$wskl_geoip = geoip_open(WSKL_PATH . "/includes/lib/geoip/GeoIP.dat",GEOIP_STANDARD);
	$wskl_country_ipcode = geoip_country_code_by_addr($wskl_geoip, $_SERVER['REMOTE_ADDR']);
	geoip_close($wskl_geoip);

	$list  = preg_replace('/\s+/', '', get_option( 'wskl_white_ipcode_list' ));
	$code_arr = (explode(',',$list)) ;
	$in_iplist  = false ;
	foreach ( $code_arr as $value ) {
		if ( $value == $wskl_country_ipcode ) {
			$in_iplist = true;
			break ;
		}
	}

	if( !$in_iplist)  wp_die() ;
}

require_once( WSKL_PATH . '/includes/class-main.php' );
new Woosym_Korean_Localization( WSKL_PREFIX, WSKL_MAIN_FILE, WSKL_VERSION );

if ( is_admin() ) {
	include_once( WSKL_PATH . '/includes/class-settings.php' );
	new Woosym_Korean_Localization_Settings( WSKL_PREFIX, WSKL_MAIN_FILE, WSKL_VERSION );
}

