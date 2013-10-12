<?php
/*
===============================================================================
help.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows help item
  = shows help topic index

TABLES USED: 
  - BASKET.HELP

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
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

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/wager.inc.php');
include('class/wager_user.inc.php');
// --- build content data -----------------------------------------------------

// include common header
$content = '';

  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 6);

  $wager = new Wager();
  $wagerbox = new WagerBox($langs, $_SESSION["_lang"]);
  if ($auth->userOn())
    $wager_user = new WagerUser($wager->tseason_id);
  else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_WAGER_LOGIN');
  } 

  $wager_filter_box = $wagerbox->getWagerFilterBox($wager->getSeason());
  $challenges = $wager->getChallenges();
  $completed_challenges = $wager->getChallenges(2);
  if (isset($wager_user))
    $mychallenges = $wager_user->getChallenges();
  $games = $wager->getGamesChallenges();

  $smarty->assign("wager_filter_box", $wager_filter_box);
  if (count($challenges) > 0) {
    $smarty->assign("challenges", $challenges);
  }
  $smarty->assign("completed_challenges", $completed_challenges);
  if ($auth->userOn()) {
    $smarty->assign("my_challenges", $mychallenges);
    $smarty->assign("games", $games);
  }
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/wager_challenges.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/wager_challenges.smarty'.($stop-$start);
  
// ----------------------------------------------------------------------------

  define("WAGER", 1);
//include('inc/top.inc.php');
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_wager.inc.php');

// close connections
include('class/db_close.inc.php');
?>