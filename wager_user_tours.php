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
include('class/wager.inc.php');
include('class/wager_user.inc.php');
// --- build content data -----------------------------------------------------

  $content = '';
    
  if (isset($_GET['user_id']) && isset($_GET['league_id'])) {
    $pleague = new League("wager", $_GET['league_id']);
    $pleague->getLeagueInfo();

    $profit = "SUM(WV.RETURN - IF(WV.RETURN>0,WV.STAKE,0)) as PROFIT";
    if ($pleague->league_info['POINT_TYPE'] == 1)
      $profit = "SUM(WV.RETURN) - SUM(IF(WV.RETURN>0,0,WV.STAKE)) as PROFIT";
    else if ($pleague->league_info['POINT_TYPE'] == 2)
       $profit = "SUM(WV.POINTS) as PROFIT";
    else if ($pleague->league_info['POINT_TYPE'] == 3)
       $profit = "SUM(WV.POINTS + WV.RETURN - IF(WV.RETURN>0,WV.STAKE,0)) as PROFIT";
    else if ($pleague->league_info['POINT_TYPE'] == 4)
       $profit = "SUM(WV.POINTS) + SUM(WV.RETURN) - SUM(IF(WV.RETURN>0,0,WV.STAKE)) as PROFIT";


    $sql= "SELECT ML.*, MLM.*, U.USER_NAME, WLT.TOUR_ID, ".$profit.", WU.REFILLED
            FROM wager_leagues ML 
		  left join wager_league_tours WLT on WLT.LEAGUE_ID=ML.LEAGUE_ID,
		 wager_leagues_members MLM, wager_users WU, 
                seasons S, games G, wager_games WG
		  left join wager_votes WV on WV.WAGER_ID=WG.WAGER_ID and WV.USER_ID=".$_GET['user_id'].",
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
                AND MLM.USER_ID=".$_GET['user_id']."
      	        AND WLT.LEAGUE_ID=MLM.LEAGUE_ID
		AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > WLT.START_DATE
		AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < WLT.END_DATE
		AND G.SCORE1 > -1 AND G.SCORE2 > -1
           GROUP BY WLT.TOUR_ID
           ORDER BY WLT.TOUR_ID ASC"; 
         $db->query($sql);   
//echo $sql;
         $tourstats = array();
         while ($row = $db->nextRow()) {
           $tourstat = $row;
           $user_name  = $row['USER_NAME'];

           $tourstats[] = $tourstat;
         }
       $smarty->assign("user_name", $user_name);
       if (count($tourstats) > 0)
         $smarty->assign("tourstats", $tourstats);
  } 

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/wager_user_tours.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/wager_user_tours.smarty'.($stop-$start);
// ----------------------------------------------------------------------------

// close connections
// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

// close connections
include('class/db_close.inc.php');
?>