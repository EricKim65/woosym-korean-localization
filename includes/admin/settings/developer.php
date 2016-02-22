<?php

function tail_custom( $file_path, $lines = 1, $adaptive = TRUE ) {

	// Open file
	$f = @fopen( $file_path, "rb" );
	if ( $f === FALSE ) {
		return FALSE;
	}
	// Sets buffer size
	if ( ! $adaptive ) {
		$buffer = 4096;
	} else {
		$buffer = ( $lines < 2 ? 64 : ( $lines < 10 ? 512 : 4096 ) );
	}
	// Jump to last character
	fseek( $f, - 1, SEEK_END );
	// Read it and adjust line number if necessary
	// (Otherwise the result would be wrong if file doesn't end with a blank line)
	if ( fread( $f, 1 ) != "\n" ) {
		$lines -= 1;
	}

	// Start reading
	$output = '';
	$chunk  = '';
	// While we would like more
	while ( ftell( $f ) > 0 && $lines >= 0 ) {
		// Figure out how far back we should jump
		$seek = min( ftell( $f ), $buffer );
		// Do the jump (backwards, relative to where we are)
		fseek( $f, - $seek, SEEK_CUR );
		// Read a chunk and prepend it to our output
		$output = ( $chunk = fread( $f, $seek ) ) . $output;
		// Jump back to where we started reading
		fseek( $f, - mb_strlen( $chunk, '8bit' ), SEEK_CUR );
		// Decrease our line counter
		$lines -= substr_count( $chunk, "\n" );
	}
	// While we have too many lines
	// (Because of buffer size we might have read too many)
	while ( $lines ++ < 0 ) {
		// Find first newline and remove all text before that
		$output = substr( $output, strpos( $output, "\n" ) + 1 );
	}
	// Close file and return
	fclose( $f );

	return trim( $output );
}


$developer = array(
	'title'       => __( '개발자용', 'wskl' ),
	'description' => __( '개발자용 탭 WP_DEBUG = TRUE 면 나오는 탭', 'wskl' ),
	'fields'      => array(
		array(
			'id'          => 'enable_debugging',
			'label'       => __( 'Enable DEBUGGING MODE', 'wskl' ),
			'description' => __( '', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'develop_xdebug_always_on',
			'label'       => __( 'Always Enable XDEBUG', 'wskl' ),
			'description' => __( '', 'wskl' ),
			'type'        => 'checkbox',
			'default'     => '',
		),
		array(
			'id'          => 'develop_xdebug_session_id',
			'label'       => __( 'XDEBUG SESSION ID', 'wskl' ),
			'description' => __( 'Value will set by cookie!', 'wskl' ),
			'type'        => 'text',
			'default'     => '',
		),
		array(
			'id'          => 'develop_cassandra_url',
			'label'       => __( 'CASSANDRA URL OVERRIDE', 'wskl' ),
			'description' => __( '', 'wskl' ),
			'type'        => 'text',
			'default'     => '',
		),

	),
);

// append only if current tab is displayed
if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'developer' ) {

	$developer['fields'][] = array(
		'id'          => 'develop_log_line',
		'type'        => 'text',
		'label'       => __( 'DISPLAY LAST N LINES', 'wskl' ),
		'default'     => 100,
	);

	// log file display //////////////////////////////////

	$log_file = WP_CONTENT_DIR . '/debug.log';

	if ( file_exists( $log_file ) ) {
		$log_text = tail_custom( $log_file, wskl_get_option( 'develop_log_line', 100 ) );
	} else {
		$log_text = 'LOG FILE NOT FOUND!';
	}

	// note: too many lines will refuse conversion.
	$log_text = htmlspecialchars( $log_text, ENT_QUOTES );

	$wp_log = array(
		'id'          => 'develop_dummy_log_text',
		'type'        => 'caption',
		'label'       => __( 'WP LOG', 'wskl' ),
		'description' => "<div><pre class=\"wskl-log-display\">$log_text</pre></div>",
	);

	$developer['fields'][] = $wp_log;
}

return $developer;