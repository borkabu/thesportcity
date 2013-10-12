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
$reports = array();
if (isset($_POST['reports_finish']) && !$ro) {
    $sql="SELECT SUM(MM.PLAYER_STATE)  PLAYER_STATE, MPR.REPORT_ID 
		FROM manager_player_reports MPR, manager_market MM
		WHERE MPR.finished = 0	
		      and MM.user_id = MPR.PLAYER_ID
		      and MPR.report_state=1
		      and (MPR.valid_till < NOW())
	group by MM.user_id
        HAVING SUM(MM.PLAYER_STATE)=0";

//		      
  $db->query($sql);
  while ($row = $db->nextRow()) {
    $reports[] = $row['REPORT_ID'];
  }

  foreach($reports as $report) {
    unset($sdata);
    $sdata['FINISHED'] = 1;
    $db->update('manager_player_reports', $sdata, "REPORT_ID=".$report);

    echo "<br>report finished ".$report;
  }
}
// close connections
include('../class/db_close.inc.php');
?>