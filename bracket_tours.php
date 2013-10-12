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
include('class/bracket.inc.php');
include('class/bracket_user.inc.php');
// --- build content data -----------------------------------------------------

  $content = '';
  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 7);

  $bracket = new Bracket();
  $bracketbox = new BracketBox($langs, $_SESSION["_lang"]);

  if ($auth->userOn())
    $bracket_user = new BracketUser($bracket->tseason_id);
//  $manager_user = '';
  $bracket_filter_box = $bracketbox->getBracketFilterBox($bracket->tseason_id);
 
  $smarty->assign("bracket_filter_box", $bracket_filter_box);
  $content .= $bracketbox->getToursSchedule();

/*  $current_tour = $bracket->getNextRaceId();
  $tours = $bracket->getToursSchedule($current_tour);

  $smarty->assign("tours", $tours);
  $smarty->assign("bracket_filter_box", $bracket_filter_box);
  $smarty->assign("current_tour", $current_tour);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/bracket_tours.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/bracket_tours.smarty'.($stop-$start);*/

// ----------------------------------------------------------------------------
  define("ARRANGER", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_arranger.inc.php');

// close connections
include('class/db_close.inc.php');

?>