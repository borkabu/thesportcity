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
// --- build content data -----------------------------------------------------

  $content = '';
    
  if (isset($_GET['user_id']) && isset($_GET['league_id'])) {
       $sql="SELECT * FROM rvs_manager_leagues WHERE LEAGUE_ID=".$_GET['league_id'];
       $db->query($sql);   
       $row = $db->nextRow();

       if ($row['LEAGUE_TYPE'] == 0) {
         $sql = "SELECT DISTINCT U.USER_NAME, U.USER_ID, MUT.POINTS AS KOEFF, MUT.USER_ID AS PLAYER_ID, MTR.NUMBER, MTR.START_DATE, MTR.END_DATE
		 FROM rvs_manager_leagues ML, rvs_manager_users_tours MUT, users U, manager_tours MTR
		WHERE U.USER_ID = MUT.USER_ID
			AND MUT.LEAGUE_ID=ML.LEAGUE_ID
			AND MUT.TOUR_ID = MTR.NUMBER 
			AND ML.SEASON_ID = MTR.SEASON_ID 
                        AND U.USER_ID=".$_GET['user_id']."
			AND ML.LEAGUE_ID=".$_GET['league_id']."
                ORDER BY MTR.NUMBER";
       } else {
         $sql = "SELECT DISTINCT U.USER_NAME, U.USER_ID, MUT.TOUR_POINTS AS KOEFF, MUT.USER_ID AS PLAYER_ID, MTR.NUMBER, MTR.START_DATE, MTR.END_DATE
		 FROM rvs_manager_leagues ML, rvs_manager_users_tours_categories MUT, users U, manager_tours MTR
		WHERE U.USER_ID = MUT.USER_ID
			AND MUT.LEAGUE_ID=ML.LEAGUE_ID
			AND MUT.TOUR_ID = MTR.NUMBER 
			AND ML.SEASON_ID = MTR.SEASON_ID 
                        AND U.USER_ID=".$_GET['user_id']."
			AND ML.LEAGUE_ID=".$_GET['league_id']."
                ORDER BY MTR.NUMBER";
       }
       $db->query($sql);   
//echo $sql;
         $tourstats = array();
         while ($row = $db->nextRow()) {
           $tourstat = $row;
           $user_name  = $row['USER_NAME'];

           $tourstats[] = $tourstat;
         }

       $smarty->assign("user_name", $user_name);
       if (count($tourstats) > 0)
         $smarty->assign("tourstats", $tourstats);
 } 

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/rvs_manager_user_tours.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_user_tours.smarty'.($stop-$start);

// ----------------------------------------------------------------------------

// close connections
// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

// close connections
include('class/db_close.inc.php');
?>