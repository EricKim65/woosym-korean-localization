<?php

function wskl_html_attr( $key, $value, $esc_func = 'esc_attr' ) {
	return is_callable( $esc_func ) ?  $key . '="' . $esc_func( $value ) . '" ' : ' ';
}

function wskl_html_anchor( $text, array $params = array(), $return = false ) {

	$html = '<a ';

	foreach( $params as $key => $v ) {

		$value = is_array( $v ) ? implode( ' ' , $v ) : $v;

		switch( $key ) {
			case 'href':
				$html .= wskl_html_attr( 'href', $value, 'esc_url' );
				break;

			default:
				$html .= wskl_html_attr( 'target', $value );
				break;
		}
	}

	$html .= '>' . esc_html( $text ) . '</a>';

	if( $return ) {
		return $html;
	}

	echo $html;
}