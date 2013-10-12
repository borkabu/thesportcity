<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
ppl.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of people records
  - deletes people records

TABLES USED:
  - BASKET.USERS
  - BASKET.MEMBERS
  - BASKET.TEAMS
  - BASKET.TOURNAMENTS
  - BASKET.ORGANIZATIONS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] interface with LDAP server
===============================================================================
*/

// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/adm_menu.php');

// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/language.class.php');
include('../class/form.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

// security layer
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');
include('../class/manager.inc.php');
include('../class/manager_log.inc.php');
include('../class/box.inc.php');
include('../class/managerbox.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header

$db->showquery=true;
     $sql = "SELECT STAKE, USER_ID, CHALLENGE_ID
		FROM manager_challenges WHERE TYPE=2 AND STATUS=5";
     $db->query($sql);
     $c = 0;
     $challenges = array();
     while ($row = $db->nextRow()) {
       $challenges[$c] = $row;
       $c++;
     }

//     $db->query("start transaction");
$unfrozen_stakes = 0;
     foreach ( $challenges as $challenge ) {
        $credits = new Credits();
        $credits->unfreezeCredits($challenge['USER_ID'], $challenge['STAKE']);
        unset($sdata);
        $sdata['status'] = 6;
        $db->update("manager_challenges", $sdata, "CHALLENGE_ID=".$challenge['CHALLENGE_ID']);
        echo "User: ".$challenge['USER_ID']. " unfroze ". $challenge['STAKE'] . " credits<br>";
        $unfrozen_stakes += $challenge['STAKE'];
     }

echo "Unfrozen: ".$unfrozen_stakes."<br>";

     $sql = "SELECT MB.STAKE, MB.BATTLE_ID, MBM.USER_ID
		FROM manager_battles MB, manager_battles_members MBM WHERE MB.STATUS=5 and MB.BATTLE_ID=MBM.BATTLE_ID";
     $db->query($sql);
     $c = 0;
     $battles = array();
     while ($row = $db->nextRow()) {
       $battles[$c] = $row;
       $c++;
     }

//     $db->query("start transaction");
     $credit_log = new CreditsLog();
     $credits = new Credits();
$refunded = 0;
     foreach ( $battles as $battle ) {
        if ($battle['STAKE'] > 0 ) {
          $credits->updateCredits($battle['USER_ID'], $battle['STAKE']); 
          $credit_log->logEvent ($battle['USER_ID'], 7, $battle['STAKE']);
        }
        unset($sdata);
        $sdata['status'] = 6;
        $db->update("manager_battles", $sdata, "BATTLE_ID=".$battle['BATTLE_ID']);
        echo "User: ".$battle['USER_ID']. " refunded ". $battle['STAKE'] . " credits<br>";
        $refunded += $battle['STAKE'];
     }
echo "Refunded: ".$refunded."<br>";


     $sql = "SELECT MC.STAKE, MC.CHALLENGE_ID, MC.USER_ID
		FROM wager_challenges MC WHERE MC.STATUS=3";
     $db->query($sql);
     $c = 0;
     $wager_challenges = array();
     while ($row = $db->nextRow()) {
       $wager_challenges[$c] = $row;
       $c++;
     }

     foreach ( $wager_challenges as $wager_challenge ) {
        $credits->unfreezeCredits($wager_challenge['USER_ID'], $wager_challenge['STAKE']);
        unset($sdata);
        $db->delete("wager_challenges", "CHALLENGE_ID=".$wager_challenge['CHALLENGE_ID']);
        echo "User: ".$wager_challenge['USER_ID']. " unfroze ". $wager_challenge['STAKE'] . " credits<br>";
     }

//     $db->query("commit");

// close connections
include('../class/db_close.inc.php');
?>