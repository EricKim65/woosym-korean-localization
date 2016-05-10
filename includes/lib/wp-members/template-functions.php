<?php
/**
 * input 체크박스 출력
 *
 * @param        $key
 * @param string $label
 * @param string $desc
 */
function wskl_members_checkbox( $key, $label = '', $desc = '' ) {

	$value = get_option( $key );
	$fmt   = <<< 'EOD'
<li>
	<label for="%2$s">%1$s</label>
	<input id="%2$s" name="%2$s" type="checkbox" value="yes" %3$s />
	<span class="description">%4$s</span>
</li>
EOD;
	printf(
		$fmt,
		esc_html( $label ),                              // 1 label
		esc_attr( $key ),                                // 2 id, name
		checked( $value, 'yes', FALSE ),                 // 3 checked
		esc_html( $desc )                                // 4 description
	);
}

/**
 * 일반적인 형태의 인풋 태그 출력
 *
 * @param        $key
 * @param string $label
 * @param string $desc
 * @param array  $attributes    별도의 프로퍼티. DB 에 저장된 값을 불러올 때는 여기에 'value' 키를
 *                              넣지 말 것.
 * @param mixed  $default_value 기본 value 프로퍼티 값.
 */
function wskl_members_input( $key, $label = '', $desc = '', $attributes = array(), $default_value = FALSE ) {

	$value = get_option( $key, $default_value );
	if ( ! isset( $attributes['value'] ) ) {
		$attributes['value'] = $value;
	}

	$buffer = array();
	foreach ( $attributes as $k => $v ) {
		$buffer[] = esc_attr( $k ) . '="' . esc_attr( $v ) . '"';;
	}
	$attr = implode( ' ', $buffer );

	$fmt = <<< 'EOD'
<li>
	<label for="%2$s">%1$s</label>
	<input id="%2$s" name="%2$s" %5$s />
	<span class="description">%4$s</span>
</li>
EOD;
	printf(
		$fmt,
		esc_html( $label ),
		esc_attr( $key ),
		esc_attr( $value ),
		esc_html( $desc ),
		$attr
	);
}

/**
 * 페이지 셀렉트 박스의 열린 컨텍스트 출력
 *
 * @used-by output_page_select_tag()
 */
function wskl_members_opened_context() {

	echo '<span class="context-opened">';
	echo '<a href="" class="context-edit" target="_blank">' . __(
			'편집',
			'wskl'
		) . '</a> | ';
	echo '<a href="" class="context-view" target="_blank">' . __(
			'보기',
			'wskl'
		) . '</a> | ';
	echo '<a href="#" class="arrow-left">' . __( '숨김', 'wskl' ) . '</a>';
	echo '</span>';
}

/**
 * 페이지가 선택되었을 때 셀렉트 박스 오른편에 닫힌 컨텍스트 링크가 보임.
 *
 * @used-by output_page_select_tag()
 *
 * @param $display
 */
function wskl_members_closed_context_button( $display ) {

	echo '<span class="context-closed" ';
	echo ( $display ) ? '>' : 'style="display:none;">';
	echo '<a href="#" class="arrow-right"> &rtrif; </a>';
	echo '</span>';
}

/**
 * page 타입 포스트를 선택할 수 있도록 option 태그 생성
 *
 * @param bool $chosen_id
 *
 * @used-by output_page_select_tag
 * @return string
 */
function wskl_members_page_option_tags( $chosen_id = FALSE ) {

	$pages = get_pages(
		array(
			'post_type'   => 'page',
			'post_status' => 'publish,private',
		)
	);

	$selected = $chosen_id ? 'selected' : '';
	$buffer   = array(
		"<option value=\"-1\" {$selected}>" .
		esc_html( __( '페이지를 선택하세요', 'wskl' ) ) .
		'</option>',
	);

	foreach ( $pages as $page ) {
		$selected = $page->ID == $chosen_id ? 'selected' : '';
		$url      = get_page_link( $page );

		$buffer[] = "<option value=\"{$page->ID}\" {$selected} data-url=\"{$url}\">";
		$buffer[] = esc_html( $page->post_title );
		$buffer[] = "</option>";
	}

	$output = implode( '', $buffer );

	return $output;
}

/**
 * poge 타입 포스트를 선택할 수 있도록 select 태그 생성
 *
 * @uses output_page_option_tags()
 * @uses output_closed_context_button()
 * @uses output_opened_context()
 *
 * @param        $key
 * @param string $label
 * @param string $desc
 */
function wskl_members_page_select_tag( $key, $label = '', $desc = '' ) {

	$value = get_option( $key );

	echo '<li><label>' . esc_html( $label ) . '</label>';
	echo "<select class=\"dabory-page-select\" name=\"{$key}\">";
	echo wskl_members_page_option_tags( $value );
	echo '</select>';

	wskl_members_closed_context_button( absint( $value ) > 0 );
	wskl_members_opened_context();

	echo '</span>';
	echo '<span class="description">' . esc_html( $desc ) . '</span></li>';
}

function wskl_members_role_select_tag( $key, $label = '', $desc = '' ) {

	$value   = get_option( $key );
	$id_name = esc_attr( $key );

	printf( '<li><label for="%2$s">%1$s</label><select id="%2$s" name="%2$s">', $label, $id_name );

	echo '<option value="">' . __( '역할을 선택하세요', 'wskl' ) . '</option>';
	wp_dropdown_roles( $value );

	echo '</select><span class="description">' . esc_html( $desc ) . '</span></li>';
}

function wskl_members_select_tag( $key, $options, $label = '', $desc = '' ) {

	$selected = get_option( $key );
	$id_name  = esc_attr( $key );

	printf( '<li><label for="%2$s">%1$s</label><select id="%2$s" name="%2$s">', $label, $id_name );

	foreach ( $options as $key => $option ) {
		printf(
			'<option value="%s" "%s">%s</option>',
			esc_attr( $key ),
			checked( $selected, $key ),
			esc_html( $option )
		);
	}

	echo '</select><span class="description">' . esc_html( $desc ) . '</span></li>';
}

function wskl_members_role_check_tag( $key, $label = '', $desc = '', $role_exclude = array() ) {

	$selected = (array) get_option( $key );
	$id_base  = esc_attr( $key );

	// list array to associative array
	$exclude_filter = array();
	foreach ( $role_exclude as $r ) {
		$exclude_filter[ $r ] = '';
	}

	// get roles
	$roles          = array();
	$editable_roles = get_editable_roles();
	foreach ( $editable_roles as $role => $details ) {
		$roles[ $role ] = translate_user_role( $details['name'] );
	}

	// exclude specified roles
	$roles = array_diff_key( $roles, $exclude_filter );
	asort( $roles );

	echo '<li><label>' . esc_html( $label ) . '</label><fieldset>';
	echo '<span class="description">' . esc_html( $desc ) . '</span>';

	foreach ( $roles as $role => $name ) {
		$id = esc_attr( "{$id_base}-{$role}" );

		echo '<label for="' . $id . '">';
		echo '<input type="checkbox"'
		     . ' name="' . esc_attr( $id_base ) . '[]"'
		     . ' id="' . esc_attr( $id ) . '"'
		     . ' value="' . esc_attr( $role ) . '"'
		     . ( in_array( $role, $selected ) ? ' checked ' : ' ' )
		     . '/>';
		echo $name . '</label><br/>';
	}
	echo '</fieldset></li>';
}