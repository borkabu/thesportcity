<?php
//return '';

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

// user session
include('class/ss_const.inc.php');

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/manager.inc.php');
include('class/manager_tournament.inc.php');
include('class/manager_tournamentbox.inc.php');
 $manager_tournamentbox = new ManagerTournamentBox($langs, $_SESSION["_lang"]);

// --- build content data -----------------------------------------------------

  $content = '';
  $content = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 4);

  $manager_tournament = new ManagerTournament();
  $manager = new Manager($manager_tournament->mseason_id);
  $data['MANAGER_TOURNAMENT_FILTER_BOX'] = $manager_tournamentbox->getManagerTournamentFilterBox($manager_tournament->getSeason());

  $manager_logbox = new LogBox($langs, $_SESSION["_lang"]);
  $manager_tournament_log = $manager_logbox->getManagerTournamentLogBox($manager_tournament->getSeason(), isset($_GET['page']) ? $_GET['page'] : 1);
  $manager_tournament_log_paging = $pagingbox->getPagingBox($manager_logbox->getRows(), isset($_GET['page']) ? $_GET['page'] : 1);


  $smarty->assign("manager_tournament_log", $manager_tournament_log);
  $smarty->assign("manager_tournament_log_paging", $manager_tournament_log_paging);
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_tournament_log.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_tournament_log.smarty'.($stop-$start);

// ----------------------------------------------------------------------------

// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');

?>