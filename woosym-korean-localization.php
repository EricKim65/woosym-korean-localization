<?php
/*
 * Plugin Name:       우커머스-심포니 통합 플러그인
 * Version:           3.3.1
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
define( 'WSKL_PLUGIN', basename( __DIR__ ) . '/' . basename( WSKL_MAIN_FILE ) );
define( 'WSKL_PREFIX', 'wskl_' );
define( 'WSKL_MENU_SLUG', WSKL_PREFIX . 'checkout_settings' );
define( 'WSKL_VERSION', '3.3.2' );

define( 'WSKL_NAME', '우커머스-심포니 통합 플러그인' );

define( 'DAUM_POSTCODE_HTTP', 'http://dmaps.daum.net/map_js_init/postcode.v2.js' );
define( 'DAUM_POSTCODE_HTTPS', 'https://spi.maps.daum.net/imap/map_js_init/postcode.v2.js' );

define( 'WP_MEMBERS_PLUGIN', 'wp-members/wp-members.php' );


if ( ! class_exists( 'Woosym_Korean_Localization' ) ) :

	final class Woosym_Korean_Localization {

		private static $_instance = NULL;

		private $_settings = NULL;

		/** @var WSKL_Submodules */
		private $_submodules = NULL;

		public static function instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new static();
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
		 */
		public function __construct() {

			do_action( 'wskl_before_init' );

			do_action( 'wskl_loaded' );

		}

		public function startup() {

			do_action( 'wskl_before_startup' );

			$this->init_constants();

			$this->includes();

			$this->check_compatibility();

			if ( ! wskl_woocommerce_found() ) {
				return;
			}

			$this->_submodules = new WSKL_Submodules();

			$this->init_hooks();

			$this->init_modules();

			do_action( 'wskl_after_startup' );
		}

		public function init_constants() {

			self::define( 'WSKL_DEBUG', FALSE );
		}
		/**
		 * @return Woosym_Korean_Localization_Settings
		 */
		public function settings() {

			return $this->_settings;
		}

		public function submodules() {

			return $this->_submodules;
		}

		public function includes() {

			require_once( WSKL_PATH . '/includes/lib/sym-mvc/wskl-sym-mvc-framework.php' );
			require_once( WSKL_PATH . '/includes/lib/auth/class-wskl-auth-info.php' );
			require_once( WSKL_PATH . '/includes/lib/wskl-functions.php' );
			require_once( WSKL_PATH . '/includes/lib/wskl-plugin.php' );
			require_once( WSKL_PATH . '/includes/lib/wskl-template-functions.php' );
			require_once( WSKL_PATH . '/includes/lib/class-wskl-submodules.php' );
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

			add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );

			/** right after the activation */
			add_action( 'activated_plugin', array( $this, 'after_plugin_activated' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

			$plugin = plugin_basename( WSKL_MAIN_FILE );
			/** 플러그인 목록의 action 항목 편집 */
			add_filter( "plugin_action_links_{$plugin}", array( $this, 'add_settings_link' ), 999 );

			add_filter( 'the_title', array( $this, 'order_received_title' ), 10, 2 );
			add_action( 'woocommerce_thankyou', array( $this, 'order_received_addition' ) );

			add_action( 'admin_bar_menu', array( $this, 'callback_admin_bar_menu' ), 99 );

			if ( wskl_debug_enabled() ) {
				add_action( 'wp_ajax_wskl_refresh_log', array( $this, 'wskl_refresh_log' ) );
			}
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

				/** wp-config.php editor */
				wskl_load_module(
					'/includes/class-wskl-config-editor.php',
					'enable_config_editor'
				);
			}

			if ( wskl_debug_enabled() ) {
				wskl_load_module( '/includes/lib/wskl-debugging.php' );
			}

			$this->init_payment_modules();
			$this->init_essential_modules();
			$this->init_extension_modules();
			$this->init_marketing_modules();
		}

		private function init_payment_modules() {

			$this->admin_notice_unauthorized(
				'payment',
				'checkout-payment-gates',
				array( __CLASS__, 'output_unauthorized_payment' )
			);

			if ( $this->is_request( 'frontend' ) ) {
				// payment verification
				wskl_load_module( '/includes/lib/auth/class-wskl-verification.php', 'enable_sym_pg' );
			}

			/**
			 * 결제 (frontend/admin 둘 다 요구 )
			 * payment 는 자체로 인증 확인을 하도록 됨.
			 */
			wskl_load_module( '/includes/class-wskl-payment-gates.php', 'enable_sym_pg' );
		}

		private function init_essential_modules() {

//			$authorized = $this->admin_notice_unauthorized(
//				'essential',
//				'essential-features',
//				array( __CLASS__, 'output_unauthorized_essential' )
//			);

			$authorized = $this->admin_notice_unauthorized(
				'payment',
				'essential-features',
				array( __CLASS__, 'output_unauthorized_payment' )
			);

			if ( ! $authorized ) {
				return;
			}

			/** 기존 Woosym 모듈에 산개해 있던 훅들 */
			wskl_load_module( '/includes/class-wskl-essential-module.php' );
		}

		private function init_extension_modules() {

//			$authorized = $this->admin_notice_unauthorized(
//				'extension',
//				'convenience-features',
//				array( __CLASS__, 'output_unauthorized_extension' )
//			);

			$authorized = $this->admin_notice_unauthorized(
				'payment',
				array( 'convenience-features', 'social-login', 'protection-features' ),
				array( __CLASS__, 'output_unauthorized_payment' )
			);

			if ( ! $authorized ) {
				return;
			}

			wskl_load_module( '/includes/class-wskl-extension-module.php' );
		}

		private function init_marketing_modules() {

			$authorized = $this->admin_notice_unauthorized(
				'marketing',
				'marketing',
				array( __CLASS__, 'output_unauthorized_marketing' )
			);

			if ( ! $authorized ) {
				return;
			}

			if ( $this->is_request( 'frontend' ) ) {

				// sales log
				wskl_load_module(
					'/includes/lib/marketing/class-sales.php',
					'enable_sales_log'
				);

				wskl_load_module(
					'/includes/lib/marketing/class-product-logs.php'
				);
			}

			if ( $this->is_request( 'admin' ) ) {

				/** post export */
				wskl_load_module(
					'/includes/lib/marketing/class-wskl-post-export.php',
					'enable_post_export'
				);
			}
		}

		/**
		 * @param $license_type
		 * @param mixed $tabs
		 * @param $callback
		 *
		 * @used-by Woosym_Korean_Localization::init_payment_modules()
		 * @used-by Woosym_Korean_Localization::init_essential_modules()
		 * @used-by Woosym_Korean_Localization::init_extension_modules()
		 * @used-by Woosym_Korean_Localization::init_marketing_modules()
		 *
		 * @return bool
		 */
		private function admin_notice_unauthorized( $license_type, $tabs, $callback ) {

			$authorized = wskl_license_authorized( $license_type );

			if ( ! $authorized && $this->is_request( 'admin' ) ) {

				if ( is_string( $tabs ) ) {
					$tabs = array( $tabs );
				}

				$page = wskl_GET( 'page' );
				$_tab = wskl_GET( 'tab' );

				if ( $page == WSKL_MENU_SLUG && in_array( $_tab, $tabs ) ) {
					add_action( 'admin_notices', $callback );
				}
			}

			return $authorized;
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

			$dabory_url        = add_query_arg( 'page', WSKL_MENU_SLUG, admin_url( 'admin.php' ) );
			$settings_link     = wskl_html_anchor( __( 'Settings' ), array( 'href' => $dabory_url, ), TRUE );
			$links['settings'] = $settings_link;

			if ( wskl_is_option_enabled( 'enable_dabory_members' ) && ! isset( $links['dabory-members'] ) ) {
				$links['dabory-members'] = wskl_html_anchor(
					__( '다보리 멤버스', 'wskl' ),
					array( 'href' => wskl_wp_members_url() ),
					TRUE
				);
			}

			if ( wskl_is_option_enabled( 'enable_dabory_sms' ) && ! isset( $links['dabory-sms'] ) ) {
				$links['dabory-sms'] = wskl_html_anchor(
					__( '다보리 SMS', 'wskl' ),
					array( 'href' => wskl_dabory_sms_url() ),
					TRUE
				);
			}

			return $links;
		}

		function order_received_title( $title, $id ) {

			if ( is_order_received_page() && get_the_ID() === $id ) {

				$alternative_text = wskl_get_option( 'thankyou_page_title_text' );

				if ( ! empty( $alternative_text ) ) {
					return $alternative_text;
				}
			}

			return $title;
		}

		function order_received_addition(
			/** @noinspection PhpUnusedParameterInspection */
			$order_id
		) {

			$text = wskl_get_option( 'woocommerce_thankyou_text' );
			if ( ! empty( $text ) ) {

				echo wp_kses_post( $text );
			}
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
					'id'     => 'wskl-checkout-payment-gates',
					'title'  => __( '지불기능', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'checkout-payment-gates' ),
				),
				array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-essential-features',
					'title'  => __( '핵심기능', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'essential-features' ),
				),
				array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-convenience-features',
					'title'  => __( '편의기능', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'convenience-features' ),
				),
				array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-social-login',
					'title'  => __( '소셜기능', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'social-login' ),
				),
				array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-protection-features',
					'title'  => __( '차단보안기능', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'protection-features' ),
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
					'id'     => 'wskl-beta-features',
					'title'  => __( '다보리 실험실', 'wskl' ),
					'href'   => wskl_get_setting_tab_url( 'beta-features' ),
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

			if ( wskl_is_option_enabled( 'enable_inactive_accounts' ) ) {
				$sub_menus[] = array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-inactive-accounts',
					'title'  => __( '휴면계정 설정', 'wskl' ),
					'href'   => wskl_wp_members_url( 'inactive-accounts' ),
				);
			}

			if ( wskl_is_option_enabled( 'enable_dabory_sms' ) ) {

				$sub_menus[] = array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-dabory-sms',
					'title'  => __( '다보리 SMS 설정', 'wskl' ),
					'href'   => wskl_dabory_sms_url(),
				);
			}

			if ( wskl_is_option_enabled( 'enable_config_editor' ) ) {
				$sub_menus[] = array(
					'parent' => 'wskl-root',
					'id'     => 'wskl-config-editor',
					'title'  => __( 'WP Config 편집', 'wskl' ),
					'href'   => wskl_wp_config_editor_url(),
				);
			}

			foreach ( $sub_menus as $menu ) {
				$wp_admin_bar->add_menu( $menu );
			}
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

		public function load_text_domain() {

			load_plugin_textdomain( 'wskl', FALSE, WSKL_PATH . '/lang' );
		}

		/**
		 * @callback
		 * @action     wp_ajax_wskl_refresh_log
		 * @used-by    Woosym_Korean_Localization::init_hooks()
		 */
		function wskl_refresh_log() {

			if ( ! wskl_debug_enabled() ) {
				die();
			}

			if ( ! wp_verify_nonce( $_GET['_wpnonce'], '_wpnonce' ) ) {
				die( 'nonce verification failed' );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				die( 'permission error' );
			}

			echo wskl_get_wp_log();
			die();
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
		public static function is_request( $type ) {

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

		public static function define( $constant, $expression ) {

			if ( ! defined( $constant ) ) {
				define( $constant, $expression );
			}
		}

		/**
		 * @used-by Woosym_Korean_Localization::output_unauthorized_payment()
		 * @used-by Woosym_Korean_Localization::output_unauthorized_essential()
		 * @used-by Woosym_Korean_Localization::output_unauthorized_extension()
		 * @used-by Woosym_Korean_Localization::output_unauthorized_marketing()
		 *
		 * @param $message
		 */
		public static function output_unauthorized( $message ) {

			?>
			<div class="notice notice-warning">
				<p>
					<?php echo $message; ?>
					<a href="<?php echo esc_url( wskl_get_setting_tab_url( 'authentication' ) ); ?>">
						<?php _e( '인증 페이지로', 'wskl' ); ?>
					</a>
				</p>
			</div>
			<?php
		}

		/**
		 * @callback
		 * @action    admin_notices
		 * @used-by   Woosym_Korean_Localization::admin_notice_unauthorized()
		 * @used-by   Woosym_Korean_Localization::init_payment_modules()
		 */
		public static function output_unauthorized_payment() {

//			$message = __( '지블 기능 활성화 키가 인증되지 않았습니니다.', 'wskl' );
			$message = __( '활성화 키가 인증되지 않았습니니다.', 'wskl' );

			self::output_unauthorized( $message );
		}

		/**
		 * @callback
		 * @action    admin_notices
		 * @used-by   Woosym_Korean_Localization::admin_notice_unauthorized()
		 * @used-by   Woosym_Korean_Localization::init_essential_modules()
		 */
		public static function output_unauthorized_essential() {

			$message = __( '핵심 기능 활성화 키가 인증되지 않았습니니다.', 'wskl' );

			self::output_unauthorized( $message );
		}

		/**
		 * @callback
		 * @action    admin_notices
		 * @used-by   Woosym_Korean_Localization::admin_notice_unauthorized()
		 * @used-by   Woosym_Korean_Localization::init_extension_modules()
		 */
		public static function output_unauthorized_extension() {

			$message = __( '확장 기능 활성화 키가 인증되지 않았습니니다.', 'wskl' );

			self::output_unauthorized( $message );
		}

		/**
		 * @callback
		 * @action    admin_notices
		 * @used-by   Woosym_Korean_Localization::admin_notice_unauthorized()
		 * @used-by   Woosym_Korean_Localization::init_marketing_modules()
		 */
		public static function output_unauthorized_marketing() {

			$message = __( '마케팅 자동화 활성화 키가 인증되지 않았습니니다.', 'wskl' );

			self::output_unauthorized( $message );
		}
	}

endif;

function WSKL() {

	return Woosym_Korean_Localization::instance();
}

$wskl = WSKL();
$wskl->startup();

$GLOBALS['wskl'] = $wskl;
