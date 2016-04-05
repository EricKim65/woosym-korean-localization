(function () {
    "use strict";

    var wcShortcodeManager = function (editor, url) {
        var wcDummyContent = 'Sample Content';
        var wcParagraphContent = '<p>Sample Content</p>';


        editor.addButton('dabory_members_shortcodes_button', function () {
            return {
                title: "다보리 멤버스 쇼트코드",
                text: "다보리 멤버스",
                type: 'menubutton',
                icons: false,
                menu: [
                    {
                        text: '다보리 멤버스 쇼트코드'
                    },
                    {
                        text: '탈퇴 폼',
                        menu: [
                            {
                                text: '탈퇴 폼 쇼트코드',
                                onclick: function () {
                                    editor.insertContent('[dabory-members withdrawal] 탈퇴 후에 출력될 메시지. [/dabory-members]');
                                }
                            }
                        ]
                    }
                ]
            }
        });
    };

    tinymce.PluginManager.add("dabory_members_shortcodes", wcShortcodeManager);
})();