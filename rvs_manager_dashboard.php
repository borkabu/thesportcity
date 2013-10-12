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
include('class/rvs_manager_user.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
//  $content = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 3);

  $manager = new Manager('', 'rvs');
  if ($auth->userOn())
    $rvs_manager_user = new RvsManagerUser($manager->mseason_id);

//  $manager_filter_box = $managerbox->getRvsManagerFilterBox($manager->getSeason());

//  $seasons = $managerbox->getManagerSeasonBox(false, '', '', false, true);
  $seasons = $managerbox->getRvsManagerSeasonBox(false, '', '', false, true);
  $timeline = $timelinebox->getTimelineBox("rvs_manager");

//  $smarty->assign("countdown", $countdown);
  $smarty->assign("seasons", $seasons);
  $smarty->assign("timeline", $timeline);
//  $smarty->assign("manager_filter_box", $manager_filter_box);
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/rvs_manager_dashboard.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/rvs_manager_dashboard.smarty'.($stop-$start);

  define("RVS_MANAGER", 1);
  include('inc/top.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_rvs_manager_dashboard.inc.php');

// close connections
include('class/db_close.inc.php');
?>