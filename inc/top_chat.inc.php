<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" >
<html>
<head>
<title>TheSportCity.Net Chat</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="author" content="admin@thesportcity.net">
<meta name="description" content="TheSportCity.Net is a game driven portal where community is encouraged to communicate and socialize through intelectual gaming experience.">
<meta name="keywords" content="Sport city, fantasy manager, role playing sport manager, basketball mind games, multilingual fantasy manager, game driven social network, sport social network, multilingual social network">
<meta http-equiv="X-UA-Compatible" content="IE=7" />

<link rel="stylesheet" href="lib/style2.css" type="text/css" />
<script type="text/javascript" src="javascripts/jquery-1.6.2.min.js"></script>

<?php 
  if (isset($chat)) {
    $chat->printJavascript();
    $chat->printStyle(); 
  }
?>

<script type='text/javascript' src='http://partner.googleadservices.com/gampad/google_service.js'>
</script>
<script type='text/javascript'>
GS_googleAddAdSenseService("ca-pub-7616887564121555");
GS_googleEnableAllServices();
</script>
<script type='text/javascript'>
GA_googleAddSlot("ca-pub-7616887564121555", "TheSportCity.Net_chat_728x90");
</script>
<script type='text/javascript'>
GA_googleFetchAds();
</script>
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-12618179-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</head> 
<body style="background-color:#fff">
<script language="javascript" type="text/javascript" src="javascripts/wz_tooltip/wz_tooltip.js"></script>
<div style="width:100%;float:left;text-align:left">

<div style="width:100%;float:left">
<?php 
  if ( isset($errorbox1))
    echo $errorbox1;
?>