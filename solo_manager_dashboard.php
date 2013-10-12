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
//  $content = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);

  $manager = new Manager();

  $seasons = $managerbox->getSoloManagerSeasonBox(false, '', '', false, true);
  $timeline = $timelinebox->getTimelineBox('solo_manager');

  $smarty->assign("seasons", $seasons);
  $smarty->assign("timeline", $timeline);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/solo_manager_dashboard.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/solo_manager_dashboard.smarty'.($stop-$start);

  define("SOLO_MANAGER", 1);
  include('inc/top.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_manager_dashboard.inc.php');

// close connections
include('class/db_close.inc.php');
?>