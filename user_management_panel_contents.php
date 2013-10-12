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

// --- build content data -----------------------------------------------------
//else 
$content = '';
if ($auth->userOn()) {
  $content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 16);

  $logbox = new LogBox($langs, $_SESSION["_lang"]);

  $content_log = $logbox->getLogBox($auth->getUserId(), 2, 1, 20);
  $content_log_paging = $pagingbox->getPagingBox($logbox->getRows());

  $smarty->assign("content_log", $content_log);
  $smarty->assign("content_log_paging", $content_log_paging);
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/user_management_panel_contents.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/user_management_panel_contents.smarty'.($stop-$start);

}
else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_NOT_LOGGED_IN');
}

  // include common header
include('inc/top.inc.php');



// content
echo $content;


// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>