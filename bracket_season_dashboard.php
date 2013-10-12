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
include('class/bracket.inc.php');
include('class/bracket_user.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 7);
  $bracket = new Bracket();
  if ($auth->userOn())
    $bracket_user = new BracketUser($bracket->tseason_id);

  $bracket_filter_box = $bracketbox->getbracketFilterBox($bracket->getSeason());
  $countdown = $countdownbox->getBracketCountdownBox($bracket->tseason_id);
  $sched = $bracketbox->getTourSchedule();
  $bracket_season = $bracketbox->getBracketSeasonDashboardBox($bracket->tseason_id);
  $bracket_standings = $bracketbox->getBracketStandingsBox($bracket->tseason_id);
  $prizes = nl2br($bracket->season_info['PRIZES']);
  $participation = $bracketbox->getBracketParticipationBox();
//  $market_stats = $managerbox->getMarketStatsBox($manager->mseason_id);


  $smarty->assign("countdown", $countdown);
  $smarty->assign("sched", $sched);
  $smarty->assign("prizes", $prizes);
//  $smarty->assign("reports", $reports);
//  $smarty->assign("market_stats", $market_stats);
  $smarty->assign("bracket_season", $bracket_season);
  $smarty->assign("bracket_standings", $bracket_standings );
  $smarty->assign("bracket_filter_box", $bracket_filter_box);
  $smarty->assign("participation", $participation);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/bracket_season_dashboard.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/bracket_season_dashboard.smarty'.($stop-$start);

  define("ARRANGER", 1);
  include('inc/top.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_arranger_season_dashboard.inc.php');

// close connections
include('class/db_close.inc.php');
?>