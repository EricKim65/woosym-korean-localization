function toggle() {
	var nzele = document.getElementById("shownzcourierinfo");
	var nllele = document.getElementById("showpostnllinfo");
	var apc = document.getElementById("showapcovernight");
					
	if(apc.style.display == "none" && document.getElementById('_order_trackurl').value == 'APCOVERNIGHT') {
		apc.style.display = "block";
	}
	else {
		apc.style.display = "none";
	}	
	
	if(nzele.style.display == "none" && document.getElementById('_order_trackurl').value == 'NZCOURIERS') {
		nzele.style.display = "block";
	}
	else {
		nzele.style.display = "none";
	}
					
	if(nllele.style.display == "none" && document.getElementById('_order_trackurl').value == 'POSTNLL') {
		nllele.style.display = "block";
	}
	else {
		nllele.style.display = "none";
	}			
}
	
function toggle1() {	
	var nzele1 = document.getElementById("shownzcourierinfo1");
	var nllele1 = document.getElementById("showpostnllinfo1");
	var apc1 = document.getElementById("showapcovernight1");
					
	if(apc1.style.display == "none" && document.getElementById('_order_trackurl1').value == 'APCOVERNIGHT') {
		apc1.style.display = "block";
	}
	else {
		apc1.style.display = "none";
	}	
		
	if(nzele1.style.display == "none" && document.getElementById('_order_trackurl1').value == 'NZCOURIERS') {
		nzele1.style.display = "block";
	}
	else {
		nzele1.style.display = "none";
	}
					
	if(nllele1.style.display == "none" && document.getElementById('_order_trackurl1').value == 'POSTNLL') {
		nllele1.style.display = "block";
	}
	else {
		nllele1.style.display = "none";
	}
				
}
	
function toggle2() {
	var nzele2 = document.getElementById("shownzcourierinfo2");
	var nllele2 = document.getElementById("showpostnllinfo2");
	var apc2 = document.getElementById("showapcovernigh2t");
					
	if(apc2.style.display == "none" && document.getElementById('_order_trackurl2').value == 'APCOVERNIGHT') {
		apc2.style.display = "block";
	}
	else {
		apc2.style.display = "none";
	}	
	
	if(nzele2.style.display == "none" && document.getElementById('_order_trackurl2').value == 'NZCOURIERS') {
		nzele2.style.display = "block";
	}
	else {
		nzele2.style.display = "none";
	}
					
	if(nllele2.style.display == "none" && document.getElementById('_order_trackurl2').value == 'POSTNLL') {
		nllele2.style.display = "block";
	}
	else {
		nllele2.style.display = "none";
	}
				
}
	
function toggle3() {
	
	var nzele3 = document.getElementById("shownzcourierinfo3");
	var nllele3 = document.getElementById("showpostnllinfo3");
	var apc3 = document.getElementById("showapcovernight3");
					
	if(apc3.style.display == "none" && document.getElementById('_order_trackurl3').value == 'APCOVERNIGHT') {
		apc3.style.display = "block";
	}
	else {
		apc3.style.display = "none";
	}	
	
	if(nzele3.style.display == "none" && document.getElementById('_order_trackurl3').value == 'NZCOURIERS') {
		nzele3.style.display = "block";
	}
	else {
		nzele3.style.display = "none";
	}
					
	if(nllele3.style.display == "none" && document.getElementById('_order_trackurl3').value == 'POSTNLL') {
		nllele3.style.display = "block";
	}
	else {
		nllele3.style.display = "none";
	}
				
}
	
function toggle4() {
	var nzele4 = document.getElementById("shownzcourierinfo4");
	var nllele4 = document.getElementById("showpostnllinfo4");
	var apc4 = document.getElementById("showapcovernight4");
					
	if(apc4.style.display == "none" && document.getElementById('_order_trackurl4').value == 'APCOVERNIGHT') {
		apc4.style.display = "block";
	}
	else {
		apc4.style.display = "none";
	}	
	
	
	if(nzele4.style.display == "none" && document.getElementById('_order_trackurl4').value == 'NZCOURIERS') {
		nzele4.style.display = "block";
	}
	else {
		nzele4.style.display = "none";
	}
					
	if(nllele4.style.display == "none" && document.getElementById('_order_trackurl4').value == 'POSTNLL') {
		nllele4.style.display = "block";
	}
	else {
		nllele4.style.display = "none";
	}
}


function sdetails1display() {
	document.getElementById('sdetails1').style.display = "block";
	document.getElementById('add1').style.visibility = "hidden";	
}
function sdetails1remove() {
	document.getElementById('sdetails1').style.display = "none";
	document.getElementById('add1').style.visibility = "visible";	
}
function sdetails2display() {
	document.getElementById('sdetails2').style.display = "block";
	document.getElementById('remove1').style.visibility = "hidden";	
	document.getElementById('add2').style.visibility = "hidden";	
}
function sdetails2remove() {
	document.getElementById('sdetails2').style.display = "none";
	document.getElementById('remove1').style.visibility = "visible";
	document.getElementById('add2').style.visibility = "visible";	
}
function sdetails3display() {
	document.getElementById('sdetails3').style.display = "block";	
	document.getElementById('remove2').style.visibility = "hidden";	
	document.getElementById('add3').style.visibility = "hidden";	
}
function sdetails3remove() {
	document.getElementById('sdetails3').style.display = "none";
	document.getElementById('remove2').style.visibility = "visible";
	document.getElementById('add3').style.visibility = "visible";		
}
function sdetails4display() {
	document.getElementById('sdetails4').style.display = "block";
	document.getElementById('remove3').style.visibility = "hidden";	
	document.getElementById('add4').style.visibility = "hidden";	
}
function sdetails4remove() {
	document.getElementById('sdetails4').style.display = "none";
	document.getElementById('remove3').style.visibility = "visible";
	document.getElementById('add4').style.visibility = "visible";		
}