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
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';

  if ($auth->userOn()) {
    unset($sdata); 
    if ($_GET['mode'] == '1') {
      $sdata['notify'] = 1;
    } else
      $sdata['notify'] = 0;
    $db->update('rvs_manager_leagues_members', $sdata, 'USER_ID='.$auth->getUserId().' AND LEAGUE_ID='.$_GET['league_id']);

    $sql="SELECT * FROM rvs_manager_leagues_members
		where USER_ID=".$auth->getUserId()."
		 AND LEAGUE_ID=".$_GET['league_id'];
    $db->query($sql);
    if ($row= $db->nextRow()) {
      if ($row['NOTIFY'] == 1) {
        $notify = true;
      } else {
        $notify = false;
      }
    }
    $smarty->assign("notify", $notify);
    $smarty->assign("league_id", $_GET['league_id']);
  }

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/rvs_manager_pex_notification_switch.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/rvs_manager_pex_notification_switch.smarty'.($stop-$start);

  echo $content;
// close connections
include('class/db_close.inc.php');
?>