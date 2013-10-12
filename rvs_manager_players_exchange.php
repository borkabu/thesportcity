<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
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
include('class/rvs_manager_user.inc.php');
// --- build content data -----------------------------------------------------

// include common header
$content = '';

//$db->showquery=true;
  $content = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 3);

  $manager = new Manager('', 'rvs');
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
  $rvs_manager_user = new RvsManagerUser($manager->mseason_id);

  $manager_filter_box = $managerbox->getRvsManagerFilterBox($manager->mseason_id);

  if (isset($rvs_manager_user->league_id)) {
    $pleague = new League("rvs_manager", $rvs_manager_user->league_id);
    $pleague->getLeagueInfo();
  }

  $forumPermission = new ForumPermission();
  if (isset($_POST['post_comment']) && $auth->userOn() &&
     $forumPermission->canAddComment($_POST['topic_id']) == 0) {
     $forumbox->addPost($forums['RVS_MANAGER_LEAGUES'], $_POST['topic_id'], $_POST['item_id']);
  }

  if (!$auth->userOn()) {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_RVS_MANAGER_LOGIN');
  } else if (!$manager->allow_rvs_leagues) {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_NO_RVS_MANAGER');
  }

  if (isset($rvs_manager_user->league_id)) {
     // particular tournament requested
     $last_tour_id = $manager->getLastTour();
     $sql= "SELECT ML.*, MLM.*, U.USER_NAME, MSS.POINTS, MSS.PLACE, MUT.POINTS AS LAST_POINTS, 
		MLM.STATUS AS MSTATUS, C.CCTLD, CD.COUNTRY_NAME, MU.ALLOW_VIEW
          FROM rvs_manager_leagues ML, rvs_manager_leagues_members MLM, 
	       manager_seasons MS, users U
               LEFT JOIN rvs_manager_standings MSS ON MSS.USER_ID=U.USER_ID AND MSS.LEAGUE_ID=".$rvs_manager_user->league_id."
               LEFT JOIN rvs_manager_users_tours MUT ON MUT.USER_ID=U.USER_ID AND MUT.LEAGUE_ID=".$rvs_manager_user->league_id." AND MUT.TOUR_ID=".$last_tour_id."
	       LEFT JOIN manager_users MU ON MU.USER_ID=U.USER_ID AND MU.SEASON_ID=".$manager->mseason_id."
	      , countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
         WHERE ML.LEAGUE_ID=".$rvs_manager_user->league_id."
	        AND MLM.LEAGUE_ID=".$rvs_manager_user->league_id."
              AND MS.SEASON_ID=ML.SEASON_ID   
              AND MLM.USER_ID=U.USER_ID
	        AND MLM.STATUS IN (1,2)
  	        AND U.COUNTRY = C.ID
         ORDER BY MSS.POINTS DESC"; 
    $db->query($sql);     
    $c=1;    
    $members = array(); 
    while ($row = $db->nextRow()) {
      $member = $row;
      $member['LOCAL_PLACE'] = $c;

      if (!empty($row['CCTLD'])) {
        $member['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $member['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }
	if ($auth->hasSupporter() || $row['ALLOW_VIEW'] == '1')
        $member['ALLOW'] = $row;
      else 
        $member['NOTALLOW'] = $row;

      if ($auth->userOn() && $auth->getUserId() == $row['USER_ID'])
	  $league['MEMBER'] = 1;

      $league['INVITE_TYPE'] = $row['INVITE_TYPE'];
      if ($row['MSTATUS'] ==1) {
        $member['OWNER'] = 1;
        $league['TITLE'] = $row['TITLE'];
        $league['OWNER'] = $row['USER_NAME'];
        $league['DURATION'] = $row['DURATION'];
        $league['PARTICIPANTS'] = $row['PARTICIPANTS'];
        $league['TEAM_SIZE'] = $row['TEAM_SIZE'];

        $league['ENTRY_FEE'] = $row['ENTRY_FEE'];
	  $owner=$row['USER_ID'];
        $league['CHAT']['CHAT_CHANNEL'] = $row['TITLE']."_".$row['LEAGUE_ID']."_".$row['SEASON_ID'];
        if (!empty($row['RULES']))  
          $league['RULES'] = $row['RULES'];

        $c++;
      }
      else if ($row['STATUS'] == 2) {
        $member['CURRENT_MEMBERS'] = 1;
        $c++;
      } 
      $members[] = $member;
    }
    $league['SEASON_TITLE'] = $manager->getTitle();
    $league['MEMBERS'] = $members;
    if (!isset($league['MEMBER']) && $auth->userOn() && ($league['ENTRY_FEE'] == 0 || ($league['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] >= $league['ENTRY_FEE'])))
      $league['CAN_JOIN'] = 1;
    else if (!isset($league['MEMBER']) && $auth->userOn() && $league['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] < $league['ENTRY_FEE']) {
      $league['NOT_ENOUGH_CREDITS'] = 1;
    }

    // get team
    $manager->getNextTour();
    $manager->countTourGamesPerTeam($manager->next_tour);
    $manager->countReportsPerPlayer();
    $current_tour = $manager->getCurrentTour();
     
    if (!$manager->manager_trade_allow)
      $manager->closeMarket();
  
    $manager->getLastTour();
//$rvs_manager_user->getTeam();

    if ($auth->userOn())
      $team = $managerbox->getRvsTeam($current_tour, $manager->last_tour); 

    $pleague = new League("manager", $rvs_manager_user->league_id);
    $rating = $pleague->getOwnerRating($owner);
    if ($rating > 0) 
      $league['OWNER_RATING'] = $rating;
    else $league['OWNER_RATING'] = $langs['LANG_NONE_U'];

    // get league rating
    $rating = $pleague->getLeagueRating();
    if ($rating > 0) 
      $league['LEAGUE_RATING'] = $rating;
    else $league['LEAGUE_RATING'] = $langs['LANG_NONE_U'];
  }

  if ($auth->userOn()) {
    $all_players = $rvs_manager_user->getPlayerExchange();
  }

  if (isset($league)) {
    $smarty->assign("league", $league);
    $league_item = $smarty->fetch('smarty_tpl/rvs_manager_league_item.smarty');    
    $smarty->assign("league_item", $league_item);
  }

  if (isset($error))  
    $smarty->assign("error", $error);
  $smarty->assign("manager_filter_box", $manager_filter_box);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/rvs_manager_players_exchange.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/rvs_manager_players_exchange.smarty'.($stop-$start);

//$db->showquery=true;
  if (isset($_GET['league_id']) && $_GET['league_id'] != '') {
    $content .= $forumbox->getComments($_GET['league_id'], 'RVS_MANAGER_LEAGUES', isset($_GET['page']) ? $_GET['page'] : 1);
  }


// ----------------------------------------------------------------------------

// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager.inc.php');

// close connections
include('class/db_close.inc.php');
?>