	if (document.getElementById("billing_postcode")) {  // If로 쪼개어 놓은 건 다 이유가 있슴.
		document.getElementById("billing_postcode").readOnly= true;
		document.getElementById("billing_address_1").readOnly=true;
		document.getElementById("billing_zipcode_button").onclick=function(){ 
			openDaumPostcode("billing");
		};
	}

	if (document.getElementById("shipping_postcode")) {
		document.getElementById("shipping_postcode").readOnly= true;
		document.getElementById("shipping_address_1").readOnly=true;
		document.getElementById("shipping_zipcode_button").onclick=function(){ 
			openDaumPostcode("shipping");
		};
	}

	 function openDaumPostcode(billship) {
        new daum.Postcode({
             oncomplete: function(data) {
                 document.getElementById(billship+"_postcode").value = data.postcode1+"-"+data.postcode2;
                 document.getElementById(billship+"_address_1").value = data.address1+" "+data.address2;
                 //document.getElementById("jibeon").value = data.relatedAddress;
                 document.getElementById(billship+"_address_2").focus();
             }
         }).open();
     }