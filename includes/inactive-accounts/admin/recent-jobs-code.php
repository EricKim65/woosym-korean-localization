<?php
/**
 * @var array $recent_jobs
 */
?>
<table class="wide widefat">
	<thead>
	<tr>
		<th><?php _e( '작업시간', 'wskl' ); ?></th>
		<th><?php _e( '휴면 통지', 'wskl' ); ?></th>
		<th><?php _e( '휴면 처리', 'wskl' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $recent_jobs as $jobs ) :
		$timestamp = wskl_get_from_assoc( $jobs, 'timestamp' );
		$total_notified = wskl_get_from_assoc( $jobs, 'total_notified' );
		$total_disabled = wskl_get_from_assoc( $jobs, 'total_disabled' );
		$notification_spent = wskl_get_from_assoc( $jobs, 'notification_spent' );
		$deactivation_spent = wskl_get_from_assoc( $jobs, 'deactivation_spent' );
		?>
		<tr>
			<td><?php echo wskl_datetime_string( $timestamp ) ?></td>
			<td><?php printf( _n( '%s명', '%s명', $total_notified, 'wskl' ), $total_notified ); ?>
				&nbsp;
				<?php printf( '(%.03fms)', $notification_spent * 1000 ); ?>
			</td>
			<td><?php printf( _n( '%s명', '%s명', $total_disabled, 'wskl' ), $total_disabled ); ?>
				&nbsp;
				<?php printf( '(%.03fms)', $deactivation_spent * 1000 ); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<p>
	<button type="button" class="button" onclick="manual_cron_job();">
		<?php _e( '수동 검사', 'wskl' ); ?>
	</button>
	&nbsp;
	<span id="manual-output"></span>
	<script type="text/javascript">
		function manual_cron_job() {
			if (!confirm('<?php _e( '수동 검사를 지금 하시겠습니까?', 'wskl' ); ?>')) {
				return;
			}
			var output = jQuery('span#manual-output');
			output.html('<?php _e( ' 검사를 시작합니다. 시간이 걸릴 수 있습니다...', 'wskl' ); ?>');
			jQuery.post(
				ajaxurl, {
					'manual_cron_job_nonce': '<?php echo wp_create_nonce( 'manual_cron_job_nonce' ); ?>',
					'action': 'manual_cron_job'
				},
				function () {
					output.html('<?php _e( '검사가 완료되었습니다!', 'wskl' ); ?>');
				}
			);
			return false;
		}
	</script>
</p>