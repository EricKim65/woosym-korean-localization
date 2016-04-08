<?php

wskl_check_abspath();


/**
 * 간단하게 설정 파일을 편집하는 모듈
 *
 * 단, 해당 조건을 만족해야 한다.
 * 1. 모듈은 쓰기 권한이 있어야 한다.
 * 2. wp-config.php 파일은 ABSPATH 가 define 되어 있어야 한다.
 * 3. 안정성을 위해, 또 실제로 PHP 프로그래밍 상, 같은 define 을 두 번 이상 만들지 말 것
 * 4. __DIR__, __FILE__ 같은 매직 상수를 쓸 수 없다. 이 경우 폼으로 전달된 문자에 한헤 문자열 평가를 해야 하는데
 *    그렇게 되면 심각한 보안 문제를 일으킬 수 있다.
 *
 * 주의: 폼에서 받은 내용을 바탕으로 값을 쓸 때는 주석 처리된 define 도 영향을 받을 수 있다.
 * define( 'A', "A" );
 * // define( 'A', "B" );  # 이 경우 주석처리되어 있지만 값이 영향을 받을 수 있다.
 *
 * Class WSKL_Config_Editor
 */
class WSKL_Config_Editor {

	/** 주석 부분을 무시하는 정규 표현 */
	const REGEX_STRIP_COMMENTS = '/\/\*([\s\S]*?)\*\/|(^(\/\/|#)|\s+(\/\/|#)).*?$/ms';

	/** define( ... , ... ); 부분을 추출하는 정규 표현 */
	const REGEX_DEFINES = '/define\s*\(\s*(\'|")(.+?)(\'|")\s*,\s*(.+?)\s*\)\s*;/';

	/**
	 * @var array define 중 웹으로 편집하기에 적절하지 않은 키워드를 필터링
	 */
	private static $keys_to_filter;

	/**
	 * @var array 이 값들은 웹 화면에서 편집하지 않음!
	 */
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

	/**
	 * @var array 사용자가 이 값을 $keys_to_filter 에 넣어 막지 않는 한 기본적으로 보이는 설정. 키는 설정 이름. 값은 디폴트 값.
	 */
	private static $must_use_configs = array(
		'WP_DEBUG'          => 'FALSE', // WordPress default debug
		'WP_DEBUG_LOG'      => 'FALSE', // Enable logging. see wp-content/debug.log
		'WP_DEBUG_DISPLAY'  => 'FALSE', // Output debug message.
		'SCRIPT_DEBUG'      => 'FALSE', // Use non-minified css or JavaScripts.
		'WSKL_DEBUG'        => 'FALSE', // Our debug features.
		'WSKL_LAB_FEATURES' => 'FALSE', // Our beta features.
	);

	public static function init() {

		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

		add_action( 'wp_loaded', array( __CLASS__, 'handle_form_submit' ) );

		self::$keys_to_filter = array_merge(
			self::$fixed_filtered_keys,
			(array) wskl_get_option( 'config_editor_keys_to_filter', array() )
		);

		self::$must_use_configs = apply_filters( 'wskl_config_editor_must_use_configs', self::$must_use_configs );
	}

	/**
	 * @callback
	 * @action    admin_menu
	 * @used-by   WSKL_Config_Editor::init()
	 * @uses      WSKL_Config_Editor::output_config_editor_menu()
	 */
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

	/**
	 * 폼 submit 처리를 맡음.
	 *
	 * @callback
	 * @action    wp_loaded
	 * @used-by   WSKL_Config_Editor::init()
	 * @uses      WSKL_Config_Editor::update_wp_config();
	 * @uses      WSKL_Config_Editor::update_wp_config_filter();
	 */
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

	/**
	 * configuration update
	 *
	 * @used-by WSKL_Config_Editor::handle_form_submit()
	 */
	private static function update_wp_config() {

		if ( ! wp_verify_nonce( $_POST['wskl-config-editor'], 'wskl-config-editor' ) ) {
			wp_die( 'Nonce verification error!' );
		}

		$prefix     = 'config-';
		$prefix_len = strlen( $prefix );

		/**
		 * @var array $new_config 폼으로 들어온 기존에 있던 wp-config define 상수
		 */
		$new_config = array();

		/**
		 * @var array $extra_config 새로 추가하려는 설정
		 */
		$extra_config = array();

		foreach ( $_POST as $key => $val ) {
			if ( strpos( $key, $prefix ) === 0 ) {
				$_key = sanitize_text_field( substr( $key, $prefix_len ) );
				$_val = sanitize_text_field( $val );

				if ( preg_match( '/__(LINE|FILE|DIR|FUNCTION|CLASS|TRAIT|METHOD|NAMESPACE)__/', $_val ) ) {
					add_action(
						'admin_notices',
						function () {

							echo '<div class="error notice is-dismissible">
									<p>__DIR__, __FILE__ 같은
									<a href="http://php.net/manual/kr/language.constants.predefined.php"">마법상수</a>
									는 여기서 사용할 수 없습니다.</p></div>';
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
					$extra_config[ $name ] = self::strip_quotes( sanitize_text_field( $_POST[ 'new-value-' . $seq ] ) );
				}
			}
		}

		self::filter_config( $new_config );
		self::filter_config( $extra_config );

		$create_backup = $_POST['create-backup'] == 'yes';

		self::create_new_wp_config( $new_config, $extra_config, $create_backup );

		add_action(
			'admin_notices',
			function () {

				echo '<div class="updated settings-error notice is-dismissible"><p><strong>' . __(
						'Settings saved.'
					) . '</strong></p></div>';
			}
		);
	}

	/**
	 * trim( $string, "\"'" ); 같은 식으로 쓰면, define( constant, expression ) 에서
	 * expression 부분의 표현에서 연산자를 이용한 수식이 있는 경우에 문제가 발생할 가능성이 있다.
	 * 예) $server_name . "$port" --> $server_name . "$port  로 마지막 따옴표가 빠질 수 있다.
	 *
	 * @param $string
	 *
	 * @return string
	 */
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

	/**
	 * 옵션 값 중 걸러지기로 할 항목을 걸러냄.
	 *
	 * @param array $options
	 */
	private static function filter_config( array &$options ) {

		foreach ( self::$keys_to_filter as $item ) {
			if ( isset( $options[ $item ] ) ) {
				unset( $options[ $item ] );
			}
		}
	}

	/**
	 * 설정 항목에 의해 실제로 wp-config.php 파일을 다시 만들어냄.
	 *
	 * @param array $config
	 * @param array $extra_config
	 * @param bool  $create_backup
	 */
	private static function create_new_wp_config( $config, $extra_config, $create_backup ) {

		if ( $create_backup ) {
			$src = self::get_wp_config_file_name();
			$dst = dirname( $src ) . '/wp-config-backup-' . date( 'YmdHis' ) . '.php';
			@copy( $src, $dst );
		}

		$file_name = self::get_wp_config_file_name();
		$code      = self::create_wp_config_code( $config, $extra_config );
		file_put_contents( $file_name, $code );
	}

	/**
	 * wp-config.php 파일을 찾아내 경로를 반환
	 *
	 * @param bool $file_name
	 *
	 * @return string wp-config.php 의 경로
	 */
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

	/**
	 * config 에 따라 define 문들을 변경.
	 *
	 * @used-by WSKL_Config_Editor::create_new_wp_config()
	 *
	 * @param array $config
	 * @param array $extra_config
	 *
	 * @return string
	 */
	private static function create_wp_config_code( &$config, &$extra_config ) {

		$replace_callback = function ( $matches ) use ( &$config, &$last_match ) {

			$key        = trim( $matches[2] );
			$substitute = WSKL_Config_Editor::get_define( $config, $matches, $key );

			if ( $key != 'ABSPATH' ) {
				$last_match = $substitute;
			}

			return $substitute;
		};

		$content    = self::get_wp_config_content();
		$last_match = '';

		// wp-config.php 파일의 내용을 업데이트.
		// 주석 안의 내용, 혹은 중복된 define 값도 같이 없데이트 되어 버릴 수 있으니 주의.
		$revised_content = preg_replace_callback( self::REGEX_DEFINES, $replace_callback, $content );

		// extra defines: 사용자가 폼을 통해 새로운 값을 전달할 경우
		// 만약 include 등을 사용해 파일 내부에 별다른 define 이 없다면, 파일 가장 처음에 값을 쓸 것임
		if ( ! empty( $extra_config ) ) {

			$dummy_match   = array( '' );
			$extra_defines = "\n";

			foreach ( array_keys( $extra_config ) as $key ) {
				$extra_defines .= self::get_define( $extra_config, $dummy_match, $key ) . "\n";
			}

			if ( empty( $last_match ) ) {
				$additional_define_pos = 0;
			} else {
				$additional_define_pos = strrpos( $revised_content, $last_match ) + strlen( $last_match );
			}

			$before_extra_defines = substr( $revised_content, 0, $additional_define_pos );
			$next_extra_defines   = substr( $revised_content, $additional_define_pos );

			return $before_extra_defines . $extra_defines . $next_extra_defines;
		}

		return $revised_content;
	}

	/**
	 * define 문을 생성. $config 에 $key 항목이 있으면 $config[ $key ] 에 설정된 값으로
	 * define( $key, $value ); 구문을 생성한다.
	 *
	 * 만약 $key 가 발견되지 않으면 정규 표현식에 의해 감지된 define( .... ); 구문을 그대로 리턴한다.
	 *
	 * @param array  $config  설정
	 * @param array  $matches 정규 표현식에 매치된 결과
	 * @param string $key     가져올 키 이름
	 *
	 * @return string define string
	 */
	public static function get_define( &$config, &$matches, $key ) {

		if ( ! isset( $config[ $key ] ) ) {
			return $matches[0];
		}

		$val = $config[ $key ];

		if ( in_array( $val, array( 'TRUE', 'true', 'FALSE', 'false' ) ) ) {
			return sprintf( "define( '%s', %s );", $key, $val );
		}

		return sprintf( "define( '%s', \"%s\" );", $key, (string) $val );
	}

	private static function get_wp_config_content() {

		return file_get_contents( self::get_wp_config_file_name() );
	}

	/**
	 * @used-by WSKL_Config_Editor::handle_form_submit()
	 */
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

		add_action(
			'admin_notices',
			function () {

				echo '<div class="updated settings-error notice is-dismissible"><p><strong>' . __(
						'Settings saved.'
					) . '</strong></p></div>';
			}
		);
	}

	/**
	 * @callback
	 * @used-by   WSKL_Config_Editor::admin_menu()
	 * @see       add_submenu_page()
	 */
	public static function output_config_editor_menu() {

		$config      = self::get_wp_config();
		$mu_defaults = array_diff_key( self::$must_use_configs, $config );

		wskl_get_template(
			'config-editor.php',
			array(
				'config'              => $config,
				'mu_defaults'         => $mu_defaults,
				'writable'            => self::has_write_permission(),
				'fixed_filtered_keys' => self::$fixed_filtered_keys,
				'config_filter'       => array_diff( self::$keys_to_filter, self::$fixed_filtered_keys ),
			)
		);
	}

	/**
	 * wp-config.php 로부터 define 구문을 가져온다. 주석 안의 코드는 무시한다.
	 *
	 * @return array
	 */
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

	private static function has_write_permission() {

		return is_writable( self::get_wp_config_file_name() );
	}
}


WSKL_Config_Editor::init();
