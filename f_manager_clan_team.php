<?php
/*
===============================================================================
toto.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows totalizator result archive

TABLES USED: 
  - BASKET.totalizators
  - BASKET.totalizator_votes
  - BASKET.users
  - BASKET.games

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
$content = '';
if (isset($_GET['team_id']) && isset($_GET['clan_id'])) {
//$db->showquery=true;
  $manager = new Manager();

  $db->select("clan_teams", "*", "CLAN_ID=".$_GET['clan_id']." and TEAM_ID=".$_GET['team_id']);
  if ($row = $db->nextRow()) {
    $season= $row['SEASON_ID']; 
    $team_name= $row['TEAM_NAME']; 
  }

  $clan = new Clan($_GET['clan_id']);

  $last_tour = $manager->getLastTour();
  $team = $clan->getClanTeam($_GET['team_id']);
  $clan->getClanMembersStandings($season, $team['ACTIVE_MEMBERS'], $last_tour, true);
  $smarty->assign("team", $team);
  $smarty->assign("team_name", $team_name);
}

$content = $smarty->fetch('smarty_tpl/f_manager_clan_team.smarty');    
// ----------------------------------------------------------------------------

// content
// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

include('inc/bot_small.inc.php');
// close connections
include('class/db_close.inc.php');

?>