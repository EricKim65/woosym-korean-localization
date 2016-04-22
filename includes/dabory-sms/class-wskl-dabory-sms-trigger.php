<?php

require_once( WSKL_PATH . '/includes/dabory-sms/admin/class-wskl-dabory-sms-settings.php' );
require_once( WSKL_PATH . '/includes/dabory-sms/providers/class-wskl-dabory-sms-provider-loading.php' );

use WSKL_Dabory_SMS_Provider_Loading as Provider_Loading;


/**
 * Class WSKL_Dabory_SMS_Trigger
 *
 * 각종 우커머스 이벤트에 맞춰 SMS 전송.
 *
 * @since 3.3.0
 */
class WSKL_Dabory_SMS_Trigger {

	const SMS_MAX_BYTES = 90;

	public static function init() {

		/** @see WSKL_Dabory_SMS::init() */
		do_action( 'dabory_sms_load_provider_module' );

		$instance = new static();

		if ( wskl_is_option_enabled( 'sms_new-order_enabled' ) ) {

			$instance->init_new_order_hooks();
		}

		if ( wskl_is_option_enabled( 'sms_cancelled-order_enabled' ) ) {

			$instance->init_cancelled_order_hooks();
		}

		if ( wskl_is_option_enabled( 'sms_failed-order_enabled' ) ) {

			$instance->init_failed_order_hooks();
		}

		if ( wskl_is_option_enabled( 'sms_processing-order_enabled' ) ) {

			$instance->init_processing_order_hooks();
		}

		if ( wskl_is_option_enabled( 'sms_completed-order_enabled' ) ) {

			$instance->init_completed_order_hooks();
		}

		if ( wskl_is_option_enabled( 'sms_refunded-order_enabled' ) ) {

			$instance->init_refunded_order_hooks();
		}

		if ( wskl_is_option_enabled( 'sms_customer-note_enabled' ) ) {

			$instance->init_customer_note_hooks();
		}

		if ( wskl_is_option_enabled( 'sms_customer-new-account_enabled' ) ) {

			$instance->init_customer_new_account_hooks();
		}

		if ( wskl_is_option_enabled( 'sms_customer-reset-password_enabled' ) ) {

			$instance->init_customer_reset_password_hooks();
		}

		if ( wskl_is_option_enabled( 'sms_payment-bacs_enabled' ) ) {

			$instance->init_payment_bacs_hooks();
		}

		return $instance;
	}

	/**
	 * @used-by WSKL_Dabory_SMS_Trigger::init()
	 */
	private function init_new_order_hooks() {

		$callback = array( $this, 'trigger_new_order' );

		add_action( 'woocommerce_order_status_pending_to_processing', $callback );
		add_action( 'woocommerce_order_status_pending_to_completed', $callback );
		add_action( 'woocommerce_order_status_pending_to_on-hold', $callback );
		add_action( 'woocommerce_order_status_failed_to_processing', $callback );
		add_action( 'woocommerce_order_status_failed_to_completed', $callback );
		add_action( 'woocommerce_order_status_failed_to_on-hold', $callback );
	}

	/**
	 * @used-by WSKL_Dabory_SMS_Trigger::init()
	 */
	private function init_cancelled_order_hooks() {

		$callback = array( $this, 'trigger_cancelled_order' );

		add_action( 'woocommerce_order_status_pending_to_cancelled', $callback );
		add_action( 'woocommerce_order_status_on-hold_to_cancelled', $callback );
	}

	/**
	 * @used-by WSKL_Dabory_SMS_Trigger::init()
	 */
	private function init_failed_order_hooks() {

		$callback = array( $this, 'trigger_failed_order' );

		add_action( 'woocommerce_order_status_pending_to_failed', $callback );
		add_action( 'woocommerce_order_status_on-hold_to_failed', $callback );
	}

	/**
	 * @used-by WSKL_Dabory_SMS_Trigger::init()
	 */
	private function init_processing_order_hooks() {

		$callback = array( $this, 'trigger_processing_order' );

		add_action( 'woocommerce_order_status_pending_to_processing', $callback );
		add_action( 'woocommerce_order_status_pending_to_on-hold', $callback );
	}

	/**
	 * @used-by WSKL_Dabory_SMS_Trigger::init()
	 */
	private function init_completed_order_hooks() {

		$callback = array( $this, 'trigger_completed_order' );

		add_action( 'woocommerce_order_status_completed', $callback );
	}

	/**
	 * @used-by WSKL_Dabory_SMS_Trigger::init()
	 */
	private function init_refunded_order_hooks() {

		add_action( 'woocommerce_order_fully_refunded', array( $this, 'trigger_fully_refunded_order' ), 10, 2 );
		add_action( 'woocommerce_order_partially_refunded', array( $this, 'trigger_partially_refunded_order' ), 10, 2 );
	}

	/**
	 * @used-by WSKL_Dabory_SMS_Trigger::init()
	 */
	private function init_customer_note_hooks() {

		$callback = array( $this, 'trigger_customer_note' );

		/**
		 * @see woocommerce/includes/abstracts/abstract-wc-order.php
		 * @see WC_Abstract_Order::add_order_note()
		 */
		add_action( 'woocommerce_new_customer_note', $callback );
	}

	/**
	 * @used-by WSKL_Dabory_SMS_Trigger::init()
	 */
	private function init_customer_new_account_hooks() {

		$callback = array( $this, 'trigger_customer_new_account' );

		add_action( 'wpmem_post_register_data', $callback );
	}

	/**
	 * @used-by WSKL_Dabory_SMS_Trigger::init()
	 */
	private function init_customer_reset_password_hooks() {

		$callback = array( $this, 'trigger_customer_reset_password' );

		add_action( 'wpmem_pwd_reset', $callback );
	}

	/**
	 * @used-by WSKL_Dabory_SMS_Trigger::init()
	 */
	private function init_payment_bacs_hooks() {

		$callback = array( $this, 'trigger_payment_bacs' );

		add_action( 'woocommerce_checkout_order_processed', $callback, 10, 1 );
	}

	private static function trim_phone_number( $phone_number ) {

		return preg_replace( '/[^0-9]/', '', $phone_number );
	}

	private static function not_empty( $phone_number ) {

		$pn = trim( $phone_number );

		return ! empty( $pn );
	}

	/**
	 * @callback
	 * @action    woocommerce_order_status_pending_to_processing
	 * @action    woocommerce_order_status_pending_to_completed
	 * @action    woocommerce_order_status_pending_to_on-hold
	 * @action    woocommerce_order_status_failed_to_processing
	 * @action    woocommerce_order_status_failed_to_completed
	 * @action    woocommerce_order_status_failed_to_on
	 *
	 * @param int $order_id
	 */
	public function trigger_new_order( $order_id ) {

		$phase = 'new-order';

		if ( ! $this->trigger_common( $order_id, NULL, $phase ) ) {
			$this->log( "'Trigger for '{$phase}' finished unsuccessfully.", __METHOD__ );
		}
	}

	/**
	 * 시나리오에 따라 문자열을 보낸다.
	 * 어떤 우커머스 이벤트가 있든지, 해당 이벤트에 대한 핸들링은 적절한 메시지를 보내는 것으로 귀결된다.
	 * 주의. $order_id 와 $user_id 가 동시에 NULL 이 될 수 없다.
	 *
	 * @param int|NULL $order_id 주문에 대한 문자열이면 주문 ID. 주문과 관련되 문자가 아니면 NULL.
	 * @param int|NULL $user_id  사용자와 관련된 문자열이면 사용자 ID. 사용자와 관련 없으면 NULL.
	 * @param string   $scenario 시나리오. 각 섹션의 ID. e.g., new-order, customer-new-account, ...
	 *
	 * @uses  WSKL_SMS_Text_Substitution
	 * @uses  WSKL_Dabory_SMS_Provider_Loading
	 *
	 * @uses  WSKL_Dabory_SMS_Trigger::get_setting_value()
	 * @uses  WSKL_Dabory_SMS_Trigger::get_settings()
	 * @uses  WSKL_Dabory_SMS_Trigger::is_already_sent()
	 * @uses  WSKL_Dabory_SMS_Trigger::log()
	 * @uses  WSKL_Dabory_SMS_Trigger::set_sending_result()
	 *
	 * @return bool
	 */
	private function trigger_common( $order_id, $user_id, $scenario ) {

		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				$this->log( '$order is invalid. Triggering halted.', __METHOD__ );

				return FALSE;
			}
		} else {
			$order = NULL;
		}

		if ( $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( ! $user ) {
				$this->log( "User ID $user_id is invalid.", __METHOD__ );

				return FALSE;
			}

		} else {
			$user = NULL;
		}

		if ( ! $order && ! $user_id ) {
			$this->log( 'Either $order_id, or $user_id must be valid.', __METHOD__ );

			return FALSE;
		}

		$emulation = wskl_is_option_enabled( 'develop_emulate_sms' );

		// check if a message is already sent (order only)
		if ( ! $emulation && $order && $this->is_already_sent( $order, $scenario ) ) {
			$this->log( "Order #$order_id has been notified. Triggering halted.", __METHOD__ );

			return FALSE;
		} else if ( ! $emulation && $user && $this->is_already_sent( $user, $scenario ) ) {
			$this->log( "User #{$user_id} ({$user->user_email}) has been notified. Triggering halted.", __METHOD__ );

			return FALSE;
		}

		// receiver meta key
		$gs = self::get_settings();

		// retrieve the other fields
		$ns                 = self::get_settings( $scenario );
		$message_template   = self::get_setting_value( $ns, $scenario, 'message_content' );
		$title_template     = self::get_setting_value( $ns, $scenario, 'message_title' );
		$notify_to_managers = self::get_setting_value( $ns, $scenario, 'send_to_managers' );

		if ( $order ) {

			$receiver_phone_meta_key = wskl_get_from_assoc( $gs, wskl_get_option_name( 'sms_receiver_meta_field' ) );
			if ( empty( $receiver_phone_meta_key ) ) {

				$this->log( '$receiver_phone_meta_key is an empty string. Triggering halted.', __METHOD__ );

				return FALSE;
			}

			$customer_phone = $order->{$receiver_phone_meta_key};

		} else {

			$receiver_phone_meta_key = self::get_setting_value( $ns, $scenario, 'phone_meta_field' );

			if ( empty( $receiver_phone_meta_key ) ) {

				$this->log( '$receiver_phone_meta_key is an empty string. Triggering halted.', __METHOD__ );

				return FALSE;
			}

			$customer_phone = $user->{$receiver_phone_meta_key};
		}

		if ( empty( $customer_phone ) ) {

			$this->log( 'customer phone is empty! Triggering halted.', __METHOD__ );

			return FALSE;
		}

		// receivers
		if ( $notify_to_managers == 'yes' ) {
			$r   = explode( "\n", wskl_get_option( 'sms_shop_manager_phones' ) );
			$r[] = $customer_phone;

			$r = array_map( array( __CLASS__, 'trim_phone_number' ), $r );
			$r = array_filter( $r, array( __CLASS__, 'not_empty' ) );
			$r = array_unique( $r );

			$receivers     = implode( ',', $r );
			$num_receivers = count( $receivers );
		} else {
			$receivers     = $customer_phone;
			$num_receivers = 1;
		}

		if ( ! $num_receivers ) {
			$this->log( 'The number of recipient is 0! Triggering halted.', __METHOD__ );

			return FALSE;
		}

		// message substitution
		$sub = new WSKL_SMS_Text_Substitution();
		$sub->init_substitute( $order, $user );

		$message_title   = $sub->substitute( $title_template );
		$message_content = $sub->substitute( $message_template );
		$str_bytes       = strlen( $message_content );

		// message type
		if ( $str_bytes > self::SMS_MAX_BYTES ) {
			$message_type = 'lms';
		} else {
			$message_type = 'sms';
		}

		$args = array(
			'remote_phone'   => $receivers,           // 수신자 목록
			'remote_msg'     => $message_content,     // 본문
			'remote_subject' => $message_title,       // 제목
			'remote_num'     => $num_receivers,       // 전송 개수 (옵션)
			'remote_etc1'    => $order_id,
		);

		if ( ! $emulation ) {

			// ... and fire.
			/** @var WSKL_Dabory_SMS_Provider $provider_class */
			$provider_class = Provider_Loading::get_provider_class();
			$sender         = $provider_class::factory();
			$response       = $sender->send_message( $args, $message_type );

			assert(
				$response[3] == $order_id,
				__FUNCTION__ . ': Order id of etc1 and $order_id are different. This is impossible.'
			);

		} else {

			$message = 'SMS emulation is enabled. Argument: ' . print_r( $args, TRUE );
			error_log( $message );
		}

		if ( ! $emulation && $order ) {
			self::set_sending_result( $order, $scenario, $message_type );
		} else if ( ! $emulation && $user ) {
			self::set_sending_result( $user, $scenario, $message_type );
		}

		return TRUE;
	}

	private static function log( $message, $func ) {

		error_log( $func . ': ' . $message );
	}

	private static function is_already_sent( $object, $scenario ) {

		if ( $object instanceof WC_Order ) {
			$value = (array) get_post_meta( $object->id, 'dabory_sms_' . $scenario, TRUE );
		} else if ( $object instanceof WP_User ) {
			$value = (array) get_user_meta( $object->ID, 'dabory_sms_' . $scenario, TRUE );
		} else {
			throw new LogicException( '$object is not an WC_Order and not an WP_User.' );
		}

		return isset( $value['message_time'] ) && $value['message_time'] > 0;
	}

	private static function get_settings( $current_setting = '' ) {

		$settings = WSKL_Dabory_SMS_Settings::static_get_settings( $current_setting );
		$output   = array();

		foreach ( $settings as $setting ) {

			$id   = wskl_get_from_assoc( $setting, 'id' );
			$type = wskl_get_from_assoc( $setting, 'type' );

			if ( ! empty( $id ) && $type != 'title' && $type != 'sectionend' ) {

				$value = get_option( $id, NULL );
				if ( is_null( $value ) ) {
					$value = wskl_get_from_assoc( $setting, 'default' );
				}
				$output[ $id ] = $value;
			}
		}

		return $output;
	}

	private static function get_setting_value( array &$assoc, $scenario, $what ) {

		return wskl_get_from_assoc( $assoc, wskl_get_option_name( "sms_{$scenario}_{$what}" ) );
	}

	private static function set_sending_result( $object, $scenario, $message_type, $message_time = FALSE ) {

		$value = array(
			'message_type' => $message_type,
			'message_time' => ! $message_time ? time() : $message_time,
		);

		if ( $object instanceof WC_Order ) {
			update_post_meta( $object->id, 'dabory_sms_' . $scenario, $value );
		} else if ( $object instanceof WP_User ) {
			update_user_meta( $object->ID, 'dabory_sms_' . $scenario, $value );
		} else {
			throw new LogicException( '$object is not an WC_Order and not an WP_User.' );
		}
	}

	/**
	 * @callback
	 * @action   woocommerce_order_status_pending_to_cancelled
	 * @action   woocommerce_order_status_on-hold_to_cancelled
	 *
	 * @param int $order_id
	 */
	public function trigger_cancelled_order( $order_id ) {

		$phase = 'cancelled-order';

		if ( ! $this->trigger_common( $order_id, NULL, $phase ) ) {
			$this->log( "'Trigger for '{$phase}' finished unsuccessfully.", __METHOD__ );
		}
	}

	/**
	 * @callback
	 * @action   woocommerce_order_status_pending_to_failed
	 * @action   woocommerce_order_status_on-hold_to_failed
	 *
	 * @param int $order_id
	 */
	public function trigger_failed_order( $order_id ) {

		$phase = 'failed-order';

		if ( ! $this->trigger_common( $order_id, NULL, $phase ) ) {
			$this->log( "'Trigger for '{$phase}' finished unsuccessfully.", __METHOD__ );
		}
	}

	/**
	 * @callback
	 * @action   woocommerce_order_status_pending_to_processing
	 * @action   woocommerce_order_status_pending_to_on-hold
	 *
	 * @param int $order_id
	 */
	public function trigger_processing_order( $order_id ) {

		$phase = 'processing-order';

		if ( ! $this->trigger_common( $order_id, NULL, $phase ) ) {
			$this->log( "'Trigger for '{$phase}' finished unsuccessfully.", __METHOD__ );
		}
	}

	/**
	 * @callback
	 * @action   woocommerce_order_status_completed
	 *
	 * @param int $order_id
	 */
	public function trigger_completed_order( $order_id ) {

		$phase = 'completed-order';

		if ( ! $this->trigger_common( $order_id, NULL, $phase ) ) {
			$this->log( "'Trigger for '{$phase}' finished unsuccessfully.", __METHOD__ );
		}
	}

	/**
	 * @callback
	 * @action   woocommerce_order_fully_refunded
	 *
	 * @param int $order_id
	 */
	public function trigger_fully_refunded_order( $order_id ) {

		$phase = 'refunded-order';

		if ( ! $this->trigger_common( $order_id, NULL, $phase ) ) {
			$this->log( "'Trigger for '{$phase}' finished unsuccessfully.", __METHOD__ );
		}
	}

	/**
	 * @callback
	 * @action   woocommerce_order_partially_refunded
	 *
	 * @param int $order_id
	 */
	public function trigger_partially_refunded_order( $order_id ) {

		$phase = 'refunded-order';

		if ( ! $this->trigger_common( $order_id, NULL, $phase ) ) {
			$this->log( "'Trigger for '{$phase}' finished unsuccessfully.", __METHOD__ );
		}
	}

	/**
	 * @callback
	 * @action    woocommerce_new_customer_note
	 * @used-by   WSKL_Dabory_SMS_Trigger::init_customer_note_hooks()
	 *
	 * @param array $args
	 *               - order_id
	 *               - customer_note
	 */
	public function trigger_customer_note( array $args ) {

		$phase = 'customer-note';

		if ( ! $this->trigger_common( $args['order_id'], NULL, $phase ) ) {
			$this->log( "'Trigger for '{$phase}' finished unsuccessfully.", __METHOD__ );
		}
	}

	/**
	 * @callback user_register
	 * @action   wpmem_post_register_data
	 *
	 * @param array $fields
	 *               - ID: user's ID
	 *               - display_name
	 *               - nickname
	 *               - user_email
	 *               - user_nicename
	 *               - username
	 *               - password
	 *               - user_registered
	 *               - user_role
	 *               - wpmem_reg_ip
	 *               - wpmem_reg_url
	 */
	public function trigger_customer_new_account( $fields ) {

		$phase = 'customer-new-account';

		$default_roles = apply_filters( 'dabory_sms_customer_new_account_user_role', array( 'customer' ) );
		$user_role     = wskl_get_from_assoc( $fields, 'user_role' );

		if ( ! in_array( $user_role, $default_roles ) ) {

			$user_id  = wskl_get_from_assoc( $fields, 'ID' );
			$username = wskl_get_from_assoc( $fields, 'username' );

			$this->log(
				"User role of {$username}(#{$user_id}) is {$user_role}. The role is not listed to send a message.",
				__METHOD__
			);

			return;
		}

		if ( ! $this->trigger_common( NULL, $fields['ID'], $phase ) ) {
			$this->log( "'Trigger for '{$phase}' finished unsuccessfully.", __METHOD__ );
		}
	}

	/**
	 * @callback wpmem_pwd_reset
	 * @action   wpmem_post_register_data
	 *
	 * @param int $user_id
	 */
	public function trigger_customer_reset_password( $user_id /* , $new_pass */ ) {

		$phase = 'customer-reset-password';

		if ( ! $this->trigger_common( NULL, $user_id, $phase ) ) {
			$this->log( "'Trigger for '{$phase}' finished unsuccessfully.", __METHOD__ );
		}
	}

	/**
	 * @callback
	 * @action    woocommerce_checkout_order_processed
	 *
	 * @param int $order_id
	 * /param array $posted
	 */
	public function trigger_payment_bacs( $order_id /*, $posted */ ) {

		$phase = 'payment-bacs';

		$order          = wc_get_order( $order_id );
		$payment_method = $order->payment_method;

		if ( $payment_method != 'bacs' ) {
			return;
		}

		if ( ! $this->trigger_common( $order_id, NULL, $phase ) ) {
			$this->log( "'Trigger for '{$phase}' finished unsuccessfully.", __METHOD__ );
		}
	}
}