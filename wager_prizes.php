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
// ----------------------------------------------------------------------------

// include common header
  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 6);

  $wager = new wager();
  $wagerbox = new wagerBox($langs, $_SESSION["_lang"]);

  if ($auth->userOn())
    $wager_user = new WagerUser($wager->tseason_id);

  $wager_filter_box = $wagerbox->getWagerFilterBox($wager->getSeason());

  $smarty->assign("manager_filter_box", $wager_filter_box);
  $smarty->assign("prizes", $wager->getPrizes());
  $smarty->assign("season_title", $wager->getTitle());
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/wager_prizes.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/wager_prizes.smarty'.($stop-$start);


  define("WAGER", 1);
  include('inc/top.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_wager.inc.php');

// close connections
include('class/db_close.inc.php');
?>