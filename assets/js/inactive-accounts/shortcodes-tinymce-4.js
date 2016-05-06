(function () {
    var wcShortcodeManager = function (editor, url) {

        editor.addButton('inactive_accounts_shortcodes_button', function () {
            return {
                title: "휴면계정 관리 쇼트코드",
                text: "휴면계정",
                type: 'menubutton',
                icons: false,
                menu: [
                    {
                        text: '쇼트코드 삽입',
                        menu: [
                            {
                                text: '사이트 이름',
                                onclick: function () {
                                    editor.insertContent('[site_name]');
                                }
                            },

                            {
                                text: '사용자 이름',
                                onclick: function () {
                                    editor.insertContent('[user_login]');
                                }
                            },
                            {
                                text: '오늘 날짜',
                                onclick: function () {
                                    editor.insertContent('[today]');
                                }
                            },
                            {
                                text: '휴면 기간(일)',
                                onclick: function () {
                                    editor.insertContent('[active_span]');
                                }
                            },
                            {
                                text: '휴면 전환일',
                                onclick: function () {
                                    editor.insertContent('[deactivation_date]');
                                }
                            }
                        ]
                    }
                ]
            }
        });
    };

    tinymce.PluginManager.add("inactive_accounts_shortcodes", wcShortcodeManager);
})();