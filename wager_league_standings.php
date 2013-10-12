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
include('class/wager.inc.php');
include('class/wager_user.inc.php');
// --- build content data -----------------------------------------------------

// include common header
$content = '';

$page_size = 50;
//$db->showquery=true;

   if (isset($_GET['league_id'])) {

     $pleague = new League("wager", $_GET['league_id']);
     $pleague->getLeagueInfo();
     // particular tournament requested
      $profit = "SUM(WV.RETURN - IF(WV.RETURN>0,WV.STAKE,0)) as PROFIT";
      if ($pleague->league_info['POINT_TYPE'] == 1)
        $profit = "SUM(WV.RETURN) - SUM(IF(WV.RETURN>0,0,WV.STAKE)) as PROFIT";
      else if ($pleague->league_info['POINT_TYPE'] == 2)
        $profit = "SUM(WV.POINTS) as PROFIT";
      else if ($pleague->league_info['POINT_TYPE'] == 3)
        $profit = "SUM(WV.POINTS + WV.RETURN - IF(WV.RETURN>0,WV.STAKE,0)) as PROFIT";
      else if ($pleague->league_info['POINT_TYPE'] == 4)
        $profit = "SUM(WV.POINTS) + SUM(WV.RETURN) - SUM(IF(WV.RETURN>0,0,WV.STAKE)) as PROFIT";


      $sql= "SELECT ML.*, MLM.*, U.USER_NAME,
		".$profit.", WU.REFILLED
            FROM wager_leagues ML 
		  left join wager_league_tours WLT on WLT.LEAGUE_ID=ML.LEAGUE_ID,
		 wager_leagues_members MLM, wager_users WU, 
                seasons S, games G, wager_games WG, wager_votes WV,
		wager_seasons MS, users U
           WHERE ML.LEAGUE_ID=".$_GET['league_id']."
	        AND MLM.LEAGUE_ID=".$_GET['league_id']."
                AND MS.SEASON_ID=ML.SEASON_ID   
                AND MLM.USER_ID=U.USER_ID
	        AND MLM.STATUS IN (1,2)
                AND WG.WSEASON_ID = MS.SEASON_ID
		and WU.SEASON_ID= MS.SEASON_ID
                AND G.SEASON_ID = S.SEASON_ID
                AND G.GAME_ID=WG.GAME_ID
                AND MLM.USER_ID=WU.USER_ID
 	        AND WV.WAGER_ID=WG.WAGER_ID
		AND WV.USER_ID=MLM.USER_ID
      	        AND WLT.LEAGUE_ID=MLM.LEAGUE_ID
                AND ML.START_DATE IS NOT NULL
		AND WLT.TOUR_ID=".$_GET['tour_id']."
		AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > WLT.START_DATE
		AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < WLT.END_DATE
		AND G.SCORE1 > -1 AND G.SCORE2 > -1
           GROUP BY MLM.USER_ID
	   ORDER BY PROFIT DESC"; 
//echo $sql;
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
    $wager = new Wager($pleague->league_info['SEASON_ID']);
    $league['SEASON_TITLE'] = $wager->getTitle();
    $league['MEMBERS'] = $members;
 
  }
//print_r($league);
if (isset($league)) {
  $smarty->assign("league_item", $league);
  $smarty->assign("tour", $_GET['tour_id']);
}

$start = getmicrotime();
$content .= $smarty->fetch('smarty_tpl/wager_league_standings.smarty');    
$stop = getmicrotime();
if (isset($_GET['debugphp']))
  echo 'smarty_tpl/wager_league_standings.smarty'.($stop-$start);

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