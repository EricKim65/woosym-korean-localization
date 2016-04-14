<?php
// includes/admin/views/html-admin-settings.php
// includes/admin/class-wc-admin-settings.php
?>
<tr valign="top">
	<th scope="row" class="titledesc"></th>
	<td>
		<div style="width: 640px; text-align: right;">
			<div>
				문자: <span id="message-byte"></span> <?php _e( '바이트', 'wskl' ); ?>
			</div>
			<button id="send_message" type="button" class="button-primary">
				<?php _e( '메시지 보내기', 'wskl' ); ?>
			</button>
		</div>
	</td>
</tr>