<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WSKL_Plugins_React' ) ) :

	class WSKL_Plugins_React {

		private static $sym_mvc;
		private static $sym_mvc_in_ours;

		public static function init() {

			static::$sym_mvc_in_ours = WSKL_PATH . '/includes/lib/sym-mvc/wskl-sym-mvc-framework.php';
		}

		/**
		 * 우커머스 비활성화시 대응
		 *
		 * @used-by wskl_plugin_monitor()
		 */
		public static function woocommerce() {

			add_action( 'admin_notices',
			            array( __CLASS__, 'output_woocommerce_warning' ) );
		}

		/**
		 * 알림 콜백
		 */
		public static function output_woocommerce_warning() {

			printf( '<div class="notice error"><p>%s</p></div>',
			        __( '우커머스 플러그인이 비활성화되어 있습니다. 우커머스-심포니 통합 플러그인의 기능을 정지합니다.',
			            'wskl' ) );
		}

		/**
		 * SYM-MVC 에 대응
		 *
		 * @used-by wskl_plugin_monitor()
		 *
		 * @param $plugin_file
		 * @param $trigger
		 */
		public static function sym_mvc_framework_is_active(
			$plugin_file,
			$trigger
		) {

			static::$sym_mvc = $plugin_file;
			add_action( 'admin_init',
			            array( __CLASS__, 'deactivate_sym_mvc_framework' ) );
		}

		/**
		 * @action  admin_init
		 * @used-by sym_mvc_framework_is_active
		 */
		public static function deactivate_sym_mvc_framework() {

			deactivate_plugins( static::$sym_mvc );
			add_action( 'admin_notices',
			            array( __CLASS__, 'output_sym_mvc_deactivation' ) );
		}

		/**
		 * @action  admin_notices
		 * @used-by deactivate_sym_mvc_framework
		 */
		public static function output_sym_mvc_deactivation() {

			printf( '<div class="notice notice-warning"><p>%s<br/>%s</p></div>',
			        __( 'SYM MVC FRAMEWORK 플러그인은 더이상 사용되지 않습니다. 플러그인을 비활성화시킵니다.',
			            'wskl' ),
			        __( '앞으로 SYM MVC FRAMEWORK 플러그인은 활성화되지 않아도 됩니다.',
			            'wskl' ) );
		}

		/**
		 * 아임포트 활성화 시 대응 (우리도 아임포트를 쓸 경우에만 동작)
		 *
		 * @used-by wskl_plugin_monitor()
		 */
		public static function iamport_plugin() {

			$pg_agency = get_option( wskl_get_option_name( 'pg_agency' ) );
			if ( wskl_woocommerce_found() && $pg_agency == 'iamport' ) {
				add_action( 'admin_notices',
				            array( __CLASS__, 'output_iamport_is_active' ) );
			}
		}

		/**
		 * @action  admin_notices
		 * @used-by iamport_plugin()
		 */
		public static function output_iamport_is_active() {

			printf( '<div class="error notice"><p>%s</p></div>',
			        __( '"우커머스용 아임포트 플러그인"이 활성화되어 있습니다! 다보리의 아임포트 지불 기능과 겹칩니다. "우커머스용 아임포트 플러그인"을 비활성화시켜 주세요.',
			            'wskl' ) );
		}
	}

endif;

WSKL_Plugins_React::init();