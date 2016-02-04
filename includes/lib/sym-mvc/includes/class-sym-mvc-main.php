<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Sym_Mvc_Main' ) ) :

	class Sym_Mvc_Main {

		protected static $_instance = null;

		public $_version;           // The version number.
		public $_token;             // The token.
		public $file;               // The main plugin file.
		public $dir;                // The main plugin directory.
		public $assets_dir;         // The plugin assets directory.
		public $assets_url;         // The plugin assets URL.
		public $_folder;            // plugin folder = plugin bootstrap
		public $_prefix;            // plugin folder = plugin bootstrap

		public function __construct( $prefix = '', $file = '', $version = '1.0.0' ) {

			$this->_version = $version;
			$this->file     = $file;
			$this->_prefix  = $prefix;

			$this->dir        = dirname( $this->file );
			$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
			$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

			$tmp_arr       = explode( "/", $file );
			$this->_folder = $tmp_arr[ count( $tmp_arr ) - 2 ]; //_token = plugin folder name
			$this->_token  = str_replace( "-", "_", $this->_folder );

		}

		public static function instance( $prefix, $file, $version ) {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new static( $prefix, $file, $version );
			}

			return self::$_instance;
		}

		public function __clone() {

			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wskl' ), '2.1' );
		}

		public function __wakeup() {

			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wskl' ), '2.1' );
		}

	}

endif;