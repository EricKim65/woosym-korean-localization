<?php
/**
 * 일러두기: 여기서 등록한 키 값은 WSKL_Dabory_Members_Admin_Settings 클래스
 * extract_option_values, validate_option_values 메소드에서 처리해 줘야 DB 레코드에 등록됨을 주의!
 */
use WSKL_Dabory_Members_Admin_Settings as Settings;


?>


<div class="metabox-holder">
	<div id="post-body">
		<div id="post-body-content">
			<div class="postbox">
				<h3><?php _e( '다보리 멤버스', 'wskl' ); ?></h3>
				<div class="inside">
					<form id="updatesettings" name="updatesettings" method="post" action="<?php echo esc_url(
						$_SERVER['REQUEST_URI']
					); ?>">
						<?php wp_nonce_field( 'wskl_83830_nonce', 'wskl_members_nonce' ); ?>
						<p>
							다보리 플러그인에서 제공하는 WP-Members 플러그인 확장입니다.
						</p>
						<h2>
							<?php _e( '페이지 설정', 'wskl' ); ?>
						</h2>
						<!-- 가입/탈퇴 조항 -->
						<div>
							<h3>
								<?php _e( '가입 약관', 'wskl' ); ?>
							</h3>
							<ul>
								<?php Settings::output_page_select_tag(
									'page_tos',
									__( '이용약관 페이지', 'wskl' ),
									__( '', 'wkl' )
								); ?>
								<?php Settings::output_page_select_tag(
									'page_privacy',
									__( '개인정보 보호정책 페이지', 'wskl' ),
									__( '', 'wkl' )
								); ?>
								<?php Settings::output_page_select_tag(
									'page_3rd_party',
									__( '개인정보 3자 제공 페이지', 'wskl' ),
									''
								); ?>
							</ul>
						</div>
						<!-- 배송과 환불 약관 -->
						<div>
							<h3>
								<?php _e( '배송과 환불 약관', 'wskl' ); ?>
							</h3>
							<ul>
								<?php Settings::output_page_select_tag( 'page_delivery', __( '배송 약관', 'wskl' ), '' ); ?>
								<?php Settings::output_page_select_tag( 'page_refund', __( '환불 약관', 'wskl' ), '' ); ?>
							</ul>
						</div>
						<!-- 가입 / 탈퇴 / 등록 완료 페이지 -->
						<div>
							<h3>
								<?php _e( '가입 / 탈퇴 / 등록 완료 페이지', 'wskl' ); ?>
							</h3>
							<ul>
								<?php Settings::output_page_select_tag(
									'page_registration',
									__( '등록 페이지', 'wskl' ),
									__( '본문에 WP-Members 쇼트코드 "[wpmem_form register /]"가 있어야 동작합니다.', 'wskl' )
								); ?>
								<?php Settings::output_page_select_tag(
									'page_registration_complete',
									__( '가입 완료 페이지', 'wskl' ),
									__( '가입 성공 시 보여줄 메시지(쇼트코드 필요 없음)', 'wskl' )
								); ?>
								<?php Settings::output_page_select_tag(
									'page_withdrawal',
									__( '탈퇴 페이지', 'wskl' ),
									__( '본문에 쇼트코드 "[wskl_members withdrawal /]" 가 있어야 동작합니다.', 'wskl' )
								); ?>
							</ul>
						</div>
						<!-- 새 포스트 -->
						<div>
							<p>
								<a href="<?php echo admin_url( 'post-new.php' ); ?>" target="_blank">
									<?php _e( '여기를 눌러 새 페이지를 작성할 수 있습니다.', 'wskl' ); ?>
								</a>
							</p>
						</div>
						<div class="spacing clear"></div>

						<h2>
							<?php _e( '등록 / 탈퇴 절차 설정', 'wskl' ); ?>
							<p></p>
						</h2>
						<!-- 등록 절차 -->
						<div>
							<h3>
								<?php _e( '등록 페이지 설정', 'wskl' ); ?>
							</h3>
							<ul>
								<?php Settings::output_checkbox(
									'show_terms',
									__( '페이지 보이기', 'wskl' ),
									__( '가입시 약관 동의 조항을 출력합니다.', 'wskl' )
								); ?>
								<?php Settings::output_checkbox(
									'enable_postcode_button',
									__( '주소검색', 'wskl' ),
									__( '주소 검색 버튼을 추가합니다.', 'wskl' )
								); ?>
							</ul>
							<ul>
								<?php Settings::output_checkbox(
									'enable_password_length',
									__( '패스워드 길이 설정', 'wskl' ),
									sprintf(
										__( '패스워드 최소 길이를 제한할 수 있도록 설정합니다. 최소 길이는 %d 글자 입니다.', 'wskl' ),
										Settings::PASSWORD_MIN_LENGTH
									)
								); ?>
								<?php Settings::output_input(
									'password_min_length',
									__( '비밀번호 최소 길이', 'wskl' ),
									'',
									array(
										'type' => 'number',
										'min'  => Settings::get_password_min_length(),
									),
									Settings::PASSWORD_MIN_LENGTH
								); ?>
								<?php Settings::output_checkbox(
									'password_mixed_chars',
									__( '비밀번호 문자 조합', 'wskl' ),
									__( '숫자와 특수문자를 포함하도록 강제합니다.', 'wskl' )
								); ?>
								<?php Settings::output_checkbox(
									'password_strength_meter',
									__( '비밀번호 강도 표시', 'wskl' ),
									__( '입력한 비밀번호의 강도를 표시하여 보다 강력한 패스워드를 생성하도록 유도합니다.', 'wskl' )
								); ?>
							</ul>
						</div>
						<!-- 등록 완료 페이지 설정 -->
						<div>
							<h3>
								<?php _e( '등록 완료 페이지 설정', 'wskl' ); ?>
							</h3>
							<ul>
								<?php Settings::output_checkbox(
									'show_registration_complete',
									__( '페이지 보이기', 'wskl' ),
									__( '가입 완료시 등록 완료 메시지를 보여줍니다.', 'wskl' )
								); ?>
								<?php Settings::output_checkbox(
									'logged_in_after_registration',
									__( '가입 후 로그인', 'wskl' ),
									__( '가입 완료 후 바로 사용자를 로그인 상태로 만듭니다.', 'wskl' )
								); ?>
							</ul>
						</div>
						<!-- 탈퇴 페이지 설정 -->
						<div>
							<h3>
								<?php _e( '회원 탈퇴 설정', 'wskl' ); ?>
							</h3>
							<ul>
								<?php Settings::output_checkbox(
									'enable_withdrawal_shortcode',
									__( '페이지 보이기', 'wskl' ),
									__( '회원 탈퇴 기능을 사용합니다.', 'wskl' )
								); ?>
								<?php Settings::output_checkbox(
									'delete_after_withdrawal',
									__( '사용자 삭제', 'wskl' ),
									__( '회원 탈퇴시 그 회원의 정보를 즉시 데이터베이스에서 삭제합니다.', 'wskl' )
								); ?>
							</ul>
						</div>

						<div class="spacing clear"></div>

						<h2>
							<?php _e( '배송 환불 약관 설정', 'wskl' ); ?>
						</h2>
						<div>
							<h3>
								<?php _e( '배송 약관 설정', 'wskl' ); ?>
							</h3>
							<ul>
								<?php Settings::output_checkbox(
									'show_delivery',
									__( '배송 약관 보이기', 'wskl' ),
									__( '상품 상세 페이지에 배송 약관을 보여줍니다.', 'wskl' )
								); ?>
							</ul>
						</div>
						<div>
							<h3>
								<?php _e( '환불 약관 설정', 'wskl' ); ?>
							</h3>
							<ul>
								<?php Settings::output_checkbox(
									'show_refund',
									__( '환불 약관 보이기', 'wskl' ),
									__( '상품 상세 페이지에 환불 약관을 보여줍니다.', 'wskl' )
								); ?>
							</ul>
						</div>
						<div class="clear spacing"></div>
						<input type="hidden" name="action" value="update_dabory_members"/>
						<?php submit_button( __( 'Update Settings', 'wp-members' ) ); ?>
					</form>
				</div> <!-- .inside -->
			</div>
		</div> <!-- #post-body-content -->
	</div> <!-- #post-body -->
</div> <!-- .metabox-holder -->