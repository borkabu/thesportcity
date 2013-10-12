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
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
 
  if ($_GET['action'] == 'subscribe' && $auth->userOn()) {
      unset($sdata); 
      $sdata['USER_ID'] = $auth->getUserId();
      $sdata['TOPIC_ID'] = $_GET['topic_id'];
      $db->replace('topic_subscribe', $sdata);
      $smarty->assign("unsubscribe", $_GET['topic_id']);
  } 
  if ($_GET['action'] == 'unsubscribe' && $auth->userOn()) {
      $db->delete('topic_subscribe', 'USER_ID='.$auth->getUserId().' AND TOPIC_ID='.$_GET['topic_id']);
      $smarty->assign("subscribe", $_GET['topic_id']);
  } 

  $start = getmicrotime();
  $content = $smarty->fetch('smarty_tpl/bar_topic_settings.smarty');
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/bar_topic_settings.smarty<br>'.($stop-$start);

  echo $content;
// close connections
include('class/db_close.inc.php');
?>