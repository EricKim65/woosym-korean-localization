<?php
/**
 * @var array  $settings
 * @var string $nonce_action
 * @var string $nonce_param
 * @var string $action
 * @var array  $output_func
 */
?>
<div class="metabox-holder">
	<div id="post-body">
		<div id="post-body-content">
			<div class="postbox">
				<h3>
					<?php echo wskl_get_from_assoc( $settings, 'title' ); ?>
				</h3>
				<div class="inside <?php echo wskl_get_from_assoc( $settings, 'class' ); ?>">
					<form id="updatesettings" name="updatesettings" method="post"
					      action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>">
						<?php wp_nonce_field( $nonce_action, $nonce_param ); ?>
						<p>
							<?php echo wskl_get_from_assoc( $settings, 'desc' ); ?>
						</p>
						<?php foreach ( $settings['sections'] as $section ) : ?>
							<h2><?php echo wskl_get_from_assoc( $section, 'title' ) ?></h2>
							<div>
								<?php if ( isset( $section['fields'] ) ) : ?>
									<ul>
										<?php foreach ( $section['fields'] as $field ) {
											call_user_func( $output_func, $field );
										} ?>
									</ul>
								<?php endif; ?>
								<?php
								if ( isset( $section['footer'] ) ) {
									do_action(
										"wskl_wp_members_section_footer_{$section['footer']['type']}",
										$section
									);
								}
								?>
							</div>
						<?php endforeach; ?>
						<div class="clear spacing"></div>
						<input type="hidden" name="action" value="<?php echo $action; ?>"/>
						<?php submit_button( __( 'Update Settings', 'wp-members' ) ); ?>
					</form>
				</div> <!-- .inside -->
			</div> <!-- .postbox -->
		</div> <!-- #post-body-content -->
	</div> <!-- #post-body -->
</div> <!-- .metabox-holder -->