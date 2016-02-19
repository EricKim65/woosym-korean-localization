<h2>결제 상세</h2>
<table class="shop_table order_details">
  <tbody>
  <tr>
    <th>결제수단</th>
    <td><?php echo $pay_method_text; ?></td>
  </tr>
  <tr>
    <th>가상계좌 입금은행</th>
    <td><?php echo $vbank_name; ?></td>
  </tr>
  <?php if ( $order->has_status(array('processing', 'completed')) ) : //이미 결제 완료 됨 ?>
    <tr>
      <th>가상계좌번호</th>
      <td>입금완료( <?php echo trim($vbank_num); ?> )</td>
    </tr>
    <tr>
      <th>가상계좌 입금기한</th>
      <td>입금완료</td>
    </tr>
    <tr>
      <th>매출전표</th>
      <td><a target="_blank" href="<?php echo $receipt_url; ?>">영수증보기(<?php echo $transction_id; ?>)</a></td>
    </tr>
  <?php else : ; ?>
    <tr>
      <th>가상계좌번호</th>
      <td><?php echo trim($vbank_num); ?> 입금을 부탁드립니다.</td>
    </tr>
    <tr>
      <th>가상계좌 입금기한</th>
      <td><?php echo date('Y-m-d H:i:s', $vbank_date); ?></td>
    </tr>
  <?php endif; ; ?>
  </tbody>
</table>