<?php

/**
 * Returns valid HTML attributes
 *
 * @param string          $key
 * @param string          $value
 * @param callable|string $esc_func
 *
 * @return string
 */
function wskl_html_attr( $key, $value, $esc_func = 'esc_attr' ) {

	return is_callable( $esc_func ) ? $key . '="' . $esc_func( $value ) . '" ' : ' ';
}


/**
 * Open a HTML tag
 *
 * @param string $tag
 * @param array  $params Each array contains two keys. 'value', and 'esc_func'.
 * @param bool   $self_closing
 * @param bool   $return
 *
 * @return string
 */
function wskl_html_open_tag( $tag, array $params = array(), $self_closing = FALSE, $return = FALSE ) {

	$html = '<' . esc_html( $tag ) . ' ';

	foreach ( $params as $attr => $param ) {

		$value    = isset( $param['value'] ) ? $param['value'] : '';
		$esc_func = isset( $param['esc_func'] ) && is_callable( $param['esc_func'] ) ? $param['esc_func'] : 'esc_attr';

		if ( is_array( $value ) ) {
			$value = implode( ' ', $value );
		}

		$html .= call_user_func_array( 'wskl_html_attr',
		                               array( $attr, $value, $esc_func ) );
	}

	$html .= $self_closing ? '/>' : '>';

	if ( $return ) {
		return $html;
	}

	echo $html;
}


/**
 * Close an HTML tag
 *
 * @param string $tag
 * @param bool   $return
 *
 * @return string
 */
function wskl_html_close_tag( $tag, $return = FALSE ) {

	$html = '</' . esc_html( $tag ) . '>';

	if ( $return ) {
		return $html;
	}

	echo $html;
}


/**
 * Generates an '<a ...>...</a>' tag.
 *
 * @param string $text   a text node.
 * @param array  $params each key is mapped to an attribute, and values are
 *                       mapped to a value string.
 * @param bool   $return
 *
 * @return string
 */
function wskl_html_anchor( $text, array $params = array(), $return = FALSE ) {

	$in = array();

	foreach ( $params as $key => $v ) {

		switch ( $key ) {
			case 'href':
				$in['href'] = array( 'value' => $v, 'esc_func' => 'esc_url' );
				break;

			default:
				$in[ $key ] = array( 'value' => $v );
				break;
		}
	}

	$out = wskl_html_open_tag( 'a', $in, FALSE, TRUE );
	$out .= esc_html( $text );
	$out .= wskl_html_close_tag( 'a', TRUE );

	if ( $return ) {
		return $out;
	}

	echo $out;
}