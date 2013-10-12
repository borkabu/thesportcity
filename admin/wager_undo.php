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

//==== UPDATE!!!!!!!!!!!!!!!!!
if (isset ( $_GET['undo'] ) && ! $ro) {

     $sql = "SELECT * 
		FROM wager_games WV
		WHERE WV.WAGER_ID=".$_GET['wager_id'];
     $db->query ( $sql );
     if ( $row = $db->nextRow () )
       $wager = $row;
     echo "Processing wager: ".$_GET['wager_id']."<br>";
     // get all votes
     $sql = "SELECT * 
		FROM wager_votes WV
		WHERE WV.WAGER_ID=".$_GET['wager_id'];
     $db->query ( $sql );
     $players='';
     while ( $row = $db->nextRow () ){
 	$players[$row['USER_ID']] = $row;
     }

     echo "Updating winnings: <br>";
     foreach ($players as $player) {
       if ($player['PROCESSED'] == 'Y') {
         if ($player['RETURN'] > 0) {
           // write down winnings, add statistics, set vote as processed
           unset($sdata);
           $sdata['MONEY'] = "MONEY-".($player['RETURN']);
           $sdata['STAKES'] = "STAKES+".$player['STAKE'];
           $sdata['WINS'] = "WINS-1";
           $db->update("wager_users", $sdata, "USER_ID=".$player['USER_ID']); 
           unset($sdata);
           $sdata['PROCESSED'] = "'N'";
           $sdata['`RETURN`'] = 0;
           $db->update("wager_votes", $sdata, "VOTE_ID=".$player['VOTE_ID']); 

//           $wager_user_log = new WagerUserLog();
//           $wager_user_log->logEvent ($player['USER_ID'], 6, $player['STAKE']*$wager['WIN_KOEFF'], 0, $wager['WSEASON_ID'], $wager['WAGER_ID']);
         } else {
           // log losses
           unset($sdata);
           $sdata['LOSSES'] = "LOSSES-1";
           $sdata['STAKES'] = "STAKES+".$player['STAKE'];
           $db->update("wager_users", $sdata, "USER_ID=".$player['USER_ID']); 
           unset($sdata);
           $sdata['PROCESSED'] = "'N'";
           $sdata['`RETURN`'] = 0;
           $db->update("wager_votes", $sdata, "VOTE_ID=".$player['VOTE_ID']); 
     
//           $wager_user_log = new WagerUserLog();
//           $wager_user_log->logEvent ($player['USER_ID'], 7, $player['STAKE'], 0, $wager['WSEASON_ID'], $wager['WAGER_ID']);
         } 
       }
       else if (isset($_GET['reprocess'])) {

       }
     }
     echo "Updating standings: <br>";
     $db->delete ( "wager_standings", "SEASON_ID=" . $wager['WSEASON_ID'] );
	
     $sql = "REPLACE INTO wager_standings 
            SELECT WU.SEASON_ID, WU.USER_ID, WU.MONEY+WU.STAKES WEALTH, 0
		FROM wager_users WU
                WHERE WU.SEASON_ID=" . $wager['WSEASON_ID'] . "
		ORDER BY WEALTH DESC";
	$db->query ( $sql );

     $sql = "SELECT USER_ID 
            FROM wager_standings         
           WHERE SEASON_ID=" . $wager['WSEASON_ID'] . "
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
	$db->update ( 'wager_standings', $sdata, "SEASON_ID=" . $wager['WSEASON_ID'] . " AND USER_ID=" . $people [$i] ['USER_ID'] );
     }
     $db->free ();


     unset($sdata);
     $sdata['PROCESSED'] = 0;
     $db->update("wager_games", $sdata, "WAGER_ID=".$_GET['wager_id']); 

//     $wager_log = new WagerLog();
//     $wager_log->logEvent(2, 0, $wager['WSEASON_ID'], $wager['WAGER_ID']);
     echo "Done: <br>";
}
?>