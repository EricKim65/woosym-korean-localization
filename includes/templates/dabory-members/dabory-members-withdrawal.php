<?php
/**
 * @var string $status complete|failure
 * @var string $message
 */
?>

<?php if ( ! isset( $status ) ) : ?>

	<div id="wpmem_reg">
		<form id="" class="form" name="form" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
			<fieldset>
				<label for="password" class="text">
					<?php _e( 'Password' ); ?>
					<span class="req">*</span>
				</label>
				<div class="div_text">
					<input name="password" type="password" id="password" class="textbox"/>
				</div>

				<label for="reason" class="textarea">
					<?php _e( '탈퇴 사유를 적어 주세요', 'wskl' ); ?>
					<!-- <span class="req">*</span> -->
				</label>
				<div class="div_textarea">
					<textarea id="reason" name="reason" class="textarea"></textarea>
				</div>
				<div class="div_text">
					<span class="req">*</span>
					<span>필수 항목</span>
				</div>
				<div class="button_div">
					<input type="submit" value="<?php _e( '탈퇴 진행', 'wskl' ); ?>"/>
				</div>
				<?php wp_nonce_field( 'dabory_members_withdrawal', 'dabory_members_withdrawal', FALSE ); ?>
				<input type="hidden" name="action" value="dabory_members_withdrawal"/>
			</fieldset>
		</form>
	</div>


<?php else : ?>

	<div>

		<?php if ( $status == 'complete' ) : ?>
			<div>
				<h3><?php _e( '회원 탈퇴 완료', 'wskl' ); ?></h3>
				<p><?php echo $message; ?></p>
			</div>
		<?php elseif ( $status == 'failure' ) : ?>
			<div>
				<?php echo $message; ?>
			</div>
		<?php endif; ?>

	</div>

<?php endif; ?>
