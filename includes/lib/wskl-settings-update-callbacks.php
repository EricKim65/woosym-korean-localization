<?php

/**
 * 다보리 멤버스 업데이트 옵션. 플러그인 활성화가 아니라, 이 옵션이 활성화가 될 때
 * "탈퇴 회원" 유저 역할을 업데이트 해야 한다.
 *
 * @callback
 * @action    'update_option_{$options}'
 * @used-by   Woosym_Korean_Localization_Settings::init_update_callbacks()
 *
 * @param $old_value
 * @param $value
 * @param $option
 */
function callback_enable_dabory_members(
	/** @noinspection PhpUnusedParameterInspection */
	$old_value,
	$value,
	/** @noinspection PhpUnusedParameterInspection */
	$option
) {

	$enabled = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	if ( $enabled ) {
		if ( get_role( 'wskl_withdrawn' ) === NULL ) {
			add_role( 'wskl_withdrawn', __( '탈퇴 회원', 'wskl' ) );
		}
	} else {
		remove_role( 'wskl_withdrawn' );
	}
}

function callback_enable_inactive_accounts(
	/** @noinspection PhpUnusedParameterInspection */
	$old_value,
	$value,
	/** @noinspection PhpUnusedParameterInspection */
	$option
) {

	$enabled = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	if ( $enabled ) {
		if ( get_role( 'wskl_deactivated' ) === NULL ) {
			add_role( 'wskl_deactivated', __( '휴면 회원', 'wskl' ) );
		}
	} else {
		remove_role( 'wskl_deactivated' );
	}
}