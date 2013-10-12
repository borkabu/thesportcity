<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
index.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - first page of a portal

TABLES USED: 
  - BASKET.NEWS
  - BASKET.EVENTS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/update.inc.php');
// classes
include('class/db.class.php');
include('class/template.class.php');
include('class/language.class.php');
include('class/form.class.php');
// connections
include('class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

// user session
include('class/ss_const.inc.php');

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/newsletter.inc.php');
// --- build content data -----------------------------------------------------
//else 
if ($auth->userOn()) {

  $newsletter = new Newsletter();
  $html = $newsletter->getHtmlNewsletter($_GET['id']);
   echo str_replace("<head>", '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />', $html);


}
else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_NOT_LOGGED_IN');

	include('inc/top.inc.php');
	include('inc/bot.inc.php');
}

include('class/db_close.inc.php');
?>