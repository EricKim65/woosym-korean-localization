<tr valign="top">
	<th class="titledesc" scope="row">
		<label for=""><?php _e( '메시지 테스트', 'wskl' ); ?></label>
	</th>
	<td class="forminp forminp-sms-tester">
		<button id="sms-message-tester" type="button" class="button button-secondary">
			<?php _e( '메시지 테스트', 'wskl' ); ?>
		</button>
		<span class="description">
			<?php _e( '발신번호로 테스트 문자를 보냅니다. SMS 포인트를 소모합니다.', 'wskl' ); ?>
		</span>
		<script type="application/javascript">
			(function ($) {
				$('button#sms-message-tester').click(function () {
					if (confirm('<?php _e( '테스트 문자를 보내시겠습니까?', 'wskl' )?>')) {
						$.ajax({
							'url': ajaxurl,
							'method': 'post',
							'async': true,
							'data': {
								'action': 'dabory-sms-tester',
								'dabory-sms-tester-nonce': '<?php echo wp_create_nonce(
									'dabory-sms-tester-nonce'
								) ?>'
							},
							success: function (response) {
								if (response.success) {
									alert('<?php _e( '문자를 성공적으로 보냈습니다.', 'wskl' );?>');
								} else {
									alert(response.data);
								}
							}
						});
					}
				});
			})(jQuery);
		</script>
	</td>
</tr>
<tr valign="top">
	<th class="titledesc" scope="row">
		<?php _e( '포인트 잔량', 'wskl' ); ?>
	</th>
	<td class="forminp forminp-sms-point">
		<button id="sms-message-point" type="button" class="button button-secondary">
			<?php _e( '포인트 잔량 확인', 'wskl' ); ?>
		</button>
		<span class="description">
			<?php _e( '포인트 소모 없음.', 'wskl' ); ?>
		</span>
		<div id="point-output" style="margin-top: 20px;"></div>
		<script type="application/javascript">
			(function ($) {
				$('button#sms-message-point').click(function () {
					$.ajax({
						'url': ajaxurl,
						'method': 'post',
						'async': true,
						'data': {
							'action': 'dabory-sms-point',
							'dabory-sms-point-nonce': '<?php echo wp_create_nonce(
								'dabory-sms-point-nonce'
							) ?>'
						},
						success: function (response) {
							console.log(response);
							if (response.success) {
								$('div#point-output').html('SMS: ' + response.data.sms + 'point(s)');
							} else {
								$('div#point-output').html('Error: ' + response.data);
							}
						}
					});
				});
			})(jQuery);
		</script>
	</td>
</tr>