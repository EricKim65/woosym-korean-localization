<?php

wskl_check_abspath();


/**
 * 간단하게 설정 파일을 편집하는 모듈
 *
 * 단, 해당 조건을 만족해야 한다.
 *
 * 1. 모듈은 쓰기 권한이 있어야 한다.
 * 2. wp-config.php 파일은 ABSPATH 가 define 되어 있어야 한다.
 * 3. ABSPATH 이외에 적어도 한 번 이상은 주석 처리가 되어 있다 하더라도 define 이 설정되어야 한다.
 *    그렇지 않으면 모듈은 새로운 정의를 파일에 추가할 수 없다.
 *
 * Class WSKL_Config_Editor
 */
class WSKL_Config_Editor {

	const REGEX_STRIP_COMMENTS = '/\/\*([\s\S]*?)\*\/|(^(\/\/|#)|\s+(\/\/|#)).*?$/ms';
	const REGEX_DEFINES        = '/define\s*\(\s*(\'|")(.+?)(\'|")\s*,\s*(.+?)\s*\)\s*;/';

	private static $config;
	private static $keys_to_filter;

	private static $fixed_filtered_keys = array(
		'ABSPATH',
		'AUTH_KEY',
		'AUTH_SALT',
		'LOGGED_IN_KEY',
		'LOGGED_IN_SALT',
		'NONCE_KEY',
		'NONCE_SALT',
		'SECURE_AUTH_KEY',
		'SECURE_AUTH_SALT',
	);

	public static function init() {

		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

		add_action( 'wp_loaded', array( __CLASS__, 'handle_form_submit' ) );

		self::$keys_to_filter = array_merge(
			self::$fixed_filtered_keys,
			(array) wskl_get_option( 'config_editor_keys_to_filter', array() )
		);
	}

	public static function admin_menu() {

		add_submenu_page(
			WSKL_MENU_SLUG,
			__( 'WP Config 편집', 'wskl' ),
			__( 'WP Config 편집', 'wskl' ),
			'manage_options',
			WSKL_PREFIX . 'config_editor',
			array( __CLASS__, 'output_config_editor_menu' )
		);
	}

	public static function handle_form_submit() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = wskl_POST( 'action' );
		if ( $action == 'wskl-update-wp-config' ) {
			self::update_wp_config();
		} else if ( $action == 'wskl-update-wp-config-filter' ) {
			self::update_wp_config_filter();
		}
	}

	private static function update_wp_config() {

		if ( ! wp_verify_nonce( $_POST['wskl-config-editor'], 'wskl-config-editor' ) ) {
			wp_die( 'Nonce verification error!' );
		}

		$prefix     = 'config-';
		$prefix_len = strlen( $prefix );
		$new_config = array();

		foreach ( $_POST as $key => $val ) {
			if ( strpos( $key, $prefix ) === 0 ) {
				$_key = sanitize_text_field( substr( $key, $prefix_len ) );
				$_val = sanitize_text_field( $val );

				if ( preg_match( '/__DIR__|__FILE__/', $_val ) ) {
					add_action(
						'admin_notices',
						function () {

							echo '<div class="error notice is-dismissible"><p>__DIR__, __FILE__ 같은 매크로는 여기서 사용할 수 없습니다.</p></div>';
						}
					);

					return;
				}
				$new_config[ $_key ] = $_val;
			}

			// additional config
			$matches = array();
			if ( preg_match( '/new-config-([0-9]+)/', $key, $matches ) ) {
				$seq  = intval( $matches[1] );
				$name = sanitize_text_field( trim( $_POST[ $matches[0] ] ) );
				if ( ! empty( $name ) && $seq && isset( $_POST[ 'new-value-' . $seq ] ) ) {
					$value               = sanitize_text_field( $_POST[ 'new-value-' . $seq ] );
					$new_config[ $name ] = $value;
				}
			}
		}

		self::filter_config( $new_config );

		$create_backup = $_POST['create-backup'] == 'yes';

		self::create_new_wp_config( $new_config, $create_backup );
	}

	private static function filter_config( array &$options ) {

		foreach ( self::$keys_to_filter as $item ) {
			if ( isset( $options[ $item ] ) ) {
				unset( $options[ $item ] );
			}
		}
	}

	private static function create_new_wp_config( $config, $create_backup ) {

		if ( $create_backup ) {
			$src = self::get_wp_config_file_name();
			$dst = dirname( $src ) . '/wp-config-backup-' . date( 'YmdHis' ) . '.php';
			@copy( $src, $dst );
		}

		$file_name = self::get_wp_config_file_name();
		$code      = self::create_wp_config_code( $config );
		file_put_contents( $file_name, $code );
	}

	private static function get_wp_config_file_name( $file_name = FALSE ) {

		if ( ! $file_name ) {
			$file_name = 'wp-config.php';
		}

		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {

			return ABSPATH . $file_name;

		} elseif ( @file_exists( dirname( ABSPATH ) . '/wp-config.php' ) &&
		           ! @file_exists( dirname( ABSPATH ) . '/wp-settings.php' )
		) {

			return dirname( ABSPATH ) . '/wp-config.php';
		}

		return FALSE; // 실제로 이 값이 리턴될 수는 없다. wp-config.php 파일이 없다면 코어가 애초부터 동작하지 않을 테니.
	}

	private static function create_wp_config_code( $config ) {

		$replace_callback = function ( $matches ) use ( &$config, &$last_match ) {

			$key = trim( $matches[2] );

			if ( $key != 'ABSPATH' ) {
				$last_match = $matches[0];
			}

			$replaced = WSKL_Config_Editor::get_define( $config, $matches, $key );
			unset( $config[ $key ] );

			return $replaced;
		};

		$content       = self::get_wp_config_content();
		$last_match    = '';
		$extra_defines = '';

		$revised_content = preg_replace_callback(
			'/define\s*\(\s*(\'|")(.+?)(\'|")\s*,\s*(.+?)\s*\)\s*;/',
			$replace_callback,
			$content
		);

		if ( ! empty( $last_match ) && ! empty( $config ) ) {
			$dummy_match   = array( '' );
			$extra_defines = "\n";
			foreach ( array_keys( $config ) as $key ) {
				$extra_defines .= self::get_define( $config, $dummy_match, $key ) . "\n";
			}
		}

		$additional_define_pos = strpos( $revised_content, $last_match ) + strlen( $last_match );
		$before_extra_defines  = substr( $revised_content, 0, $additional_define_pos );
		$next_extra_defines    = substr( $revised_content, $additional_define_pos );

		return $before_extra_defines . $extra_defines . $next_extra_defines;
	}

	public static function get_define( &$config, &$matches, $key ) {

		if ( ! isset( $config[ $key ] ) ) {
			return $matches[0];
		}

		$val = $config[ $key ];

		if ( in_array( $val, array( 'TRUE', 'true', 'FALSE', 'false' ) ) ) {
			$v = filter_var( $val, FILTER_VALIDATE_BOOLEAN );

			return sprintf( "define( '%s', %s );", $key, $v );
		}

		return sprintf( "define( '%s', \"%s\" );", $key, (string) $val );
	}

	private static function get_wp_config_content() {

		return file_get_contents( self::get_wp_config_file_name() );
	}

	private static function update_wp_config_filter() {

		if ( ! wp_verify_nonce( $_POST['wskl-config-filter-nonce'], 'wskl-config-filter-nonce' ) ) {
			wp_die( 'Nonce verification error!' );
		}

		$values = array_map(
			function ( $item ) { return sanitize_text_field( trim( $item ) ); },
			explode( "\n", wskl_POST( 'wskl-config-filter' ) )
		);

		wskl_update_option( 'config_editor_keys_to_filter', $values );

		self::$keys_to_filter = array_merge( self::$fixed_filtered_keys, $values );
	}

	public static function output_config_editor_menu() {

		self::$config = self::get_wp_config();

		wskl_get_template(
			'config-editor.php',
			array(
				'config'              => self::$config,
				'writable'            => self::has_write_permission(),
				'fixed_filtered_keys' => self::$fixed_filtered_keys,
				'config_filter'       => array_diff( self::$keys_to_filter, self::$fixed_filtered_keys ),
			)
		);
	}

	private static function get_wp_config() {

		$content = self::strip_comments( self::get_wp_config_content() );
		$matches = array();
		$output  = array();

		preg_match_all( self::REGEX_DEFINES, $content, $matches );

		if ( ! empty( $matches ) ) {
			$count = count( $matches[0] );
			for ( $i = 0; $i < $count; ++ $i ) {
				$key = trim( $matches[2][ $i ] );
				$val = self::strip_quotes( trim( $matches[4][ $i ] ) );

				$output[ $key ] = $val;
			}
		}

		self::filter_config( $output );

		return $output;
	}

	private static function strip_comments( $content ) {

		return preg_replace_callback(
			self::REGEX_STRIP_COMMENTS,
			function ( $match ) {

				return '';
			},
			$content
		);
	}

	private static function strip_quotes( $string ) {

		if ( empty( $string ) ) {
			return $string;
		}

		$len = strlen( $string );

		if ( $len < 2 ) {
			return $string;
		}

		$first_char = $string[0];
		$last_char  = $string[ $len - 1 ];

		if ( $first_char == "'" && $last_char == "'" ) {
			return trim( $string, "'" );
		}

		if ( $first_char == "\"" && $last_char == "\"" ) {
			return trim( $string, "\"" );
		}

		return $string;
	}

	private static function has_write_permission() {

		return is_writable( self::get_wp_config_file_name() );
	}
}


WSKL_Config_Editor::init();
