<?php

if ( ! function_exists( 'alert_close' ) ) {
	function alert_close( $message ) {

		echo "<script>alert('" . esc_html( $message ) . "'); window.close();</script>";
	}
}


class WSKL_Social_Login {

	public static function init() {

		add_action(
			'wp_enqueue_scripts',
			function () {

				wp_enqueue_style(
					'wskl-login-css',
					plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/css/login.css',
					NULL,
					WSKL_VERSION
				);
			}
		);

		add_action(
			'login_enqueue_scripts',
			function () {

				wp_enqueue_style(
					'wskl-login-css',
					plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/css/login.css',
					NULL,
					WSKL_VERSION
				);
			}
		);

		add_action(
			'wp_enqueue_scripts',
			function () {

				wp_enqueue_style(
					'wskl-login-css',
					plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/css/login.css',
					NULL,
					WSKL_VERSION
				);
			}
		);

		add_action( 'login_form', array( __CLASS__, 'output_wordpress_login_form' ) );
		add_action( 'woocommerce_login_form', array( __CLASS__, 'output_my_account_login_form' ) );

		if ( wskl_is_option_enabled( 'fb_login' ) ) {
			add_action( 'init', array( __CLASS__, 'service_social_login_fb' ) );
		}

		if ( wskl_is_option_enabled( 'naver_login' ) ) {
			add_action( 'init', array( __CLASS__, 'service_social_login_naver' ) );
		}

		add_action( 'init', array( __CLASS__, 'process_social_login' ) );
	}

	public static function output_wordpress_login_form() {

		$login_options = static::get_login_options();

		if ( empty( $login_options ) ) {
			return;
		}

		echo '<div class="auth-provider-wrapper"><h3>' . __( '소셜계정으로 로그인', 'wskl' ) . '</h3>';
		foreach ( $login_options as $opt ) {
			echo "<a href=\"{$opt['href']}\">{$opt['link_title']}</a>";
		}
		echo '</div>';
	}

	private static function get_login_options() {

		$login_options = array();

		foreach ( array( 'fb', 'naver' ) as $prefix ) {

			$login_link_text = get_option( wskl_get_option_name( $prefix . '_login_link_text' ), '[icon]' );

			if ( $login_link_text && ! empty( $login_link_text ) ) {

				switch ( $prefix ) {
					case 'fb':
						$img_url = plugin_dir_url( WSKL_MAIN_FILE ) . "assets/image/social-login/facebook.png";
						$alt     = __( '페이스북으로 로그인', 'wskl' );
						break;
					case 'naver':
						$img_url = plugin_dir_url( WSKL_MAIN_FILE ) . "assets/image/social-login/naver.png";
						$alt     = __( '네이버 아이디로 로그인', 'wskl' );
						break;
					default:
						$img_url = '';
						$alt     = '';
				}

				$login_link_text = str_replace(
					'[icon]',
					sprintf(
						'<img src="%s" class="%s" alt="%s" title="%3$s">',
						esc_attr( $img_url ),
						esc_attr( 'auth-provider-icon ' ),
						esc_attr( $alt )
					),
					$login_link_text
				);

				$login_link_text = wp_kses_post( $login_link_text );

				$login_options[ $prefix ] = array(
					'href'       => esc_url( '/index.php?sym-api=service-social-login-fb' ),
					'link_title' => $login_link_text,
					'alt'        => $alt,
				);
			}
		}

		return $login_options;
	}

	public static function service_social_login_fb() {

		if ( ( isset( $_GET['sym-api'] ) && $_GET['sym-api'] == 'service-social-login-fb' ) ) {
			add_option( WSKL_PREFIX . 'sym-api-' . $_SERVER['HTTP_CLIENT_IP'], 'service-social-login-fb' );
		}

		if ( ( isset( $_GET['sym-api'] ) && $_GET['sym-api'] == 'service-social-login-fb' ) || ( isset( $_GET['code'] ) && get_option(
			                                                                                                                   WSKL_PREFIX . 'sym-api-' . $_SERVER['HTTP_CLIENT_IP']
		                                                                                                                   ) == 'service-social-login-fb' )
		) {

			require( 'home-social-login/http.php' );
			require( 'home-social-login/oauth_client.php' );

			$client               = new oauth_client_class;
			$client->debug        = FALSE;
			$client->debug_http   = TRUE;
			$client->server       = 'Facebook';
			$client->redirect_uri = site_url() . '/index.php';

			$client->client_id     = get_option( WSKL_PREFIX . 'fb_app_id' );
			$client->client_secret = get_option( wskl_get_option_name( 'fb_app_secret' ) );

			if ( strlen( $client->client_id ) == 0 || strlen( $client->client_secret ) == 0 ) {
				wskl_sym__alert( '페이스북 연동키값을 확인해 주세요.' );
			}
			/*
				  die('Please go to Facebook Apps page https://developers.facebook.com/apps , '.
					  'create an application, and in the line '.$application_line.
					  ' set the client_id to App ID/API Key and client_secret with App Secret');
				  */
			/* API permissions
				   */
			$client->scope = 'email';
			if ( ( $success = $client->Initialize() ) ) {
				if ( ( $success = $client->Process() ) ) {
					if ( strlen( $client->access_token ) ) {
						$success = $client->CallAPI(
							'https://graph.facebook.com/me',
							'GET',
							array(),
							array( 'FailOnAccessError' => TRUE ),
							$user
						);
					}
				}
				$success = $client->Finalize( $success );
			}
			if ( $client->exit ) {
				exit;
			}
			if ( $success ) {
				$client->GetAccessToken( $AccessToken );

				$mb_gubun      = 'facebook';
				$mb_id         = $user->id;
				$mb_name       = $user->name;
				$mb_nick       = $user->name;
				$mb_email      = $user->email;
				$token_value   = $AccessToken['value'];
				$token_secret  = '';
				$token_refresh = '';

				//$client->ResetAccessToken();

				if ( ! trim( $mb_id ) || ! trim( $token_value ) ) {
					wskl_sym__alert( "정보가 제대로 넘어오지 않아 오류가 발생했습니다." );
				}

				$token_array  = urlencode(
					static::encryptIt(
						$mb_gubun . '|' . substr(
							str_replace( '|', '', $mb_id ),
							0,
							18
						) . '|' . $mb_name . '|' . $mb_nick . '|' . $mb_email
					)
				);
				$redirect_url = '/?sym-api=process-social-login&token=' . $token_array;
				wp_redirect( $redirect_url );
				exit;


			} else {
				$error = HtmlSpecialChars( $client->error );
				wskl_sym__alert( $error );
			}

		}
	}

	private static function encryptIt( $q ) {

		$cryptKey = 'qJB0rGtIn5UB1xG03efyCp';
		$qEncoded = base64_encode(
			mcrypt_encrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), $q, MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) )
		);

		return ( $qEncoded );
	}

	public static function service_social_login_naver() {

		if ( ( isset( $_GET['sym-api'] ) && $_GET['sym-api'] == 'service-social-login-naver' ) ) {
			add_option( WSKL_PREFIX . 'sym-api-' . $_SERVER['HTTP_CLIENT_IP'], 'service-social-login-naver' );
		}

		if ( ( isset( $_GET['sym-api'] ) && $_GET['sym-api'] == 'service-social-login-naver' ) || ( isset( $_GET['code'] ) && get_option(
			                                                                                                                      WSKL_PREFIX . 'sym-api-' . $_SERVER['HTTP_CLIENT_IP']
		                                                                                                                      ) == 'service-social-login-naver' )
		) {

			require( 'home-social-login/http.php' );
			require( 'home-social-login/oauth_client.php' );

			$client               = new oauth_client_class;
			$client->debug        = FALSE;
			$client->debug_http   = TRUE;
			$client->server       = 'Naver';
			$client->redirect_uri = site_url() . '/index.php';

			$client->client_id     = get_option( wskl_get_option_name( 'naver_client_id' ) );
			$client->client_secret = get_option( wskl_get_option_name( 'naver_client_secret' ) );

			if ( strlen( $client->client_id ) == 0 || strlen( $client->client_secret ) == 0 ) {
				wskl_sym__alert( '페이스북 연동키값을 확인해 주세요.' );
			}

			if ( $login == 'Y' ) {
				unset( $_SESSION['OAUTH_STATE'] );
				$client->ResetAccessToken();
			}

			/* API permissions
				   */
			if ( ( $success = $client->Initialize() ) ) {
				if ( ( $success = $client->Process() ) ) {
					if ( strlen( $client->access_token ) ) {
						$success = $client->CallAPI(
							'https://apis.naver.com/nidlogin/nid/getUserProfile.xml',
							'POST',
							array( 'mode' => 'userinfo' ),
							array( 'FailOnAccessError' => TRUE ),
							$user
						);
					}
				}
				$success = $client->Finalize( $success );
			}
			if ( $client->exit ) {
				exit;
			}
			if ( $success ) {
				$xml = simplexml_load_string( $user );
				if ( $xml->result->resultcode == '00' ) {
					$client->GetAccessToken( $AccessToken );

					$mb_gubun      = 'naver';
					$mb_id         = $xml->response->enc_id;
					$mb_name       = $xml->response->nickname;
					$mb_nick       = $xml->response->nickname;
					$mb_email      = $xml->response->email;
					$token_value   = $AccessToken['value'];
					$token_refresh = $AccessToken['refresh'];
					$token_secret  = '';

					//$client->ResetAccessToken();

					if ( ! trim( $mb_id ) || ! trim( $token_value ) ) {
						wskl_sym__alert( "정보가 제대로 넘어오지 않아 오류가 발생했습니다." );
					}

					$token_array  = urlencode(
						static::encryptIt(
							$mb_gubun . '|' . substr(
								str_replace( '|', '', $mb_id ),
								0,
								18
							) . '|' . $mb_name . '|' . $mb_nick . '|' . $mb_email
						)
					);
					$redirect_url = '/?sym-api=process-social-login&token=' . $token_array;
					wp_redirect( $redirect_url );
					exit;

				} else {
					$error = HtmlSpecialChars( $xml->result->resultcode );
					alert_close( $error );
				}
			} else {
				$error = HtmlSpecialChars( $client->error );
				alert_close( $error );
			}
		}
	}

	public static function process_social_login() {

		if ( isset( $_GET['sym-api'] ) AND $_GET['sym-api'] == 'process-social-login' ) {
			list( $social_code, $social_id, $user_name, $user_nick, $user_email ) = explode(
				"|",
				static::decryptIt( $_GET['token'] )
			);
			//여기에서 할당된 리스트 데이터 갯구가 5개를 넘어가면 에러가나면 아래 코멘트를 제거, 맨아래 exit을 코멘트 하고 넘어온 내용을 볼것
			// sym__alert($social_code. '==='.  $social_id. '==='.  $user_name. '==='.  $user_nick. '==='.  $user_email. '===' );

			$user_login = $social_code . '_' . $social_id;
			$user       = get_user_by( 'login', $user_login );

			if ( ! $user ) {  // 없으면 user record insert
				if ( email_exists( $user_email ) ) {
					$mail_user   = get_user_by( 'email', $user_email );
					$social_type = get_user_meta( $mail_user->ID, 'sym_social_code' );

					$social_name = '';
					if ( count( $social_type ) > 0 ) {
						$social_name = $social_type[0];
					}
					$msg = "<p>이미 {$user_email} 이메일 사용자 계정이 있습니다. 가입 계정으로 로그인 해주세요. </p>";

					if ( ! empty( $social_name ) ) {
						$msg .= "<p> {$social_name} 를 통해서 가입하셨습니다.</p>";
					} else {
						$msg .= "<p>소셜 사이트를 거치지 않은 일반 계정입니다. </p>";
					}

					$msg .= "<a href=" . wp_login_url(
						) . " style='margin:10px 0; display:block;text-align:right;'>로그인</a>";

					wp_die( $msg );
				}

				$user_password = wp_generate_password();
				$user_role     = get_option( 'default_role' );

				$user_fields = array(
					'user_login'    => $user_login,
					'user_email'    => $user_email,
					'first_name'    => $user_name,
					'display_name'  => $user_nick,
					'user_nicename' => $user_nick,
					'user_pass'     => $user_password,
					'role'          => $user_role,
				);

				$user_id = wp_insert_user( $user_fields );
				add_user_meta( $user_id, 'sym_social_code', $social_code );
				update_user_meta( $user_id, 'nickname', $user_nick );

				//static::user_join_email($user_id, $social_code);

			} else {
				$user_id = $user->ID;

				if ( ! email_exists( $user_email ) ) {
					wp_update_user( array( 'ID' => $user->ID, 'user_email' => $user_email ) );
				}
			}

			$user_data = get_userdata( $user_id );

			wp_clear_auth_cookie();
			wp_set_auth_cookie( $user_data->ID, TRUE );

			$redirect_url = isset( $_SESSION['referrer_url'] ) ? $_SESSION['referrer_url'] : get_home_url();

			delete_option( WSKL_PREFIX . 'sym-api-' . $_SERVER['HTTP_CLIENT_IP'] ); //사용했던 옵션 반드시 삭제해야 함.

			do_action( 'wp_login', $user_data->user_login, $user_data );
			wp_redirect( $redirect_url );
			exit;
		}
	}

	private static function decryptIt( $q ) {

		$cryptKey = 'qJB0rGtIn5UB1xG03efyCp';
		$qDecoded = rtrim(
			mcrypt_decrypt(
				MCRYPT_RIJNDAEL_256,
				md5( $cryptKey ),
				base64_decode( $q ),
				MCRYPT_MODE_CBC,
				md5( md5( $cryptKey ) )
			),
			"\0"
		);

		return ( $qDecoded );
	}

	public function output_my_account_login_form() {

		$login_options = static::get_login_options();

		if ( empty( $login_options ) ) {
			return;
		}

		ob_start(); ?>

		<p class="form-row form-row-wide">
		<h3><?php _e( '소셜계정으로 로그인', 'wskl' ) ?></h3>
		<?php foreach ( $login_options as $opt ) : ?>
			<a href="<?php echo $opt['href'] ?>"><?php echo $opt['link_title'] ?></a>
		<?php endforeach; ?>
		</p>

		<?php echo ob_get_clean();
	}
}


WSKL_Social_Login::init();