<?php

require_once( WSKL_PATH . '/includes/dabory-members/admin/class-wskl-dabory-members-admin-settings.php' );


class WSKL_Dabory_Members_Admin {

	const SETTING_CLASS = 'WSKL_Dabory_Members_Admin_Settings';

	public static function init() {

		add_filter(
			'wpmem_admin_tabs',
			array( __CLASS__, 'add_tab' ),
			30
		);

		add_action(
			'wpmem_admin_do_tab',
			array( __CLASS__, 'output_tabs' )
		);

		add_action(
			'admin_enqueue_scripts',
			array(
				__CLASS__,
				'enqueue_scripts',
			)
		);

		/**
		 * 세팅 페이지 form submission handling
		 */
		add_action(
			'load-settings_page_wpmem-settings',
			array( self::SETTING_CLASS, 'update_dabory_members', )
		);
	}

	/**
	 * 탭 추가
	 *
	 * @filter  wpmem_admin_tabs
	 * @used-by init()
	 * @see     wp-members/admin/admin.php
	 * @see     wpmem_admin_tabs()
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public static function add_tab( array $tabs ) {

		$tabs['dabory-members'] = __( '다보리 멤버스', 'wskl' );

		return $tabs;
	}

	/**
	 * 탭 내부의 내용 추가
	 *
	 * @filter  wpmem_admin_do_tab
	 * @used-by init()
	 * @see     wp-members/admin/admin.php
	 * @see     wpmem_admin()
	 *
	 * @param $tab
	 */
	public static function output_tabs( $tab ) {

		switch ( $tab ) {
			case 'dabory-members':
				wskl_get_template( '/dabory-members-tab.php' );
				break;
		}
	}

	/**
	 * 스크립트 추가 콜백
	 *
	 * @action  admin_enqueue_scripts
	 * @used-by init()
	 */
	public static function enqueue_scripts() {

		$screen = get_current_screen();

		if ( $screen->id == 'settings_page_wpmem-settings' &&
		     wskl_GET( 'tab' ) == 'dabory-members'
		) {

			wskl_enqueue_script(
				'dabory-members-js',
				'assets/js/dabory-members-admin.js',
				array( 'jquery' ),
				WSKL_VERSION,
				TRUE,
				'daboryMembers',
				array(
					'editUrl' => admin_url( 'post.php' ),
				)
			);

			wp_enqueue_style(
				'dabory-members-css',
				plugin_dir_url(
					WSKL_MAIN_FILE
				) . 'assets/css/dabory-members-admin.css'
			);
		}
	}
}


WSKL_Dabory_Members_Admin::init();