(function ($) {

    var self = this;

    $.fn.exists = function () {
        return this.length > 0;
    };

    this.pickInput = function (candidateNames) {
        var widget;
        for (var i = 0; i < candidateNames.length; ++i) {
            widget = self.form.find('input[name="' + candidateNames[i] + '"]');
            if (widget.exists()) {
                return widget;
            }
        }
        return null;
    };

    this.form = $('form[name="form"]');

    this.onPostcodeButton = function () {

        var postcode = self.pickInput(['zip', 'postcode']);
        var address1 = self.pickInput(['addr1', 'billing_address1']);
        var address2 = self.pickInput(['addr2', 'billing_address2']);

        if (postcode && address1 && address2) {
            new daum.Postcode({
                oncomplete: function (data) {
                    if (data.userSelectedType == 'J') { // 지번 주소
                        postcode.val(data.postcode);
                        address1.val(data.jibunAddress);
                    } else if (data.userSelectedType == 'R') { // 도로명 주소
                        postcode.val(data.zonecode);
                        address1.val(data.roadAddress);
                    }
                    address2.focus();
                }
            }).open();
        } else {
            var widget;
            if (!postcode) {
                widget = '우편번호';
            } else if (!address1) {
                widget = '주소 1';
            } else if (!address2) {
                widget = '주소 2';
            }
            alert('"' + widget + '" 입력 상자의 설정이 잘못되었습니다.');
        }
    };

    $('#dabory-postcode-button').click(this.onPostcodeButton);

})(jQuery);