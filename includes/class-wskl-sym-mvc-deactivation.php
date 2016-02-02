<?php

if ( ! class_exists( 'WSKL_Sym_MVC_Deactivation' ) ) :

	require_once( WSKL_PATH . '/includes/lib/wskl-plugin.php' );


	/**
	 * Symphony MVC Framework 플러그인을 비활성화합니다.
	 * 이 플러그인은 include/lib/sym-mvc 에 통합되어 있습니다.
	 * sym-mvc-framework 플러그인이 설치, 그리고 활성화 되어 있을 경우, 비활성화시킵니다.
	 * sym-mvc-framework 플러그인은 이 플러그인이 활성화 되어 있는 한 다시 활성화 되지 못합니다
	 *
	 * Class WSKL_Sym_MVC_Deactivation
	 */
	class WSKL_Sym_MVC_Deactivation {

		private static $sym_mvc_path = 'sym-mvc-framework/sym-mvc-framework.php';

		public static function init() {

			//if ( is_admin() ) {

			if ( file_exists( WP_PLUGIN_DIR . '/sym-mvc-framework/sym-mvc-framework.php' ) ) {

				if ( wskl_is_plugin_active( static::$sym_mvc_path ) ) {
					add_action( 'admin_init', array( __CLASS__, 'deactivate_sym_mvc_framework' ) );
				} else {
					require_once( WSKL_PATH . '/includes/lib/sym-mvc/sym-mvc-framework.php' );
				}

			} else {
				require_once( WSKL_PATH . '/includes/lib/sym-mvc/sym-mvc-framework.php' );
			}
			//}
		}

		public static function deactivate_sym_mvc_framework() {

			deactivate_plugins( static::$sym_mvc_path );
			add_action( 'admin_notices', array( __CLASS__, 'sym_mvc_deactivation_output' ) );
		}

		public static function sym_mvc_deactivation_output() {

			printf( '<div class="update-nag notice"><p>%s</p></div>', __( 'SYM MVC FRAMEWORK 플러그인은 더이상 사용되지 않습니다. 플러그인을 비활성화시킵니다.', 'wskl' ) );
		}


	}


endif;

WSKL_Sym_MVC_Deactivation::init();