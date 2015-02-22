var please_wait = null;
function makePOSTRequest(url, param) {
		//alert('queryString is ' + param);
	http_request = false;
	  	if (please_wait != null) {
		document.getElementById('searchDiv').innerHTML = please_wait;
	}
	  // Mozilla, Safari,...
	if (window.XMLHttpRequest) { 
		http_request = new XMLHttpRequest();
		if (http_request.overrideMimeType) {
		// set type accordingly to anticipated content type
		http_request.overrideMimeType('text/html');
		} //Now set ajax for IE below
	} else if (window.ActiveXObject) { 
		try {
		http_request = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				http_request = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e) {}
		}
	}
	if (!http_request) {
		alert('Cannot create XMLHTTP instance');
		return false;
	}
	http_request.onreadystatechange = alertContents;
	http_request.open('POST', url, true);
	//Need line below for POST
	http_request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	////////////////// these two lines below are optional
	//http_request.setRequestHeader("Content-length", parameters.length);
	//http_request.setRequestHeader("Connection", "close");
	http_request.send(param);
	//var empty = '';
	//param = empty;
	//alert('empty: ' + param);
}

function alertContents() {
	if (http_request.readyState == 4) {
		if (http_request.status == 200) {
			result = http_request.responseText;
			document.getElementById('searchDiv').innerHTML = result;
			//document.getElementById('searchDiv').style.display ="block";
			document.getElementById('searchDiv').style.position = "absolute";
		  	//document.getElementById('formDiv').style.display = "none";
			//document.getElementById('formDiv').style.zIndex = "1";
			
		} 
		else {
			http_request.responseText = "Ooops!! A broken link! Please contact the webmaster of this website ASAP and give him the following errorcode: " + http_request.status;
			alert('There was a problem with the request.');
		}
	}
}
function doDelReq(id) {
	//alert('delreq id is: ' + id);
	var deleteid = id.split(":");
	var delid = deleteid[0];
	//alert('delid is ' + delid);
	var modemmac = deleteid[1];
	//alert('modemmac is ' + modemmac);
	var netid = deleteid[2];
	//alert('netid is ' + netid);
	var param = "delid=" + encodeURI(delid) + "&modemmac=" + encodeURI(modemmac) + "&netid=" + encodeURI(netid);
	makePOSTRequest('emaildeletion.php', param);
	document.getElementById('searchDiv').style.display ="block";
}
function doDelete(id) {
	//alert('delete id is: ' + id);
	var deleteid = id.split(":");
	var delid = deleteid[0];
	//alert('delid is ' + delid);
	var modemmac = deleteid[1];
	//alert('modemmac is ' + modemmac);
	var netid = deleteid[2];
	//alert('netid is ' + netid);
	var param = "delid=" + encodeURI(delid) + "&modemmac=" + encodeURI(modemmac) + "&netid=" + encodeURI(netid);
	makePOSTRequest('deleterecord.php', param);
	document.getElementById('searchDiv').style.display ="block";
}

function set_loading_message(msg) {
	please_wait = msg;
}
set_loading_message('<center>Processing...<br /><br /><img src="images/loader.gif" /></center>');


