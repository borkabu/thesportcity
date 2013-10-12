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
  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/bar_manager_stock_exchange_notification.tpl.html');
 

  if ($auth->userOn()) {
    unset($sdata); 
    if ($_GET['mode'] == '1') {
      $sdata['notify'] = 1;
    } else
      $sdata['notify'] = 0;
    $db->update('manager_stock_exchange', $sdata, 'USER_ID='.$auth->getUserId().' AND SEASON_ID='.$_GET['season_id'].' AND PLAYER_ID='.$_GET['player_id']);

    $sql="SELECT * FROM manager_stock_exchange 
		where USER_ID=".$auth->getUserId()."
		 AND SEASON_ID=".$_GET['season_id']." 
		 AND PLAYER_ID=".$_GET['player_id'];
    $db->query($sql);
    if ($row= $db->nextRow()) {
      if ($row['NOTIFY'] == 1) {
        $data['NOTIFICATION_ON'][0]=$row;
      } else {
        $data['NOTIFICATION_OFF'][0]=$row;
      }
    }
  }
  $tpl->addData($data);

  $content .= $tpl->parse();

  echo $content;
// close connections
include('class/db_close.inc.php');
?>