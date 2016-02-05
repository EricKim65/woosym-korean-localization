<?php

if ( ! class_exists( 'WSKL_Plugins_React' ) ) :

	class WSKL_Plugins_React {

		private static $sym_mvc;
		private static $sym_mvc_in_ours;

		public static function init() {

			static::$sym_mvc_in_ours = WSKL_PATH . '/includes/lib/sym-mvc/sym-mvc-framework.php';
		}
		public static function notice_iamport_present() {

			add_action( 'admin_notices', array( __CLASS__, 'output_iamport_deactivated' ) );
		}

		public static function output_iamport_deactivated() {

			printf( '<div class="error notice"><p>%s</p></div>', __( '"우커머스용 아임포트 플러그인"이 활성화되어 있습니다! 다보리의 아임포트 지불 기능과 겹칩니다. "우커머스용 아임포트 플러그인"을 비활성화시켜 주세요.', 'wskl' ) );
		}

		public static function woocommerce() {
			add_action( 'admin_notices', array( __CLASS__, 'output_woocommerce_warning' ) );
		}

		public static function sym_mvc_framework_is_active( $plugin_file, $trigger ) {

			static::$sym_mvc = $plugin_file;
			add_action( 'admin_init', array( __CLASS__, 'deactivate_sym_mvc_framework' ) );
		}

		public static function sym_mvc_framework_is_inactive( $plugin_file, $trigger ) {
			/** @noinspection PhpIncludeInspection */
			require_once( static::$sym_mvc_in_ours );
		}

		public static function deactivate_sym_mvc_framework() {

			deactivate_plugins( static::$sym_mvc );
			add_action( 'admin_notices', array( __CLASS__, 'output_sym_mvc_deactivation' ) );
		}

		public static function iamport_plugin() {
			if( wskl_woocommerce_found() ) {
				add_action( 'admin_notices', array( __CLASS__, 'output_iamport_warning' ) );
			}
		}

		public static function output_woocommerce_warning() {
			printf(
				'<div class="notice error"><p>%s</p></div>',
				__( '우커머스 플러그인이 비활성화되어 있습니다. 우커머스-심포니 통합 플러그인의 기능을 정지합니다.', 'wskl' )
			);
		}

		public static function output_sym_mvc_deactivation() {
			printf(
				'<div class="notice notice-warning"><p>%s<br/>%s</p></div>',
				__( 'SYM MVC FRAMEWORK 플러그인은 더이상 사용되지 않습니다. 플러그인을 비활성화시킵니다.', 'wskl' ),
				__( '앞으로 SYM MVC FRAMEWORK 플러그인은 활성화되지 않아도 됩니다.', 'wskl' )
			);
		}

		public static function output_iamport_warning() {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				__( '아임포트 플러그인이 활성화되어 있습니다. 우커머스-심포니 통합 플러그인의 기능과 충돌할 우려가 있습니다.', 'wskl' )
			);
		}
	}

endif;

WSKL_Plugins_React::init();