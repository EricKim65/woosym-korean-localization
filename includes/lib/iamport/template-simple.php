<h2>결제 상세</h2>
<table class="shop_table order_details">
	<tbody>
	<tr>
		<th>결제수단</th>
		<td><?php echo $pay_method_text; ?></td>
	</tr>
	<tr>
		<th>매출전표</th>
		<td><a target="_blank" href="<?php echo $receipt_url; ?>">영수증보기(<?php echo $transction_id; ?>)</a></td>
	</tr>
	</tbody>
</table>