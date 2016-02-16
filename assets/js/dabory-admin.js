(function ($) {
    /* 결제대행업체 변수가 바뀔 경우 자동 submit */
    $('select#pg_agency').change(
        function () {
            $(this).closest('form').submit();
        }
    );
})(jQuery);
