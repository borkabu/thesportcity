<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" >
<html>
<head>
<title><?php echo !empty($html_page->page_title) ? $html_page->page_title. ' | ': ''; ?>TheSportCity.Net</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="admin@thesportcity.net" />
<meta name="description" content="<?php echo !empty($html_page->page_descr) ? $html_page->page_descr : "TheSportCity.Net is a game driven portal where community is encouraged to communicate and socialize through intelectual gaming experience." ?>" />
<meta name="keywords" content="Sport city, fantasy manager, role playing sport manager, basketball mind games, multilingual fantasy manager, game driven social network, sport social network, multilingual social network" />
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" /> 
<script type="text/javascript" src="ckeditor/ckeditor.js"></script>
<script language="javascript" type="text/javascript" src="javascripts/javascript.js"></script>
<script language="javascript" type="text/javascript" src="javascripts/ajax_common.js"></script>
<script language="javascript" type="text/javascript" src="javascripts/ajax_ss.js"></script>

<link rel="stylesheet" href="lib/style.css" type="text/css" />

<script type="text/javascript" src="javascripts/jquery-1.2.2.pack.js"></script>
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
<body topmargin="0" leftmargin="0" rightmargin="0" bottommargin="0" marginheight="0" marginwidth="0">
<script language="javascript" type="text/javascript" src="javascripts/wz_tooltip/wz_tooltip.js"></script>
<div id="main_container">
<div id="header" style="text-align:center">
<img src="./img/tsc_logo.gif?d">
</div>

<?php echo $menu->getMenu(scriptName($_SERVER["PHP_SELF"])) ?>
<div style="width:200px;float:left;">
<?php 
 echo $loginbox->getLoginBox($auth);
 echo $donationbox->getDonationBox();
 echo $networkbox->getNetworkBox();
 if (!isset($_GET['survey_id']))
   echo $surveybox->getSurveyQuestionBox();
?>
</div>
<div style="width:600px;float:left;">
<?php 
  if ( isset($errorbox1))
    echo $errorbox1;
?>
