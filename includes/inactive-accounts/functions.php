<?php

/**
 * 마지막 로그인 시각 조회
 *
 * @param int $user_id
 *
 * @return int
 */
function wskl_get_last_login( $user_id ) {

	return intval( get_user_meta( $user_id, wskl_get_option_name( 'last_login' ), TRUE ) );
}

/**
 * @param int      $user_id
 * @param bool|int $timestamp
 */
function wskl_set_user_last_login( $user_id, $timestamp = FALSE ) {

	if ( FALSE === $timestamp ) {
		$timestamp = time();
	}

	update_user_meta( $user_id, wskl_get_option_name( 'last_login' ), $timestamp );
}

/**
 * @param int $user_id
 *
 * @return int
 */
function wskl_get_user_alerted( $user_id ) {

	return intval( get_user_meta( $user_id, wskl_get_option_name( 'inactive-accounts_alerted' ), TRUE ) );
}

/**
 * @param int      $user_id
 * @param bool|int $timestamp
 */
function wskl_set_user_alerted( $user_id, $timestamp = FALSE ) {

	if ( FALSE === $timestamp ) {
		$timestamp = time();
	}

	update_user_meta( $user_id, wskl_get_option_name( 'inactive-accounts_alerted' ), $timestamp );
}

/**
 * @param int $user_id
 */
function wskl_delete_user_alerted( $user_id ) {

	delete_user_meta( $user_id, wskl_get_option_name( 'inactive-accounts_alerted' ) );
}

/**
 * @param int $user_id
 *
 * @return int
 */
function wskl_get_user_deactivated( $user_id ) {

	return intval( get_user_meta( $user_id, wskl_get_option_name( 'inactive-accounts_deactivated' ), TRUE ) );
}

/**
 * @param int      $user_id
 * @param bool|int $timestamp
 */
function wskl_set_user_deactivated( $user_id, $timestamp = FALSE ) {

	if ( FALSE === $timestamp ) {
		$timestamp = time();
	}

	update_user_meta( $user_id, wskl_get_option_name( 'inactive-accounts_deactivated' ), $timestamp );
}

/**
 * @param int $user_id
 */
function wskl_delete_user_deactivated( $user_id ) {

	delete_user_meta( $user_id, wskl_get_option_name( 'inactive-accounts_deactivated' ) );
}

/**
 * @param bool $linebreak 일자와 시간 사이에 강제 줄바꿈을 하려면 true
 *
 * @return string
 */
function wskl_get_datetime_format( $linebreak = FALSE ) {

	return get_option( 'date_format' ) . ( $linebreak ? '\\<\\b\\r\\/\\>' : ' ' ) . get_option( 'time_format' );
}

/**
 *
 * date_i18n 함수의 포맷팅 능력은 좋은데, $gmt 파라미터에 따라 쓰이는 date() 함수는 date_default_timezone_set() 를 쓴다.
 * 그냥 GMT 로 파라미터를 보내고, 그만큼의 offset 값을 가감하는 식으로 구현됨.
 *
 * @param string   $format
 * @param bool|int $timestamp 현재 시간인 경우 false
 *
 * @return string
 */
function wskl_localised_date( $format, $timestamp = FALSE ) {

	if ( ! $timestamp ) {
		$timestamp = current_time( 'timestamp', TRUE );
	}

	return date_i18n( $format, $timestamp + wskl_get_gmt_offset(), TRUE );
}

/**
 * @param bool $timestamp
 *
 * @see wskl_localised_date()
 *
 * @return string
 */
function wskl_date_string( $timestamp = FALSE ) {

	return wskl_localised_date( get_option( 'date_format' ), $timestamp );
}

/**
 * @param bool $timestamp
 *
 * @see wskl_localised_date()
 *
 * @return string
 */
function wskl_time_string( $timestamp = FALSE ) {

	return wskl_localised_date( get_option( 'time_format' ), $timestamp );
}

/**
 * @param bool $timestamp
 * @param bool $linebreak
 *
 * @see wskl_localised_date()
 *
 * @return string
 */
function wskl_datetime_string( $timestamp = FALSE, $linebreak = FALSE ) {

	return wskl_localised_date( wskl_get_datetime_format( $linebreak ), $timestamp );
}

/**
 * @see wp_timezone_override_offset()
 *
 * @return int
 */
function wskl_get_gmt_offset() {

	return get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
}

/**
 * 해당 시각을 받아서 그 날짜의 자정 시각을 돌려준다. 반환은 unix timestamp 로 한다.
 *
 * @param string $time
 * @param bool   $gmt
 *
 * @return int
 */
function wskl_get_midnight_timestamp( $time = 'now', $gmt = FALSE ) {

	if ( $gmt ) {
		$timezone_string = 'UTC';
	} else {
		$timezone_string = get_option( 'timezone_string' ) ? get_option( 'timezone_string' ) : 'UTC';
	}

	$today = new DateTime( $time, new DateTImeZone( $timezone_string ) );

	return $today->setTime( 0, 0, 0 )->getTimestamp();
}

/**
 * 휴면 처리할 회원을 필터.
 * - $timestamp <= $maximum_timestamp
 * - 휴면 처리를 통지 받은 회원.
 *
 * @param int    $maximum_timestamp 휴면 처리 기준 timestamp
 * @param string $role              휴먼 처리 대상 유저 역할
 *
 * @return array WP_User 객체의 array
 */
function wskl_get_deactivation_staged_users( $maximum_timestamp, $role ) {

	$query = new WP_User_Query(
		array(
			'role'       => $role,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => wskl_get_option_name( 'last_login' ),
					'value'   => $maximum_timestamp,
					'type'    => 'NUMERIC',
					'compare' => '<=',
				),
				array(
					'key'     => wskl_get_option_name( 'inactive-accounts_alerted' ),
					'value'   => 0,
					'type'    => 'NUMERIC',
					'compare' => '>',
				),
			),
		)
	);

	return $query->get_results();
}

/**
 * 휴면 처리 전, 통지해야 할 회원의 목록
 * - $min_timestamp <= timestamp < $max_timestamp
 * - 이전에 통지 받지 않은 회원.
 *
 * @param int    $min_timestamp 통지 기준 최소 timestamp
 * @param int    $max_timestamp 통지 기준 최대 timestamp
 * @param string $role
 *
 * @return array WP_User 객체의 array
 */
function wskl_get_alert_staged_users( $min_timestamp, $max_timestamp, $role ) {

	$key_name = wskl_get_option_name( 'last_login' );

	$query = new WP_User_Query(
		array(
			'role'       => $role,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => $key_name,
					'value'   => $max_timestamp,
					'type'    => 'NUMERIC',
					'compare' => '<',
				),
				array(
					'key'     => $key_name,
					'value'   => $min_timestamp,
					'type'    => 'NUMERIC',
					'compare' => '>=',
				),
				array(
					'key'     => wskl_get_option_name( 'inactive-accounts_alerted' ),
					'value'   => 0,
					'type'    => 'NUMERIC',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

	return $query->get_results();
}

function wskl_deactivate_account( WP_User $user, $timestamp, array $meta_keys_preserve, $role_to_dismiss ) {

	/** @var wpdb $wpdb */
	global $wpdb;

	// wipe out all user metadata.
	$query = "DELETE FROM `{$wpdb->usermeta}` WHERE `user_id` = '%d' ";

	if ( count( $meta_keys_preserve ) ) {
		$quoted = implode(
			',',
			array_map(
				function ( $key ) {

					return str_pad( $key, strlen( $key + 2 ), '\'', STR_PAD_BOTH );
				},
				$meta_keys_preserve
			)
		);
		$query .= $wpdb->prepare( 'AND `meta_key` NOT IN (%s)', $quoted );
	}

	$prepared_query = $wpdb->prepare( $query, $user->ID );
	$wpdb->query( $prepared_query );

	// create random password, and replace an existing one.
	wp_set_password( wp_generate_password( 22, TRUE, TRUE ), $user->ID );

	// update user's role as deactivated
	$user->remove_role( $role_to_dismiss );
	$user->add_role( 'deactivated' );

	wskl_set_user_deactivated( $user->ID, $timestamp );
}
