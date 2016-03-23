<?php
/**
 * @var array $metadata
 *             - exported: timestamp when exported
 *             - post_modified_gmt
 *             - post_modified
 *
 */
?>
<div class="wskl-post-export">
	<input type="checkbox" id="allow-export" name="allow-export" <?php echo ! isset( $metadata['exported'] ) ? 'checked' : ''; ?>>
	<label for="allow-export">
		<span class="dashicons dashicons-share-alt2"></span>
		<?php _e( '다보리로 이 포스트 내보내기', 'wskl' ); ?>
	</label>
	<fieldset class="hide-if-js">
		<legend class="screen-reader-text">
			<?php _e( '다보리로 이 포스트 내보내기', 'wskl' ); ?>
		</legend>
	</fieldset>

	<div>
		<p>
		<ul>
			<li>
				상태:
				<?php if ( isset( $metadata['exported'] ) ) {
					_e( '내보내짐.', 'wskl' );
				} else {
					_e( '아직 내보내지 않음.', 'wskl' );
				} ?>
			</li>
			<?php if ( isset( $metadata['exported'] ) ) : ?>
				<li>
					<?php
					$exported = wskl_get_from_assoc( $metadata, 'exported' );
					if ( $exported ) {
						printf(
							'%s: %s',
							__( '보낸 시각', 'wskl' ),
							date_i18n( get_option( 'date_format' ), $exported )
						);
					} else {
						_e( '정확한 시각을 알 수 없습니다.', 'wskl' );
					}
					?>
				</li>
			<?php endif; ?>
		</ul>
		</p>
	</div>
</div>