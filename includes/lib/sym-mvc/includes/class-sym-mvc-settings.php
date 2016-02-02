<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

//if ( ! class_exists( 'Sym_Mvc_Main' ) ) :

	class Sym_Mvc_Settings {

		private static $_instance = null;
		public         $_prefix   = ''; //* Prefix for plugin settings.
		public         $admin;
		public         $file;
		public         $dir;
		public         $_token;
		public         $_folder;
		public         $script_suffix;
		public         $settings  = array();  //* Available settings for plugin.

		public function __construct( $prefix = '', $file = '', $version = '1.0.0' ) {

			// Load plugin environment variables
			$this->_prefix  = $prefix;
			$this->_version = $version;
			$this->file     = $file;

			$this->dir           = dirname( $this->file );
			$this->assets_dir    = trailingslashit( $this->dir ) . 'assets';
			$this->assets_url    = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );
			$tmp_arr             = explode( "/", $file );
			$this->_folder       = $tmp_arr[ count( $tmp_arr ) - 2 ]; //_token = plugin folder name
			$this->_token        = str_replace( "-", "_", $this->_folder );
			$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// Load API for generic admin functions
			$this->admin = new Sym_Mvc_Admin_API();

			// Initialize settings
			add_action( 'init', array( $this, 'init_settings' ), 11 );

			// Register plugin settings
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			// Add settings page to menu
			add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

			// Add settings link to plugins page
			add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), array( $this, 'add_settings_link' ) );
		}

		public function add_settings_link( $links ) {  //Add settings link to plugin list table
			$settings_link = '<a href="options-general.php?page=' . $this->_token . '_settings">' . __( 'Settings', $this->_folder ) . '</a>';
			array_push( $links, $settings_link );

			return $links;
		}

		public function register_settings() { //Register plugin settings
			if ( is_array( $this->settings ) ) {

				// Check posted/selected tab
				$current_section = '';
				if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
					$current_section = $_POST['tab'];
				} else {
					if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
						$current_section = $_GET['tab'];
					}
				}

				foreach ( $this->settings as $section => $data ) {

					if ( $current_section && $current_section != $section ) {
						continue;
					}

					// Add section to page
					add_settings_section( $section, $data['title'], array(
						$this,
						'settings_section',
					), $this->_token . '_settings' );

					foreach ( $data['fields'] as $field ) {

						// Validation callback for field
						$validation = '';
						if ( isset( $field['callback'] ) ) {
							$validation = $field['callback'];
						}

						// Register field
						$option_name = $this->_prefix . $field['id'];
						register_setting( $this->_token . '_settings', $option_name, $validation );

						// Add field to page
						add_settings_field( $field['id'], $field['label'], array(
							$this->admin,
							'display_field',
						), $this->_token . '_settings', $section, array(
							'field'  => $field,
							'prefix' => $this->_prefix,
						) );
					}

					if ( ! $current_section ) {
						break;
					}
				}
			}
		}

		public function settings_section( $section ) {

			$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
			echo $html;
		}

		public function settings_page() { // Load settings page content

			// Build page HTML
			$html = '<div class="wrap" id="' . $this->_token . '_settings">' . "\n";
			$tab  = '';
			if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
				$tab .= $_GET['tab'];
			}

			// Show page tabs
			if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {

				$html .= '<h2 class="nav-tab-wrapper">' . "\n";

				$c = 0;
				foreach ( $this->settings as $section => $data ) {

					// Set tab class
					$class = 'nav-tab';
					if ( ! isset( $_GET['tab'] ) ) {
						if ( 0 == $c ) {
							$class .= ' nav-tab-active';
						}
					} else {
						if ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
							$class .= ' nav-tab-active';
						}
					}

					// Set tab link
					$tab_link = add_query_arg( array( 'tab' => $section ) );
					if ( isset( $_GET['settings-updated'] ) ) {
						$tab_link = remove_query_arg( 'settings-updated', $tab_link );
					}

					// Output tab
					$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

					++ $c;
				}

				$html .= '</h2>' . "\n";
			}

			$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

			// Get settings fields
			ob_start();
			settings_fields( $this->_token . '_settings' );
			do_settings_sections( $this->_token . '_settings' );
			$html .= ob_get_clean();

			$html .= '<p class="submit">' . "\n";
			$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
			$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( '변경 사항 저장', $this->_folder ) ) . '" />' . "\n";
			$html .= '</p>' . "\n";
			$html .= '</form>' . "\n";
			$html .= '</div>' . "\n";

			echo $html;
		}

	}

//endif;