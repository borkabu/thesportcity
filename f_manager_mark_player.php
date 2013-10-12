<?php
/*
===============================================================================
help.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows help item
  = shows help topic index

TABLES USED: 
  - BASKET.HELP

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
include('class/manager.inc.php');
include('class/manager_user.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';

  if ($auth->userOn() && isset($_GET['season_id']) && isset($_GET['player_id'])) {
    if ($_GET['action'] == 'mark') {
      unset($sdata);
      $sdata['SEASON_ID'] = $_GET['season_id'];
      $sdata['PLAYER_ID'] = $_GET['player_id'];
      $sdata['USER_ID'] = $auth->getUserId();
      $db->insert("manager_players_marked", $sdata);
    } else if ($_GET['action'] == 'unmark') {
      $db->delete("manager_players_marked", "SEASON_ID=".$_GET['season_id']." AND PLAYER_ID=".$_GET['player_id']." AND USER_ID=".$auth->getUserId()); 
    }
    $content .= $managerbox->getManagerPlayerMarkBox($_GET['action']);
  }

  echo $content;
// close connections
include('class/db_close.inc.php');
?>