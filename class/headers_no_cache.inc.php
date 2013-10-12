<?php
/*
===============================================================================
headers.inc.php
-------------------------------------------------------------------------------
Sets proper HTTP headers
===============================================================================
*/

// set cache-control tu public
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");		        // expires in the past
  header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()+10) . " GMT");     // Last modified, right now
  header("Cache-Control: no-cache, must-revalidate, proxy-revalidate, max-age=0, s-maxage=0 ");	        // Prevent caching, HTTP/1.1
  header("Pragma: no-cache");		                        // Prevent caching, HTTP/1.0
  session_cache_limiter("nocache");

?>