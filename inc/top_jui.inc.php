<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" >
<html>
<head>
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

<?php
if (!isset($_SESSION['external_user'])) {
?>
<link rel="stylesheet" href="lib/style2.css" type="text/css" />
<link rel="stylesheet" href="javascripts/themes/redmond/jquery.ui.all.css">
<?php } else { ?>
<link rel="stylesheet" href="lib/style_<?php echo $_SESSION['external_user']['SOURCE']?>.css?sdfsfsfdsdsdswssdsde" type="text/css" />
<link rel="stylesheet" href="javascripts/themes/<?php echo $clients[$_SESSION['external_user']['SOURCE']]['external_source_jquery']?>/jquery.ui.all.css">
<?php } ?>

<link rel="shortcut icon" href="http://www.thesportcity.net/favicon.ico">

<script type="text/javascript" src="javascripts/jquery-1.6.2.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.core.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.widget.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.mouse.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.slider.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.button.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.datepicker.min.js"></script>
<script src="javascripts/ui/minified/jquery.ui.sortable.min.js"></script>
<script type="text/javascript" src="javascripts/jquery-ui-timepicker-addon.js"></script>
<script src="javascripts/ui/i18n/jquery.ui.datepicker-<?php echo $_SESSION['_lang']?>.js"></script>

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