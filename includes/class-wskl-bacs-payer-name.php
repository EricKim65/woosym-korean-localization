<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class WSKL_BACS_Payer_Name
 *
 * BACS 활성화 때 입금자명을 달리 지정할 수 있도록 한다.
 */
class WSKL_BACS_Payer_Name {

	static private $bacs_payer_name_in_own_column;

	public static function init() {

		self::$bacs_payer_name_in_own_column = wskl_is_option_enabled(
			'bacs_payer_name_in_own_column'
		);

		// 스크립트 처리가 필요하다면 주석 해제
		// add_action(
		// 	'wp_enqueue_scripts',
		// 	array( __CLASS__, 'enqueue_scripts' )
		// );

		/** 결제 완료 되면 입금자 이름을 포스트 메타 정보로 기록 */
		add_action(
			'woocommerce_checkout_order_processed',
			array( __CLASS__, 'add_name_to_postmeta' ),
			10,
			2
		);

		/** 결제 화면에서 입금자 이름을 받을 수 있도록 설정 */
		add_action(
			'woocommerce_checkout_after_customer_details',
			array( __CLASS__, 'output_payer_field' ),
			10,
			0
		);

		/** 관리자 초기화 */
		add_action( 'admin_init', array( __CLASS__, 'prepare_admin' ) );
	}

	public static function prepare_admin() {

		if ( ! self::is_bacs_enabled() ) {
			return;
		}

		if ( self::$bacs_payer_name_in_own_column ) {
			add_action(
				'manage_edit-shop_order_columns',
				array( __CLASS__, 'add_payer_name_column' )
			);
		}

		add_action(
			'manage_shop_order_posts_custom_column',
			array( __CLASS__, 'add_payer_name_column_details' ),
			10,
			2
		);
	}

	/**
	 * @return bool 사용 중인 결제 방법 중 BACS 가 있는지 검사.
	 *              주의: init, admin_init 훅 이후에 사용.
	 */
	public static function is_bacs_enabled() {

		foreach (
			WC()->payment_gateways()->get_available_payment_gateways(
			) as $id => $gateway
		) {
			if ( $id == 'bacs' ) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * @action  woocommerce_checkout_after_customer_details
	 *
	 * @used-by init
	 */
	public static function output_payer_field() {

		if ( ! self::is_bacs_enabled() ) {
			return;
		}

		echo '<div class="col2-set">';
		echo '<div class="col-1">&nbsp;</div><div class="col-2">';
		woocommerce_form_field(
			'bacs_payer_name',
			array(
				'type'        => 'text',
				'class'       => array( 'form-row', 'form-row-wide' ),
				'label'       => __( '입금자 이름', 'wskl' ),
				'placeholder' => __(
					'직접 은행 이체만. 계좌 이름과 입금자 이름을 별도로 할 때만',
					'wskl'
				),
				'required'    => FALSE,
			)
		);
		echo '</div></div>';
	}

	/**
	 * @action  woocommerce_checkout_order_processed
	 *
	 * @used-by init
	 *
	 * @param $order_id
	 * @param $posted
	 */
	public static function add_name_to_postmeta( $order_id, $posted ) {

		$bacs_payer_name = wskl_POST(
			'bacs_payer_name',
			'sanitize_text_field'
		);

		$is_bacs = isset( $posted['payment_method'] ) && $posted['payment_method'] == 'bacs';

		if ( $is_bacs && ! empty( $bacs_payer_name ) ) {
			update_post_meta(
				$order_id,
				wskl_get_option_name( 'bacs_payer_name' ),
				$bacs_payer_name
			);
		}
	}

	/**
	 * 스크립트 처리 콜백
	 */
	public static function enqueue_scripts() {
		//		wskl_enqueue_script(
		//			'wskl-payer-name-js',
		//			'assets/js/bacs-payer-name.js',
		//			array( 'jquery', 'wc-checkout' ),
		//			WSKL_VERSION,
		//			TRUE
		//		);
	}

	/**
	 * 우커머스 주문 목록 테이믈에 컬럼 추가.
	 *
	 * @param $columns
	 *
	 * @return array
	 */
	public static function add_payer_name_column( $columns ) {

		$pos = array_search( 'order_actions', array_keys( $columns ) );

		$before_actions = array_slice( $columns, 0, $pos );
		$after_actions  = array_slice( $columns, $pos );
		$columns        = array_merge(
			$before_actions,
			array(
				"bacs_payer_name" => __(
					'입금자명',
					'wskl'
				),
			),
			$after_actions
		);

		return $columns;
	}

	/**
	 * 특정 테이블 컬럼에 사용자 정의 자료 출력.
	 *
	 * @param $column
	 * @param $post_id
	 */
	public static function add_payer_name_column_details( $column, $post_id ) {

		if ( self::$bacs_payer_name_in_own_column ) {

			if ( $column == 'bacs_payer_name' ) {

				$payer_name = get_post_meta(
					$post_id,
					wskl_get_option_name( 'bacs_payer_name' ),
					TRUE
				);

				echo esc_html( $payer_name );
			}

		} else {

			if ( $column == 'order_title' ) {
				$payer_name = get_post_meta(
					$post_id,
					wskl_get_option_name( 'bacs_payer_name' ),
					TRUE
				);

				if ( ! empty( $payer_name ) ) {
					echo __( '입금자', 'wskl' ) . ': ' . esc_html( $payer_name );
				}
			}
		}
	}
}


WSKL_BACS_Payer_Name::init();