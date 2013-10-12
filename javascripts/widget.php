var tsc_http_base = 'http://www.thesportcity.net/';
var tsc_stylesheet_url = 'http://www.thesportcity.net/lib/style_widget.css';
var tsc_url = '<?php echo $_GET['url'];?>';
var tsc_div_id = '<?php echo $_GET['div'];?>';

function tsc_createRequestObject() {
    var tmpXmlHttpObject;
    
    //depending on what the browser supports, use the right way to create the XMLHttpRequest object
    if (window.XDomainRequest) {
       tmpXmlHttpObject = new XDomainRequest();   

       tmpXmlHttpObject.onload = function() {
              document.getElementById(tsc_div_id).innerHTML = tsc_http.responseText;
       };

    } else if (window.XMLHttpRequest) { 
        // Mozilla, Safari would use this method ...
        tmpXmlHttpObject = new XMLHttpRequest();
        tmpXmlHttpObject.onreadystatechange = function () {
	      document.getElementById(tsc_div_id).innerHTML = tsc_http.responseText; 
	};
    } else if (window.ActiveXObject) { 
        // IE would use this method ...
	 try
	    {
	    tmpXmlHttpObject=new ActiveXObject("Msxml2.XMLHTTP");
        tmpXmlHttpObject.onreadystatechange = function () {
	      document.getElementById(tsc_div_id).innerHTML = tsc_http.responseText; 
	};

	    }
	  catch (e)
	    {
	    tmpXmlHttpObject=new ActiveXObject("Microsoft.XMLHTTP");
        tmpXmlHttpObject.onreadystatechange = function () {
	      document.getElementById(tsc_div_id).innerHTML = tsc_http.responseText; 
	};

	    }

    }
    
    return tmpXmlHttpObject;
}

//call the above function to create the XMLHttpRequest object
var tsc_http = tsc_createRequestObject();

function tsc_submitRequest(method, url) {
//    document.getElementById(div_id).innerHTML = "<img src=\"../img/icons/ajax-loader.gif\" />";
    tsc_http.open(method, url, true);
    tsc_http.send(null);
//    document.getElementById(tsc_div_id).innerHTML = tsc_http.responseText;
    
}


function tsc_requestStylesheet(stylesheet_url) {
  stylesheet = document.createElement("link");
  stylesheet.rel = "stylesheet";
  stylesheet.type = "text/css";
  stylesheet.href = stylesheet_url;
  stylesheet.media = "all";
  document.lastChild.firstChild.appendChild(stylesheet);
}


tsc_requestStylesheet(tsc_stylesheet_url);
tsc_submitRequest('get', tsc_url);
