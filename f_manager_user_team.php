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
if (isset($_GET['user_id'])) 
 {
//$db->showquery=true;
  $manager = new Manager();

  $allow_all = false;
  $allow_view_points = false;
  $db->select("manager_seasons", "*", "START_DATE < NOW() AND END_DATE > NOW()");
  if (!$row = $db->nextRow()) {
    $allow_all = true;
  }

  $user = new User($_GET['user_id']);
  $user->getUserIdFromId($_GET['user_id']);
  $last_tour = $manager->getLastTour();

  $where_view_points = '';
  $fields_view_points = '';
  if ($auth->hasSupporter() && $last_tour > 0) {
    $where_view_points = " LEFT JOIN manager_player_stats MPS on MT.PLAYER_ID = MPS.PLAYER_ID AND MPS.TOUR_ID=".$last_tour." AND MPS.SEASON_ID=".$manager->mseason_id;
    $fields_view_points = ', MPS.TOTAL_POINTS';
    $allow_view_points = true;
  }

  $db->select("manager_users", "ALLOW_VIEW", "USER_ID=".$_GET['user_id']." AND ALLOW_VIEW='1' AND SEASON_ID=".$manager->mseason_id);
  if ($row = $db->nextRow() || $allow_all || ($auth->hasSupporter() && !$manager->manager_trade_allow)) {
     $sql="SELECT DISTINCT U.USER_NAME, MM.LAST_NAME, MM.FIRST_NAME, MC.ENTRY_ID AS CAPTAIN, MM.TEAM_TYPE, 
		MM.TEAM_NAME2, CD.COUNTRY_NAME".$fields_view_points."
          FROM users U,manager_teams MT
                LEFT JOIN manager_market MM ON MT.PLAYER_ID = MM.USER_ID AND MM.SEASON_ID=".$manager->mseason_id."
  	        LEFT JOIN manager_captain MC ON MT.ENTRY_ID=MC.ENTRY_ID AND MC.END_DATE IS NULL 
		".$where_view_points."
	      , teams T 
			LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
         WHERE MT.USER_ID=".$_GET['user_id']." AND U.USER_ID=MT.USER_ID
               AND MT.SELLING_DATE IS NULL  AND MT.SEASON_ID=".$manager->mseason_id."
	       AND T.TEAM_ID=MM.TEAM_ID
         ORDER BY MM.LAST_NAME, MM.FIRST_NAME";
      $db->query($sql);

      $c = 0;
      $players = array();
      while ($row = $db->nextRow()) {
         $user_name=$row['USER_NAME'];
         $player = $row;
         $player['NUMBER'] = $c+1;
         if ($row['TEAM_TYPE'] == 2)
 	   $player['TEAM_NAME2'] = $row['COUNTRY_NAME'];

         if (!empty($row['CAPTAIN']))
           $player['CAPTAINCY'] = 1;
         $c++;
         $players[]=$player;
       }
      $db->free();

     $sql="SELECT DISTINCT U.USER_NAME, MM.LAST_NAME, MM.FIRST_NAME, MM.TEAM_TYPE, MM.TEAM_NAME2, 
		CD.COUNTRY_NAME".$fields_view_points."
          FROM users U,manager_teams_substitutes MT
                LEFT JOIN manager_market MM ON MT.PLAYER_ID = MM.USER_ID AND MM.SEASON_ID=".$manager->mseason_id."
		".$where_view_points."
	      , teams T 
			LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
         WHERE MT.USER_ID=".$_GET['user_id']." AND U.USER_ID=MT.USER_ID
               AND MT.SELLING_DATE IS NULL  AND MT.SEASON_ID=".$manager->mseason_id."
	       AND T.TEAM_ID=MM.TEAM_ID
         ORDER BY MM.LAST_NAME, MM.FIRST_NAME";
//echo $sql;
      $db->query($sql);

      $subst_players = array();
      while ($row = $db->nextRow()) {

         $subst_player = $row;
         $subst_player['NUMBER'] = $c+1;
         if ($row['TEAM_TYPE'] == 2)
 	   $subst_player['TEAM_NAME2'] = $row['COUNTRY_NAME'];
         $subst_players[] = $subst_player;
         $c++;
       }

    $smarty->assign("allow_view_points", $allow_view_points);
    $smarty->assign("user_name", $user->user_name);
    $smarty->assign("players", $players);
    $smarty->assign("allow_substitutes", $manager->allow_substitutes);
    $smarty->assign("subst_players", $subst_players);

  }

}

$content = $smarty->fetch('smarty_tpl/f_manager_user_team.smarty');    
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