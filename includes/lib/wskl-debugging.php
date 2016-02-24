<?php

if ( ! wskl_debug_enabled() ) {
	return;
}


if ( wskl_is_option_enabled( 'develop_xdebug_always_on' ) ) {
	add_action( 'init', 'wskl_add_xdebug_session_start' );
}

function wskl_add_xdebug_session_start() {

	$session_value = absint( wskl_get_option( 'develop_xdebug_session_id' ) );

	if ( $session_value ) {
		setcookie( 'XDEBUG_SESSION', $session_value, time() + HOUR_IN_SECONDS );
	} else {
		setcookie( 'XDEBUG_SESSION', '', time() - DAY_IN_SECONDS );
	}
}


add_action( 'admin_notices', 'wskl_output_this_is_debug_mode' );

function wskl_output_this_is_debug_mode() {

	printf( '<div class="notice error"><p>%s</p></div>',
	        __( '알림: 다보리 플러그인이 디버그 모드에서 동작하고 있습니다!', 'wskl' ) );
}


if ( wskl_is_option_enabled( 'develop_enable_update_session_id' ) ) {
	add_action( 'init', 'wskl_update_xdebug_session_id', 5 );
}

function wskl_update_xdebug_session_id() {

	if ( isset( $_GET['XDEBUG_SESSION_START'] ) ) {
		$session_id = absint( $_GET['XDEBUG_SESSION_START'] );
		if ( $session_id ) {
			update_option( wskl_get_option_name( 'develop_xdebug_session_id' ),
			               $session_id );
		}
	}
}