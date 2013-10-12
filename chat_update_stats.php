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

  if (isset($_GET['user_id']) && isset($_GET['room'])) {
// content
//$db->showquery=true;
   // remove entries more that 5 minutes old for all channels
   $db->delete("chat_stats", "CHECKIN_TIME < DATE_ADD(NOW(), INTERVAL -5 MINUTE )");

   // update checkin time for given channel and user
   unset($sdata);
   $sdata['USER_ID'] = $_GET['user_id'];
   $sdata['CHANNEL_NAME'] = "'".$_GET['room']."'";
   $sdata['CHECKIN_TIME'] = "NOW()";
   $db->replace("chat_stats", $sdata);

  }

// include common footer

// close connections
include('class/db_close.inc.php');
?>