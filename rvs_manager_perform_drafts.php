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

   $league = new League('rvs_manager', $_GET['league_id']);
   $league->getLeagueInfo();

   // get league
   $manager = new Manager($league->league_info['SEASON_ID'], 'rvs');
   $rvs_manager_user = new RvsManagerUser($league->league_info['SEASON_ID'], $_GET['league_id']);

   $content = $rvs_manager_user->performManualDraft($_GET['league_id']);
 }

 echo $content;
// content

// include common footer

// close connections
include('class/db_close.inc.php');
?>