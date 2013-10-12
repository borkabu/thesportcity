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
include('class/manager_user.inc.php');
// --- build content data -----------------------------------------------------

// include common header
$content = '';

$page_size = 50;
//$db->showquery=true;

   if (isset($_GET['league_id'])) {

     $pleague = new League("manager", $_GET['league_id']);
     $pleague->getLeagueInfo();
     // particular tournament requested
     $sql= "SELECT ML.*, MLM.*, U.USER_NAME, MSS.POINTS, MUT.PLACE_TOUR as PLACE, 
		MUT.POINTS AS LAST_POINTS, MUT.POINTS_MAIN,
		C.CCTLD, CD.COUNTRY_NAME, MU.ALLOW_VIEW
          FROM manager_leagues ML, manager_leagues_members MLM, 
	       manager_seasons MS, users U
               LEFT JOIN manager_standings MSS ON MSS.USER_ID=U.USER_ID AND MSS.MSEASON_ID=".$pleague->league_info['SEASON_ID']."
               LEFT JOIN manager_users_tours MUT ON MUT.USER_ID=U.USER_ID AND MUT.SEASON_ID=".$pleague->league_info['SEASON_ID']." AND MUT.TOUR_ID=".$_GET['tour_id']."
		 LEFT JOIN manager_users MU ON 	MU.USER_ID=U.USER_ID AND MU.SEASON_ID=".$pleague->league_info['SEASON_ID']."
	      , countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
         WHERE ML.LEAGUE_ID=".$_GET['league_id']."
	        AND MLM.LEAGUE_ID=".$_GET['league_id']."
              AND MS.SEASON_ID=ML.SEASON_ID   
              AND MLM.USER_ID=U.USER_ID
	        AND MLM.STATUS IN (1,2)
 	        AND U.COUNTRY = C.ID
                AND MUT.PLACE_TOUR IS NOT NULL
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

      if ($auth->userOn() && $auth->getUserId() == $row['USER_ID'])
	  $league['MEMBER'] = 1;

      $league['INVITE_TYPE'] = $row['INVITE_TYPE'];
      if ($row['STATUS'] ==1) {
        $member['OWNER'] = 1;
        $league['TITLE'] = $row['TITLE'];
        $league['OWNER'] = $row['USER_NAME'];

        $league['ENTRY_FEE'] = $row['ENTRY_FEE'];
	  $owner=$row['USER_ID'];
        $league['CHAT']['CHAT_CHANNEL'] = $row['TITLE']."_".$row['LEAGUE_ID']."_".$row['SEASON_ID'];
        if (!empty($row['RULES']))  
          $league['RULES'] = $row['RULES'];

        $c++;
      }
      else if ($row['STATUS'] == 2) {
        $member['CURRENT_MEMBERS'] = 1;
        $c++;
      } 
      $members[] = $member;
    }
    $manager = new Manager($pleague->league_info['SEASON_ID']);
    $league['SEASON_TITLE'] = $manager->getTitle();
    $league['MEMBERS'] = $members;
 
  }
//print_r($league);
if (isset($league)) {
  $smarty->assign("league_item", $league);
  $smarty->assign("tour", $_GET['tour_id']);
}

$start = getmicrotime();
$content .= $smarty->fetch('smarty_tpl/f_manager_league_standings.smarty');    
$stop = getmicrotime();
if (isset($_GET['debugphp']))
  echo 'smarty_tpl/f_manager_league_standings.smarty'.($stop-$start);

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