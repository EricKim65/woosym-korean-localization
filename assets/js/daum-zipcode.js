if (document.getElementById("billing_postcode")) {  // If�� �ɰ��� ���� �� �� ������ �ֽ�.
    document.getElementById("billing_postcode").readOnly = true;
    document.getElementById("billing_address_1").readOnly = true;
    document.getElementById("billing_zipcode_button").onclick = function () {
        openDaumPostcode("billing");
    };
}

if (document.getElementById("shipping_postcode")) {
    document.getElementById("shipping_postcode").readOnly = true;
    document.getElementById("shipping_address_1").readOnly = true;
    document.getElementById("shipping_zipcode_button").onclick = function () {
        openDaumPostcode("shipping");
    };
}

function openDaumPostcode(billship) {
    new daum.Postcode({
        oncomplete: function (data) {
            if(data.userSelectedType == 'J') {
                document.getElementById(billship + "_postcode").value = data.postcode;
                document.getElementById(billship + "_address_1").value = data.jibunAddress;
            } else if(data.userSelectedType == 'R') {
                document.getElementById(billship + "_postcode").value = data.zonecode;
                document.getElementById(billship + "_address_1").value = data.roadAddress;
            }
            //document.getElementById("jibeon").value = data.relatedAddress;
            document.getElementById(billship + "_address_2").focus();
        }
    }).open();
}