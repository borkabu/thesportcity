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
//print_r($_POST);

  if (isset($_GET['league_id'])) {
    $pleague = new League("rvs_manager", $_GET['league_id']);
    $pleague->getLeagueInfo();
  }

  $auth->refreshEssensials();

  if ($auth->userOn() && isset($_POST['transfer']) && $_POST['transfer'] == 'y' && isset($_POST['league_id']) && isset($_POST['credits'])) {
    if ($_SESSION['_user']['CREDIT']>=$_POST['credits']) {
      $credits = new Credits();
      $credits->transferCreditsRvsLeague($auth->getUserId(), $_POST['league_id'], $pleague->league_info['SEASON_ID'], $_POST['credits']); //sender
      $transfer['SUCCESS']['MSG'] = str_replace("%c", $_POST['credits'], $langs['LANG_CREDITS_TRANSFERED_LEAGUE_U']);  
    } 
    else $transfer['ERROR']['MSG'] = $langs['LANG_ERROR_NOT_ENOUGH_CREDITS_U'];
    $smarty->assign("transfer",  $transfer);
  }

  $manager = new Manager(isset($pleague) ? $pleague->league_info['SEASON_ID'] : '', 'rvs');
  if (isset($manager->mseason_id)) {
    $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
    $rvs_manager_user = new RvsManagerUser($manager->mseason_id);
  
    $manager_filter_box = $managerbox->getRvsManagerFilterBox($manager->mseason_id);
    $current_tour = $manager->getCurrentTour();       
    if (isset($_GET['league_id'])) {
      $rvs_manager_user->finishRVSLeague($current_tour, $pleague->league_info); 
    }
    if (isset($_POST['join_league']) && isset($_GET['league_id'])) {
      $code = $pleague->league_info['INVITE_CODE'];
      if ($code != "" && $code == $_POST['league_code']) {
       // join
         if ($pleague->league_info['PARTICIPANTS'] > $pleague->league_info['JOINED']) {
           unset($sdata);
  	   $sdata['JOINED'] = 'JOINED+1';  
           $db->update('rvs_manager_leagues', $sdata, "LEAGUE_ID=".$_GET['league_id']);
           unset($sdata);
           $sdata['LEAGUE_ID'] = $_GET['league_id'];
           $sdata['USER_ID'] = $auth->getUserId();
           $sdata['STATUS'] = 2;
           $sdata['START_DATE'] = "NOW()";
           $sdata['ENTRY_FEE'] = $pleague->league_info['ENTRY_FEE'];
    
           if ($pleague->league_info['ENTRY_FEE'] == 0) {
             $db->delete('rvs_manager_leagues_members', "LEAGUE_ID=".$_GET['league_id']." AND USER_ID=".$auth->getUserId());
             $db->insert('rvs_manager_leagues_members', $sdata);
             unset($udata);
             $manager_user_log = new RvsManagerUserLog();
             $manager_user_log->logEvent($auth->getUserId(), 1, $pleague->league_info['SEASON_ID'], $_GET['league_id']);
             $league['SUCCESS']['MSG'] = $langs['LANG_MANAGER_LEAGUE_JOINED_U'];
           } else {
             if ($pleague->league_info['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] >= $pleague->league_info['ENTRY_FEE']) {
               $db->delete('rvs_manager_leagues_members', "LEAGUE_ID=".$_GET['league_id']." AND USER_ID=".$auth->getUserId());
               $db->insert('rvs_manager_leagues_members', $sdata);
    
               // transfer credits
               $credits = new Credits();
               $credit_log = new CreditsLog();
               $credits->updateRvsLeagueCredits($_GET['league_id'], $pleague->league_info['ENTRY_FEE']);
               $credits->updateCredits($auth->getUserId(), -1*$pleague->league_info['ENTRY_FEE']); 
               $credit_log->logEvent ($auth->getUserId(), 5, $pleague->league_info['ENTRY_FEE'], $_GET['league_id']);
               $manager_user_log = new RvsManagerUserLog();
               $manager_user_log->logEvent($auth->getUserId(), 1, $pleague->league_info['SEASON_ID'], $_GET['league_id']);
               $rvs_manager_log = new RvsManagerLog();
               $rvs_manager_log->logEvent ($auth->getUserId(), 5, $pleague->league_info['SEASON_ID'], $_GET['league_id']);
    
               $league['SUCCESS']['MSG'] = $langs['LANG_MANAGER_LEAGUE_JOINED_U'];
             }
           }     
           if ($pleague->league_info['PARTICIPANTS'] == $pleague->league_info['JOINED'] + 1) {
             // league is full, remove all invites
	     unset($sdata);
  	     $sdata['STATUS'] = 2;  
             $db->update('rvs_manager_leagues', $sdata, "LEAGUE_ID=".$_GET['league_id']);
             $db->delete('rvs_manager_leagues_members', "LEAGUE_ID=".$_GET['league_id']." AND STATUS=3");
             $rvs_manager_log = new RvsManagerLog();
             $rvs_manager_log->logEvent ($auth->getUserId(), 2, $pleague->league_info['SEASON_ID'], $_GET['league_id']);

           }
         } else {
           $league['ERROR']['MSG'] = $langs['LANG_ERROR_LEAGUE_IS_FULL_U'];
         }
      } else {
        // cannot verify code
        $league['ERROR']['MSG'] = $langs['LANG_ERROR_LEAGUE_INVITE_CODE_U'];
      }
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

    if (!empty($_GET['league_id'])) {
       // particular tournament requested
       $last_tour_id = $manager->getLastTour();
       $sql= "SELECT ML.*, MLM.*, U.USER_NAME, MSS.POINTS, MSS.PLACE, MUT.POINTS AS LAST_POINTS, MUT.SCORE AS LAST_SCORE, MUTC.TOUR_POINTS,
		ML.STATUS as LEAGUE_STATUS, MLM.STATUS AS MSTATUS, C.CCTLD, CD.COUNTRY_NAME, MU.ALLOW_VIEW,
		ML.ENTRY_FEE as LEAGUE_ENTRY_FEE, ML.DRAFT_START_DATE > NOW() as DRAFTING,
	        DATE_ADD(DRAFT_START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS DRAFT_START_DATE_UTC
            FROM rvs_manager_leagues ML, rvs_manager_leagues_members MLM, 
	       manager_seasons MS, users U
                 LEFT JOIN rvs_manager_standings MSS ON MSS.USER_ID=U.USER_ID AND MSS.LEAGUE_ID=".$_GET['league_id']."
                 LEFT JOIN rvs_manager_users_tours MUT ON MUT.USER_ID=U.USER_ID AND MUT.LEAGUE_ID=".$_GET['league_id']." AND MUT.TOUR_ID=".$last_tour_id."
                 LEFT JOIN rvs_manager_users_tours_categories MUTC ON MUTC.USER_ID=U.USER_ID AND MUTC.LEAGUE_ID=".$_GET['league_id']." AND MUTC.TOUR_ID=".$last_tour_id."
	       LEFT JOIN manager_users MU ON MU.USER_ID=U.USER_ID AND MU.SEASON_ID=".$manager->mseason_id."
	      , countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
           WHERE ML.LEAGUE_ID=".$_GET['league_id']."
	        AND MLM.LEAGUE_ID=".$_GET['league_id']."
                AND MS.SEASON_ID=ML.SEASON_ID   
                AND MLM.USER_ID=U.USER_ID
	        AND MLM.STATUS IN (1,2)
    	        AND U.COUNTRY = C.ID
           ORDER BY MSS.POINTS DESC, MSS.PLACE aSC"; 
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
  
        if ($auth->userOn() && $auth->getUserId() == $row['USER_ID']) {  
	  $league['MEMBER'] = 1;
          $member['CURRENT'] = 1;
	  $smarty->assign("showthings", true);
        }

        $league['INVITE_TYPE'] = $row['INVITE_TYPE'];
        if ($row['MSTATUS'] ==1) {
          $member['OWNER'] = 1;
          $league['LEAGUE_ID'] = $row['LEAGUE_ID'];
          $league['STATUS'] = $row['LEAGUE_STATUS'];
          $league['LEAGUE_TYPE'] = $row['LEAGUE_TYPE'];
          $league['TITLE'] = $row['TITLE'];
          $league['OWNER'] = $row['USER_NAME'];
          $league['DRAFT_STATE'] = $row['DRAFT_STATE'];
          $league['DRAFTING'] = $row['DRAFTING'];
          $league['DRAFT_START_DATE'] = $row['DRAFT_START_DATE'];
          $league['DRAFT_START_DATE_UTC'] = $row['DRAFT_START_DATE_UTC'];
          $league['DURATION'] = $row['DURATION'];
          $league['FORMAT'] = $row['LEAGUE_TYPE'];
          $league['FORMAT_DESCR'] = $fl_format[$row['LEAGUE_TYPE']];
          $league['DRAFT_TYPE'] = $row['DRAFT_TYPE'];
          $league['MODERATE_TRANSFERS'] = $row['MODERATE_TRANSFERS'];
          $league['DRAFT_TYPE_DESCR'] = $draft_types[$row['DRAFT_TYPE']];
          $league['PARTICIPANTS'] = $row['PARTICIPANTS'];
          $league['TEAM_SIZE'] = $row['TEAM_SIZE'];
          $league['RESERVE_SIZE'] = $row['RESERVE_SIZE'];
          $league['ENTRY_FEE'] = $row['LEAGUE_ENTRY_FEE'];
          $league['FREE_TRANSFER_FEE'] = $row['FREE_TRANSFER_FEE'];
          $league['PRIZE_FUND'] = $row['PRIZE_FUND'];
          $league['START_TOUR'] = $row['START_TOUR'];
          $league['END_TOUR'] = $row['END_TOUR'];
          $league['UTC'] = $auth->getUserTimezoneName();
	  $owner=$row['USER_ID'];
          $league['CHAT']['CHAT_CHANNEL'] = $row['TITLE']."_".$row['LEAGUE_ID']."_".$row['SEASON_ID'];
          $league['RULES'] = $row['RULES'];
  
          $c++;
        }
        else if ($row['STATUS'] == 2) {
          $member['CURRENT_MEMBERS'] = 1;
          $c++;
        } 

        if ($row['USER_ID'] == $auth->getUserId())
          $league['IN_LEAGUE'] = 1;
        $members[] = $member;
      }
      $league['SEASON_TITLE'] = $manager->getTitle();
      $league['MEMBERS'] = $members;
      if (!isset($league['MEMBER']) && $auth->userOn() 
           && $pleague->league_info['PARTICIPANTS'] > $pleague->league_info['JOINED']
  	   && ($pleague->league_info['ENTRY_FEE'] == 0 
		|| ($pleague->league_info['ENTRY_FEE'] > 0 
		    && $auth->getCredits() >= $pleague->league_info['ENTRY_FEE'])))
        $league['CAN_JOIN'] = 1;
      else if (!isset($league['MEMBER']) && $auth->userOn() && $league['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] < $league['ENTRY_FEE']) {
        $league['NOT_ENOUGH_CREDITS'] = 1;
      }
  
      // get team
      $manager->getNextTour();
      $manager->countTourGamesPerTeam($manager->next_tour);
      $manager->countReportsPerPlayer();

      if (!$manager->manager_trade_allow) {
        $manager->closeMarket();
      }
    
      $manager->getLastTour();
  
      if (isset($_POST['discard']) && $manager->manager_trade_allow) {
        $rvs_manager_user->getTeam($current_tour, $manager->last_tour);
        $outcome = $rvs_manager_user->discardPlayer($current_tour);
        if ($outcome == -1) {
            $error['MSG'] = $langs['LANG_NO_MORE_BLIND_TRADES_U'];
        } 
      }

      if (isset($_POST['protect'])) {
        $rvs_manager_user->getTeam($current_tour, $manager->last_tour);
        $outcome = $rvs_manager_user->protectPlayer();
      }
      if (isset($_POST['unprotect'])) {
        $rvs_manager_user->getTeam($current_tour, $manager->last_tour);
        $outcome = $rvs_manager_user->unprotectPlayer();
      }

      if (isset($_POST['free_transfer']) && $manager->manager_trade_allow) {
        $rvs_manager_user->getTeam($current_tour, $manager->last_tour);
        $outcome = $rvs_manager_user->freeTransferPlayer($current_tour);
        if ($outcome == -1) {
            $error['MSG'] = $langs['LANG_NO_MORE_FREE_TRANSFERS_U'];
        } 
      }
  
      if (isset($_POST['transfer']) && $manager->manager_trade_allow) {
        $rvs_manager_user->getTeam($current_tour, $manager->last_tour);
        $outcome = $rvs_manager_user->requestPlayerTransfer($current_tour);
     /*      if ($outcome == -1) {
            $error['MSG'] = $langs['LANG_NO_MORE_DISCARDS_U'];
        } */
      }
  
      if (isset($_POST['suggest_transfer'])) {
        $outcome = $rvs_manager_user->suggestPlayerTransfer();
        if ($outcome == -1) {
            $error['MSG'] = $langs['LANG_NO_U'];
        } 
      }
  
      if (isset($_POST['retreat_transfer'])) {
        $outcome = $rvs_manager_user->retreatPlayerTransfer();
      }

      if (isset($_POST['reject_transfer'])) {
        $outcome = $rvs_manager_user->rejectPlayerTransfer();
      }

      if (isset($_POST['reject_transfer_moderate']) && $auth->userOn() && $auth->getUserId() == $pleague->league_info['USER_ID']) {
        $outcome = $rvs_manager_user->rejectPlayerTransferModerate();
      }
      if (isset($_POST['accept_transfer_moderate']) && $auth->userOn() && $auth->getUserId() == $pleague->league_info['USER_ID']) {
        $outcome = $rvs_manager_user->acceptPlayerTransferModerate();
      }

      if (isset($_POST['return_player'])) {
        $outcome = $rvs_manager_user->returnPlayerTransfer();
      }

      if (isset($_POST['accept_transfer']) && $manager->manager_trade_allow) {
        if ($pleague->league_info['MODERATE_TRANSFERS'] == 'N')
          $outcome = $rvs_manager_user->acceptPlayerTransfer(); 
        else
          $rvs_manager_user->acceptPlayerTransferForModeration(); 
      }
  
      if ($auth->userOn())
        $team = $managerbox->getRvsTeam($current_tour, $manager->last_tour); 

      if ($auth->userOn() && $auth->getUserId() == $pleague->league_info['USER_ID'] && $pleague->league_info['MODERATE_TRANSFERS'] == 'Y') {
        $moderate_transfers = $managerbox->getRvsModerateTransfers(); 
        $smarty->assign("moderate_transfers", $moderate_transfers);
      }
  
      // get voting
      if ($auth->userOn()) { 
        $sql= "SELECT DISTINCT MLV.VOTE, MLM.LEAGUE_ID, MLM.USER_ID
            FROM rvs_manager_leagues_members MLM
			left join rvs_manager_leagues_votes MLV
					ON MLV.LEAGUE_ID=MLM.LEAGUE_ID
						AND MLV.USER_ID=".$auth->getUserId()."
           WHERE MLM.LEAGUE_ID=".$_GET['league_id']."
		AND MLM.STATUS <> 3
		AND MLM.USER_ID=".$auth->getUserId(); 
  
        $db->query($sql);     
        if ($row = $db->nextRow()) {
          // can vote
	  $league['VOTING'] = $row;
          if ($row['VOTE'] > 0)
    	    $league['VOTING']['THUMB_UP'] = 1;
          if ($row['VOTE'] < 0)
    	    $league['VOTING']['THUMB_DOWN'] = 1;
        }
      }
  
      $rating = $pleague->getOwnerRating($owner);
      if ($rating > 0) 
        $league['OWNER_RATING'] = $rating;
      else $league['OWNER_RATING'] = $langs['LANG_NONE_U'];
  
      // get league rating
      $rating = $pleague->getLeagueRating();
      if ($rating > 0) 
        $league['LEAGUE_RATING'] = $rating;
      else $league['LEAGUE_RATING'] = $langs['LANG_NONE_U'];
  
      if ($auth->userOn())
        $all_players = $rvs_manager_user->getPlayerExchange();

      if ($league['LEAGUE_TYPE'] == 0 || $league['LEAGUE_TYPE'] == 1) {
        $sql = "SELECT DISTINCT MUT.TOUR_ID
  	          FROM rvs_manager_users_tours MUT
   	        WHERE MUT.LEAGUE_ID=".$_GET['league_id']." 
		     AND PLACE > 0
                ORDER BY MUT.TOUR_ID";
      } else {
        $sql = "SELECT DISTINCT MUT.TOUR_ID
  	          FROM rvs_manager_users_tours_categories MUT
   	        WHERE MUT.LEAGUE_ID=".$_GET['league_id']." 
		     AND PLACE > 0
                ORDER BY MUT.TOUR_ID";
      } 
      $db->query($sql);   
      $tours = array();
      while ($row = $db->nextRow()) {
        unset($tour);
        $state = 'NORMAL'; 
        $tour[$state] = $row;
        $tour[$state]['NUMBER'] = $row['TOUR_ID'];
        $tour[$state]['LEAGUE_ID'] = $_GET['league_id'];
        $tours[] = $tour;
      }

    }
  
    $filtering = "";
    $leagues_number = 0;
    $open_leagues_number = 0;
    $open_leagues = array();
    $past_leagues_number = 0;
    $past_leagues = array();
    if (!isset($_GET['league_id'])) {
      if ((isset($_GET['all']) && $_GET['all'] == 'y') || !$auth->userOn()) {
        $_GET['all'] = 'y';
        $leagues = $manager->getRvsLeagues();
        $leagues_number = $manager->leagues;
        $filtering['ALL_LEAGUES'] = 1;
        foreach ($leagues as $vleague) {
          if ($vleague['LEAGUE_STATUS'] == 1) {
            $open_leagues[] = $vleague;
	  $open_leagues_number++;
          }
          if ($vleague['LEAGUE_STATUS'] == 3) {
            $past_leagues[] = $vleague;
            $past_leagues_number++;
          }
        }
      }
      else {
        $_GET['all'] = 'n';
        $leagues = $rvs_manager_user->getRvsLeagues();
        $leagues_number = $rvs_manager_user->leagues;
        $filtering['MY_LEAGUES'] = 1;
        foreach ($leagues as $vleague) {
          if ($vleague['LEAGUE_STATUS'] == 1) {
            $open_leagues[] = $vleague;
	  $open_leagues_number++;
          }
          if ($vleague['LEAGUE_STATUS'] == 3) {
            $past_leagues[] = $vleague;
            $past_leagues_number++;
          }
        }
      }
    }

    if ($leagues_number > 0) {      
      $leagues_data['LEAGUES'] = $leagues;
      $leagues_data['LEAGUES_PAGING'] = $pagingbox->getPagingBox($leagues_number, isset($_GET['page2']) ? $_GET['page2'] : 1, $page_size, 'page2');
    }
    else if ($auth->userOn() && !$rvs_manager_user->inited)
           $create_league_offer = 1;
  
    if ($open_leagues_number > 0) {      
      $open_leagues_data['LEAGUES'] = $open_leagues;
      $open_leagues_data['LEAGUES_PAGING'] = $pagingbox->getPagingBox($open_leagues_number, isset($_GET['page2']) ? $_GET['page2'] : 1, $page_size, 'page2');
    }

    if ($past_leagues_number > 0) {      
      $past_leagues_data['LEAGUES'] = $past_leagues;
      $past_leagues_data['LEAGUES_PAGING'] = $pagingbox->getPagingBox($past_leagues_number, isset($_GET['page2']) ? $_GET['page2'] : 1, $page_size, 'page2');
    }
  
    //print_r($league);
    if (isset($league['TITLE'])) {
      $smarty->assign("league", $league);
      $smarty->assign("tours", $tours);
      $league_item = $smarty->fetch('smarty_tpl/rvs_manager_league_item.smarty');    
      $smarty->assign("league_item", $league_item);
    }
  
    if (isset($all_players) && $league['STATUS'] != 3) {
      $smarty->assign("league_id", $rvs_manager_user->league_id);
      $smarty->assign("notify", $rvs_manager_user->notify);
      $smarty->assign("players", $all_players);
      $players_exchange = $smarty->fetch('smarty_tpl/rvs_manager_players_exchange.smarty');    
      $smarty->assign("players_exchange", $players_exchange);
    }
  
    $current_tour = $manager->getCurrentTour();
    $market_status = array();
    if ($manager->season_over) {
      $market_status['SEASON_OVER'] = 1;      
    } else if (isset($manager->next_tour_date) && $manager->manager_trade_allow) {
      $market_status['MARKET_OPEN']['START_DATE'] = $manager->next_tour_date_utc;   
      $market_status['MARKET_OPEN']['UTC'] = $manager->utc;   
    } else if (isset($manager->current_tour_end_date)) {
      $market_status['NOMARKET']['START_DATE'] = $manager->current_tour_end_date;   
      $market_status['NOMARKET']['UTC'] = $manager->utc;   
    }
    else if (!$manager->manager_trade_allow)
       $market_status['NOMARKET_DELAY'] = 1;   

    $smarty->assign("market_status", $market_status);
    if (isset($leagues_data))
      $smarty->assign("leagues", $leagues_data);
    if (isset($open_leagues_data))
      $smarty->assign("open_leagues", $open_leagues_data);
    if (isset($past_leagues_data))
      $smarty->assign("past_leagues", $past_leagues_data);
 
    if (isset($team))
      $smarty->assign("league_team", $team);
  
    $smarty->assign("filtering", $filtering);
  
    if (isset($error))  
      $smarty->assign("error", $error);
    $smarty->assign("manager_filter_box", $manager_filter_box);
    if (isset($create_league_offer))
      $smarty->assign("create_league_offer", $create_league_offer);
  
    $start = getmicrotime();
    $content .= $smarty->fetch('smarty_tpl/rvs_manager_league.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/rvs_manager_league.smarty'.($stop-$start);
  
    //$db->showquery=true;
    if (!empty($_GET['league_id'])) {
      $content .= $forumbox->getComments($_GET['league_id'], 'RVS_MANAGER_LEAGUES', isset($_GET['page']) ? $_GET['page'] : 1);
    }
  } else {
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_NO_RVS_MANAGERS');
  }

// ----------------------------------------------------------------------------
  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 3);
  define("RVS_MANAGER", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_rvs_manager.inc.php');

// close connections
include('class/db_close.inc.php');
?>