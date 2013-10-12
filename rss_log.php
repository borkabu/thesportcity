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

  $manager = new Manager($_GET['season_id']);
$manager_log = new ManagerLog();
  $log = $manager_log->getManagerLog($_GET['season_id'], 1, 10);

  $log_header['LANG'] = $_SESSION["_lang"];
  $log_header['TITLE'] = $manager->getTitle();
  $log_header['SEASON_ID'] = $_GET['season_id'];
  $log_header['LAST_BUILD_DATE'] = date("D, d M Y H:i:s T");

  $smarty->assign("log_header", $log_header);
  $smarty->assign("log", $log);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/rss_log.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/bracket_league.smarty'.($stop-$start);

// content
echo $content;

// ----------------------------------------------------------------------------
// include common footer


// close connections
include('class/db_close.inc.php');

?>