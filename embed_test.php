var test = '<?php echo $_GET['test'];?>';

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

function submitRequest(method, url) {
//    document.getElementById(div_id).innerHTML = "<img src=\"../img/icons/ajax-loader.gif\" />";
    http.open(method, url, false);
    http.send(null);
    document.getElementById('test').innerHTML = http.responseText;
    
}


submitRequest('get', 'http://localhost/thesportcity/f_manager_season_widget.php');