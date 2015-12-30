<?php
/**
 * @var $last_export mixed timestamp of last export. false if never exported before.
 */
?>
<div class="misc-pub-section dabory-post-export">
  <span class="dashicons dashicons-share-alt"></span>
	<input type="checkbox" id="checkbox-dabory-export" name="dabory-post-export" <?= ( $last_export ) ? 'checked' : '' ?>>
	<label for="checkbox-dabory-export"><?= __( '다보리로 이 포스트 보내기', 'wskl' ) ?></label>
	<fieldset class="hide-if-js">
		<legend class="screen-reader-text"><?php _e( '다보리로 이 포스트 보내기', 'wskl' ); ?></legend>
	</fieldset>
</div>