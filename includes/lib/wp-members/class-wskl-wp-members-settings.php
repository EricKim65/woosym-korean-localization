<?php

require_once( 'template-functions.php' );


if ( ! class_exists( 'WSKL_WP_Members_Settings' ) ) :

	class WSKL_WP_Members_Settings {

		public $id = 'custom_tab';

		public $tab_priority = 20;

		public $template = 'base-template.php';

		public $nonce_action = '_wpnonce';

		public $nonce_param = 'wskl_custom_nonce';

		public $action = 'update_custom';

		public $fields;

		public function __construct() {

			$this->fields = $this->get_fields();

			add_filter( 'wpmem_admin_tabs', array( $this, 'add_tab' ), $this->tab_priority );

			add_action( 'wpmem_admin_do_tab', array( $this, 'output_tab' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			/**
			 * @see    wp-admin/admin.php
			 * @action 'load-' . $page_hook
			 */
			add_action( 'load-settings_page_wpmem-settings', array( $this, 'update_settings' ) );

			add_action( 'wskl_wp_members_section_footer_new_page', array( $this, 'section_footer_new_page' ), 10, 0 );
		}

		public function enqueue_scripts() {

			if ( ! wp_script_is( 'dabory-members' ) ) {

				wskl_enqueue_script(
					'dabory-members',
					'assets/js/dabory-members/admin.js',
					array( 'jquery' ),
					WSKL_VERSION,
					TRUE,
					'daboryMembers',
					array(
						'editUrl' => admin_url( 'post.php' ),
					)
				);
			}

			if ( ! wp_style_is( 'dabory-members' ) ) {

				wp_enqueue_style(
					'dabory-members',
					plugin_dir_url(
						WSKL_MAIN_FILE
					) . 'assets/css/dabory-members/admin.css'
				);
			}
		}

		public function add_tab( $tabs ) {

			$tabs[ $this->id ] = $this->fields['tab_name'];

			return $tabs;
		}

		public function output_tab( $tab ) {

			if ( $tab == $this->id ) {

				/** @noinspection PhpUnusedLocalVariableInspection */
				$settings = &$this->fields;

				/** @noinspection PhpUnusedLocalVariableInspection */
				$nonce_action = $this->nonce_action;

				/** @noinspection PhpUnusedLocalVariableInspection */
				$nonce_param = $this->nonce_param;

				/** @noinspection PhpUnusedLocalVariableInspection */
				$action = $this->action;

				/** @noinspection PhpUnusedLocalVariableInspection */
				$output_func = array( $this, 'output_fields' );

				/** @noinspection PhpIncludeInspection */
				include( $this->template );
			}
		}

		public function update_settings() {

			if ( wskl_GET( 'tab' ) !== $this->id || wskl_POST( 'action' ) != $this->action ) {
				return;
			}

			wskl_verify_nonce( $this->nonce_action, wskl_POST( $this->nonce_param ) );

			/** @var array $extracted two keys are present: options, and error. */
			$extracted = $this->extract_option_values();

			if ( is_array( $extracted['options'] ) ) {
				foreach ( $extracted['options'] as $key => $option_value ) {
					$this->update_option( $key, $option_value );
				}
			}

			if ( isset( $extracted['error'] ) && ! empty( $extracted['error'] ) ) {
				add_settings_error(
					$this->id,
					'validation_error',
					$extracted['error'],
					'error'
				);

				return;
			}

			do_action( 'wskl_wp_members_update_settings', $extracted );

			/** success notice */
			add_settings_error(
				'dabory-members',
				'settings_updated',
				__( 'Settings saved.' ),  // use wordpress default text
				'updated'
			);
		}

		public function get_fields() {

			return array(
				// Displayed in tab
				'tab_name' => 'Custom Tab',

				// Page content title
				'title'    => 'General Settings',

				// Page description
				'desc'     => 'Description.',

				// extra CSS class that appended in div.inside
				'class'    => '',

				// many sections.
				'sections' => array(
					array(
						// section id
						'id'     => 'section_id',

						// section title
						'title'  => 'Section Title',

						// fields
						'fields' => array(
							array(
								'type'     => 'input',
								// supply type: input|page_select|checkbox.
								'key'      => 'key_1',
								'label'    => 'Key 1 Label',
								'desc'     => 'Key 1 Description',
								'attrs'    => array(
									'type'  => 'text',
									'class' => 'text key-1',
								),
								'default'  => 'key 1 default',
								'validate' => '',
								'sanitize' => '',
							),
							array(
								'type'     => 'page_select',
								'key'      => 'key_2',
								'label'    => 'Key 2 Label',
								'desc'     => 'Key 2 Desc',
								'validate' => '',
								'sanitize' => '',
							),
							array(
								'type'     => 'checkbox',
								'key'      => 'key_3',
								'label'    => 'Key 3 Label',
								'desc'     => 'Key 3 Description',
								'validate' => '',
								'sanitize' => '',
							),
							array(
								'type' => 'custom',
								// do action 'wskl_wp_members_field_{$type}'
								// you may specify key to validate, or update setting.
							),
						),
						'footer' => array(
							'type' => 'new_page',
						),
					),
				),
			);
		}

		/**
		 * @return array|string
		 */
		public function extract_option_values() {

			$output = array(
				'options' => array(),
				'error'   => NULL,
			);

			$options = array();

			$sections = &$this->fields['sections'];
			foreach ( $sections as $section ) {

				$fields = &$section['fields'];
				foreach ( $fields as $field ) {

					if ( ! isset( $field['key'] ) ) {
						continue;
					}

					$key      = wskl_get_from_assoc( $field, 'key' );
					$validate = wskl_get_from_assoc( $field, 'validate' );
					$sanitize = wskl_get_from_assoc( $field, 'sanitize' );
					$default  = wskl_get_from_assoc( $field, 'default' );
					$label    = wskl_get_from_assoc( $field, 'label' );

					$options[] = array( $key, $validate, $sanitize, $default, $label );
				}
			}

			foreach ( $options as $elem ) {

				$key      = $elem[0];
				$validate = $elem[1];
				$sanitize = $elem[2];
				$default  = $elem[3];
				$label    = $elem[4];

				$opt_name = $this->get_option_name( $key );
				$val      = wskl_POST( $opt_name, $sanitize, $default );

				if ( is_callable( $validate ) ) {
					/** @var true|string $validated */
					$validated = call_user_func( $validate, $val );
					if ( TRUE !== $validated ) {
						$output['error'] = sprintf( __( '항목 \'%s\' 오류: ', 'wskl' ) . $validated, $label );

						return $output;
					}
				}

				$output['options'][ $key ] = $val;
			}

			return $output;
		}

		public function update_option( $key, $value, $autoload = NULL ) {

			return update_option(
				$this->get_option_name( $key ),
				$value,
				$autoload
			);
		}

		public function get_option( $key, $fallback = FALSE ) {

			return get_option( $this->get_option_name( $key ), $fallback );
		}

		public function get_option_name( $key ) {

			return wskl_get_option_name( $this->id . '_' . $key );
		}

		public function is_option_enabled( $key ) {

			return wskl_is_option_enabled( $this->id . '_' . $key );
		}

		public function output_fields( $field ) {

			if ( ! isset( $field['type'] ) ) {
				return;
			}

			$type = wskl_get_from_assoc( $field, 'type' );
			$key  = $this->get_option_name( $field['key'] );

			switch ( $type ) {
				case 'input':
					wskl_members_input( $key, $field['label'], $field['desc'], $field['attrs'], $field['default'] );
					break;

				case 'checkbox':
					wskl_members_checkbox( $key, $field['label'], $field['desc'] );
					break;

				case 'page_select':
					wskl_members_page_select_tag( $key, $field['label'], $field['desc'] );
					break;

				case 'role_select':
					wskl_members_role_select_tag( $key, $field['label'], $field['desc'] );
					break;

				case 'role_checkbox':
					$role_exclude = isset( $field['role_exclude'] ) ? $field['role_exclude'] : array();
					wskl_members_role_check_tag( $key, $field['label'], $field['desc'], $role_exclude );
					break;

				case 'select':
					wskl_members_select_tag( $key, $field['label'], $field['desc'] );
					break;

				default:
					do_action( "wskl_wp_members_field_{$type}", $field );
			}
		}

		public function section_footer_new_page() { ?>
			<p>
				<a href="<?php echo admin_url( 'post-new.php?post_type=page' ); ?>" target="_blank">
					<?php _e( '여기를 눌러 새 페이지를 작성할 수 있습니다.', 'wskl' ); ?>
				</a>
			</p>
		<?php }

		/**
		 * validation function
		 *
		 * @param mixed $value
		 *
		 * @return true|string
		 */
		public function validate_positive_integer( $value ) {

			if ( ! empty( $value ) && ctype_digit( (string) $value ) && intval( $value ) > 0 ) {
				return TRUE;
			}

			return __( '값은 0보다 큰 정수이어야 합니다.', 'wskl' );
		}

		public function validate_email( $value ) {

			if ( is_email( $value ) ) {
				return TRUE;
			}

			return __( '정확한 이메일 주소를 입력해 주세요.', 'wskl' );
		}

		public function validate_non_administrator_role( $value ) {

			if ( $value != 'administrator' ) {
				return TRUE;
			}

			return __( '관리자는 선택할 수 없습니다.', 'wskl' );
		}
	}

endif;