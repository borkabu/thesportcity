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

  // get league
//$db->showquery=true;
 if (isset($_GET['league_id'])) {

   // get league
   $manager = new Manager('', 'rvs');
   $rvs_manager_user = new RvsManagerUser($manager->mseason_id, $_GET['league_id']);

   $perform_drafts = $rvs_manager_user->performManualDraft($_GET['league_id']);
   $smarty->assign("perform_drafts", $perform_drafts);
   if ($rvs_manager_user->inited)
     $smarty->assign("in_draft", 1);
 }

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/rvs_manager_drafts.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/rvs_manager_drafts.smarty'.($stop-$start);


  include('inc/top_very_small.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');
?>