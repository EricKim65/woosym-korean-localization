<?php


/**
 * TinyMCE 4 만을 지원합니다.
 *
 * Class WSKL_Inactive_Accounts_TinyMCE_Buttons
 */
class WSKL_Inactive_Accounts_TinyMCE_Buttons {

	public static function init() {

		add_action( 'load-post-new.php', array( __CLASS__, 'load_tinymce' ) );
		add_action( 'load-post.php', array( __CLASS__, 'load_tinymce' ) );
	}

	public static function load_tinymce() {

		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			add_filter( 'mce_external_plugins', array( __CLASS__, 'add_plugin' ), 12 );
			add_filter( 'mce_buttons', array( __CLASS__, 'register_buttons' ), 12 );
		}
	}

	public static function add_plugin( $plugin_array ) {

		global $post;

		$post_alert        = wskl_get_option( 'inactive-accounts_post_alert' );
		$post_deactivation = wskl_get_option( 'inactive-accounts_post_deactivation' );

		if ( $post->ID != $post_alert && $post->ID != $post_deactivation ) {
			return $plugin_array;
		}

		// WP version 3.9 updated to tinymce 4.0
		if ( version_compare( get_bloginfo( 'version' ), '3.9', '>=' ) ) {
			$plugin_array['inactive_accounts_shortcodes'] = plugin_dir_url( WSKL_MAIN_FILE )
			                                                . 'assets/js/inactive-accounts/shortcodes-tinymce-4.js?ver='
			                                                . WSKL_VERSION;
		} else {

		}

		return $plugin_array;
	}

	public static function register_buttons( $buttons ) {

		global $post;

		$post_alert        = wskl_get_option( 'inactive-accounts_post_alert' );
		$post_deactivation = wskl_get_option( 'inactive-accounts_post_deactivation' );

		if ( $post->ID != $post_alert && $post->ID != $post_deactivation ) {
			return $buttons;
		}

		array_push( $buttons, 'inactive_accounts_shortcodes_button' );

		return $buttons;
	}
}


WSKL_Inactive_Accounts_TinyMCE_Buttons::init();
