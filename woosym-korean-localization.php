<?php
/*
 * Plugin Name:       우커머스-심포니 통합 플러그인
 * Version:           3.2.3-branch
 * Plugin URI:        https://www.dabory.com/
 * Description:       우커머스를 카페24 같이 편리하게 만들어주는 한국 쇼핑몰 환경 표준 플러그인.
 * Author:            (주)심포니소프트 - Dabory
 * Author URI:        https://www.dabory.com/
 * Requires at least: 4.1
 * Tested up to:      4.0004
 * Text Domain:       wskl
 * Domain Path:       /lang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// plugin's defines
define( 'WSKL_PATH', __DIR__ );
define( 'WSKL_MAIN_FILE', __FILE__ );
define( 'WSKL_PREFIX', 'wskl_' );
define( 'WSKL_VERSION', '3.2.3-branch' );

require_once( WSKL_PATH . '/includes/lib/wskl-functions.php' );
require_once( WSKL_PATH . '/includes/class-wskl-sym-mvc-deactivation.php' );
require_once( WSKL_PATH . '/includes/class-wskl-woocommerce-activation.php' );
require_once( WSKL_PATH . '/includes/class-wskl-iamport-notice.php' );

if ( is_admin() ) {

	add_action( 'admin_enqueue_scripts', 'wskl_admin_style' );

	function wskl_admin_style() {

		wp_enqueue_style( 'wskl-admin-css', plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/css/admin.css' );
	}
}

if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<' ) ) {
	$woocommerce_ver21_less = true;
} else {
	$woocommerce_ver21_less = false;
}

if ( ! function_exists( 'wskl_plugin_add_settings_link' ) ) {

	function wskl_plugin_add_settings_link( $links ) {

		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=woosym_korean_localization_checkout_settings' ),
			__( 'Settings' )
		);

		if ( isset( $links['0'] ) && false !== strstr( $links[0], 'Settings' ) ) {
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
		$args['posts_per_page'] = get_option( $woo_sym_prefix . 'related_products_count' ); // 4 related products
		$args['columns']        = 1; // arranged in 2 columns
		return $args;
	}
}

$sym_checkout_titles = array(
	'credit'  => __( '신용카드', 'wskl' ),
	'remit'   => __( '실시간 계좌이체', 'wskl' ),
	'virtual' => __( '가상계좌 이체', 'wskl' ),
	'mobile'  => __( '모바일소액결제', 'wskl' ),
);
$sym_checkout_desc   = '로 결제합니다.';
$sym_pg_agency       = get_option( $woo_sym_prefix . 'pg_agency' );

if ( wskl_is_option_enabled( 'enable_sym_pg' ) ) {

	// NOTE: 옵션 켜져 있는 것과 pay_gate_agency 찾을 수 있는 것은 별개의 사항으므로 에러 체크가 필요함.
	$pay_gate_agency = get_option( wskl_get_option_name( 'pg_agency' ) );

	if ( $pay_gate_agency && ! empty( $pay_gate_agency ) ) {

		$pg_main_path   = WSKL_PATH . '/includes/lib/class-pg-' . $pay_gate_agency . '-main.php';
		$pg_common_path = WSKL_PATH . '/includes/lib/class-pg-' . $pay_gate_agency . '-common.php';

		if ( file_exists( $pg_main_path ) && file_exists( $pg_common_path ) ) {

			/** @noinspection PhpIncludeInspection */
			require_once( $pg_main_path );

			/** @noinspection PhpIncludeInspection */
			require_once( $pg_common_path );

		} else {
			add_action(
				'admin_notices', function () use ( $pay_gate_agency ) {

				printf( '<div class="error"><p class="wskl-warning">%s: %s</p></div>', __( '다음 PG 모듈이 발견되지 않았습니다.', 'wskl' ), $pay_gate_agency );
			}
			);
		}

		/**
		 * Woocommerce REST API V3 action.
		 *
		 * @see \WC_API::handle_api_requests()
		 */
		add_action( 'woocommerce_api_request', 'wskl_add_api_request' );

		if ( ! function_exists( 'wskl_add_api_request' ) ) {

			function wskl_add_api_request( $api_request ) {

				if ( class_exists( $api_request ) ) {
					new $api_request();
				}
			}
		}
	}
}

/**
 * 배송자 이메일 전화번호 보여지 않기
 */
if ( ! wskl_is_option_enabled( 'disable_show_delivery_phone' ) ) {
	add_filter( 'woocommerce_admin_shipping_fields', 'woo_add_shipping_fields' );
	// woocommerce order meta box
	// adding shipping email, phone data
	function woo_add_shipping_fields( $fields ) {

		return array_merge( $fields, array(
			'email' => array(
				'label' => __( 'Email', 'woocommerce' ),
			),
			'phone' => array(
				'label' => __( 'Phone', 'woocommerce' ),
			),
		) );
	}
}

// moved to Woosym_Korean_Localization::includes()
///**
// * 모듈 배송추적
// */
//if ( wskl_is_option_enabled( 'enable_ship_track' ) ) {
//	require_once( WSKL_PATH . '/includes/lib/class-wskl-shipping-tracking.php' );
//}

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
	if ( ! is_admin() ) {
		add_action( 'plugins_loaded', 'wskl_country_ip_block' );
	}
}

function wskl_country_ip_block() {

	$wskl_geoip          = geoip_open( WSKL_PATH . "/includes/lib/geoip/GeoIP.dat", GEOIP_STANDARD );
	$wskl_country_ipcode = geoip_country_code_by_addr( $wskl_geoip, $_SERVER['REMOTE_ADDR'] );
	geoip_close( $wskl_geoip );

	$list     = preg_replace( '/\s+/', '', get_option( 'wskl_white_ipcode_list' ) );
	$code_arr = ( explode( ',', $list ) );
	$ip_list  = false;

	foreach ( $code_arr as $value ) {
		if ( $value == $wskl_country_ipcode ) {
			$ip_list = true;
			break;
		}
	}

	if ( ! $ip_list ) {
		wp_die( 'Blocked by IP' );
	}
}

// moved to Woosym_Korean_Localization::includes()
//if ( is_admin() ) {
//	include_once( WSKL_PATH . '/includes/class-settings.php' );
//	$wskl_setting = new Woosym_Korean_Localization_Settings( WSKL_PREFIX, WSKL_MAIN_FILE, WSKL_VERSION );
//
//	/** authorization */
//	require_once( WSKL_PATH . '/includes/lib/auth/class-auth.php' );
//	$auth = new \wskl\lib\auth\Auth( $wskl_setting );
//
//	/** post export */
//	if ( wskl_is_option_enabled( 'enable_post_export' ) ) {
//
//		require_once( WSKL_PATH . '/includes/lib/mat-logs/class-post-export.php' );
//		\wskl\lib\posts\Post_Export::initialize();
//	}
//
//} else {
//
//	// verification
//	require_once( WSKL_PATH . '/includes/lib/auth/class-verification.php' );
//	$verification = new \wskl\lib\auth\Verification();
//
//	// sales log
//	if ( wskl_is_option_enabled( 'enable_sales_log' ) ) {
//		require_once( WSKL_PATH . '/includes/lib/mat-logs/class-sales.php' );
//		$sales = new \wskl\lib\sales\Sales();
//	}
//
//	require_once( WSKL_PATH . '/includes/lib/mat-logs/class-product-logs.php' );
//	\wskl\lib\logs\Product_Logs::initialize();
//}

require_once( WSKL_PATH . '/includes/class-main.php' );
