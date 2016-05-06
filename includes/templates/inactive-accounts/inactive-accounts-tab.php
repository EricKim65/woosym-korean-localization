<?php
require_once( WSKL_PATH . '/includes/lib/wp-members/template-functions.php' );
?>

<div class="metabox-holder">
	<div id="post-body">
		<div id="post-body-content">
			<div class="postbox">
				<h3>
					<?php _e( '휴면계정 설정', 'wskl' ); ?>
				</h3>
				<div class="inactive-account inside">
					<form id="updatesettings" name="updatesettings" method="post"
					      action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>">
						<p> <?php echo WSKL_NAME; ?>에서 제공하는 휴면 계정 관리 기능입니다.</p>

						<h3>
							<?php _e( '', 'wskl' ); ?>
						</h3>
						<ul>
							<?php wskl_members_input(
								'inactive_accounts_interval',
								__( '검사 주기', 'wskl' ),
								__( '시간 단위로 입력하세요', 'wskl' ),
								array( 'type' => 'number' )
							); ?>
							<?php wskl_members_input(
								'inactive_accounts_duration',
								__( '휴면 기한', 'wskl' ),
								__( '일 동안 로그인하지 않은 계정을 휴면 처리합니다.', 'wskl' ),
								array( 'type' => 'number' )
							); ?>
							<?php wskl_members_input(
								'inactive_accounts_notification',
								__( '휴면 통지 기한', 'wskl' ),
								__( '일 전에 휴면 처리 통지를 보냅니다.', 'wskl' ),
								array( 'type' => 'number' )
							); ?>
							<?php wskl_members_page_select_tag(
								'inactive_accounts_email_post',
								__( '통지 메일 ', 'wskl' ),
								__( '메일 본문용 포스트를 선택하세요.', 'wskl' )
							); ?>
						</ul>

						<ul>
							<?php wskl_members_checkbox(
								'inactive_accounts_display_last_login',
								__( '사용자 컬럼 확장', 'wskl' ),
								__( '모든 사용자 목록에 최근 로그인 일자를 표시합니다.', 'wskl' )
							); ?>
						</ul>
						<?php wp_nonce_field( 'wskl_18348_nonce' ); ?>
						<div class="clear spacing"></div>
						<input type="hidden" name="action" value="update_inactive_accounts"/>
						<?php submit_button( __( 'Update Settings', 'wp-members' ) ); ?>
					</form>
				</div> <!-- .inside -->
			</div> <!-- .postbox -->
		</div> <!-- #post-body-content -->
	</div> <!-- #post-body -->
</div> <!-- .metabox-holder -->
