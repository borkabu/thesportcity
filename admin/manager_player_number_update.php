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

  if ($mode =='update') {
    $sdata['NUM'] = $_GET['num'];
    $db->update('members', $sdata, "ID=".$_GET['member_id']);
  }

  $sql = "SELECT ID, NUM FROM members WHERE ID=".$_GET['member_id'];
  $db->query($sql);
  if ($row = $db->nextRow()) {
    $data['ID'] = $row['ID'];
    $data['NUM'] = $row['NUM'];
  }

$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/manager_player_number_update.tpl.html');
$tpl->addData($data);
echo $tpl->parse();


// close connections
include('../class/db_close.inc.php');
?>