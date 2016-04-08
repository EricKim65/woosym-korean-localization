<?php
/**
 * @var array $config
 * @var array $mu_defaults
 * @var bool  $writable
 * @var array $fixed_filtered_keys
 * @var array $config_filter
 */
?>
<div class="wrap">
	<h3 class="title">
		<?php _e( 'WP Config 편집기', 'wskl' ) ?>
	</h3>
	<p>
		<?php _e( '설정 변경은 워드프레스 동작 전반에 영향을 끼칩니다. 주의해서 사용하세요.', 'wskl' ); ?>
		<?php _e(
			'<a href="https://codex.wordpress.org/Editing_wp-config.php" target="_blank">코덱스 페이지</a>',
			'wskl'
		); ?>
	</p>

	<?php $readonly = ! $writable ? 'readonly' : ''; ?>
	<?php if ( ! $writable ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php _e( '쓰기 권한이 없어 설정값을 고칠 수 없습니다.', 'wskl' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $writable ) : ?>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<?php endif; ?>
		<table class="form-table" id="wp-config-table">
			<?php foreach ( $config as $key => $value ) : ?>
				<tr class="form-row">
					<th>
						<?php echo esc_html( $key ); ?>
					</th>
					<td>
						<?php if ( in_array( $value, array( 'true', 'TRUE', 'false', 'FALSE' ) ) ) : ?>
							<select name="config-<?php echo esc_attr( $key ); ?>" <?php echo $readonly; ?>>
								<option value="TRUE" <?php echo $value == 'true' || $value == 'TRUE' ? 'selected' : ''; ?>>
									TRUE
								</option>
								<option value="FALSE" <?php echo $value == 'false' || $value == 'FALSE' ? 'selected' : ''; ?>>
									FALSE
								</option>
							</select>
						<?php else : ?>
							<input type="text"
							       class="input wskl-long-input"
							       name="config-<?php echo esc_attr( $key ); ?>"
							       value="<?php echo esc_attr( $value ); ?>" <?php echo $readonly; ?>
							/>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php if ( $writable ) : ?>
				<?php
				// must-use defaults
				$idx = 1;
				foreach ( $mu_defaults as $key => $value ) : ?>
					<tr class="form-row">
						<th>
							<?php echo esc_html( $key ); ?>
							<input
								type="hidden"
								name="<?php echo "new-config-$idx"; ?>"
								value="<?php echo esc_attr( $key ); ?>"
							/>
						</th>
						<td>
							<?php if ( in_array( $value, array( 'true', 'TRUE', 'false', 'FALSE' ) ) ) : ?>
								<select name="new-value-<?php echo $idx; ?>">
									<option value="TRUE" <?php echo $value == 'true' || $value == 'TRUE' ? 'selected' : ''; ?>>
										TRUE
									</option>
									<option value="FALSE" <?php echo $value == 'false' || $value == 'FALSE' ? 'selected' : ''; ?>>
										FALSE
									</option>
								</select>
							<?php else : ?>
								<input type="text"
								       class="input wskl-long-input"
								       name="new-value-<?php echo $idx; ?>"
								       value="<?php echo esc_attr( $value ); ?>" ?>
								/>
							<?php endif; ?>
						</td>
					</tr>
					<?php
					++ $idx;
				endforeach;
				?>
				<tr class="form-row plus">
					<th>
						<input type="text" class="wskl-label-input" name="new-config-<?php echo $idx; ?>"/>
					</th>
					<td>
						<input type="text" class="wskl-long-input" name="new-value-<?php echo $idx; ?>"/>
					<span>
						<button type="button" class="plus wskl-add-remove-button wskl-add">&plus;</button>
						<button type="button" class="minus wskl-add-remove-button wskl-remove hidden">&minus;</button>
					</span>
					</td>
				</tr>
				<tr class="form-row">
					<th>
						<?php _e( '서버에 백업본 생성', 'wskl' ); ?>
					</th>
					<td>
						<input id="create-backup" name="create-backup" type="checkbox" class="checkbox" value="yes" checked/>
						<label for="create-backup">
							<span class="description">
								<?php _e( '저장하기 전 기존 wp-config.php 의 사본을 생성합니다.', 'wskl' ); ?>
							</span>
						</label>
					</td>
				</tr>
			<?php endif ?>
		</table>

		<?php if ( $writable ) : ?>
			<script type="text/javascript">
				(function ($) {
					function onClickAddButton() {
						var tr = $(this).closest('tr.form-row');
						var cloned = tr.clone();

						var config = cloned.find('input[name^="new-config"]');
						var value = cloned.find('input[name^="new-value"]');

						var config_name = config.attr('name');
						var value_name = value.attr('name');
						var config_seq = config_name.substring('new-config-'.length, config_name.length);
						var value_seq = value_name.substring('new-value-'.length, value_name.length);

						if ($.isNumeric(config_seq) && $.isNumeric(value_seq)) {
							config.attr('name', 'new-config-' + (parseInt(config_seq) + 1));
							value.attr('name', 'new-value-' + (parseInt(value_seq) + 1));
							cloned.find('button.wskl-add').bind('click', onClickAddButton);
							cloned.find('button.wskl-remove').bind('click', onClickRemoveButton);
							cloned.find('button.hidden').removeClass('hidden');
							cloned.insertAfter(tr);
						}
					}

					function onClickRemoveButton() {
						$(this).closest('tr.form-row').remove();
					}

					$('button.wskl-add').click(onClickAddButton);
					$('button.wskl-remove').click(onClickRemoveButton);
				})(jQuery);
			</script>
			<input type="hidden" name="action" value="wskl-update-wp-config"/>
			<?php wp_nonce_field( 'wskl-config-editor', 'wskl-config-editor' ); ?>
		<?php endif; ?>

		<?php
		$submit_button_attr = array();
		if ( ! $writable ) {
			$submit_button_attr['disabled'] = 'disabled';
		}
		submit_button( __( 'WP Config 업데이트', 'wskl' ), 'primary', 'submit', TRUE, $submit_button_attr ); ?>

	</form>

	<h3 class="title">
		<?php _e( '설정값 필터링', 'wskl' ) ?>
	</h3>
	<p class="title">
		<?php _e( '몇몇 중요한 설정 값들은 본 편집 화면에서 보이지 않게 지정할 수 있습니다.', 'wskl' ); ?>
	</p>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<div>
			<?php _e( '다음 설정은 추가하거나 값을 수정할 수 없습니다.', 'wskl' ); ?>
			<ul>
				<?php foreach ( $fixed_filtered_keys as $key ) : ?>
					<li><?php echo esc_html( $key ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div>
			<?php _e( '한 줄에 한 항목씩 입력하세요. 온점(.)이나 반점(,)을 입력하지 마세요.', 'wskl' ); ?>
			<br/>
			<label for="wskl-config-filter">&nbsp;</label>
			<textarea
				name="wskl-config-filter"
				id="wskl-config-filter"><?php echo implode( "\n", $config_filter ); ?></textarea>
		</div>
		<input type="hidden" name="action" value="wskl-update-wp-config-filter"/>
		<?php wp_nonce_field( 'wskl-config-filter-nonce', 'wskl-config-filter-nonce' ); ?>
		<?php submit_button( __( '필터링 값 업데이트', 'wskl' ) ); ?>
	</form>
</div>