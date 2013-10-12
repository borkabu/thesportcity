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
include('class/rvs_manager_user.inc.php');

// --- build content data -----------------------------------------------------
$content = '';
if (isset($_GET['user_id']) && isset($_GET['league_id'])) 
 {
//$db->showquery=true;
  $manager = new Manager('', 'rvs');
  $last_tour = $manager->getLastTour();

     $sql="SELECT DISTINCT U.USER_NAME, MM.LAST_NAME, MM.FIRST_NAME, MM.TEAM_TYPE, MM.TEAM_NAME2, CD.COUNTRY_NAME, MPS.TOTAL_POINTS
          FROM users U, rvs_manager_teams MT
                LEFT JOIN manager_market MM ON MT.PLAYER_ID = MM.USER_ID AND MM.SEASON_ID=".$manager->mseason_id."
                LEFT JOIN manager_player_stats MPS on MT.PLAYER_ID = MPS.PLAYER_ID AND MPS.TOUR_ID=".$last_tour." AND MPS.SEASON_ID=".$manager->mseason_id."
	      , teams T 
			LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
         WHERE MT.USER_ID=".$_GET['user_id']." 
	       AND U.USER_ID=MT.USER_ID
               AND MT.SELLING_DATE IS NULL  
               AND MT.LEAGUE_ID=".$_GET['league_id']."
	       AND T.TEAM_ID=MM.TEAM_ID
         ORDER BY MM.LAST_NAME, MM.FIRST_NAME";

      $db->query($sql);
//echo $sql;
      $c = 0;
      $players = array();
      while ($row = $db->nextRow()) {
         $user_name=$row['USER_NAME'];
         $player = $row;
         $player['NUMBER'] = $c+1;
         if ($row['TEAM_TYPE'] == 2)
 	   $player['TEAM_NAME2'] = $row['COUNTRY_NAME'];

         $c++;
         $players[]=$player;
       }
      $db->free();

  $smarty->assign("user_name", $user_name);
  $smarty->assign("players", $players);
}

$content = $smarty->fetch('smarty_tpl/rvs_manager_user_team.smarty');    
// ----------------------------------------------------------------------------

// content
// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

include('inc/bot_small.inc.php');
// close connections
include('class/db_close.inc.php');

?>