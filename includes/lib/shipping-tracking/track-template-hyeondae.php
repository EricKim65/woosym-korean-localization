<?php
/**
 * @var string $url
 * @var string $number
 */
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
</head>
<body>
<form action="https://www.hlc.co.kr/home/personal/inquiry/track" id="form" method="post">
  <input type="hidden" name="InvNo" value="<?php echo $number; ?>"/>
  <input type="hidden" name="action" value="processInvoiceSubmit"/>
</form>
<script type="text/javascript">
  document.getElementById('form').submit();
</script>
</body>
</html>
