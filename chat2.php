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
define("IGNORE_MOVE", 1);
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
  if (!isset($_GET['title']))
    $title = "TheSportCity.Net Chat";
  else $title = $_GET['title'];
  $smarty->assign("title", $title);
  $smarty->assign("room", urlencode($title));
  $smarty->assign("nick", $_SESSION['_user']['USER_NAME']);
  $smarty->assign("user_id", $auth->getUserId());

  $db->delete("chat_stats", "CHECKIN_TIME < DATE_ADD(NOW(), INTERVAL -5 MINUTE )");
  unset($sdata);
  $sdata['USER_ID'] = $auth->getUserId();
  $sdata['CHANNEL_NAME'] = "'".$title."'";
  $sdata['CHECKIN_TIME'] = "NOW()";
  $db->insert("chat_stats", $sdata);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/chat.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/chat.smarty'.($stop-$start);

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