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
include('../class/log.inc.php');
$log = new Log();
include('../class/trust.inc.php');
include('../class/manager.inc.php');
include('../class/manager_log.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------
$db->showquery = true;
if (isset($_POST['report_ignore']) && !$ro) {
    unset($sdata);
    $sdata['REPORT_STATE'] = 1;
    $sdata['FINISHED'] = 1;
    $db->update('games_reports', $sdata, "REPORT_ID=".$_POST['report_id']);
} else if ((isset($_POST['report_confirmation']) ||
     isset($_POST['report_denial'])) && !$ro) {
  $sql="SELECT * FROM games_reports
		WHERE report_id=".$_POST['report_id']."
		      and report_state=0";
//and (valid_till > NOW() or status < 0)
  $db->query($sql);
  if ($row = $db->nextRow()) {
    $game_id= $row['GAME_ID'];

    unset($sdata);
    if (isset ( $_POST['report_confirmation'] ) && !$ro) {
      $sdata['REPORT_STATE'] = 1;
    } else if (isset ( $_POST['report_denial'] ) && !$ro) {
      $sdata['REPORT_STATE'] = -1;
    }

    $sdata['FINISHED'] = 1;
    $db->update('games_reports', $sdata, "REPORT_ID=".$_POST['report_id']);
  
    if (isset ( $_POST['report_confirmation'] ) && ! $ro) {
    // reward reporter
      $trust = new Trust();
      $trust->changeContentTrust(0.1, $row['USER_ID']);
      $credits = new Credits();
      $credits->updateCredits ($row['USER_ID'], 0.2);
      $credit_log = new CreditsLog();
      $credit_log->logEvent ($row['USER_ID'], 8, 0.2);
      
      // update player state
      
      unset($sdata);
      $sdata['START_DATE'] = "'".$row['REPORTED_START_DATE']."'";
      $db->update('games', $sdata, "GAME_ID=".$game_id);
      
      echo "confirmation successed";
    } else if (isset ( $_POST['report_denial'] ) && !$ro) {
      $trust = new Trust();
      $trust->changeContentTrust(-0.1, $row['USER_ID']);
    }
  }
} // close connections

include('../class/db_close.inc.php');
?>