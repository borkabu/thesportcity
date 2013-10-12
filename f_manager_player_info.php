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
  // build stats
  $sql = 'SELECT MPS.*, B.FIRST_NAME, B.LAST_NAME
          FROM 
            manager_player_stats MPS, busers B
          WHERE 
            MPS.PLAYER_ID='.$_GET['user_id'].'
            AND MPS.SEASON_ID='.$manager->mseason_id.'
            AND MPS.PLAYER_ID = B.USER_ID
          ORDER BY
            MPS.TOUR_ID';

  $db->query($sql);

  $c = 0;
  $tours = array();
  $info = array();
  while ($row = $db->nextRow()) {
     $info['FIRST_NAME'] = $row['FIRST_NAME'];
     $info['LAST_NAME'] = $row['LAST_NAME'];
     $tour = $row;
     $tour['PLAYED_TOTAL'] = $row['PLAYED'] + $row['PLAYED_PREV'];
     $tour['POINTS'] = $row['TOTAL_POINTS'] + $row['TOTAL_POINTS_PREV'];
     $tours[] = $tour;
   }
  $info['SEASON_TITLE'] = $manager->getTitle();
  $db->free();       

  $smarty->assign("tours", $tours);
  $smarty->assign("info", $info);
  $smarty->assign("sport_id", $manager->sport_id);
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_player_info.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_player_info.smarty'.($stop-$start);

// ----------------------------------------------------------------------------

include('inc/top_very_small.inc.php');
// content
echo $content;

// ----------------------------------------------------------------------------
// include common footer
include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');

?>
