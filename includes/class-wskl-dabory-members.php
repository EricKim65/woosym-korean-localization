<?php


/**
 * Class WSKL_Dabory_Members
 */
class WSKL_Dabory_Members {

	const WP_MEMBERS = 'wp-members/wp-members.php';

	private static $validation_errors = array();
	private static $agreement_keys    = array( 'tos', 'privacy', '3rd_party' );

	public static function init() {

		if ( wskl_is_plugin_inactive( self::WP_MEMBERS ) ) {
			return;
		}

		self::$agreement_keys = apply_filters( 'dabory_members_agreement_keys', self::$agreement_keys );

		if ( is_admin() ) {
			wskl_load_module( '/includes/admin/class-wskl-dabory-members-admin.php', 'enable_dabory_members' );
		}

		self::init_hooks();
	}

	private static function init_hooks() {

		if ( wskl_is_option_enabled( 'members_show_terms' ) ) {

			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'add_registration_scripts' ) );

			add_filter( 'wpmem_register_form_rows', array( __CLASS__, 'include_terms' ), 10, 2 );
			add_filter( 'wpmem_pre_register_data', array( __CLASS__, 'validate_agreements' ) );
		}
	}

	public static function add_registration_scripts() {

		// wskl_enqueue_script( '')

		wp_enqueue_style(
			'dabory-members-registration',
			plugin_dir_url( WSKL_MAIN_FILE ) . 'assets/css/dabory-members-registration.css'
		);
	}

	public static function validate_agreements( $fields ) {

		/**
		 * @var string $wpmem_themsg validation string
		 *
		 * @see wp-members/inc/register.php
		 * @see wpmem_registration
		 */
		global $wpmem_themsg;

		foreach ( self::$agreement_keys as $key ) {

			$agreed  = wskl_POST( 'checkbox-' . $key ) == 'yes';
			$post_id = intval( wskl_get_option( 'members_page_' . $key ) );

			if ( $post_id && ! $agreed ) {
				$post                            = WP_Post::get_instance( $post_id );
				$message                         = apply_filters(
					'dabory_members_validate_agreements_message',
					sprintf( esc_html__( '%s에 동의 체크해 주세요', 'wskl' ), $post->post_title )
				);
				self::$validation_errors[ $key ] = $message;
			}
		}

		if ( count( self::$validation_errors ) ) {
			$validation_messages = '<ul class="validation_error_list">';
			foreach ( self::$validation_errors as $error ) {
				$validation_messages .= '<li>' . $error . '</li>';
			}
			$validation_messages .= '</ul>';

			$wpmem_themsg = $validation_messages;
		}
	}

	/**
	 * @filter  wpmem_register_form_rows
	 * @used-by init_hooks()
	 *
	 * @see     wp-members/inc/forms.php
	 * @see     wpmem_inc_registration()
	 *
	 * @param array  $rows   form rows
	 * @param string $toggle new|edit
	 *
	 * @return array
	 */
	public static function include_terms( $rows, $toggle ) {

		$terms_rows = array(
			array(
				'order' => 0,
				'field' => '<h2 class="terms-title">' . __( '다보리 약관 동의', 'wskl' ) . '</h2>',
			),
		);

		foreach ( self::$agreement_keys as $key ) {
			$field = self::get_tos_page_text( $key );
			if ( ! empty( $field ) ) {
				$terms_rows[] = array(
					'field' => $field,
				);
			}
		}

		return array_merge( $terms_rows, $rows );
	}

	private static function get_tos_page_text( $key ) {

		$post_id = intval( wskl_get_option( 'members_page_' . $key ) );

		if ( ! $post_id ) {
			return '';
		}

		$post           = WP_Post::get_instance( $post_id );
		$title          = esc_html( $post->post_title );
		$content        = wpautop( wptexturize( esc_html( $post->post_content ) ) );
		$agreement_text = __( '약관에 동의합니다.', 'wskl' );
		$checked        = ( wskl_POST( 'checkbox-' . $key ) == 'yes' ) ? 'checked' : '';

		if ( isset( self::$validation_errors[ $key ] ) ) {
			$validation_error_css_class = 'validation_error';
		} else {
			$validation_error_css_class = '';
		}

		$output = <<< PHP_EOD
<div class="tos-wrapper $validation_error_css_class">
	<h3 class="tos-title">$title</h3>
	<div class="text tos-content tos-content-$key">
		$content
	</div>
	<label for="checkbox-$key">
		<input type="checkbox" id="checkbox-$key" class="text tos-agreement" name="checkbox-$key" value="yes" $checked />
		$agreement_text
	</label>
	<span class="req">*</span>
</div>
PHP_EOD;

		return $output;
	}
}


WSKL_Dabory_Members::init();
