function TSC_Widget(name) {  

    {   
         this.name = name;
    }

  this.createRequestObject = function (url, div_id) {
    var tmpXmlHttpObject;
     
    //depending on what the browser supports, use the right way to create the XMLHttpRequest object
    if (window.XDomainRequest) {
       tmpXmlHttpObject = new XDomainRequest();   

       tmpXmlHttpObject.onload = function() { 
            _callBackFunction2(tmpXmlHttpObject, div_id); 
       };

    } else if (window.XMLHttpRequest) { 
        // Mozilla, Safari would use this method ...
        tmpXmlHttpObject = new XMLHttpRequest();
        tmpXmlHttpObject.onreadystatechange = function() { 
            _callBackFunction(tmpXmlHttpObject, div_id); 
        };
    } else if (window.ActiveXObject) { 
        // IE would use this method ...
	 try
	    {
	    tmpXmlHttpObject=new ActiveXObject("Msxml2.XMLHTTP");
	    tmpXmlHttpObject.onreadystatechange = function () {
	      _callBackFunction(tmpXmlHttpObject, div_id); 
	    };

         }
         catch (e) {
	    tmpXmlHttpObject=new ActiveXObject("Microsoft.XMLHTTP");
            tmpXmlHttpObject.onreadystatechange = function () {
	      _callBackFunction(tmpXmlHttpObject, div_id); 
   	    };
         }

    }
    tmpXmlHttpObject.open('get', url, true);
    tmpXmlHttpObject.send(null);

    return tmpXmlHttpObject;
  }

this.getWidget = function(surl, url, div_id) {
  this.requestStylesheet(surl);
  this.createRequestObject(url, div_id);
}

this.requestStylesheet = function(stylesheet_url) {
  stylesheet = document.createElement("link");
  stylesheet.rel = "stylesheet";
  stylesheet.type = "text/css";
  stylesheet.href = stylesheet_url;
  stylesheet.media = "all";
  document.lastChild.firstChild.appendChild(stylesheet);
}


    _callBackFunction = function (http_request, div_id) {
        if (http_request.readyState == 4) {
            if (http_request.status == 200) {
                //alert(http_request.responseText);
	        document.getElementById(div_id).innerHTML = http_request.responseText; 
            } else {
                //alert('ERROR: AJAX request status = ' + http_request.status);
            }
        }
     }

    _callBackFunction2 = function (http_request, div_id) {
                //alert(1);
	        document.getElementById(div_id).innerHTML = http_request.responseText; 
     }


function updateDiv(div_id) {
   document.getElementById(div_id).innerHTML = this.http.responseText; 
};
}