(function ($) {
    $('input#checkbox-all').click(function () {
        var checked = $(this).is(':checked');
        $('input.checkbox-agreement').each(function (idx, val) {
            $(val).attr('checked', checked);
        });
    });
})(jQuery);
