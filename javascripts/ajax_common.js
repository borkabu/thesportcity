function createRequestObject() {
    var tmpXmlHttpObject;
    
    //depending on what the browser supports, use the right way to create the XMLHttpRequest object
    if (window.XMLHttpRequest) { 
        // Mozilla, Safari would use this method ...
        tmpXmlHttpObject = new XMLHttpRequest();
	
    } else if (window.ActiveXObject) { 
        // IE would use this method ...
	 try
	    {
	    tmpXmlHttpObject=new ActiveXObject("Msxml2.XMLHTTP");
	    }
	  catch (e)
	    {
	    tmpXmlHttpObject=new ActiveXObject("Microsoft.XMLHTTP");
	    }

    }
    
    return tmpXmlHttpObject;
}

//call the above function to create the XMLHttpRequest object
var http = createRequestObject();

function submitRequest(method, url, div_id, data) {
    _gaq.push(['_trackPageview', url]);
    document.getElementById(div_id).innerHTML = "<img src=\"../img/icons/ajax-loader.gif\" />";
    if (method == 'get') {
      http.open(method, url);
      http.onreadystatechange = function () { processResponse (div_id); };
      http.send(null);
    }
    else if (method == 'post') {
      http.open(method, url, true);
      http.onreadystatechange = function () { processResponse (div_id); };
      http.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");               
      http.send(data);
    }
}

function processResponse(div_id) {
    //check if the response has been received from the server
    if(http.readyState == 4){
	
        //read and assign the response from the server
        var response = http.responseText;
		
        //do additional parsing of the response, if needed
        //in this case simply assign the response to the contents of the <div> on the page. 
        document.getElementById(div_id).innerHTML = response;
		
        //If the server returned an error message like a 404 error, that message would be shown within the div tag!!. 
        //So it may be worth doing some basic error before setting the contents of the <div>
    }
}


function submitSingleRequestMultipleResponces(method, url, div_id, data) {
    _gaq.push(['_trackPageview', url]);
    try {
    document.getElementById(div_id).innerHTML = "<img src=\"./img/icons/ajax-loader.gif\" />";
    }
    catch (err) {
    }
    if (method == 'get') {
      http.open(method, url);
      http.onreadystatechange = function () { processResponseMultiple (); };
      http.send(null);
    }
    else if (method == 'post') {
      http.open(method, url, true);
      http.onreadystatechange = function () { processResponseMultiple (); };
      http.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");               
      http.send(data);
    }

}


function processResponseMultiple() {
    //check if the response has been received from the server
    if(http.readyState == 4){
	
        //read and assign the response from the server
        var response = http.responseText;
	var mySplitResponce = response.split("###");
        //do additional parsing of the response, if needed
        //in this case simply assign the response to the contents of the <div> on the page. 
	for (i = 0; i < mySplitResponce.length; i++) {
          var entry = mySplitResponce[i].split("@@@");
          if (entry.length == 2) 
            document.getElementById(entry[0]).innerHTML = entry[1];
          else {
            eval(entry[0]);
          }
        }
		
        //If the server returned an error message like a 404 error, that message would be shown within the div tag!!. 
        //So it may be worth doing some basic error before setting the contents of the <div>
    }
}


function parseForm(obj) {
      var getstr = "";
      var inputs = obj.getElementsByTagName("INPUT");
      for (i=0; i<inputs.length; i++) {
         if (inputs[i].tagName == "INPUT") {
            if (inputs[i].type == "text" || inputs[i].type == "hidden") {
               getstr += inputs[i].name + "=" + inputs[i].value + "&";
            }
            if (inputs[i].type == "checkbox") {
               if (inputs[i].checked) {
                  getstr += inputs[i].name + "=" + inputs[i].value + "&";
               } else {
                  getstr += inputs[i].name + "=&";
               }
            }
            if (inputs[i].type == "radio") {
               if (inputs[i].checked) {
                  getstr += inputs[i].name + "=" + inputs[i].value + "&";
               }
            }
         }   
/*         if (obj.childNodes[i].tagName == "SELECT") {
            var sel = obj.childNodes[i];
            getstr += sel.name + "=" + sel.options[sel.selectedIndex].value + "&";
         }*/
         
      }
      return getstr;
}


function valButton(form, btn_name) {
    var cnt = -1;
    var btn = form.getElementsByTagName("INPUT");

    for (var i=btn.length-1; i > -1; i--) {
      if (btn[i].type == "radio") {
        if (btn[i].checked) {cnt = i; i = -1;}
      }
    }

    if (cnt > -1) form.submit();
    else return null;
}