<?php
/**
 * @var array $order_magic_texts
 * @var array $user_magic_texts
 */
?>
<tr valign="top">
	<th>
		<?php _e( '치환문자 안내', 'wskl' ); ?>
	</th>
	<td>
		<ul class="subs-ul sub-ul-left">
			<?php foreach ( $order_magic_texts as $id => $text ) : ?>
				<li>
					<span class="subs-label">
						<label class="magic-text" data-value="<?php echo $text['find']; ?>">
							<?php echo esc_html( $text['find'] ); ?>
						</label>
					</span>
					<span class="clear">
						<?php echo esc_html( $text['desc'] ); ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
		<ul class="subs-ul sub-ul-left">
			<?php foreach ( $user_magic_texts as $id => $text ) : ?>
				<li>
					<span class="subs-label">
						<label class="magic-text" data-value="<?php echo $text['find']; ?>">
							<?php echo esc_html( $text['find'] ); ?>
						</label>
					</span>
					<span class="clear">
						<?php echo esc_html( $text['desc'] ); ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
		<div class="clear"></div>
		<span class="description clear">
			<?php _e( '치환문자를 더블클릭하면 메시지 내용에 삽입됩니다.', 'wskl' ); ?>
		</span>
		<script type="text/javascript">
			(function ($) {
				$('label.magic-text').dblclick(function () {
					var textarea = $('textarea[id$="message_content"]');
					var selected = $(this);
					textarea.focus();
					var s = textarea[0].selectionStart;
					var e = textarea[0].selectionEnd;
					var text = textarea.val();
					textarea.val(text.substring(0, s) + selected.data('value') + text.substring(e));
				});
			})(jQuery);
		</script>
		<style type="text/css" scoped>
			.subs-label {
				float: left;
				min-width: 200px;
			}

			.subs-label:hover {
				font-weight: bold;
				color: #3333E0;
				background-color: #e8e4e3;
			}

			.subs-ul {
				margin-top: 0;
			}

			.sub-ul-left {
				display: inline;
				float: left;
				margin-right: 50px;
			}
		</style>

		<?php
		//		require_once( WSKL_PATH . '/includes/libraries/dabory-sms/providers/mdalin/class-wskl-dabory-sms-providers-mdalin.php' );
		//		require_once( WSKL_PATH . '/includes/libraries/dabory-sms/class-wskl-sms-text-substitution.php' );
		//		$order = wc_get_order( 140 );
		//		$sub = new WSKL_SMS_Text_Substitution();
		//		$template = wskl_get_option( 'sms_new_order_message_content' );
		//		$output = $sub->substitute( $template, $order, wp_get_current_user() );
		//		var_dump( $output );
		?>
	</td>
</tr>
