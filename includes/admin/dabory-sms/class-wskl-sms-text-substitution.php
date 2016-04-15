<?php


class WSKL_SMS_Text_Substitution {

	private $order_magic_texts = array();

	private $user_magic_texts = array();

	private $find = array();

	private $replace = array();

	public function __construct() {

		$blog_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$this->order_magic_texts = array(
			'blog-name'     => array(
				'find'    => '{blog_name}',
				'replace' => $blog_name,
				'desc'    => __( '상점 이름.', 'wskl' ) . ' ' . esc_html( $blog_name ),
			),
			'order-content' => array(
				'find'    => '{order_content}',
				'replace' => '',
				'desc'    => __( '주문 내역. \'상품명\', 혹은 \'상품명 외 N건\'으로 표시됩니다.', 'wskl' ),
			),
			'order-date'    => array(
				'find'    => '{order_date}',
				'replace' => '',
				'desc'    => __( '주문 일시', 'wskl' ),
			),
			'order-id'      => array(
				'find'    => '{order_id}',
				'replace' => '',
				'desc'    => __( '주문 ID', 'wskl' ),
			),
			'order-number'  => array(
				'find'    => '{order_number}',
				'replace' => '',
				'desc'    => __( '주문 번호', 'wskl' ),
			),
			'order-total'   => array(
				'find'    => '{order_total}',
				'replace' => '',
				'desc'    => __( '주문 총액', 'wskl' ),
			),
			'order-status'  => array(
				'find'    => '{order_status}',
				'replace' => '',
				'desc'    => __( '주문 상태', 'wskl' ),
			),
			'site-title'    => array(
				'find'    => '{site_title}',
				'replace' => $blog_name,
				'desc'    => __( '상점 이름.', 'wskl' ) . ' ' . esc_html( $blog_name ),
			),
			'order:custom'  => array(
				'find'    => '{order:&lt;meta_key&gt;}',
				'replace' => '',
				'desc'    => __( '주문의 메타 키 값.', 'wskl' ),
			),
		);

		$this->user_magic_texts = array(
			'user-email'  => array(
				'find'    => '{user_email}',
				'replace' => '',
				'desc'    => __( '유저 이메일', 'wskl' ),
			),
			'user-id'     => array(
				'find'    => '{user_id}',
				'replace' => '',
				'desc'    => __( '유저 ID (숫자)', 'wskl' ),
			),
			'user-login'  => array(
				'find'    => '{user_login}',
				'replace' => '',
				'desc'    => __( '로그인 아이디', 'wskl' ),
			),
			'user:custom' => array(
				'find'    => '{user:&lt;meta_key&gt;}',
				'replace' => '',
				'desc'    => __( '사용자의 메타 키 값.', 'wskl' ),
			),
		);
	}

	public function get_order_magic_texts() {

		return $this->order_magic_texts;
	}

	public function get_user_magic_texts() {

		return $this->user_magic_texts;
	}
}


