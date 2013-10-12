<?php
ini_set('display_errors', 1);
error_reporting (E_ERROR);
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
$permissions = new ForumPermission();
if ($auth->userOn() && $permissions->canChat()) {
  $params = array();
  $title = "TheSportCity.Net Chat";
  $smarty->assign("title", $title);
  $smarty->assign("room", urlencode($title));
  $smarty->assign("nick", $_SESSION['_user']['USER_NAME']);
  $smarty->assign("user_id", $auth->getUserId());

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/chat_irc.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/chat_irc.smarty'.($stop-$start);

  //$db->showquery=true;
}
else {
  $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
  $errorbox1 = $errorbox->getErrorBox('ERROR_NOT_LOGGED_IN');
}

  // include common header
include('inc/top_chat.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_chat.inc.php');

include('class/db_close.inc.php');
?>