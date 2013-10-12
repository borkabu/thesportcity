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

  if (isset($_GET['season_id']) && isset($_GET['unsubscribe_key']) && isset($_GET['user_id'])) {
    $db->delete('reminder_subscribe', "UNSUBSCRIBE_KEY='".$_GET['unsubscribe_key']."' AND USER_ID=".$_GET['user_id']." AND TYPE=1 AND SEASON_ID=".$_GET['season_id']);
  }

  $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
  $errorbox1 = $errorbox->getMessageBox('MESSAGE_REMINDER_UNSUBSCRIBE_SUCCESS');


include('inc/top.inc.php');

echo  $content;
// include common footer
include('inc/bot.inc.php');

// close connections
include('class/db_close.inc.php');
?>