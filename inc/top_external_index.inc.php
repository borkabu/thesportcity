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


<link rel="stylesheet" href="lib/style_<?php echo $_SESSION['external_user']['SOURCE']?>.css?sdfsfsfdsdsdswssdsde" type="text/css" />
<link rel="stylesheet" href="javascripts/themes/<?php echo $clients[$_SESSION['external_user']['SOURCE']]['external_source_jquery']?>/jquery.ui.all.css" />


<link rel="shortcut icon" href="http://www.thesportcity.net/favicon.ico" />

<script type="text/javascript" src="javascripts/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="javascripts/jquery.cookie.js"></script>
<script src="javascripts/ui/minified/jquery.ui.core.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.widget.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.mouse.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.slider.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.button.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.datepicker.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.sortable.min.js"></script>
<script type="text/javascript" src="javascripts/jquery.tinysort.min.js"></script>
<script type="text/javascript" src="javascripts/jquery-ui-timepicker-addon.js"></script>
<script src="javascripts/ui/i18n/jquery.ui.datepicker-<?php echo $_SESSION['_lang']?>.js"></script>

<script type='text/javascript' src='http://partner.googleadservices.com/gampad/google_service.js'>
</script>
<script type='text/javascript'>
GS_googleAddAdSenseService("ca-pub-7616887564121555");
GS_googleEnableAllServices();
</script>
<script type='text/javascript'>
GA_googleAddSlot("ca-pub-7616887564121555", "TheSportCity.Net_180x150");
GA_googleAddSlot("ca-pub-7616887564121555", "TheSportCity.Net_top_468x60");
GA_googleAddSlot("ca-pub-7616887564121555", "TheSportCity.Net_comment_468x60");
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

(function ($) {

  // Log all jQuery AJAX requests to Google Analytics
  $(document).ajaxSend(function(event, xhr, settings){
    if (typeof _gaq !== "undefined" && _gaq !== null) {
      _gaq.push(['_trackPageview', settings.url]);
    }
  });

})(jQuery);

<?php  
  if ($_SERVER["HTTP_HOST"] == "www.thesportcity.net" || $_SERVER["HTTP_HOST"] == "thesportcity.net") {
?>
 if (self.location.href == top.location.href)
   top.location.href="index.php?kill_external=y";
<?php  } ?>
</script>
</head> 
<body onload="iframeResizePipe()">
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1&appId=166385193421856";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>

<iframe id="helpframe" src='' height='0' width='0' frameborder='0'></iframe>
<script type="text/javascript">
  function iframeResizePipe()
  {
     // What's the page height?
     var height = document.body.scrollHeight;

     // Going to 'pipe' the data to the parent through the helpframe..
     var pipe = document.getElementById('helpframe');

     // Cachebuster a precaution here to stop browser caching interfering
     pipe.src = '<?php echo "http://".$_SESSION['external_user']['HOST'];?>/helper.html?height='+height+'&cacheb='+Math.random();

  }
</script>
                                    
<script language="javascript" type="text/javascript" src="javascripts/wz_tooltip/wz_tooltip.js"></script>
<div class="main_container">
<div style="vertical-align:bottom">
<div style="float:left;width:320px">
<div class="logo">
<img src="<?php echo $clients[$_SESSION['external_user']['SOURCE']]['logo']?>" align="left" height="100"/>
<a href="index.php"><img src="./img/design/eurofootballlt/herbas.gif" border=0 width="190"/></a>
<br>
<?php echo $external_menu->getMenu(scriptName($_SERVER["PHP_SELF"]), "left", 150, 2) ?>
</div>
</div>
<div class="toptop" style="float:right;margin-right:5px;"> <a href="shop.php"><?php echo $langs['LANG_MENU_SHOP'] ?></a> | <a href="contacts.php"><?php echo $langs['LANG_MENU_CONTACT'] ?></a> | <a href="about_us.php"><?php echo $langs['LANG_MENU_ABOUT_US'] ?></a></div>

<iframe id="keepsessionframe" src='external_keep_session.php' height='0' width='0' frameborder='0'></iframe>

<?php //echo $menu->getMenu(scriptName($_SERVER["PHP_SELF"]), "left", 150) ?>
<?php echo $loginbox->getLoginBox($auth, true); ?>
<div style="float:right;width:200px;margin-left:10px;padding:0px">
<?php echo $networkbox->getNetworkBox(); ?>
</div>
<!-- TheSportCity.Net_top_468x60 -->
<script type='text/javascript'>
GA_googleFillSlot("TheSportCity.Net_top_468x60");
</script>

<?php //echo $commmenu->getMenu(scriptName($_SERVER["PHP_SELF"]), "right", 120) ?>
<div style="clear:both;"></div>
</div>
</div>
<div class="middleimage">
<div class="main_container">
<div style="width:200px;float:left;">
<?php 
 if (!isset($_GET['survey_id']))
   echo $surveybox->getSurveyQuestionBox();
 echo $donationbox->getDonationBox();
 echo $wager_index_box;
 echo $clan;
 echo $clubs;
?>
</div>
<div style="width:800px;float:left;">
<?php 
  if (defined("FANTASY_MANAGER"))
    echo $loginbox->getHeaderBox($langs['LANG_FANTASY_MANAGER_U']);
  else if (defined("RVS_MANAGER"))
    echo $loginbox->getHeaderBox($langs['LANG_RVS_LEAGUES_U']);
  else if (defined("FANTASY_TOURNAMENT"))
    echo $loginbox->getHeaderBox($langs['LANG_TOURNAMENTS_U']);
  else if (defined("WAGER"))
    echo $loginbox->getHeaderBox($langs['LANG_WAGER_U']);
  else if (defined("ARRANGER"))
    echo $loginbox->getHeaderBox($langs['LANG_ARRANGER_U']);
  else if (defined("CLUBS"))
    echo $loginbox->getHeaderBox($langs['LANG_CLUBS_U']);
  else if (defined("CLANS"))
    echo $loginbox->getHeaderBox($langs['LANG_CLANS_U']);
  else if (defined("SURVEYS"))
    echo $loginbox->getHeaderBox($langs['LANG_SURVEYS_U']);


  if (isset($submenu))
    echo $submenu;
  if ( isset($errorbox1))
    echo $errorbox1;

?>