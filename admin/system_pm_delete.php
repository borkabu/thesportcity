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
include('../class/pm.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------

$db->showquery=true;
if (isset ( $_POST['remove_old'] ) && ! $ro) {

   $sql = "SELECT PM.PM_ID, PMR.RECEIVER_ID
		FROM pm_message PM, pm_message_receiver PMR
              WHERE PM.opened=0 and PM.sender_id=-1 
			and DATE_ADD(PM.SENT_DATE, INTERVAL 4 DAY) < NOW()
			and PMR.PM_ID=PM.PM_ID";
   $db->query ( $sql );
   $pms = array();
   while ( $row = $db->nextRow () ) {
       $pms[] = $row;
   }

   echo "deleting ".count($pms)." pms<br>";
   $prm = new PM(); 
   foreach ($pms as $pm) {
     $prm->deleteSystemMessage($pm['PM_ID']);
     echo "deleting ".$pm['PM_ID']."<br>";
   }

  echo "Clean up completed!";
}

?>