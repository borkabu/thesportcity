<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" >
<html>
<head>
<!-- WG885aC -->
<title><?php echo !empty($html_page->page_title) ? $html_page->page_title. ' | ': ''; ?>TheSportCity.Net</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="author" content="admin@thesportcity.net" />
<meta name="description" content="<?php echo !empty($html_page->page_descr) ? $html_page->page_descr : "TheSportCity.Net is a game driven portal where community is encouraged to communicate and socialize through intelectual gaming experience." ?>" />
<meta name="keywords" content="Sport city, fantasy manager, basketball fantasy manager, football fantasy manager, tennis fantasy manager, role play sport manager, basketball mind games, multilingual fantasy manager, game driven social network, sport social network, multilingual social network" />
<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" /> 
<script type="text/javascript" src="ckeditor/ckeditor.js?x=y"></script>
<script language="javascript" type="text/javascript" src="javascripts/javascript.js?x=x"></script>
<script language="javascript" type="text/javascript" src="javascripts/ajax_common.js"></script>
<script language="javascript" type="text/javascript" src="javascripts/ajax_ss.js"></script>

<link rel="stylesheet" href="lib/style2.css" type="text/css" />
<link rel="stylesheet" href="lib/tabber.css" TYPE="text/css" MEDIA="screen" />
<link rel="shortcut icon" href="http://www.thesportcity.net/favicon.ico" />

<script type="text/javascript" src="javascripts/jquery-1.6.2.min.js"></script>

<script  type="text/javascript">
document.write('<style type="text/css">.tabber{display:none;}<\/style>');

var tabberOptions = {
  'manualStartup':true,
  'cookie':"tabber", /* Name to use for the cookie */

  'onLoad': function(argsObj)
  {
    var t = argsObj.tabber;
    var i;

    /* Optional: Add the id of the tabber to the cookie name to allow
       for multiple tabber interfaces on the site.  If you have
       multiple tabber interfaces (even on different pages) I suggest
       setting a unique id on each one, to avoid having the cookie set
       the wrong tab.
    */
    if (t.id) {
      t.cookie = t.id + t.cookie;
    }

    /* If a cookie was previously set, restore the active tab */
    i = parseInt(readCookie(t.cookie));
    if (isNaN(i)) { return; }
    t.tabShow(i);
  },

  'onClick':function(argsObj)
  {
    var c = argsObj.tabber.cookie;
    var i = argsObj.index;
    createCookie(c, i, 365);
  }
};

</script>
<script type="text/javascript" src="javascripts/tabber-minimized.js"></script>    
<script type="text/javascript" src="javascripts/jquery.jclock.js"></script>    
<script type='text/javascript' src='http://partner.googleadservices.com/gampad/google_service.js'>
</script>
<script type='text/javascript'>
GS_googleAddAdSenseService("ca-pub-7616887564121555");
GS_googleEnableAllServices();
</script>
<script type='text/javascript'>
GA_googleAddSlot("ca-pub-7616887564121555", "TheSportCity.Net_top_468x60");
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
<body >
<script language="javascript" type="text/javascript" src="javascripts/wz_tooltip/wz_tooltip.js"></script>
<div class="topimage">
<div class="main_container">
<div style="vertical-align:bottom">
<div style="float:left;width:250;">
<div class="logo">
<a href="index.php"><img src="./img/herbas.gif" border=0 /></a>
</div>
</div>
<div class="toptop"> <a href="shop.php"><?php echo $langs['LANG_MENU_SHOP'] ?></a> | <a href="contacts.php"><?php echo $langs['LANG_MENU_CONTACT'] ?></a> | <a href="about_us.php"><?php echo $langs['LANG_MENU_ABOUT_US'] ?></a></div>
<div style="clear:right;"></div>
<?php echo $menu->getMenu(scriptName($_SERVER["PHP_SELF"]), "left", 150) ?>
<div class="portlet" style="float:left;width:468px;height:60px;margin:10px">
<!-- TheSportCity.Net_top_468x60 -->
<script type='text/javascript'>
GA_googleFillSlot("TheSportCity.Net_top_468x60");
</script>
</div>
<?php echo $commmenu->getMenu(scriptName($_SERVER["PHP_SELF"]), "right", 120) ?>
<div style="clear:both;"></div>
</div>
</div>
</div>
<div class="middleimage">
<div class="main_container">
<div style="width:200px;float:left;">

<?php 
 echo $networkbox->getNetworkBox();
 if (!isset($_GET['survey_id']))
   echo $surveybox->getSurveyQuestionBox();
 echo $donationbox->getDonationBox();
?>
</div>
<div style="width:800px;float:left;">
<?php 
  if ( isset($errorbox1))
    echo $errorbox1;
?>
