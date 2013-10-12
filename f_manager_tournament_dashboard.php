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
include('class/manager_tournamentbox.inc.php');
 $manager_tournamentbox = new ManagerTournamentBox($langs, $_SESSION["_lang"]);
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 4);
  $manager = new Manager();

  if ($auth->userOn()) 
    $manager_user = new ManagerUser($manager->mseason_id);

  $manager_filter_box = $managerbox->getManagerFilterBox($manager->mseason_id);
  $countdown = $countdownbox->getCountdownBox($manager->mseason_id);
  $sched = $managerbox->getTourSchedule();
  $tournaments = $manager_tournamentbox->getManagerTournamentSummaryBox();
  $active_tournaments = $manager_tournamentbox->getManagerActiveTournamentsBox();
  $tournaments_fixtures = $manager_tournamentbox->getManagerTournamentsFixturesBox();

  $smarty->assign("countdown", $countdown);
  $smarty->assign("sched", $sched);
  $smarty->assign("tournaments", $tournaments);
  $smarty->assign("active_tournaments", $active_tournaments);
  $smarty->assign("tournaments_fixtures", $tournaments_fixtures);
  $smarty->assign("manager_filter_box", $manager_filter_box);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_tournament_dashboard.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_tournament_dashboard.smarty'.($stop-$start);

  define("FANTASY_TOURNAMENT", 1);
  include('inc/top.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_manager_tournament_dashboard.inc.php');

// close connections
include('class/db_close.inc.php');
?>
