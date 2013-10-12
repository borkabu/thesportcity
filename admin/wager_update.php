<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
season_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit tournament seasons
  - create new tournament season

TABLES USED: 
  - BASKET.SEASONS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
ob_start ();
// includes
include ('../class/conf.inc.php');
include ('../class/func.inc.php');
include ('../class/adm_menu.php');
include ('../class/update.inc.php');

// classes
include ('../class/db.class.php');
include ('../class/template.class.php');
include ('../class/language.class.php');
include ('../class/form.class.php');

// connections
include ('../class/db_connect.inc.php');
$tpl = new template ( );
$frm = new form ( );

// security layer
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');
include('../class/wager_log.inc.php');
include('../class/wager_users_log.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_WAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_WAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_WAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------
$db->showquery=true;
// get seasons
  if (isset($_GET['game_id'])) {
    $sql= "SELECT * from games G, seasons S where S.season_id=G.season_id and G.GAME_ID=" . $_GET['game_id'];
    $db->query($sql);
    $row = $db->nextRow ();

    $sport_id = $row['SPORT_ID'];
    $score1 = $row['SCORE1'];
    $score2 = $row['SCORE2'];
    $db->select ( 'wager_games', '*', 'PROCESSED=0 AND GAME_ID=' . $_GET['game_id']);
    $c = 0;
    $wagers = '';
    while ( $row = $db->nextRow () ) {
	$wagers[$c] = $row;
        $season_id = $row['WSEASON_ID'];
	$c++;
    }
    $db->free ();
  } else if (isset($_GET['season_id'])) {
    $season_id = $_GET['season_id'];
  }

// get outcome	


//==== UPDATE!!!!!!!!!!!!!!!!!
if (isset ( $_GET['update'] ) && !$ro) {
 if (($sport_id == 1 && ($score1 > 0 && $score2 > 0)) 
	|| ($sport_id == 2 && ($score1 > -1 && $score2 > -1))) {
	//set_time_limit(6000);
	//  ignore_user_abort(true);
	echo "..............................................................................................................................................................................................................................................................................................................................................
..............................................................................................................................................................................................................................................................................................................................................
..............................................................................................................................................................................................................................................................................................................................................
..............................................................................................................................................................................................................................................................................................................................................";
	ob_flush ();
	flush ();
 
  if ($wagers != '') { 
   foreach($wagers as $wager) {
     echo "Processing wager: ".$wager['WAGER_ID']."<br>";
     $sql = "SELECT COUNT(WV.VOTE_ID) VOTES
		FROM wager_votes WV 
		WHERE WV.WAGER_ID=".$wager['WAGER_ID']."
			AND WV.STAKE=0";
     $db->query ( $sql );
     $nostakes = 0; 
     if ( $row = $db->nextRow () ){
       $nostakes = $row['VOTES'];
     }
     $sql = "SELECT COUNT(WV.VOTE_ID) VOTES
		FROM wager_votes WV 
		WHERE WV.WAGER_ID=".$wager['WAGER_ID']."
			AND WV.STAKE=1";
     $db->query ( $sql );
     $creditstakes = 0; 
     if ( $row = $db->nextRow () ){
       $creditstakes = $row['VOTES'];
     }

     // get all votes
     $diff = $score2-$score1;
     $sum = $score1+$score2;
     $diff_coeff = "ABS(WV.DIFFERENCE-".$diff.")";
     $sum_coeff = "ABS(WV.HOST_SCORE+WV.VISITOR_SCORE-".$sum.")";
     if ($score1 != $score2)
       $win_coeff = "(WV.HOST_SCORE-WV.VISITOR_SCORE)*(".($score1-$score2).")";
     else
       $win_coeff = "WV.HOST_SCORE-WV.VISITOR_SCORE+1";

     // get winner for nostakes
     if ($nostakes >= 2) {
       $sql = "SELECT WV.*, ".($diff_coeff."+".$sum_coeff)."+if(".$win_coeff.">0, 0, 100) POINTS
		FROM wager_votes WV 
		WHERE WV.WAGER_ID=".$wager['WAGER_ID']."
			and WV.STAKE=0
		ORDER BY ".($diff_coeff."+".$sum_coeff)."+if(".$win_coeff.">0, 0, 100) ASC
		LIMIT 1";
       $db->query ( $sql );
       $best_score= 100;       
       if ( $row = $db->nextRow () ){
         $best_score = $row['POINTS'];
       }
       	
       $sql = "SELECT WV.*, ".($diff_coeff."+".$sum_coeff)."+if(".$win_coeff.">0, 0, 100) POINTS
		FROM wager_votes WV 
		WHERE WV.WAGER_ID=".$wager['WAGER_ID']."
			and WV.STAKE=0
			and ".($diff_coeff."+".$sum_coeff)."+if(".$win_coeff.">0, 0, 100)=".$best_score;
echo $sql;
       $db->query ( $sql );
       while ( $row = $db->nextRow () ){
         $nostakewinners[] = $row['USER_ID'];
       }
     }

     if ($creditstakes >= 2) {
       $sql = "SELECT WV.*, ".($diff_coeff."+".$sum_coeff)."+if(".$win_coeff.">0, 0, 100) POINTS
		FROM wager_votes WV 
		WHERE WV.WAGER_ID=".$wager['WAGER_ID']."
			and WV.STAKE=1
		ORDER BY ".($diff_coeff."+".$sum_coeff)."+if(".$win_coeff.">0, 0, 100) ASC
		LIMIT 1";
       $db->query ( $sql );
       $best_score_one= 100;       
       if ( $row = $db->nextRow () ){
         $best_score_one = $row['POINTS'];
       }

       $sql = "SELECT WV.*, ".($diff_coeff."+".$sum_coeff)."+if(".$win_coeff.">0, 0, 100) POINTS
		FROM wager_votes WV 
		WHERE WV.WAGER_ID=".$wager['WAGER_ID']."
			and WV.STAKE=1
		and ".($diff_coeff."+".$sum_coeff)."+if(".$win_coeff.">0, 0, 100)=".$best_score_one;
       $db->query ( $sql );
       while ( $row = $db->nextRow () ){
         $creditstakewinners[] = $row['USER_ID'];
       }
     }

     $sql = "SELECT WV.*, ".($diff_coeff."+".$sum_coeff)."+if(".$win_coeff.">0, 0, 100) POINTS
		FROM wager_votes WV 
		WHERE WV.WAGER_ID=".$wager['WAGER_ID']."
			and WV.STAKE=0
		ORDER BY ".($diff_coeff."+".$sum_coeff)."+if(".$win_coeff.">0, 0, 100) ASC";
     $db->query ( $sql );
echo $sql;
     $players=array();
     while ( $row = $db->nextRow () ){
 	$players[$row['USER_ID']] = $row;
     }

     echo "Updating no stakes winnings: <br>";
     if ($players != '') 
       $outcome = 0;
       if ($score1 > $score2) {
         $outcome = 1;
       }
       else if ($score1 < $score2) {
         $outcome = -1;
       }

      $winners = count($nostakewinners);
      echo "No stake winners: ".$winners."<br>";
      $money_won = 1/$winners;
      echo "Credits won: ".$money_won."<br>";

      foreach ($players as $player) {
       if ($player['PROCESSED'] != 'Y') {
         if ($outcome == $player['CHOICE'] && in_array ( $player['USER_ID'], $nostakewinners)) {
           // write down winnings, add statistics, set vote as processed           
           unset($sdata);
           $sdata['WINS'] = "WINS+1";
           $sdata['BALANCE'] = "BALANCE+".$money_won;
           $db->update("wager_users", $sdata, "USER_ID=".$player['USER_ID']. " AND SEASON_ID=".$wager['WSEASON_ID']); 
           unset($sdata);
           $sdata['PROCESSED'] = "'Y'";
           $sdata['POINTS'] = $money_won;
           $sdata['`RETURN`'] = $money_won;
           $db->update("wager_votes", $sdata, "VOTE_ID=".$player['VOTE_ID']); 

           $credits = new Credits();
           $credits->updateCredits($player['USER_ID'], $money_won);
           $credit_log = new CreditsLog();
           $credit_log->logEvent ($player['USER_ID'], 31, $money_won);

           $wager_user_log = new WagerUserLog();
           $wager_user_log->logEvent ($player['USER_ID'], 6, $money_won, 0, $wager['WSEASON_ID'], $wager['WAGER_ID']);
         } else {
           // log losses
           unset($sdata);
           $sdata['LOSSES'] = "LOSSES+1";
           $sdata['STAKES'] = "STAKES-".$player['STAKE'];
           $db->update("wager_users", $sdata, "USER_ID=".$player['USER_ID']. " AND SEASON_ID=".$wager['WSEASON_ID']); 
           unset($sdata);
           $sdata['PROCESSED'] = "'Y'";
           $sdata['`RETURN`'] = 0;
           $db->update("wager_votes", $sdata, "VOTE_ID=".$player['VOTE_ID']); 
     
           $wager_user_log = new WagerUserLog();
           $wager_user_log->logEvent ($player['USER_ID'], 7, $player['STAKE'], 0, $wager['WSEASON_ID'], $wager['WAGER_ID']);
         } 
       }
       else if (isset($_GET['reprocess'])) {

       }
     }

     $sql = "SELECT WV.*, ".($diff_coeff."+".$sum_coeff)." POINTS
		FROM wager_votes WV 
		WHERE WV.WAGER_ID=".$wager['WAGER_ID']."
			and WV.STAKE=1
		ORDER BY ".($diff_coeff."+".$sum_coeff)." ASC";
     $db->query ( $sql );
echo $sql;
     $players=array();
     while ( $row = $db->nextRow () ){
 	$players[$row['USER_ID']] = $row;
     }

     echo "Updating 1 credit stakes winnings: <br>";
     if ($players != '') 
       $outcome = 0;
       if ($score1 > $score2) {
         $outcome = 1;
       }
       else if ($score1 < $score2) {
         $outcome = -1;
       }

      $winners = count($creditstakewinners);
      echo "Credit winners: ".$winners."<br>";
      $money_won = count($players) * 0.9/$winners;
      echo "Credits won: ".$money_won."<br>";

      foreach ($players as $player) {
       if ($player['PROCESSED'] != 'Y') {
         if ($outcome == $player['CHOICE'] && in_array ( $player['USER_ID'], $creditstakewinners)) {
           // write down winnings, add statistics, set vote as processed           
           unset($sdata);
           $sdata['WINS'] = "WINS+1";
           $sdata['BALANCE'] = "BALANCE+".$money_won;
           $db->update("wager_users", $sdata, "USER_ID=".$player['USER_ID']. " AND SEASON_ID=".$wager['WSEASON_ID']); 
           unset($sdata);
           $sdata['PROCESSED'] = "'Y'";
           $sdata['POINTS'] = $money_won;
           $sdata['`RETURN`'] = $money_won;
           $db->update("wager_votes", $sdata, "VOTE_ID=".$player['VOTE_ID']); 

           $credits = new Credits();
           $credits->updateCredits($player['USER_ID'], $money_won);
           $credit_log = new CreditsLog();
           $credit_log->logEvent ($player['USER_ID'], 31, $money_won);

           $wager_user_log = new WagerUserLog();
           $wager_user_log->logEvent ($player['USER_ID'], 6, $money_won, 0, $wager['WSEASON_ID'], $wager['WAGER_ID']);
         } else {
           // log losses
           unset($sdata);
           $sdata['LOSSES'] = "LOSSES+1";
           $sdata['STAKES'] = "STAKES-".$player['STAKE'];
           $sdata['BALANCE'] = "BALANCE-1";
           $db->update("wager_users", $sdata, "USER_ID=".$player['USER_ID']. " AND SEASON_ID=".$wager['WSEASON_ID']); 
           unset($sdata);
           $sdata['PROCESSED'] = "'Y'";
           $sdata['`RETURN`'] = 0;
           $db->update("wager_votes", $sdata, "VOTE_ID=".$player['VOTE_ID']); 
     
           $wager_user_log = new WagerUserLog();
           $wager_user_log->logEvent ($player['USER_ID'], 7, $player['STAKE'], 0, $wager['WSEASON_ID'], $wager['WAGER_ID']);
         } 
       }
       else if (isset($_GET['reprocess'])) {

       }
     }

     unset($sdata);
     $sdata['PROCESSED'] = 1;
     $db->update("wager_games", $sdata, "WAGER_ID=".$wager['WAGER_ID']); 

     $wager_log = new WagerLog();
     $wager_log->logEvent(2, 0, $wager['WSEASON_ID'], $wager['WAGER_ID']);
     echo "Done: <br>";

    }
   }

     $db->select ( 'wager_challenges', '*', 'STATUS=2 AND GAME_ID='.$_GET['game_id']);
     $wager_challenges = array();
     while ( $row = $db->nextRow () ) {
       $wager_challenge = $row;
       $outcome = 0;
       if ($score1 > $score2) {
         $outcome = 1;
       }
       else if ($score1 < $score2) {
         $outcome = -1;
       }       
       $wager_challenge['FINAL_OUTCOME'] = $outcome;
       $wager_challenges[] = $wager_challenge;
     }
print_r($wager_challenges);
     foreach ($wager_challenges as $wager_challenge) {
       $credits = new Credits();
       $credits->unfreezeCredits($wager_challenge['USER_ID'], $wager_challenge['STAKE']);
       $credits->unfreezeCredits($wager_challenge['USER2_ID'], $wager_challenge['STAKE']);
       if ($wager_challenge['OUTCOME'] == $wager_challenge['FINAL_OUTCOME']) {
         $credits->transferCredit($wager_challenge['USER2_ID'], $wager_challenge['USER_ID'], $wager_challenge['STAKE'], 1, 14);
       } else {
         $credits->transferCredit($wager_challenge['USER_ID'], $wager_challenge['USER2_ID'], $wager_challenge['STAKE'], 1, 14);
       }
       unset($sdata);
       $sdata['SCORE1'] = $score1;
       $sdata['SCORE2'] = $score2;
       $sdata['PROCESSED'] = 1;
       $sdata['STATUS'] = 4;
       $db->update("wager_challenges", $sdata, "CHALLENGE_ID=".$wager_challenge['CHALLENGE_ID']); 
     }

     unset($sdata);
     $sdata['STATUS'] = 3;
     $db->update ( 'wager_challenges', $sdata, 'STATUS=1 AND GAME_ID='.$_GET['game_id']);

  } else {
     echo "Wrong scores: <br>";
  }
}



     echo "Updating standings: <br>";
     $db->delete ( "wager_standings", "SEASON_ID=" . $season_id );
	
     $sql = "REPLACE INTO wager_standings 
            SELECT WU.SEASON_ID, WU.USER_ID, WU.BALANCE, 0
		FROM wager_users WU
                WHERE WU.SEASON_ID=" . $season_id . "
			AND WU.GAMES > 0
		ORDER BY BALANCE DESC, WINS DESC";
	$db->query ( $sql );

     $sql = "SELECT USER_ID 
            FROM wager_standings         
           WHERE SEASON_ID=" . $season_id . "
           ORDER BY WEALTH DESC";
     $db->query ( $sql );
     $c = 0;
     while ( $row = $db->nextRow () ) {
	$people [$c] ['USER_ID'] = $row['USER_ID'];
	$people [$c] ['PLACE'] = $c + 1;
	$c ++;
     }
     $db->free ();
	
     //$db->showquery = true;
     for ($i = 0; $i < $c; $i++) {
	unset ( $sdata );
	$sdata['PLACE'] = $people[$i]['PLACE'];
	$db->update ( 'wager_standings', $sdata, "SEASON_ID=" . $season_id . " AND USER_ID=" . $people [$i] ['USER_ID'] );
     }
     $db->free ();

?>