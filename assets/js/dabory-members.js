(function ($) {

    function initContextLinks(elem) {

        var selected = $(elem).children('option:selected');
        var context_closed = $(elem).siblings('.context-closed');
        var context_opened = $(elem).siblings('.context-opened');

        if (selected) {

            var postId = selected.val();
            var postViewUrl = selected.data('url');

            if (!isNaN(postId) && typeof(postViewUrl) != 'undefined') {
                var a_edit = context_opened.children('a.context-edit');
                var a_view = context_opened.children('a.context-view');

                var postEditUrl = daboryMembers.editUrl + '?action=edit&post=' + postId;

                a_edit.attr('href', postEditUrl);
                a_view.attr('href', postViewUrl);

                context_opened.hide();
                context_closed.show();
            } else {
                context_opened.css('display', 'none');
                context_closed.hide();
            }
        }
    }

    function showContext(target) {
        var closed = $(target).parent();
        var opened = closed.siblings('.context-opened');
        closed.hide();
        opened.show();
    }

    function hideContext(target) {
        var opened = $(target).parent();
        var closed = opened.siblings('.context-closed');
        closed.show();
        opened.hide();
    }

    $('select.dabory-page-select').change(function () {
        initContextLinks(this);
    });

    $('.context-closed .arrow-right').mouseenter(function () {
        showContext(this);
        return false;
    });

    $('.context-opened .arrow-left').click(function () {
        hideContext(this);
        return false;
    });

    $('select').each(function (idx, elem) {
        initContextLinks(elem);
    });

})(jQuery);
