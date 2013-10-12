<?php
error_reporting(E_ALL);
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
   
if (isset($_GET['battle_id'])) {

//$db->showquery=true;
  $forumPermission = new ForumPermission();
  if (isset($_POST['post_comment']) && $auth->userOn() &&
     $forumPermission->canAddComment($_POST['topic_id']) == 0) {
     $forumbox->addPost($forums['MANAGER_BATTLES'], $_POST['topic_id'], $_POST['item_id']);
  }

  if ($auth->userOn()) {
  // show list of active battles
    $sql="SELECT MC.BATTLE_ID, U.USER_NAME, U.USER_ID, MC.DATE_INITIATED, MC.DATE_COMMITED, 
		C.CCTLD, CD.COUNTRY_NAME, MU2.ALLOW_VIEW, MC.STAKE, MC.TYPE, MC.STATUS, MC.PARTICIPANTS, MC.JOINED,
		MC.STAKE, MC.PRIZE_FUND, IFNULL(MUT.POINTS, 0) as POINTS, MS.PLACE, MC.PLACES_LIMIT, MS2.PLACE as OWNER_PLACE,
		U2.USER_ID as USER_ID2, U2.USER_NAME as USER_NAME2, MBM2.USER_ID as IN_BATTLE, MBM.TEAM_ID, MC.TOPIC_ID, T.POSTS
             FROM users U, manager_users MU, countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
		, manager_battles MC
                LEFT JOIN topic T ON MC.topic_id=T.topic_id 
		LEFT JOIN manager_standings MS2 ON MS2.USER_ID=MC.USER_ID
						AND MS2.MSEASON_ID=MC.SEASON_ID
		LEFT JOIN manager_battles_members MBM ON MC.BATTLE_ID=MBM.BATTLE_ID
                    left join users U2 on U2.USER_ID=MBM.USER_ID
                    left join manager_users MU2 on MU2.USER_ID=MBM.USER_ID
							AND MU2.SEASON_ID=MC.SEASON_ID
		    left join manager_users_tours MUT on MUT.season_id=MC.SEASON_ID
 							AND MUT.TOUR_ID=MC.TOUR_ID
							AND MUT.USER_ID=MBM.USER_ID
		LEFT JOIN manager_battles_members MBM2 ON MC.BATTLE_ID=MBM2.BATTLE_ID
                    					AND MBM2.USER_ID=".$auth->getUserId()."
		LEFT JOIN manager_standings MS ON MS.USER_ID=MBM.USER_ID
						AND MS.MSEASON_ID=MC.SEASON_ID 

             WHERE 
		MC.BATTLE_ID=".$_GET['battle_id']." 
	       AND MC.USER_ID=U.USER_ID	
		AND MU.USER_ID=U.USER_ID
		AND MU.SEASON_ID=MC.SEASON_ID
   	        AND U2.COUNTRY = C.ID
                AND MC.STATUS IN (4, 2)
	     ORDER BY MC.BATTLE_ID, IFNULL(MS.PLACE, 10000)";
   } else {
    $sql="SELECT MC.BATTLE_ID, U.USER_NAME, U.USER_ID, MC.DATE_INITIATED, MC.DATE_COMMITED, 
		C.CCTLD, CD.COUNTRY_NAME, MU2.ALLOW_VIEW, MC.STAKE, MC.TYPE, MC.STATUS, MC.PARTICIPANTS, MC.JOINED,
		MC.STAKE, MC.PRIZE_FUND, IFNULL(MUT.POINTS, 0) as POINTS, MS.PLACE, MC.PLACES_LIMIT,
		U2.USER_ID as USER_ID2, U2.USER_NAME as USER_NAME2, NULL as IN_BATTLE, MBM.TEAM_ID, MC.TOPIC_ID, T.POSTS
             FROM users U, manager_users MU, countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
		, manager_battles MC
                LEFT JOIN topic T ON MC.topic_id=T.topic_id 
		LEFT JOIN manager_battles_members MBM ON MC.BATTLE_ID=MBM.BATTLE_ID
		LEFT JOIN manager_standings MS ON MS.USER_ID=MBM.USER_ID
						AND MS.MSEASON_ID=MC.SEASON_ID
                    left join users U2 on U2.USER_ID=MBM.USER_ID
                    left join manager_users MU2 on MU2.USER_ID=MBM.USER_ID
							AND MU2.SEASON_ID=MC.SEASON_ID
		    left join manager_users_tours MUT on MUT.season_id=MC.SEASON_ID
 							AND MUT.TOUR_ID=MC.TOUR_ID
							AND MUT.USER_ID=MBM.USER_ID
             WHERE 		
		MC.BATTLE_ID=".$_GET['battle_id']." 
	       AND MC.USER_ID=U.USER_ID	
		AND MU.USER_ID=U.USER_ID
		AND MU.SEASON_ID=MC.SEASON_ID
   	        AND U2.COUNTRY = C.ID
                AND MC.STATUS IN (4, 2)
	     ORDER BY MC.BATTLE_ID, IFNULL(MS.PLACE, 10000)";
   }

      $db->query($sql);    
//echo $sql;
      $c = 0;
      $stakes = 0;
      $topic_id = '';
      $active_open_battles = array();
      $active_closed_battles = array();
      while ($row = $db->nextRow()) {
        $active_battle = $row;
        if (!empty($row['CCTLD'])) {
          $active_battle['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
          $active_battle['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
        }
       
        $topic_id = $row['TOPIC_ID'];
        if ($auth->getUserId() == $row['USER_ID2']) {          
          if ($row['ALLOW_VIEW'] == '-1') {
            $active_battle['NOTALLOW'] = 1;
          }
          else {
            $active_battle['ALLOW'] = 1;
          }
        }
        else {
/*          if (($auth->hasSupporter() && (!$manager->manager_trade_allow || $manager->season_over)) || $row['ALLOW_VIEW'] == '1') {
            $active_battle['ALLOW'] = 1;
          }
          else {*/
            $active_battle['NOTALLOW'] = 1;
//          }
        }

        if (!isset($active_closed_battles[$active_battle['BATTLE_ID']]))
          $active_closed_battles[$active_battle['BATTLE_ID']] = $active_battle;
        $active_closed_battles[$active_battle['BATTLE_ID']]['TEAM'.$active_battle['TEAM_ID']][] = $active_battle;
        if (isset($active_closed_battles[$active_battle['BATTLE_ID']]['SCORE'.$active_battle['TEAM_ID']]))
          $active_closed_battles[$active_battle['BATTLE_ID']]['SCORE'.$active_battle['TEAM_ID']] += $active_battle['POINTS'];
        else $active_closed_battles[$active_battle['BATTLE_ID']]['SCORE'.$active_battle['TEAM_ID']] = $active_battle['POINTS'];
      }

  $smarty->assign("battle_id", $_GET['battle_id']);
  $smarty->assign("topic_id", $topic_id);
  $smarty->assign("user_on", $auth->userOn());
  if (isset($active_closed_battles) && count($active_closed_battles) > 0)
    $smarty->assign("active_closed_battles", $active_closed_battles);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_battle.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_battle.smarty'.($stop-$start);

  $content .= $forumbox->getComments($_GET['battle_id'], 'MANAGER_BATTLES', isset($_GET['page']) ? $_GET['page'] : 1);
} else {
}


// ----------------------------------------------------------------------------

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