<?php
/*
 * Plugin Name:       우커머스-심포니 통합 플러그인
 * Version:           3.2.5-branch
 * Plugin URI:        https://www.dabory.com/
 * Description:       우커머스를 카페24 같이 편리하게 만들어주는 한국 쇼핑몰 환경 표준 플러그인.
 * Author:            (주)심포니소프트 - 다보리
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
define( 'WSKL_VERSION', '3.2.5-branch' );

define( 'WSKL_MENU_SLUG', WSKL_PREFIX . 'checkout_settings' );

if ( ! defined( 'WSKL_DEBUG' ) ) {
	define( 'WSKL_DEBUG', FALSE );
}

require_once( WSKL_PATH . '/includes/lib/sym-mvc/wskl-sym-mvc-framework.php' );
require_once( WSKL_PATH . '/includes/lib/wskl-plugin.php' );
require_once( WSKL_PATH . '/includes/lib/wskl-functions.php' );
require_once( WSKL_PATH . '/includes/lib/wskl-template-functions.php' );

add_action( 'plugins_loaded', 'wskl_plugin_monitor', 1 );


if ( ! function_exists( 'wskl_plugin_monitor' ) ) :

	function wskl_plugin_monitor() {

		require_once( WSKL_PATH . '/includes/class-wskl-plugins-monitor.php' );
		require_once( WSKL_PATH . '/includes/class-wskl-plugins-react.php' );

		/** 우커머스 비할성화 시 알림.*/
		wskl_add_plugin_status( 'woocommerce/woocommerce.php', 'inactive', array(
			'WSKL_Plugins_React',
			'woocommerce',
		) );

		/** SYM-MVC 활성화 시 대응 */
		wskl_add_plugin_status( 'sym-mvc-framework/sym-mvc-framework.php', 'active', array(
			'WSKL_Plugins_React',
			'sym_mvc_framework_is_active',
		) );


		/** 아임포트 활성화 시 대응 */
		wskl_add_plugin_status( 'iamport-for-woocommerce/IamportPlugin.php', 'active', array(
			'WSKL_Plugins_React',
			'iamport_plugin',
		) );

		// 플러그인 확인.
		wskl_check_plugin_status();
	}

endif;


add_action( 'plugins_loaded', 'wskl_startup_plugin', 2 );

function wskl_startup_plugin() {

	if ( ! wskl_woocommerce_found() ) {
		// 에러 메시지는 별도로 출력됨.
		return;
	}

	if ( is_admin() ) {
		add_action( 'admin_enqueue_scripts', function () {

			wp_enqueue_style( 'wskl-admin-css', plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/css/admin.css' );
		} );
	}

	if ( ! function_exists( 'wskl_plugin_add_settings_link' ) ) {

		function wskl_plugin_add_settings_link( $links ) {

			if ( isset( $links['0'] ) && false !== strstr( $links[0], 'Settings' ) ) {
				unset( $links[0] );
			}

			$dabory_url        = add_query_arg( 'page', WSKL_MENU_SLUG, admin_url( 'admin.php' ) );
			$settings_link     = wskl_html_anchor( __( 'Settings' ), array( 'href' => $dabory_url, ), true );
			$links['settings'] = $settings_link;

			return $links;
		}
	}

	$plugin = plugin_basename( __FILE__ );
	add_filter( "plugin_action_links_$plugin", 'wskl_plugin_add_settings_link', 99 );


	/** 많은 기능들이 이 곳으로 옮겨졌고, 앞으로 위 코드들도 이 쪽으로 옮겨질 예정. */
	require_once( WSKL_PATH . '/includes/class-main.php' );
}
