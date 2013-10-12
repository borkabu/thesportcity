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
  $content = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 6);
  $wager = new Wager();
  if ($auth->userOn())
    $wager_user = new WagerUser($wager->tseason_id);

  $wager_filter_box = $wagerbox->getWagerFilterBox($wager->tseason_id);
//  $countdown = $countdownbox->getCountdownBox($manager->mseason_id);
  if ($auth->userOn())  
    $sched = $wagerbox->getWagerGamesBox($wager->tseason_id, 1, 5);
  else 
    $sched = $wagerbox->getWagerGamesBox($wager->tseason_id, 1, 3);
  $openchallenges = $wagerbox->getWagerChallengesBox(1, 1, 4);
  if ($auth->userOn())  
    $mychallenges = $wagerbox->getWagerChallengesBox(2, 1, 4);
  else $mychallenges = "";
  $wager_season = $wagerbox->getWagerSeasonDashboardBox($wager->tseason_id);
  $wager_standings = $wagerbox->getWagerStandingsBox($wager->tseason_id);
  $prizes = nl2br($wager->season_info['PRIZES']);

//  $smarty->assign("countdown", $countdown);
  $smarty->assign("sched", $sched);
  $smarty->assign("openchallenges", $openchallenges);
  $smarty->assign("mychallenges", $mychallenges);
  $smarty->assign("prizes", $prizes);
  $smarty->assign("wager_season", $wager_season);
  $smarty->assign("wager_standings", $wager_standings);
  $smarty->assign("wager_filter_box", $wager_filter_box);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/wager_season_dashboard.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/wager_season_dashboard.smarty'.($stop-$start);

  define("WAGER", 1);
  include('inc/top.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_wager_season_dashboard.inc.php');

// close connections
include('class/db_close.inc.php');
?>