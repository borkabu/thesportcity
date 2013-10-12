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
include('class/manager.inc.php');
include('class/manager_user.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);
  $manager = new Manager();
  if ($auth->userOn())
    $manager_user = new ManagerUser($manager->mseason_id);

  $manager_filter_box = $managerbox->getManagerFilterBox($manager->mseason_id);
  $countdown = $countdownbox->getCountdownBox($manager->mseason_id);
  $sched = $managerbox->getTourSchedule();
  $manager_season = $managerbox->getManagerSeasonDashboardBox($manager->mseason_id);
  $manager_standings = $managerbox->getManagerStandingsBox($manager->mseason_id);
  $manager_standings_clans = $managerbox->getManagerStandingsClansBox($manager->mseason_id);
  $prizes = nl2br($manager->season_info['PRIZES']);
  $market_stats = $managerbox->getMarketStatsBox($manager->mseason_id);
  $participation = $managerbox->getManagerParticipationBox();

  include("class/manager_reports.inc.php");
  include('class/manager_reportsbox.inc.php');
  $manager_reportsbox = new ManagerReportsBox($langs, $_SESSION['_lang']);

  $reports = $manager_reportsbox->getManagerReportsBox(5, $manager->mseason_id);

  $smarty->assign("countdown", $countdown);
  $smarty->assign("sched", $sched);
  $smarty->assign("prizes", $prizes);
  $smarty->assign("reports", $reports);
  $smarty->assign("market_stats", $market_stats);
  $smarty->assign("manager_season", $manager_season);
  $smarty->assign("manager_standings", $manager_standings);
  $smarty->assign("manager_standings_clans", $manager_standings_clans);
  $smarty->assign("manager_filter_box", $manager_filter_box);
  $smarty->assign("participation", $participation);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_season_dashboard.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_season_dashboard.smarty'.($stop-$start);

  define("FANTASY_MANAGER", 1);
  include('inc/top.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_manager_season_dashboard.inc.php');

// close connections
include('class/db_close.inc.php');
?>