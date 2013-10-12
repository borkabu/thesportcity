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

  $manager = new Manager();
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
  if ($auth->userOn())
    $manager_user = new ManagerUser($manager->mseason_id);

  $manager_filter_box = $managerbox->getManagerFilterBox($manager->getSeason());

if (!$auth->userOn()) {
  $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
  $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_LOGIN');
} else {
  // show if challenges can be held
  // from second tour, team must be updated, +-10 points range, team quality > 95%
  $current_tour = $manager->getCurrentTour();
  $last_tour = $manager->getLastTour();
//echo $current_tour;
//echo $last_tour;
  
  $stakes = $manager->getChallengesStakes($auth->getUserId());

  // show list of current challenges
  $sql="SELECT U.USER_NAME, U.USER_ID, MS.PLACE, MS.POINTS, MC.DATE_CHALLENGED, MC.DATE_ACCEPTED, 
		MUT1.POINTS TOUR_POINTS, MUT2.POINTS YOUR_POINTS, C.CCTLD, CD.COUNTRY_NAME, MU.ALLOW_VIEW, 
		MC.STAKE, MC.TYPE
             FROM users U, manager_users MU, manager_standings MS, countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
		, manager_challenges MC
		  left join manager_users_tours MUT1 on MUT1.season_id=MC.SEASON_ID
							AND MUT1.TOUR_ID=".$last_tour."
							AND MUT1.USER_ID=MC.USER_ID
		  left join manager_users_tours MUT2 on MUT2.season_id=MC.SEASON_ID
							AND MUT2.TOUR_ID=".$last_tour."
							AND MUT2.USER_ID=MC.USER2_ID
             WHERE MC.SEASON_ID=".$manager->mseason_id." 
               AND MC.USER2_ID=".$auth->getUserId()."
	       AND MC.USER_ID=U.USER_ID	
	       AND MS.Mseason_ID=MC.SEASON_ID
	       AND MS.Mseason_ID=MU.SEASON_ID
		AND MU.USER_ID=U.USER_ID
		AND MS.USER_ID=MC.USER_ID
   	        AND U.COUNTRY = C.ID
               AND MC.STATUS=2
	    UNION 
	    SELECT U.USER_NAME, U.USER_ID, MS.PLACE, MS.POINTS, MC.DATE_CHALLENGED, MC.DATE_ACCEPTED, 
		MUT1.POINTS TOUR_POINTS, MUT2.POINTS YOUR_POINTS, C.CCTLD, CD.COUNTRY_NAME, MU.ALLOW_VIEW, 
		MC.STAKE, MC.TYPE
             FROM users U, manager_users MU, manager_standings MS, countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
		, manager_challenges MC
		  left join manager_users_tours MUT1 on MUT1.season_id=MC.SEASON_ID
							AND MUT1.TOUR_ID=".$last_tour."
							AND MUT1.USER_ID=MC.USER2_ID
		  left join manager_users_tours MUT2 on MUT2.season_id=MC.SEASON_ID
							AND MUT2.TOUR_ID=".$last_tour."
							AND MUT2.USER_ID=MC.USER_ID

             WHERE MC.SEASON_ID=".$manager->mseason_id." 
               AND MC.USER_ID=".$auth->getUserId()."
	       AND MC.USER2_ID=U.USER_ID	
	       AND MS.Mseason_ID=MC.SEASON_ID
	       AND MS.Mseason_ID=MU.SEASON_ID
		AND MU.USER_ID=U.USER_ID
		AND MS.USER_ID=MC.USER2_ID
   	        AND U.COUNTRY = C.ID
               AND MC.STATUS=2	
            ORDER BY PLACE";
      $db->query($sql);    
//echo $sql;
      $c = 0;
      $accepted_challenges = array();
      $accepted_stake_credits = 0;
      $accepted_points = 0;
      while ($row = $db->nextRow()) {
        $accepted_challenge = $row;
        if (!empty($row['CCTLD'])) {
          $accepted_challenge['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
          $accepted_challenge['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
        }

	if ($auth->hasSupporter() || $row['ALLOW_VIEW'] == '1')
          $accepted_challenge['ALLOW'] = 1;
        else 
          $accepted_challenge['NOTALLOW'] = 1;
        if ($row['TYPE'] == 2) {
          $accepted_challenge['CREDITS'] = 1;
	  $accepted_stake_credits += $row['STAKE'];
        }
        $accepted_points = $row['YOUR_POINTS'];
        $accepted_challenges[] = $accepted_challenge;
      }

    if ($current_tour < 2) {
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_CHALLENGES_TOO_EARLY');
    } else if ($manager->disabled_trade) {
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_CHALLENGES_MARKET_CLOSED');
    } else {
      // also check that user can challenge himself
      $sql = "SELECT END_DATE
                 FROM manager_tours 
                 WHERE NUMBER=".($current_tour-1)."
                       AND SEASON_ID=".$manager->mseason_id;
      $db->query($sql);
      $row = $db->nextRow();
      $market_open_date = $row['END_DATE'];
  
//$db->showquery=true;
      if ($manager_user->canChallenge($market_open_date, $current_tour)) {
//echo $_SESSION['_user']['MANAGER'][$manager->mseason_id]['PLACE'];
        $place = $_SESSION['_user']['MANAGER'][$manager->mseason_id]['PLACE'];

        if ($_SESSION["_user"]['CREDIT'] >= 1)
          $credit_candidates = $manager_user->getCandidates($current_tour, $market_open_date, $place, 2);
        else $credit_candidates = '';
  
        if (count($credit_candidates)==0 || $credit_candidates == '')
	  $cleared['NOCANDIDATES'] = 1;
        else $cleared['CANDIDATES'] = $credit_candidates; 
      }
      else {
        $error = $langs['LANG_MANAGER_CHALLENGE_NOT_QUALIFIED_U'];
      }
    }

  // show list of completed challenges
  $sql="SELECT U1.USER_NAME USER_NAME1, U2.USER_NAME USER_NAME2, MS.PLACE, MS.POINTS, 
		MUT1.POINTS POINTS1, MUT2.POINTS POINTS2, MC.TOUR_ID, MC.STAKE, MC.TYPE
             FROM manager_standings MS, manager_challenges MC
		  left join manager_users_tours MUT1 on MUT1.season_id=MC.SEASON_ID
							AND MUT1.TOUR_ID=MC.TOUR_ID
							AND MUT1.USER_ID=MC.USER_ID
		  left join manager_users_tours MUT2 on MUT2.season_id=MC.SEASON_ID
							AND MUT2.TOUR_ID=MC.TOUR_ID
							AND MUT2.USER_ID=MC.USER2_ID
                  left join users U1 on U1.user_id=MC.USER_ID    
                  left join users U2 on U2.user_id=MC.USER2_ID
             WHERE MC.SEASON_ID=".$manager->mseason_id." 
               AND MC.USER2_ID=".$auth->getUserId()."
	       AND MS.Mseason_ID=MC.SEASON_ID
		AND MS.USER_ID=MC.USER_ID
               AND MC.STATUS=4
	    UNION 
	    SELECT U1.USER_NAME USER_NAME1, U2.USER_NAME USER_NAME2, MS.PLACE, MS.POINTS, 
		MUT1.POINTS POINTS1, MUT2.POINTS POINTS2, MC.TOUR_ID, MC.STAKE, MC.TYPE
             FROM manager_standings MS, manager_challenges MC
		  left join manager_users_tours MUT1 on MUT1.season_id=".$manager->mseason_id."
							AND MUT1.TOUR_ID=MC.TOUR_ID
							AND MUT1.USER_ID=MC.USER_ID
		  left join manager_users_tours MUT2 on MUT2.season_id=".$manager->mseason_id."
							AND MUT2.TOUR_ID=MC.TOUR_ID          
							AND MUT2.USER_ID=MC.USER2_ID
                  left join users U1 on U1.user_id=MC.USER_ID    
                  left join users U2 on U2.user_id=MC.USER2_ID
             WHERE MC.SEASON_ID=".$manager->mseason_id." 
               AND MC.USER_ID=".$auth->getUserId()."
	       AND MS.Mseason_ID=MC.SEASON_ID
		AND MS.USER_ID=MC.USER_ID
               AND MC.STATUS=4	
            ORDER BY TOUR_ID, USER_NAME1";
      $db->query($sql);    

//echo $sql;
      $completed_challenges = array();
      while ($row = $db->nextRow()) {
        $completed_challenge = $row;
        $completed_challenge['NUMBER'] = $c+1;
        if ($row['POINTS1'] > $row['POINTS2'])
          $completed_challenge['USER_NAME1_WON'] = 1;
        else if ($row['POINTS1'] < $row['POINTS2'])
          $completed_challenge['USER_NAME2_WON'] = 1;
        $completed_challenges[] = $completed_challenge;
      }

      $tours = array();
      $sql = "SELECT DISTINCT MC.TOUR_ID
		 FROM manager_challenges MC
		WHERE MC.SEASON_ID=".$manager->mseason_id." 
			AND (MC.USER_ID=".$auth->getUserId()." OR MC.USER2_ID=".$auth->getUserId().")
			AND MC.STATUS=4
               ORDER BY MC.TOUR_ID";
      $db->query($sql);   
       while ($row = $db->nextRow()) {
          $state = 'NORMAL'; 
          if (!empty($_GET['tour_id']) && $row['TOUR_ID'] == $_GET['tour_id'])
            $state = 'SELECTED'; 
          $tour = $row;
	   $tour[$state] = 1;
          $tour['NUMBER'] = $row['TOUR_ID'];
          $tour['MSEASON_ID'] = $manager->mseason_id;
          $tours[] = $tour;
       }
       if (isset($_GET['tour_id'])) {
         $all['NORMAL']['MSEASON_ID'] = $manager->mseason_id;;
       } else {
         $all['SELECTED'] = 1;
       }

  // show challenges log

  // get list of available challenges
 $smarty->assign("logged", 1);
}

 $smarty->assign("manager_filter_box", $manager_filter_box);
 $smarty->assign("rules", $rules);
 if (isset($accepted_challenges))
   $smarty->assign("accepted_challenges", $accepted_challenges);
 $smarty->assign("accepted_stake_credits", $accepted_stake_credits);
 $smarty->assign("accepted_points", $accepted_points);
 if (isset($completed_challenges))
   $smarty->assign("completed_challenges", $completed_challenges);
 if (isset($error))
   $smarty->assign("error", $error);
 if (isset($cleared))
   $smarty->assign("cleared", $cleared);
 $smarty->assign("tours", $tours);
 $smarty->assign("all", $all);

 $rules = $pagebox->getPage(19);

 $start = getmicrotime();
 $content .= $smarty->fetch('smarty_tpl/f_manager_challenges.smarty');    
 $stop = getmicrotime();
 if (isset($_GET['debugphp']))
   echo 'smarty_tpl/f_manager_challenges.smarty'.($stop-$start);

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