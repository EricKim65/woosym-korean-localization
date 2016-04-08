<?php
/*
 * Plugin Name:       우커머스-심포니 통합 플러그인
 * Version:           3.3.0-alpha1
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
define( 'WSKL_PLUGIN', 'woosym-korean-localization/woosym-korean-localization.php' );
define( 'WSKL_PREFIX', 'wskl_' );
define( 'WSKL_VERSION', '3.3.0-alpha1' );

define( 'WSKL_MENU_SLUG', WSKL_PREFIX . 'checkout_settings' );

if ( ! defined( 'WSKL_DEBUG' ) ) {
	define( 'WSKL_DEBUG', FALSE );
}

require_once( WSKL_PATH . '/includes/lib/sym-mvc/wskl-sym-mvc-framework.php' );
require_once( WSKL_PATH . '/includes/lib/wskl-functions.php' );
require_once( WSKL_PATH . '/includes/lib/wskl-plugin.php' );
require_once( WSKL_PATH . '/includes/lib/wskl-template-functions.php' );


if ( ! class_exists( 'Woosym_Korean_Localization' ) ) :

	final class Woosym_Korean_Localization {

		private static $_instance = NULL;

		private $_settings = NULL;

		public static function instance( $prefix, $file, $version ) {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new static( $prefix, $file, $version );
			}

			return self::$_instance;
		}

		public function __clone() {

			_doing_it_wrong(
				__FUNCTION__,
				__( 'You\'re calling __clone() for a singleton class.', 'wskl' ),
				WSKL_VERSION
			);
		}

		public function __wakeup() {

			_doing_it_wrong(
				__FUNCTION__,
				__( 'You\'re calling __wakeup() for a singleton class.', 'wskl' ),
				WSKL_VERSION
			);
		}

		/**
		 * Woosym_Korean_Localization constructor.
		 *
		 * @param string $file
		 * @param string $version
		 */
		public function __construct( $file = '', $version = '1.0.0' ) {

			do_action( 'wskl_before_init' );

			$this->init_constants();

			$this->check_compatibility();

			if ( ! wskl_woocommerce_found() ) {
				return;
			}

			$this->init_hooks();

			$this->init_modules();

			do_action( 'wskl_init' );

		} // End __construct ()

		/**
		 * @return Woosym_Korean_Localization_Settings
		 */
		public function settings() {

			return $this->_settings;
		}

		public function init_constants() {

			$this->define( 'DAUM_POSTCODE_HTTP', 'http://dmaps.daum.net/map_js_init/postcode.v2.js' );
			$this->define( 'DAUM_POSTCODE_HTTPS', 'https://spi.maps.daum.net/imap/map_js_init/postcode.v2.js' );
		}

		/**
		 * @uses Woosym_Korean_Localization::on_plugin_activated()
		 * @uses Woosym_Korean_Localization::on_plugin_deactivated()
		 * @uses Woosym_Korean_Localization::after_plugin_activated()
		 * @uses Woosym_Korean_Localization::check_compatibility()
		 */
		public function init_hooks() {

			register_activation_hook( WSKL_MAIN_FILE, array( $this, 'on_plugin_activated' ) );
			register_deactivation_hook( WSKL_MAIN_FILE, array( $this, 'on_plugin_deactivated' ) );

			/** right after the activation */
			add_action( 'activated_plugin', array( $this, 'after_plugin_activated' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

			$plugin = plugin_basename( WSKL_MAIN_FILE );
			/** 플러그인 목록의 action 항목 편집 */
			add_filter( "plugin_action_links_{$plugin}", array( $this, 'add_settings_link' ), 999 );

			if ( $this->is_request( 'frontend' ) ) {

				// 관련상품 표시 갯수
				$related_products_count = (int) get_option( wskl_get_option_name( 'related_products_count' ) );
				if ( $related_products_count > 0 ) {

					$priority = (int) get_option( wskl_get_option_name( 'related_products_priority' ) );
					$callback = function ( $args ) {

						$args['posts_per_page'] = (int) get_option( wskl_get_option_name( 'related_products_count' ) );
						$args['columns']        = (int) get_option(
							wskl_get_option_name( 'related_products_columns' )
						);

						return $args;
					};

					add_filter( 'woocommerce_output_related_products_args', $callback, $priority, 1 );
				}

				if ( wskl_is_option_enabled( 'hide_product_review_tab' ) ) {
					add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'callback_hide_product_review_tab' ) );
				}
			}

			if ( wskl_get_option( 'disable_sku' ) == 'on' ) {
				add_filter( 'wc_product_sku_enabled', '__return_false' );
			}

			if ( wskl_get_option( 'disable_returntoshop' ) == 'on' ) {
				add_filter( 'woocommerce_return_to_shop_redirect', array( $this, 'sym_change_empty_cart_button_url' ) );
			}

			if ( wskl_get_option( 'korean_won' ) == 'on' ) {
				add_filter( 'woocommerce_currencies', array( $this, 'woosym_kwon_currency' ) );
				add_filter( 'woocommerce_currency_symbol', array( $this, 'woosym_kwon_currency_symbol' ), 10, 2 );
			}

			add_filter( 'the_title', array( $this, 'order_received_title' ), 10, 2 );
			add_action( 'woocommerce_thankyou', array( $this, 'order_received_addition' ) );

			add_action( 'admin_bar_menu', array( $this, 'callback_admin_bar_menu' ), 99 );
		}

		public function init_modules() {

			if ( $this->is_request( 'admin' ) ) {

				wskl_load_module( '/includes/class-settings.php' );
				$this->_settings = Woosym_Korean_Localization_Settings::instance(
					WSKL_PREFIX,
					WSKL_MAIN_FILE,
					WSKL_VERSION
				);

				/** authorization */
				wskl_load_module( '/includes/lib/auth/class-wskl-auth.php' );
				new WSKL_Auth( $this->settings() );

				/** post export */
				wskl_load_module(
					'/includes/lib/marketing/class-wskl-post-export.php',
					'enable_post_export'
				);

				/** wp-config.php editor */
				wskl_load_module(
					'/includes/class-wskl-config-editor.php',
					'enable_config_editor'
				);
			}

			if ( $this->is_request( 'frontend' ) ) {

				// verification
				wskl_load_module(
					'/includes/lib/auth/class-wskl-verification.php'
				);

				// sales log
				wskl_load_module(
					'/includes/lib/marketing/class-sales.php',
					'enable_sales_log'
				);

				wskl_load_module(
					'/includes/lib/marketing/class-product-logs.php'
				);

				/** 한국형 주소 및 체크아웃 필드 구성 */
				wskl_load_module(
					'/includes/class-wskl-sym-checkout.php',
					'enable_sym_checkout'
				);
			}

			/** 복합과세 */
			wskl_load_module( '/includes/class-wskl-combined-tax.php' );

			/** 다보리 배송 */
			wskl_load_module( '/includes/class-wskl-shipping-method.php', 'enable_korean_shipping' );

			/** IP blocking */
			wskl_load_module( '/includes/class-wskl-ip-block.php', 'enable_countryip_block' );

			/** 소셜 로그인 */
			wskl_load_module( '/includes/lib/class-social-login.php', 'enable_social_login' );

			/** 바로 구매 */
			wskl_load_module( '/includes/lib/class-direct-purchase.php', 'enable_direct_purchase' );

			/** BACS 입금자 다른 이름 */
			wskl_load_module(
				'/includes/class-wskl-bacs-payer-name.php',
				'enable_bacs_payer_name'
			);

			/** 배송추적 */
			wskl_load_module( '/includes/class-wskl-shipping-tracking.php', 'enable_ship_track' );

			/** 다보리 멤버스 */
			wskl_load_module(
				'/includes/class-wskl-dabory-members.php',
				'enable_dabory_members'
			);

			/** 결제 (frontend/admin 둘 다 요구 ) */
			wskl_load_module( '/includes/class-wskl-payment-gates.php', 'enable_sym_pg' );

			if ( wskl_debug_enabled() ) {
				wskl_load_module( '/includes/lib/wskl-debugging.php' );
			}
		}

		/** @callback */
		public function enqueue_scripts() {

			wp_enqueue_style(
				'wskl-common',
				plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/css/common.css',
				array(),
				WSKL_VERSION
			);

			wp_enqueue_style(
				'wskl-frontend',
				plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/css/frontend.css',
				array( 'wskl-common', ),
				WSKL_VERSION
			);
		}
		
		/**
		 * @callback
		 * @action    admin_enqueue_scripts
		 */
		public function admin_enqueue_scripts() {

			wp_enqueue_style(
				'wskl-common',
				plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/css/common.css',
				array(),
				WSKL_VERSION
			);

			wp_enqueue_style(
				'wskl-admin',
				plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/css/admin.css',
				array( 'wskl-common', ),
				WSKL_VERSION
			);
		}

		/**
		 * @callback
		 * @filter    plugin_action_links_{$plugin}
		 *
		 * @param $links
		 *
		 * @return array
		 */
		public function add_settings_link( $links ) {

			if ( isset( $links['0'] ) && FALSE !== strstr( $links[0], 'Settings' ) ) {
				unset( $links[0] );
			}

			$dabory_url = add_query_arg(
				'page',
				WSKL_MENU_SLUG,
				admin_url( 'admin.php' )
			);

			$settings_link = wskl_html_anchor(
				__( 'Settings' ),
				array( 'href' => $dabory_url, ),
				TRUE
			);

			$links['settings'] = $settings_link;

			if ( wskl_is_option_enabled( 'enable_dabory_members' ) ) {

				$links[] = wskl_html_anchor(
					__( '다보리 멤버스', 'wskl' ),
					array( 'href' => wskl_wp_members_url() ),
					TRUE
				);
			}

			return $links;
		}

		function order_received_title( $title, $id ) {

			if ( is_order_received_page() && get_the_ID() === $id ) {
				$title = "주문이 완료되었습니다.";
			}

			return $title;
		}

		function order_received_addition(
			/** @noinspection PhpUnusedParameterInspection */
			$order_id
		) {

			echo __( '<p><h5>주문에 감사드리며 항상 정성을 다 하겠습니다 !</h5></p>', 'wskl' );
		}

		function woosym_kwon_currency( $currencies ) {

			$currencies['KRW'] = __( '대한민국', 'woocommerce' );

			return $currencies;
		}

		function woosym_kwon_currency_symbol( $currency_symbol, $currency ) {

			switch ( $currency ) {
				case 'KRW':
					$currency_symbol = __( '원', 'wskl' );
					break;
			}

			return $currency_symbol;
		}

		function sym_change_empty_cart_button_url() {

			return get_site_url();
		}

		public function check_compatibility() {

			/** Check other plugins and notify to users. */
			add_action( 'plugins_loaded', array( $this, 'do_plugin_monitor' ), 1 );
		}

		/**
		 * @callback
		 * @action    plugins_loaded
		 */
		public function do_plugin_monitor() {

			require_once( WSKL_PATH . '/includes/class-wskl-plugins-monitor.php' );
			require_once( WSKL_PATH . '/includes/class-wskl-plugins-react.php' );

			/** 우커머스 비할성화 시 알림.*/
			wskl_add_plugin_status(
				'woocommerce/woocommerce.php',
				'inactive',
				array( 'WSKL_Plugins_React', 'woocommerce' )
			);

			/** SYM-MVC 활성화 시 대응 */
			wskl_add_plugin_status(
				'sym-mvc-framework/sym-mvc-framework.php',
				'active',
				array(
					'WSKL_Plugins_React',
					'sym_mvc_framework_is_active',
				)
			);

			/** 아임포트 활성화 시 대응 */
			wskl_add_plugin_status(
				'iamport-for-woocommerce/IamportPlugin.php',
				'active',
				array(
					'WSKL_Plugins_React',
					'iamport_plugin',
				)
			);

			/** WP-Members 대응 */
			wskl_add_plugin_status(
				'wp-members/wp-members.php',
				'inactive',
				array(
					'WSKL_Plugins_React',
					'wp_members',
				)
			);

			// 플러그인 확인.
			wskl_check_plugin_status();
		}

		/**
		 * @callback
		 */
		public function on_plugin_activated() {
		}

		/**
		 * @callback
		 */
		public function on_plugin_deactivated() {
		}

		/**
		 * @callback
		 * @action     activated_plugin
		 * @used-by    Woosym_Korean_Localization::init_hooks()
		 * @uses       Woosym_Korean_Localization::ensure_plugin_loading_sequence()
		 *
		 * @param $plugin
		 */
		public function after_plugin_activated( $plugin ) {

			if ( $plugin != WSKL_PLUGIN ) {
				return;
			}

			$this->ensure_plugin_loading_sequence();
		}

		/**
		 * @callback
		 *
		 * @param \WP_Admin_Bar $wp_admin_bar
		 */
		public function callback_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {

			$wp_admin_bar->add_node(
				array(
					'id'     => 'wskl-root',
					'title'  => '<span class="ab-icon"></span><span>' . __( '다보리', 'wskl' ) . '</span>',
					'parent' => FALSE,
					'href'   => wskl_get_setting_tab_url( '' ),
					'meta'   => array(),
				)
			);

			$sub_menus = array(
				array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-preview',
					'title'  => __( '일러두기', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'preview' ),
				),
				array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-authentication',
					'title'  => __( '제품인증', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'authentication' ),
				),
				array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-checkout-payment-gate',
					'title'  => __( '지불기능', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'checkout-payment-gate' ),
				),
				array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-checkout-shipping',
					'title'  => __( '핵심기능', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'checkout-shipping' ),
				),
				array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-ship_tracking',
					'title'  => __( '편의기능', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'ship_tracking' ),
				),
				array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-social-login',
					'title'  => __( '소셜기능', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'social-login' ),
				),
				array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-b_security',
					'title'  => __( '차단보안기능', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'b_security' ),
				),
				array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-marketing',
					'title'  => __( '마케팅자동화기능', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'marketing' ),
				),
			);

			if ( wskl_lab_enabled() ) {
				$sub_menus[] = array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-lab',
					'title'  => __( '다보리 실험실', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'lab' ),
				);
			}

			if ( wskl_debug_enabled() ) {
				$sub_menus[] = array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-developer',
					'title'  => __( '개발자용 ', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'developer' ),
				);
			}

			if ( wskl_is_option_enabled( 'enable_dabory_members' ) ) {
				$sub_menus[] = array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-dabory-members',
					'title'  => __( '다보리 멤버스 설정', 'wskl' ),
					'href'   => wskl_wp_members_url(),
				);
			}

			foreach ( $sub_menus as $menu ) {
				$wp_admin_bar->add_menu( $menu );
			}
		}

		/**
		 * @callback
		 *
		 * @param $tabs
		 *
		 * @return array
		 */
		public function callback_hide_product_review_tab( $tabs ) {

			if ( isset( $tabs['reviews'] ) ) {
				unset( $tabs['reviews'] );
			}

			return $tabs;
		}

		/**
		 * 우리 플러그인이 우커머스보다 뒤쪽에 로딩되도록 조정
		 *
		 * @used-by    Woosym_Korean_Localization::after_plugin_activated
		 */
		public function ensure_plugin_loading_sequence() {

			$active_plugins = (array) get_option( 'active_plugins', array() );
			$wc_index       = array_search( 'woocommerce/woocommerce.php', $active_plugins );
			$wskl_index     = array_search( WSKL_PLUGIN, $active_plugins );

			if ( $wc_index !== FALSE && $wskl_index !== FALSE && $wc_index > $wskl_index ) {

				unset( $active_plugins[ $wskl_index ] );
				$active_plugins[] = WSKL_PLUGIN;

				update_option( 'active_plugins', array_values( $active_plugins ) );
			}
		}

		/**
		 * clone of WooCommerce::is_request
		 *
		 * @see \WooCommerce::is_request
		 *
		 * @param $type
		 *
		 * @return bool
		 */
		public function is_request( $type ) {

			switch ( $type ) {
				case 'admin' :
					return is_admin();
				case 'ajax' :
					return defined( 'DOING_AJAX' );
				case 'cron' :
					return defined( 'DOING_CRON' );
				case 'frontend' :
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}

			throw new LogicException( 'is_request() does not support type: ' . $type );
		}

		private function define( $constant, $expression ) {

			if ( ! defined( $constant ) ) {
				define( $constant, $expression );
			}
		}
	}

endif;

function WSKL() {

	return Woosym_Korean_Localization::instance( WSKL_PREFIX, WSKL_MAIN_FILE, WSKL_VERSION );
}

$GLOBALS['wskl'] = WSKL();