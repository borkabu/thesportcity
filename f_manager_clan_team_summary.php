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
  // get all clan members
  $sql = "SELECT CM.USER_ID FROM clan_members CM , manager_users MU
		where CM.clan_id=".$_GET['clan_id']."
			and CM.status in (1,2,4)
			and MU.USER_ID=CM.USER_ID
			and (CM.DATE_LEFT = '0000-00-00 00:00:00' 
				OR (CM.DATE_LEFT > '".$manager->season_info['START_DATE']."' 
					AND CM.DATE_JOINED < '".$manager->season_info['END_DATE']."'))
			and MU.SEASON_ID=".$season;
//echo $sql;
  $db->query($sql);
  $clan_members = "";
  $clan_members_ar = array();
  $pre = "";
  while ($row = $db->nextRow()) {
    $clan_members .= $pre.$row['USER_ID'];
    $pre = ",";
    $clan_members_ar[] = $row['USER_ID'];
  }

  // get all clan tours
  $sql = "SELECT distinct MCTT.TOUR_ID FROM manager_clan_teams_tours MCTT 
		where MCTT.team_id=".$_GET['team_id']." ORDER BY MCTT.TOUR_ID";
  $db->query($sql);
  $clan_team_tours = "";
  $clan_team_tours_ar = array();
  $pre = "";
  while ($row = $db->nextRow()) {
    $clan_team_tours .= $pre.$row['TOUR_ID'];
    $pre = ",";
    $clan_team_tours_ar[] = $row['TOUR_ID'];
  }

  $clan_team_members = "";

  $sql = "select U.USER_ID, U.USER_NAME, MUT.TOUR_ID, MUT.POINTS, A.USER_ID as IN_TEAM, MS.WEALTH AS MONEY 
		from manager_users MU,  users U, manager_users_tours MUT
		    LEFT JOIN (SELECT CTM.USER_ID, MT.NUMBER 
		    FROM clan_team_members CTM, manager_tours MT
			 WHERE 
			   ((MT.START_DATE > CTM.DATE_JOINED AND MT.END_DATE < CTM.DATE_LEFT) OR
			   (MT.START_DATE > CTM.DATE_JOINED AND CTM.DATE_LEFT = '0000-00-00 00:00:00'))
			   AND MT.SEASON_ID=".$season."
			    AND MT.NUMBER IN (".$clan_team_tours.")
			    AND CTM.TEAM_ID=".$_GET['team_id'].")  A ON A.USER_ID=MUT.USER_ID and A.NUMBER=MUT.TOUR_ID
                    LEFT JOIN manager_standings MS ON MS.USER_ID=MUT.USER_ID AND MS.MSEASON_ID=".$season."
	WHERE MU.USER_ID IN (".$clan_members.")
		AND MU.SEASON_ID=".$season."
		and U.USER_ID = MU.USER_ID
		and MUT.USER_ID=MU.USER_ID
		and MUT.SEASON_ID=MU.SEASON_ID
		and MUT.TOUR_ID IN (".$clan_team_tours.")";

  $db->query($sql);
//  echo $sql;
  $summary = array();
  $contribution = array();
  $total_points = 0;
  while ($row = $db->nextRow()) {
    $summary[$row['USER_ID']]['USER_NAME'] = $row['USER_NAME'];
    $summary[$row['USER_ID']]['MONEY'] = $row['MONEY'];
    $summary[$row['USER_ID']][$row['TOUR_ID']] = $row;
    if ($row['IN_TEAM'] > 0) {
      $contribution[$row['USER_ID']]['POINTS'] += $row['POINTS'];
      $total_points += $row['POINTS'];
    }
  }
 
  foreach ($contribution as &$contr) {
    $contr['PERCENT'] = round($contr['POINTS']*100/$total_points,2);
  }

  $smarty->assign("total_points", $total_points);
  $smarty->assign("summary", $summary);
  $smarty->assign("contribution", $contribution);
  $smarty->assign("team_name", $team_name);
  $smarty->assign("tours", $clan_team_tours_ar);
  $smarty->assign("users", $clan_members_ar);
}

$content = $smarty->fetch('smarty_tpl/f_manager_clan_team_summary.smarty');    
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