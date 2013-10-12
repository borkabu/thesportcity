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
   
  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);

  $manager = new Manager();

  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
  if ($auth->userOn())
    $manager_user = new ManagerUser($manager->mseason_id);

  $manager_filter_box = $managerbox->getManagerFilterBox($manager->getSeason());
  $current_tour = $manager->getCurrentTour();

  if ($manager->manager_trade_allow && $auth->userOn() && isset($_POST['start_battle'])) {
    if ($_POST['stake'] <= $auth->getCredits()) {
      unset($sdata);
      $sdata['USER_ID'] = $auth->getUserId();
      $sdata['STAKE'] = $_POST['stake'] >= 0 ? $_POST['stake'] : 0;
      $sdata['STATUS'] = 0;
      $sdata['TYPE'] = 0;   
      $sdata['PARTICIPANTS'] = ($_POST['participants'] % 2 == 0) ? $_POST['participants'] : ($_POST['participants'] + 1);
      $sdata['SEASON_ID'] = $manager->mseason_id;
      $sdata['TOUR_ID'] = $current_tour;
      $sdata['PLACES_LIMIT'] = $_POST['place_limit'];
      $sdata['DATE_INITIATED'] = "NOW()";
      $sdata['PRIZE_FUND'] = $_POST['stake'];
      $db->insert('manager_battles', $sdata);
      $battle_id = $db->id();
      unset($sdata);
      $sdata['BATTLE_ID'] = $battle_id;
      $sdata['USER_ID'] = $auth->getUserId();
      $sdata['DATE_JOINED'] = "NOW()";
      $db->insert('manager_battles_members', $sdata);
  
      if ($_POST['stake'] > 0) {
        $credits = new Credits();
        $credit_log = new CreditsLog();
        $credits->updateCredits($auth->getUserId(), -1*$_POST['stake']); 
        $credit_log->logEvent ($auth->getUserId(), 5, $_POST['stake']);
      }
      header('Location: f_manager_battles.php');
    } else {
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_NOT_ENOUGH_CREDIT');
    }
//print_r($sdata);

  }

  if ($manager->manager_trade_allow && $auth->userOn() && isset($_POST['cancel_battle']) && isset($_POST['battle_id'])) {
    $sql = "SELECT * FROM manager_battles MB 
		WHERE MB.BATTLE_ID=".$_POST['battle_id']."
			AND MB.JOINED = 1
			AND MB.STATUS = 0
			AND MB.USER_ID=".$auth->getUserId();
     $db->query($sql);    
     if ($row = $db->nextRow()) {
       $db->delete("manager_battles_members", "BATTLE_ID=".$_POST['battle_id']);
       $db->delete("manager_battles", "BATTLE_ID=".$_POST['battle_id']);
       if ($row['STAKE'] > 0) {
         $credits = new Credits();
         $credit_log = new CreditsLog();
         $credits->updateCredits($auth->getUserId(), $row['STAKE']); 
         $credit_log->logEvent ($auth->getUserId(), 7, $row['STAKE']);
       }
     }
     header('Location: f_manager_battles.php');
  }

  if ($manager->manager_trade_allow && $auth->userOn() && isset($_POST['join_battle']) && isset($_POST['battle_id'])) {
     $sql = "SELECT MB.STAKE, MB.PARTICIPANTS, MB.JOINED, MBM.USER_ID FROM manager_battles MB
		   left join manager_battles_members MBM ON
					MBM.BATTLE_ID = MB.BATTLE_ID
					AND MBM.USER_ID=".$auth->getUserId()."
		WHERE MB.BATTLE_ID=".$_POST['battle_id']."
			AND MB.STATUS = 0";
//echo $sql;
     $db->query($sql);    
     if ($row = $db->nextRow()) {
       if ($row['USER_ID'] == '' && 
	  ($row['PARTICIPANTS'] == 0 || $row['PARTICIPANTS'] - $row['JOINED'] > 0) &&
           $row['STAKE'] <= $auth->getCredits()) {
         unset($sdata);
         $sdata['BATTLE_ID'] = $_POST['battle_id'];
         $sdata['USER_ID'] = $auth->getUserId();
         $sdata['DATE_JOINED'] = "NOW()";
         $db->insert("manager_battles_members", $sdata);
         unset($sdata);
         $sdata['JOINED'] = "JOINED+1";
         $sdata['PRIZE_FUND'] = "PRIZE_FUND+".$row['STAKE'];        
         if ($row['PARTICIPANTS'] > 0 && $row['PARTICIPANTS'] -  $row['JOINED'] == 1) {
           $sdata['STATUS'] = 1;
         }
         $db->update("manager_battles", $sdata, "BATTLE_ID=".$_POST['battle_id']);

         if ($row['STAKE'] > 0) {
           $credits = new Credits();
           $credit_log = new CreditsLog();
           $credits->updateCredits($auth->getUserId(), -1*$row['STAKE']); 
           $credit_log->logEvent ($auth->getUserId(), 5, $row['STAKE']);
         }
       }
     }
     header('Location: f_manager_battles.php');
  }

  if ($manager->manager_trade_allow && $auth->userOn() && isset($_POST['leave_battle']) && isset($_POST['battle_id'])) {
    $sql = "SELECT * FROM manager_battles MB, manager_battles_members MBM 
		WHERE MB.BATTLE_ID=".$_POST['battle_id']."
			AND MBM.BATTLE_ID = MB.BATTLE_ID
			AND MB.STATUS = 0
			AND MBM.USER_ID <> MB.USER_ID
			AND MBM.USER_ID=".$auth->getUserId();
     $db->query($sql);    
     if ($row = $db->nextRow()) {
       $db->delete("manager_battles_members", "BATTLE_ID=".$_POST['battle_id']." AND USER_ID=".$auth->getUserId());
       unset($sdata);
       $sdata['JOINED'] = "JOINED-1";
       $sdata['PRIZE_FUND'] = "PRIZE_FUND-".$row['STAKE'];
       $db->update("manager_battles", $sdata, "BATTLE_ID=".$_POST['battle_id']);
       if ($row['STAKE'] > 0) {
         $credits = new Credits();
         $credit_log = new CreditsLog();
         $credits->updateCredits($auth->getUserId(), $row['STAKE']); 
         $credit_log->logEvent ($auth->getUserId(), 7, $row['STAKE']);
       }
     }
     header('Location: f_manager_battles.php');
  }

//$tpl->setCacheLevel(TPL_CACHE_NOTHING);

$manager->generateTeamsForBattles();


  // show if challenges can be held
  // from second tour, team must be updated, +-10 points range, team quality > 95%
  $last_tour = $manager->getLastTour();
  $current_tour = $manager->getCurrentTour();
//echo $current_tour;
//echo $last_tour;
  // start battle offer
  $can_start_battle = false;

   // inited
  if ($auth->userOn()) {
    if ($manager->manager_trade_allow) {
      if ($manager_user->inited && $_SESSION['_user']['MANAGER'][$manager->mseason_id]['PLACE'] > 0) {
        if ($auth->getCredits() >= 0) {
          if ($manager_user->canStartBattle($current_tour)) {
            $can_start_battle = true;
          }
          else $start_battle_error = $langs['LANG_ERROR_MANAGER_START_BATTLE_LIMIT_U'];
        }
        else $start_battle_error = $langs['LANG_ERROR_MANAGER_START_BATTLE_NO_CREDITS_U'];
      }
      else $start_battle_error = $langs['LANG_ERROR_MANAGER_START_BATTLE_NO_TEAM_U'];
    }
    else $start_battle_error = $langs['LANG_ERROR_MANAGER_BATTLES_MARKET_CLOSED_U'];
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
						AND MS2.MSEASON_ID=".$manager->mseason_id." 
		LEFT JOIN manager_battles_members MBM ON MC.BATTLE_ID=MBM.BATTLE_ID
                    left join users U2 on U2.USER_ID=MBM.USER_ID
                    left join manager_users MU2 on MU2.USER_ID=MBM.USER_ID
							AND MU2.SEASON_ID=".$manager->mseason_id." 
		    left join manager_users_tours MUT on MUT.season_id=MC.SEASON_ID
 							AND MUT.TOUR_ID=MC.TOUR_ID
							AND MUT.USER_ID=MBM.USER_ID
		LEFT JOIN manager_battles_members MBM2 ON MC.BATTLE_ID=MBM2.BATTLE_ID
                    					AND MBM2.USER_ID=".$auth->getUserId()."
		LEFT JOIN manager_standings MS ON MS.USER_ID=MBM.USER_ID
						AND MS.MSEASON_ID=".$manager->mseason_id." 

             WHERE MC.SEASON_ID=".$manager->mseason_id." 
	       AND MC.USER_ID=U.USER_ID	
		AND MU.USER_ID=U.USER_ID
		AND MU.SEASON_ID=".$manager->mseason_id." 
   	        AND U2.COUNTRY = C.ID
                AND MC.STATUS IN (0, 2)
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
						AND MS.MSEASON_ID=".$manager->mseason_id." 
                    left join users U2 on U2.USER_ID=MBM.USER_ID
                    left join manager_users MU2 on MU2.USER_ID=MBM.USER_ID
							AND MU2.SEASON_ID=".$manager->mseason_id." 
		    left join manager_users_tours MUT on MUT.season_id=MC.SEASON_ID
 							AND MUT.TOUR_ID=MC.TOUR_ID
							AND MUT.USER_ID=MBM.USER_ID
             WHERE MC.SEASON_ID=".$manager->mseason_id." 
	       AND MC.USER_ID=U.USER_ID	
		AND MU.USER_ID=U.USER_ID
		AND MU.SEASON_ID=".$manager->mseason_id." 
   	        AND U2.COUNTRY = C.ID
                AND MC.STATUS IN (0, 2)
	     ORDER BY MC.BATTLE_ID, IFNULL(MS.PLACE, 10000)";
   }

      $db->query($sql);    
//echo $sql;
      $c = 0;
      $stakes = 0;
      $active_open_battles = array();
      $active_closed_battles = array();
      while ($row = $db->nextRow()) {
        $active_battle = $row;
        if (!empty($row['CCTLD'])) {
          $active_battle['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
          $active_battle['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
        }
       
        if ($auth->getUserId() == $row['USER_ID2']) {          
          if ($row['ALLOW_VIEW'] == '-1') {
            $active_battle['NOTALLOW'] = 1;
          }
          else {
            $active_battle['ALLOW'] = 1;
          }
        }
        else {
          if (($auth->hasSupporter() && (!$manager->manager_trade_allow || $manager->season_over)) || $row['ALLOW_VIEW'] == '1') {
            $active_battle['ALLOW'] = 1;
          }
          else {
            $active_battle['NOTALLOW'] = 1;
          }
        }
        if (empty($row['POSTS']))
          $active_battle['POSTS'] = 0;

        if ($active_battle['STATUS'] == 0) {
            if ($auth->userOn() 
		&& empty($active_battle['IN_BATTLE']) 
		&& $active_battle['STAKE'] <= $auth->getCredits() 
		&& $manager_user->inited
		&& (($active_battle['PLACES_LIMIT'] == 0) || 
		    ($_SESSION['_user']['MANAGER'][$manager->mseason_id]['PLACE'] > 0 &&
                      ($active_battle['OWNER_PLACE'] + $active_battle['PLACES_LIMIT'] > $_SESSION['_user']['MANAGER'][$manager->mseason_id]['PLACE']
                      && $active_battle['OWNER_PLACE'] - $active_battle['PLACES_LIMIT'] < $_SESSION['_user']['MANAGER'][$manager->mseason_id]['PLACE'])))) {
              $active_battle['CAN_JOIN'] = 1;
            } else if (!$auth->userOn()) {
                $active_battle['CANT_JOIN_LOGIN'] = 1;
            } else if (!$manager_user->inited || empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['PLACE'])) {
                $active_battle['CANT_JOIN_NO_TEAM_OR_PLACE'] = 1;
            } else if (empty($active_battle['IN_BATTLE']) && $active_battle['STAKE'] > $auth->getCredits() && $manager_user->inited) {
                $active_battle['CANT_JOIN_NO_CREDITS'] = 1;
            } else if ((($active_battle['PLACES_LIMIT'] > 0) && 
				(empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['PLACE'])
				  || $active_battle['OWNER_PLACE'] + $active_battle['PLACES_LIMIT'] < $_SESSION['_user']['MANAGER'][$manager->mseason_id]['PLACE']
	                          || $active_battle['OWNER_PLACE'] - $active_battle['PLACES_LIMIT'] > $_SESSION['_user']['MANAGER'][$manager->mseason_id]['PLACE']))) {
              $active_battle['CANT_JOIN_WRONG_PLACE'] = 1;
            }

            if (!empty($active_battle['IN_BATTLE']) && $active_battle['IN_BATTLE'] != $active_battle['USER_ID']) {
              $active_battle['CAN_LEAVE'] = 1;
            }
       
            if ($active_battle['PLACES_LIMIT'] > 0) {
              $active_battle['PLACES_LIMITATION'] = $battle_places_limit[$active_battle['PLACES_LIMIT']];
            }

            if (!empty($active_battle['IN_BATTLE']) && 
		$active_battle['IN_BATTLE'] == $active_battle['USER_ID'] &&
		$active_battle['JOINED'] == 1) {
              $active_battle['CAN_CANCEL'] = 1;
            } else if (!empty($active_battle['IN_BATTLE']) && 
		$active_battle['IN_BATTLE'] == $active_battle['USER_ID'] &&
		$active_battle['JOINED'] > 1) {
              $active_battle['CANT_CANCEL'] = 1;
            }

            if (!isset($active_open_battles[$active_battle['BATTLE_ID']]))
              $active_open_battles[$active_battle['BATTLE_ID']] = $active_battle;
            $counter = 1; 
            $counter2 = 0; 
            if (isset($active_open_battles[$active_battle['BATTLE_ID']]['PLAYERS1']))
              $counter += count($active_open_battles[$active_battle['BATTLE_ID']]['PLAYERS1']);
            if (isset($active_open_battles[$active_battle['BATTLE_ID']]['PLAYERS2']))
              $counter += count($active_open_battles[$active_battle['BATTLE_ID']]['PLAYERS2']);
            else $active_open_battles[$active_battle['BATTLE_ID']]['PLAYERS2'] = array();
            if ($counter %4 == 0 || $counter %4 == 1)
	      $counter2 = 1;
            else $counter2 = 2;
            $active_open_battles[$active_battle['BATTLE_ID']]['PLAYERS'.$counter2][] = $active_battle;
            $active_open_battles[$active_battle['BATTLE_ID']]['TOTAL_PLAYERS'] = $counter;

        } else if ($active_battle['STATUS'] == 2) {
          if (!isset($active_closed_battles[$active_battle['BATTLE_ID']]))
            $active_closed_battles[$active_battle['BATTLE_ID']] = $active_battle;
          $active_closed_battles[$active_battle['BATTLE_ID']]['TEAM'.$active_battle['TEAM_ID']][] = $active_battle;
          if (isset($active_closed_battles[$active_battle['BATTLE_ID']]['SCORE'.$active_battle['TEAM_ID']]))
            $active_closed_battles[$active_battle['BATTLE_ID']]['SCORE'.$active_battle['TEAM_ID']] += $active_battle['POINTS'];
          else $active_closed_battles[$active_battle['BATTLE_ID']]['SCORE'.$active_battle['TEAM_ID']] = $active_battle['POINTS'];
        }
      }

    if (!$auth->userOn()) {
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_LOGIN');
    } 
    else if ($current_tour < 2) {
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_BATTLES_TOO_EARLY');
    } else if ($manager->disabled_trade) {
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_BATTLES_MARKET_CLOSED');
    }

  $manager->getCompletedBattles();

  $rules = $pagebox->getPage(30);

  $smarty->assign("manager_filter_box", $manager_filter_box);
  $smarty->assign("rules", $rules);
  $smarty->assign("user_on", $auth->userOn());
  if (isset($active_open_battles) && count($active_open_battles) > 0)
    $smarty->assign("active_open_battles", $active_open_battles);

  if (isset($active_closed_battles) && count($active_closed_battles) > 0)
    $smarty->assign("active_closed_battles", $active_closed_battles);

  if (isset($can_start_battle) && $can_start_battle)
    $smarty->assign("can_start_battle", $can_start_battle);
  if (isset($error))
    $smarty->assign("error", $error);

  if (isset($start_battle_error))
    $smarty->assign("start_battle_error", $start_battle_error);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_battles.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_battles.smarty'.($stop-$start);

// ----------------------------------------------------------------------------
    define("FANTASY_MANAGER", 1);
// include common header
//include('inc/top.inc.php');
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager.inc.php');

// close connections
include('class/db_close.inc.php');
?>