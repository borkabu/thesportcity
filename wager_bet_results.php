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
    
  if (isset($_GET['wager_id'])) {
     $sql = "SELECT WG.WSEASON_ID, G.SCORE1, G.SCORE2
		FROM wager_games WG, games G
		WHERE WG.GAME_ID=G.GAME_ID 
			AND WG.wager_id=".$_GET['wager_id'];
//echo $sql;
     $db->query ( $sql );
     $row = $db->nextRow();
     $game= $row;

     $wager = new Wager($row['WSEASON_ID']);
     $diff = $row['SCORE2']-$row['SCORE1'];
     $sum = $row['SCORE1']+$row['SCORE2'];
     $diff_coeff = "ABS(WV.DIFFERENCE-".$diff.")";
     $sum_coeff = "ABS(WV.HOST_SCORE+WV.VISITOR_SCORE-".$sum.")";
     if ($row['SCORE1'] <> $row['SCORE2'])
       $win_coeff = "(WV.HOST_SCORE-WV.VISITOR_SCORE)*(".($row['SCORE1']-$row['SCORE2']).")";
     else
       $win_coeff = "WV.HOST_SCORE-WV.VISITOR_SCORE+1";

     $sql = "SELECT WV.*, ".($diff_coeff."+".$sum_coeff)."+if(".$win_coeff.">0, 0, 100) DISTANCE, U.USER_NAME
		FROM wager_games WG, wager_votes WV, users U
		WHERE WV.WAGER_ID=".$_GET['wager_id']."
			AND WV.USER_ID=U.USER_ID
			AND WG.WAGER_Id=WV.WAGER_ID
			AND WG.PROCESSED=1
		ORDER BY ".($diff_coeff."+".$sum_coeff)."+if(".$win_coeff.">0, 0, 100)  ASC";
     $db->query ( $sql );

     $bets = array();
     while ($row = $db->nextRow()) {
       $bet= $row;       
       $bets[] = $bet;
     }

     $smarty->assign("season_title", $wager->title);
       $smarty->assign("game", $game);
     if (count($bets) > 0)
       $smarty->assign("bets", $bets);
  } 

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/wager_bet_results.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/wager_bet_results.smarty'.($stop-$start);
// ----------------------------------------------------------------------------

// close connections
// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

// close connections
include('class/db_close.inc.php');
?>