<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
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
include('class/rvs_manager_user.inc.php');
// --- build content data -----------------------------------------------------

// include common header
$content = '';

//$db->showquery=true;


    if (!empty($_GET['league_id'])) {
       // particular tournament requested
      $pleague = new League("rvs_manager", $_GET['league_id']);
      $pleague->getLeagueInfo();

      $points = "MUT.POINTS";
      if ($pleague->league_info['LEAGUE_TYPE'] == 1)
        $points = "MUT.SCORE";

       $sql= "SELECT ML.*, MLM.*, U.USER_NAME, MSS.POINTS, MSS.PLACE, ".$points." AS LAST_POINTS, 
		ML.STATUS as LEAGUE_STATUS, MLM.STATUS AS MSTATUS, C.CCTLD, CD.COUNTRY_NAME, MU.ALLOW_VIEW,
		ML.ENTRY_FEE as LEAGUE_ENTRY_FEE, ML.DRAFT_STATE, MUT.PLACE_TOUR
            FROM rvs_manager_leagues ML, rvs_manager_leagues_members MLM, 
	       manager_seasons MS, users U
                 LEFT JOIN rvs_manager_standings MSS ON MSS.USER_ID=U.USER_ID AND MSS.LEAGUE_ID=".$_GET['league_id']."
                 LEFT JOIN rvs_manager_users_tours MUT ON MUT.USER_ID=U.USER_ID AND MUT.LEAGUE_ID=".$_GET['league_id']." AND MUT.TOUR_ID=".$_GET['tour_id']."
	       LEFT JOIN manager_users MU ON MU.USER_ID=U.USER_ID AND MU.SEASON_ID=".$pleague->league_info['SEASON_ID']."
	      , countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
           WHERE ML.LEAGUE_ID=".$_GET['league_id']."
	        AND MLM.LEAGUE_ID=".$_GET['league_id']."
                AND MS.SEASON_ID=ML.SEASON_ID   
                AND MLM.USER_ID=U.USER_ID
	        AND MLM.STATUS IN (1,2)
    	        AND U.COUNTRY = C.ID
           ORDER BY MUT.PLACE_TOUR ASC"; 
       
      $db->query($sql);     
      $c=1;    
      $members = array(); 
      while ($row = $db->nextRow()) {
        $member = $row;
        $member['LOCAL_PLACE'] = $c;
  
        if (!empty($row['CCTLD'])) {
          $member['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
          $member['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
        }
  
        $league['TITLE'] = $row['TITLE'];
        $league['TOUR'] = $_GET['tour_id'];
        $member['CURRENT_MEMBERS'] = 1;
        $members[] = $member;
      }
      $manager = new Manager($pleague->league_info['SEASON_ID']);
      $league['SEASON_TITLE'] = $manager->getTitle();
      $league['MEMBERS'] = $members;
      $smarty->assign("league", $league);
    }
  
    //print_r($league);
 
    $start = getmicrotime();
    $content .= $smarty->fetch('smarty_tpl/rvs_manager_league_standings.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/rvs_manager_league_standings.smarty'.($stop-$start);
  

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
