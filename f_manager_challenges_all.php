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
include('class/manager_user.inc.php');
// --- build content data -----------------------------------------------------

// include common header
$content = '';

  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);

  $manager = new Manager($_GET['mseason_id']);

  $sql = "SELECT DISTINCT MC.TOUR_ID
		 FROM manager_challenges MC
		WHERE MC.SEASON_ID=".$_GET['mseason_id']." 
            ORDER BY MC.TOUR_ID";
  $db->query($sql);   

   $last_tour = 0;
   $tours = array();
   while ($row = $db->nextRow()) {
      if ($row['TOUR_ID'] > 0) {
         $last_tour = $row['TOUR_ID'];
         $state = 'NORMAL'; 
         if ((isset($_GET['tour']) && $row['TOUR_ID'] == $_GET['tour']))
           $state = 'SELECTED'; 
         unset($ttour);
         $ttour[$state] = $row;
         $ttour[$state]['NUMBER'] = $row['TOUR_ID'];
         $ttour[$state]['SEASON_ID'] = $_GET['mseason_id'];
         $tours[] = $ttour;
      }
   }
   if (!isset($_GET['tour']))
     $_GET['tour'] = $last_tour;

  // show list of current challenges
  $sql="SELECT U.USER_NAME, U.USER_ID, U1.USER_NAME as USER_NAME1, U1.USER_ID as USER_ID1, MC.DATE_CHALLENGED, MC.DATE_ACCEPTED, 		
		MC.STAKE, MC.TOUR_ID, MC.SCORE1, MC.SCORE2, MUT.POINTS, MUT1.POINTS as POINTS1
             FROM manager_challenges MC
                    LEFT JOIN users U ON MC.USER_ID=U.USER_ID
                    LEFT JOIN manager_users_tours MUT ON MUT.USER_ID=MC.USER_ID 
							and MUT.TOUR_ID=".$_GET['tour']."
							and MUT.SEASON_ID=".$_GET['mseason_id']."
                    LEFT JOIN users U1 ON MC.USER2_ID=U1.USER_ID
                    LEFT JOIN manager_users_tours MUT1 ON MUT1.USER_ID=MC.USER2_ID 
							and MUT1.TOUR_ID=".$_GET['tour']."
							and MUT1.SEASON_ID=".$_GET['mseason_id']."
             WHERE MC.SEASON_ID=".$_GET['mseason_id']." 
		AND MC.STATUS in (2,4)
		AND MC.TOUR_ID = ".$_GET['tour']."
            ORDER BY MC.CHALLENGE_ID";
  $db->query($sql);    

  $challenges = array();
  while ($row = $db->nextRow()) {
    $challenge = $row;
    $challenges[] = $challenge;
  }


  $smarty->assign("tours", $tours);
  $smarty->assign("challenges", $challenges);
  $content = $smarty->fetch('smarty_tpl/f_manager_challenges_all.smarty');    

// ----------------------------------------------------------------------------
 define("FANTASY_MANAGER", 1);
// include common header
//include('inc/top.inc.php');
include('inc/top_very_small.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');
?>