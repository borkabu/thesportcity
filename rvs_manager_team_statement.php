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
//$db->showquery=true;
//$tpl->setCacheTtl(60);

  $content = '';

if (isset($_GET['league_id'])) {
//$db->showquery=true;
  $manager = new Manager('', 'rvs');

  if (isset($_GET['league_id'])) {
    $pleague = new League("rvs_manager", $_GET['league_id']);
    $pleague->getLeagueInfo();
  }

  if ($pleague->league_info['LEAGUE_TYPE'] == 0 || $pleague->league_info['LEAGUE_TYPE'] == 1) {
    $sql = "SELECT DISTINCT MUT.TOUR_ID
  	          FROM rvs_manager_users_tours MUT
  	        WHERE MUT.LEAGUE_ID=".$_GET['league_id']." 
		     AND PLACE > 0
            ORDER BY MUT.TOUR_ID";
  } else {
    $sql = "SELECT DISTINCT MUT.TOUR_ID
  	          FROM rvs_manager_users_tours_categories MUT
  	        WHERE MUT.LEAGUE_ID=".$_GET['league_id']." 
		     AND PLACE > 0
            ORDER BY MUT.TOUR_ID";
  } 
  $db->query($sql);   
  $tours = array();
  while ($row = $db->nextRow()) {
    unset($tour);
    $state = 'NORMAL'; 
    $tour[$state] = $row;
    $tour[$state]['NUMBER'] = $row['TOUR_ID'];
    $tour[$state]['LEAGUE_ID'] = $_GET['league_id'];
    $tours[] = $tour;
  }

    if (isset($_GET['tour_id']))
      $tour_id = $_GET['tour_id'];
    else {
      $item = end($tours);
      $tour_id = $item['NORMAL']['NUMBER'];
    }

   $sql = "SELECT * FROM manager_tours MTR
		WHERE MTR.SEASON_ID=" . $pleague->league_info['SEASON_ID'] . "
		      AND MTR.NUMBER=" . $tour_id;
	$db->query ( $sql );
        $row = $db->nextRow ();
        $tour_start_date = $row['START_DATE'];
        $tour_end_date = $row['END_DATE'];


      $points = "MPS.TOTAL_POINTS";
      if ($pleague->league_info['LEAGUE_TYPE'] == 1)
        $points = "MPS.SCORE as TOTAL_POINTS";

     $sql="SELECT DISTINCT U.USER_NAME, MM.LAST_NAME, MM.FIRST_NAME, MM.TEAM_TYPE, MM.TEAM_NAME2, CD.COUNTRY_NAME, ".$points.", RMTT.LEAGUE_ID IS_IN_TOUR
          FROM users U, rvs_manager_teams MT
                LEFT JOIN manager_market MM ON MT.PLAYER_ID = MM.USER_ID AND MM.SEASON_ID=".$manager->mseason_id."
                LEFT JOIN manager_player_stats MPS on MT.PLAYER_ID = MPS.PLAYER_ID AND MPS.TOUR_ID=".$tour_id." AND MPS.SEASON_ID=".$manager->mseason_id."
                LEFT JOIN rvs_manager_teams_tours RMTT on MT.PLAYER_ID = RMTT.PLAYER_ID AND RMTT.TOUR_ID=".$tour_id." AND RMTT.LEAGUE_ID=MT.LEAGUE_ID and RMTT.USER_ID=MT.USER_ID
	      , teams T 
			LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."

         WHERE MT.USER_ID=".$auth->getUserId()." 
	       AND U.USER_ID=MT.USER_ID
               AND '".$tour_start_date."' > MT.BUYING_DATE 
	       AND ('".$tour_end_date."' < MT.SELLING_DATE OR MT.SELLING_DATE IS NULL)
               AND MT.LEAGUE_ID=".$_GET['league_id']."
	       AND T.TEAM_ID=MM.TEAM_ID
         ORDER BY MPS.TOTAL_POINTS DESC, RMTT.LEAGUE_ID dESC, MM.LAST_NAME, MM.FIRST_NAME";

    $db->query($sql);
//echo $sql;
    $users = array();
    $c=1;
    while ($row = $db->nextRow()) {
      $user = $row;
      $user['NUMBER'] = $c++;
      $users[] = $user;

    }
    $db->free();

    $sql = "SELECT DISTINCT MUT.TOUR_ID
		 FROM manager_users_tours MUT
		WHERE MUT.SEASON_ID=".$manager->mseason_id." 
                ORDER BY MUT.TOUR_ID";
    $db->query($sql);   
    $tours = array();
    while ($row = $db->nextRow()) {
      unset($tour);
      $state = 'NORMAL'; 
      if (isset($_GET['tour_id']) && $row['TOUR_ID'] == $_GET['tour_id'])
        $state = 'SELECTED'; 
      $tour[$state] = $row;
      $tour[$state]['NUMBER'] = $row['TOUR_ID'];
      $tour[$state]['LEAGUE_ID'] = $_GET['league_id'];
      $tours[] = $tour;
    }
    if (isset($_GET['tour_id'])) {
      $all['NORMAL']['LEAGUE_ID'] = $_GET['league_id'];
    } else {
      $all['SELECTED'] = 1;
    }

  $smarty->assign("league_id", $_GET['league_id']);
  $smarty->assign("tours", $tours);
  $smarty->assign("users", $users);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/rvs_manager_team_statement.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/rvs_manager_team_statement.smarty'.($stop-$start);
 }
// ----------------------------------------------------------------------------
    define("FANTASY_MANAGER", 1);
// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

// include common footer

// close connections
include('class/db_close.inc.php');
?>