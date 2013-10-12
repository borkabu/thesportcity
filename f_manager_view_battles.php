<?php
error_reporting(E_ALL);
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

// include common header
$content = '';
   
  $manager = new Manager();
  $user = new User($_GET['user_id']);
  $manager->getCompletedBattles($_GET['user_id']);

  $user_data = $user->getUserData();
  $smarty->assign("season_title", $manager->title);
  $smarty->assign("user_data", $user_data);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_view_battles.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_view_battles.smarty'.($stop-$start);

// ----------------------------------------------------------------------------

// include common header
//include('inc/top.inc.php');
include('inc/top_very_small.inc.php');

// content
echo $content;

// close connections
include('class/db_close.inc.php');
?>