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
 $manager = new Manager($_GET['mseason_id']);
 if ($auth->userOn() && $auth->hasSupporter()) {
   if (!$manager->manager_trade_allow) {
     if (isset($_GET['username'])) {
       $_POST['user1'] = $auth->getUserName();
       $_POST['user2'] = $_GET['username'];
     }
     if (isset($_POST['user1']) && isset($_POST['user2']) && !empty($_POST['user1']) && !empty($_POST['user2'])) {
       $user = new User();
       $user->getUserIdFromUsername($_POST['user1']);
       $user2 = new User();
       $user2->getUserIdFromUsername($_POST['user2']);
  
       $last_tour = $manager->getLastTour();
     //$db->showquery=true;
   
       $sql="SELECT DISTINCT U.USER_ID, MT.PLAYER_ID, MM.LAST_NAME, MM.FIRST_NAME, MC.ENTRY_ID AS CAPTAIN, MM.TEAM_TYPE, 
		MM.TEAM_NAME2, CD.COUNTRY_NAME, MPS.TOTAL_POINTS
            FROM users U, manager_teams MT
                  LEFT JOIN manager_market MM ON MT.PLAYER_ID = MM.USER_ID AND MM.SEASON_ID=".$manager->mseason_id."
    	        LEFT JOIN manager_captain MC ON MT.ENTRY_ID=MC.ENTRY_ID AND MC.END_DATE IS NULL 
                  LEFT JOIN manager_player_stats MPS on MT.PLAYER_ID = MPS.PLAYER_ID AND MPS.TOUR_ID=".$last_tour." AND MPS.SEASON_ID=".$manager->mseason_id."
	      , teams T 
			LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
           WHERE MT.USER_ID in (".$user->user_id.", ".$user2->user_id.") AND U.USER_ID=MT.USER_ID
                 AND MT.SELLING_DATE IS NULL  AND MT.SEASON_ID=".$manager->mseason_id."
	       AND T.TEAM_ID=MM.TEAM_ID
           ORDER BY CAPTAIN DESC, MPS.TOTAL_POINTS DESC, MM.LAST_NAME, MM.FIRST_NAME";
        $db->query($sql);
     //echo $sql;
        $c = 0;
        $players = array();
        $team[$user->user_id]  = array();
	$team[$user2->user_id]  = array();
        while ($row = $db->nextRow()) {
           $player = $row;
           $player['NUMBER'] = $c+1;
           if ($row['TEAM_TYPE'] == 2)
  	   $player['TEAM_NAME2'] = $row['COUNTRY_NAME'];
  
           if (!empty($row['CAPTAIN']))
             $player['CAPTAINCY'] = 1;
           $c++;
           $team[$row['USER_ID']][$row['PLAYER_ID']]=$player;
         }
        $db->free();
  
     //  print_r($team);
       if (count($team[$user->user_id]) == 0 ||  count($team[$user2->user_id]) == 0) {
         $error['MSG'] = $langs['LANG_MANAGER_LEAGUE_NO_TEAM_U'];
       } else {
         $common_players = array_intersect_key($team[$user->user_id], $team[$user2->user_id]);
//         $common_players2 = array_intersect_key($team[$user2->user_id], $team[$user->user_id]);
         $different_players1 = array_diff_key($team[$user->user_id], $team[$user2->user_id]);
         $different_players2 = array_diff_key($team[$user2->user_id], $team[$user->user_id]);

/*         $c=0;
         foreach ($common_players1 as $player) {
           $common_players[$c][0] = $player;
           $c++;
         }
    
         $c=0;
         foreach ($common_players2 as $player) {
           $common_players[$c][1] = $player;
           $c++;
         }*/

         $c=0;
         foreach ($different_players1 as $player) {
           $different_players[$c][0] = $player;
           $c++;
         }
    
         $c=0;
         foreach ($different_players2 as $player) {
           $different_players[$c][1] = $player;
           $c++;
         }
    
         $smarty->assign("user_name", $_POST['user1']);
         $smarty->assign("user_name2", $_POST['user2']);
         $smarty->assign("user_id", $user->user_id);
         $smarty->assign("user_id2", $user2->user_id);
         $smarty->assign("common_players", $common_players);
         $smarty->assign("different_players", $different_players);
         $smarty->assign("team", $team);
       }
     } else {
      $error['MSG'] = $langs['LANG_ERROR_BAD_USER_NAMES_U'];
     }
     $smarty->assign("can_compare", true);
   } else {
    $error['MSG'] = $langs['LANG_ERROR_MANAGER_MARKET_OPENED_U'];
   }
 } else {
  $error['MSG'] = $langs['LANG_ERROR_GC_U'];
 }
 if (isset($error))
   $smarty->assign("error", $error);
 $smarty->assign("mseason_id", $_GET['mseason_id']);

$content = $smarty->fetch('smarty_tpl/f_manager_user_team_compare.smarty');    
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