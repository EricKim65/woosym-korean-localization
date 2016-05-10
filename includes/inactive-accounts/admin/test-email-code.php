<li>
	<label>
		<?php _e( '테스트 메일 송신', 'wskl' ); ?>
	</label>
	<button type="button" class="button" id="test_email">
		<?php _e( '테스트 메일', 'wskl' ); ?>
	</button>
	<span class="description">
		<?php _e( '발신자 주소로 시험 메일을 보냅니다.', 'wskl' ); ?>
	</span>
	<script type="text/javascript">
		(function ($) {
			var button = $('button#test_email');

			button.click(function () {
				$.ajax(ajaxurl, {
					"data": {
						"action": "wskl_inactive-accounts_test_email",
						"_wpnonce": "<?php echo wp_create_nonce( '_wpnonce' ); ?>"
					},
					'method': 'post',
					'success': function (data) {
						button.closest('li').children('span.description').html(data);
					}
				})
			});
		})(jQuery);
	</script>
</li>