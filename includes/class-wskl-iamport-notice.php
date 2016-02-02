<?php

if ( ! class_exists( 'WSKL_Iamport_Notice' ) ) :

	require_once( WSKL_PATH . '/includes/lib/wskl-plugin.php' );

	/**
	 *
	 * Class WSKL_WooCommerce_Activation
	 */
	class WSKL_Iamport_Notice {

		private static $iamport = 'iamport-for-woocommerce/IamportPlugin.php';

		public static function init() {

			if( is_admin() ) {

				if ( file_exists( WP_PLUGIN_DIR . '/' . static::$iamport ) ) {

					// 지불기능이 확실히 활성화되어 있어야함.
					if ( wskl_is_plugin_active( static::$iamport ) ) {
						add_action( 'admin_init', array( __CLASS__, 'notice_iamport_present' ) );
					}
				}
			}
		}

		public static function notice_iamport_present() {

			add_action( 'admin_notices', array( __CLASS__, 'output_iamport_deactivated' ) );
		}

		public static function output_iamport_deactivated() {

			printf( '<div class="error notice"><p>%s</p></div>', __( '"우커머스용 아임포트 플러그인"이 활성화되어 있습니다! 다보리의 아임포트 지불 기능과 겹칩니다. "우커머스용 아임포트 플러그인"을 비활성화시켜 주세요.', 'wskl' ) );
		}
	}

	WSKL_Iamport_Notice::init();

endif;