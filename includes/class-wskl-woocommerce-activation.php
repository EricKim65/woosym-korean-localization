<?php

if ( ! class_exists( 'WSKL_WooCommerce_Activation' ) ) :

	require_once( WSKL_PATH . '/includes/lib/wskl-plugin.php' );

	/**
	 *
	 * Class WSKL_WooCommerce_Activation
	 */
	class WSKL_WooCommerce_Activation {

		private static $woocommerce = 'woocommerce/woocommerce.php';

		public static function init() {

			if( is_admin() ) {

				if ( file_exists( WP_PLUGIN_DIR . '/' . static::$woocommerce ) ) {

					if ( wskl_is_plugin_inactive( static::$woocommerce ) ) {
						add_action( 'admin_init', array( __CLASS__, 'woocommerce_is_deactivated' ) );
					}

				} else {
					add_action( 'admin_notices', array( __CLASS__, 'output_woocommerce_missing' ) );
				}
			}
		}

		public static function woocommerce_is_deactivated() {

			add_action( 'admin_notices', array( __CLASS__, 'output_woocommerce_is_deactivated' ) );
		}

		public static function output_woocommerce_is_deactivated() {
			printf( '<div class="notice error"><p>%s</p></div>', __( '우커머스가 비활성화되어 있었습니다. 우커머스 플러그인을 활성화해 주세요.', 'wskl' ) );
		}

		public static function output_woocommerce_missing() {
			printf( '<div class="notice error"><p>%s</p></div>', __( '우커머스 플러그인이 발견되지 않았습니다. <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">우커머스 플러그인</a>을 설치해 주세요.', 'wskl' ) );
		}
	}

	WSKL_WooCommerce_Activation::init();

endif;