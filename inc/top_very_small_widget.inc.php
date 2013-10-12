<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" >
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php if (isset($_GET['source'])) { ?>
<link rel="stylesheet" href="lib/style_<?php echo $_GET['source']?>.css?sdfsfsfdsdsdswssdsde" type="text/css" />
<?php } else { ?>
<link rel="stylesheet" href="lib/style_widget.css" type="text/css" />
<?php } ?>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-12618179-1']);
  _gaq.push(['_trackPageview']);

/*  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();*/

</script>
<base target="_parent" />
</head> 
<body onload="iframeResizePipe()" topmargin="0" leftmargin="0" rightmargin="0" bottommargin="0" marginheight="0" marginwidth="0" style="text-align: left;">

<iframe id="helpframe" src='' height='0' width='0' frameborder='0'></iframe>
<script type="text/javascript">
  function iframeResizePipe()
  {
     // What's the page height?
     var height = document.body.scrollHeight;

     // Going to 'pipe' the data to the parent through the helpframe..
     var pipe = document.getElementById('helpframe');

     // Cachebuster a precaution here to stop browser caching interfering
     pipe.src = '<?php echo "http://".$_GET['host'];?>/helper.html?height='+height+'&cacheb='+Math.random();

  }
</script>
