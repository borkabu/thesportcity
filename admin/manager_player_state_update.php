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
// get player state

$mode = $_GET['mode'];
//$player_state = $mode;

$sql="SELECT PLAYER_STATE FROM manager_market 
		WHERE season_id=".$_GET['season_id']."
		      and user_id=".$_GET['player_id'];
$db->query($sql);
if ($row = $db->nextRow()) {
  $player_state = $row['PLAYER_STATE'];

  unset($sdata);
  if ($mode == 1) {
    $sdata['PLAYER_STATE'] = '(PLAYER_STATE & 3)';
    $db->update('manager_market', $sdata, "USER_ID=".$_GET['player_id']);
    $sdata['PLAYER_STATE'] = '(PLAYER_STATE | 1)';
    $db->update('manager_market', $sdata, "USER_ID=".$_GET['player_id']);
  } else if ($mode == -1) {
    $sdata['PLAYER_STATE'] = '(PLAYER_STATE & 6)';
    $db->update('manager_market', $sdata, "USER_ID=".$_GET['player_id']);
  } else if ($mode == 4) {
    $sdata['PLAYER_STATE'] = '(PLAYER_STATE & 6)';
    $db->update('manager_market', $sdata, "USER_ID=".$_GET['player_id']);
    $sdata['PLAYER_STATE'] = '(PLAYER_STATE | 4)';
    $db->update('manager_market', $sdata, "USER_ID=".$_GET['player_id']);
  } else if ($mode == -4) {
    $sdata['PLAYER_STATE'] = '(PLAYER_STATE & 3)';
    $db->update('manager_market', $sdata, "USER_ID=".$_GET['player_id']);
  } else {
    if ($mode == 2)
      $sdata['PLAYER_STATE'] = '(PLAYER_STATE | 2)';
    else if ($mode == -2)
      $sdata['PLAYER_STATE'] = '(PLAYER_STATE & 5)';

    $db->update('manager_market', $sdata, "USER_ID=".$_GET['player_id']." AND SEASON_ID=".$_GET['season_id']);
  }

}

 $sql="SELECT PLAYER_STATE FROM manager_market 
		WHERE season_id=".$_GET['season_id']."
		      and user_id=".$_GET['player_id'];

 $db->query($sql);
 if ($row = $db->nextRow()) {
   $player_state = $row['PLAYER_STATE'];
 }

  $manager = new Manager($_GET['season_id']);
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
//echo $player_state;
  $content = $managerbox->getPlayerStateDiv($_GET['player_id'], $_GET['season_id'], $player_state, true);

  echo $content;

// close connections
include('../class/db_close.inc.php');
?>