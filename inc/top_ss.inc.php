<?php 
if ((isset($_SESSION['external_user']) && $_SESSION['external_user']['SOURCE'] != 'facebook')
   || ($_SERVER["HTTP_HOST"] != "www.thesportcity.net" && $_SERVER["HTTP_HOST"] != "thesportcity.net" && $_SERVER["HTTP_HOST"] != "localhost") && $_SERVER["HTTP_HOST"] != "127.0.0.1:8080") {
  include('inc/top_external.inc.php');
} else {

?>
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
<link rel="stylesheet" href="javascripts/themes/redmond/jquery.ui.all.css">
<link rel="shortcut icon" href="http://www.thesportcity.net/favicon.ico" />

<script type="text/javascript" src="javascripts/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="javascripts/jquery.cookie.js"></script>
<script src="javascripts/ui/minified/jquery.ui.core.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.widget.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.mouse.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.slider.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.position.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.button.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.datepicker.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.sortable.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.dialog.min.js"></script>
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
GA_googleAddSlot("ca-pub-7616887564121555", "TheSportCity.Net_160x600");
GA_googleAddSlot("ca-pub-7616887564121555", "TheSportCity.Net_728x90_2");
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

</script>
</head> 
<body >
 <div id="fb-root"></div>
      <script>
        window.fbAsyncInit = function() {
          FB.init({
            appId      : '477192532313402', // App ID
            channelUrl : '//www.thesportcity.net/channel.html', // Channel File
            status     : true, // check login status
            cookie     : true, // enable cookies to allow the server to access the session
            xfbml      : true  // parse XFBML
          });
          // Additional initialization code here
          FB.Event.subscribe('auth.login', function(response) {
            window.location.reload();
          });
        };
        // Load the SDK Asynchronously
        (function(d){
           var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
           if (d.getElementById(id)) {return;}
           js = d.createElement('script'); js.id = id; js.async = true;
           js.src = "//connect.facebook.net/en_US/all.js";
           ref.parentNode.insertBefore(js, ref);
         }(document));
      </script>

<script language="javascript" type="text/javascript" src="javascripts/wz_tooltip/wz_tooltip.js"></script>
<div class="topimage">
<div class="main_container">
<div style="vertical-align:bottom">
<div style="float:left;width:250;">
<div class="logo">
<a href="index.php"><img src="./img/herbas.gif" border=0 /></a>
</div>
</div>
<div class="toptop"> <a href="shop.php"><?php echo $langs['LANG_MENU_SHOP'] ?></a> | <a href="contacts.php"><?php echo $langs['LANG_MENU_CONTACT'] ?></a> | <a href="adverts.php"><?php echo $langs['LANG_MENU_ADVERTS'] ?></a> | <a href="about_us.php"><?php echo $langs['LANG_MENU_ABOUT_US'] ?></a></div>
<div style="clear:right;"></div>
<?php echo $menu->getMenu(scriptName($_SERVER["PHP_SELF"]), "left", 150, 1, -13, 23) ?>
<?php echo $loginbox->getLoginBox($auth); ?>
<?php if ((!$auth->userOn() && !isset($facebook_user)) || ($auth->userOn() && !isset($facebook_user))) { ?>
<!-- TheSportCity.Net_top_468x60 -->
<script type='text/javascript'>
GA_googleFillSlot("TheSportCity.Net_top_468x60");
</script>
<?php } ?> 

<?php echo $commmenu->getMenu(scriptName($_SERVER["PHP_SELF"]), "right", 120) ?>
<div style="clear:both;"></div>
</div>
</div>
</div>
<div class="middleimage">
<div class="main_container">
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
  else if (defined("SOLO_MANAGER"))
    echo $loginbox->getHeaderBox($langs['LANG_SOLO_MANAGER_U']);


  if (isset($submenu))
    echo $submenu;
  if ( isset($errorbox1))
    echo $errorbox1;

}   
?>