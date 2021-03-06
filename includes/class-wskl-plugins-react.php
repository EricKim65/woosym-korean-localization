<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WSKL_Plugins_React' ) ) :

	class WSKL_Plugins_React {

		private static $sym_mvc;

		public static function init() {
		}

		/**
		 * 우커머스 비활성화시 대응
		 *
		 * @callback
		 * @used-by wskl_plugin_monitor()
		 */
		public static function woocommerce() {

			add_action(
				'admin_notices',
				array( __CLASS__, 'output_woocommerce_warning' )
			);
		}

		/**
		 * 알림 콜백
		 *
		 * @callback
		 * @action    admin_notices
		 * @used-by   WSKL_Plugins_React::woocommerce()
		 */
		public static function output_woocommerce_warning() {

			printf(
				'<div class="notice error"><p>%s</p></div>',
				__(
					'다보리 알림: 우커머스 플러그인이 비활성화되어 있습니다. 우커머스-심포니 통합 플러그인의 기능을 정지합니다.',
					'wskl'
				)
			);
		}

		/**
		 * SYM-MVC 에 대응
		 *
		 * @callback
		 * @used-by   wskl_plugin_monitor()
		 *
		 * @param $plugin_file
		 * @param $trigger
		 */
		public static function sym_mvc_framework_is_active(
			$plugin_file,
			/** @noinspection PhpUnusedParameterInspection */
			$trigger
		) {

			static::$sym_mvc = $plugin_file;
			add_action(
				'admin_init',
				array( __CLASS__, 'deactivate_sym_mvc_framework' )
			);
		}

		/**
		 * @callback
		 * @action    admin_init
		 * @used-by   WSKL_Plugins_React::sym_mvc_framework_is_active()
		 */
		public static function deactivate_sym_mvc_framework() {

			deactivate_plugins( static::$sym_mvc );
			add_action(
				'admin_notices',
				array( __CLASS__, 'output_sym_mvc_deactivation' )
			);
		}

		/**
		 * @callback
		 * @action    admin_notices
		 * @used-by   WSKL_Plugins_React::deactivate_sym_mvc_framework()
		 */
		public static function output_sym_mvc_deactivation() {

			printf(
				'<div class="notice notice-warning"><p>%s<br/>%s</p></div>',
				__(
					'다보리 알림: SYM MVC FRAMEWORK 플러그인은 더이상 사용되지 않습니다. 플러그인을 비활성화시킵니다.',
					'wskl'
				),
				__(
					'앞으로 SYM MVC FRAMEWORK 플러그인은 활성화되지 않아도 됩니다.',
					'wskl'
				)
			);
		}

		/**
		 * 아임포트 활성화 시 대응 (우리도 아임포트를 쓸 경우에만 동작)
		 *
		 * @callback
		 * @used-by wskl_plugin_monitor()
		 */
		public static function iamport_plugin() {

			if ( wskl_woocommerce_found() && wskl_get_option( 'pg_agency' ) == 'iamport' ) {
				add_action( 'admin_notices', array( __CLASS__, 'output_iamport_is_active' ) );
			}
		}

		/**
		 * @callback
		 * @action    admin_notices
		 * @used-by   WSKL_Plugins_React::iamport_plugin()
		 */
		public static function output_iamport_is_active() {

			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				__(
					'다보리 알림: "우커머스용 아임포트 플러그인"이 활성화되어 있습니다! 다보리의 아임포트 지불 기능과 겹칩니다. "우커머스용 아임포트 플러그인"을 비활성화시켜 주세요.',
					'wskl'
				)
			);
		}

		/**
		 * @callback
		 * @used-by   wskl_plugin_monitor
		 */
		public static function wp_members() {

			if ( wskl_is_option_enabled( 'enable_dabory_members' ) ) {
				add_action( 'admin_notices', array( __CLASS__, 'output_wp_member_is_inactive' ) );
			}

			if ( wskl_is_option_enabled( 'enabled_inactive_accounts' ) ) {
				add_action( 'admin_notices', array( __CLASS__, 'output_inactive_accounts' ) );

				if ( ! defined( 'DISABLE_WP_CRON' ) || ! DISABLE_WP_CRON ) {
					add_action( 'admin_notices', array( __CLASS__, 'output_cron_is_disabled' ) );
				}
			}
		}

		/**
		 * @callback
		 * @action     admin_notices
		 * @used-by    WSKL_Plugins_React::wp_members()
		 */
		public static function output_wp_member_is_inactive() {

			printf(
				'<div class="error notice"><p>%s</p></div>',
				__(
					'다보리 알림: WP Members 플러그인이 비활성화되어 있습니다. 다보리 멤버스의 기능을 사용하려면 이 플러그인을 활성화시켜 주세요.',
					'wskl'
				)
			);
		}

		/**
		 * @callback
		 * @action     admin_notices
		 * @used-by    WSKL_Plugins_React::wp_members()
		 */
		public static function output_inactive_accounts() {

			printf(
				'<div class="error notice"><p>%s</p></div>',
				__(
					'다보리 알림: WP Members 플러그인이 비활성화되어 있습니다. 휴면계정 관리 기능을 사용하려면 이 플러그인을 활성화시켜 주세요.',
					'wskl'
				)
			);
		}

		public static function output_cron_is_disabled() {

			printf(
				'<div class="error notice"><p>%s</p></div>',
				__(
					'다보리 알림: 크론 사용이 중지되어 있습니다. 휴면계정 관리 기능을 사용하려면 크론을 사용해야 합니다.',
					'wskl'
				)
			);
		}
	}

endif;

WSKL_Plugins_React::init();