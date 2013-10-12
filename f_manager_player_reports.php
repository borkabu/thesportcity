<?php
/*
===============================================================================
toto.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows totalizator result archive

TABLES USED: 
  - BASKET.totalizators
  - BASKET.totalizator_votes
  - BASKET.users
  - BASKET.games

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

  $content = '';


  $manager = new Manager();
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
  $content = $managerbox->getPlayerReports($_GET['player_id'], $_GET['season_id']);
  // build stats


//$content .= $tpl->parse();
// ----------------------------------------------------------------------------

// content
echo $content;

// ----------------------------------------------------------------------------
// include common footer

// close connections
include('class/db_close.inc.php');

?>