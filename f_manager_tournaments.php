<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
//return '';

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

// user session
include('class/ss_const.inc.php');

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/manager.inc.php');
include('class/manager_user.inc.php');
include('class/manager_tournament.inc.php');
include('class/manager_tournamentbox.inc.php');
 $manager_tournamentbox = new ManagerTournamentBox($langs, $_SESSION["_lang"]);

// --- build content data -----------------------------------------------------
 $db->query("start transaction");

 $content = '';

  if (isset($_GET['mt_id'])) {
    $manager_tournament = new ManagerTournament($_GET['mt_id']);
    $manager = new Manager($manager_tournament->mseason_id); 
  } else {
    $manager_tournament = new ManagerTournament();
    $manager = new Manager(); 
  }
//echo $manager->mseason_id;
  $manager_filter_box = $managerbox->getManagerFilterBox($manager->mseason_id);
//echo "<!--".$manager_tournament->mseason_id."-->";

  if ($auth->userOn()) 
    $manager_user = new ManagerUser($manager->mseason_id);

  $current_tour = $manager->getCurrentTour();
  $manager_tournament->draw($current_tour);

  $forumPermission = new ForumPermission();
  if (isset($_POST['post_comment']) && $auth->userOn() &&
     $forumPermission->canAddComment($_POST['topic_id']) == 0) {
     $forumbox->addPost($forums['MANAGER_TOURNAMENT'], $_POST['topic_id'], $_POST['item_id']);
  }
 
 $has_team = false;
 $in_tournament = false;
 if ($auth->userOn()) {
    $muser = $manager->getUser($auth->getUserId());
    if ($muser != '') {
      $has_team = true;
      $mtuser = $manager_tournament->getUser($auth->getUserId());
      if ($mtuser != '') {
        $in_tournament = true;
        $registration['REGISTERED'] = 1;
      }
    } else  {
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_TOURNAMENT_NO_TEAM');
    }
 } else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_LOGIN');
 } 


//$db->showquery=true;
 if ($auth->userOn() && $has_team && !$in_tournament
	&& $manager_tournament->registration_allowed
	&& isset($_POST['enter_tournament']) 
        && $manager_tournament->fee <= $_SESSION['_user']['CREDIT'] 
        && $manager_tournament->invite_type == 1) {
    $code = $manager_tournament->invite_code;
    if ($code != "" && $code == $_POST['tournament_code']) {
      if ($manager_tournament->participants > $manager_tournament->joined) {
        unset($sdata);   
        $sdata['USER_ID'] = $auth->getUserId();
        $sdata['MT_ID'] = $manager_tournament->mt_id;
        $sdata['START_DATE'] = "NOW()";
        $sdata['STATUS'] = 2;
        $db->delete('manager_tournament_members', "MT_ID=".$_GET['mt_id']." AND USER_ID=".$auth->getUserId());
        $db->insert('manager_tournament_members', $sdata);
        unset($sdata);   
        $sdata['USER_ID'] = $auth->getUserId();
        $sdata['MT_ID'] = $manager_tournament->mt_id;
        $sdata['TOUR'] = 0;
        $db->insert('manager_tournament_users', $sdata);
        if ($manager_tournament->fee > 0) {
          $credits = new Credits();
          $credits->updateCredits ($auth->getUserId(), $manager_tournament->fee * -1);
          $credit_log = new CreditsLog();
          $credit_log->logEvent ($auth->getUserId(), 5, $manager_tournament->fee);
        }

        unset($sdata);   
        $sdata['JOINED'] = 'JOINED+1';
        $sdata['PRIZE_FUND'] = 'PRIZE_FUND+'.$manager_tournament->fee;
        $db->update('manager_tournament', $sdata, "MT_ID=".$manager_tournament->mt_id);
    
        $manager_tournament_log = new ManagerTournamentLog();
        $manager_tournament_log->logEvent($auth->getUserId(), 2, 0, 0, $manager_tournament->mt_id);
   
        if ($manager_tournament->participants == $manager_tournament->joined + 1) {
           // league is full, remove all invites
          unset($sdata);
          $sdata['STATUS'] = 2;  
          $db->update('manager_tournament', $sdata, "MT_ID=".$_GET['mt_id']);
          $db->delete('manager_tournament_members', "MT_ID=".$_GET['mt_id']." AND STATUS=3");
          $manager_tournament_log = new ManagerTournamentLog();
          $manager_tournament_log->logEvent($auth->getUserId(), 7, 0, 0, $_GET['mt_id']);
          $manager_tournament->setTours();
        }
        header('Location: f_manager_tournaments.php?mt_id='.$manager_tournament->mt_id);
      } else {
        $registration['ERROR']['MSG'] = $langs['LANG_ERROR_LEAGUE_IS_FULL_U'];
      }
    } else {
        $registration['ERROR']['MSG'] = $langs['LANG_ERROR_LEAGUE_INVITE_CODE_U'];
    }
 }


  if ($auth->userOn() && $manager_tournament->registration_allowed
    && !$in_tournament && $has_team) {
// register user for tournament
      if ($manager_tournament->fee <= $_SESSION['_user']['CREDIT']) {
        $registration['ENTER_TOURNAMENT_OFFER']['MSG'] = str_replace("%m", $manager_tournament->fee, $langs['LANG_TOURNAMENT_ENTER_OFFER_U']);
        $registration['CAN_JOIN'] = 1;
      } else {
        $data['ERROR'][0]['MSG'] = str_replace("%m", $manager_tournament->fee, $langs['LANG_TOURNAMENT_NOT_ENOUGH_CREDITS_U']);
      }  
  } 

  if (isset($manager_tournament->registration_allowed) && $manager_tournament->registration_allowed)
    $registration['REGISTRATION_OPEN']['REGISTRATION_END_DATE'] = $manager_tournament->registration_end_date;   
  else if (isset($manager_tournament->registration_allowed) && !$manager_tournament->registration_allowed)
    $registration['REGISTRATION_CLOSED'] = 1;   


  if (isset($_GET['mt_id'])) {  
    // show list of participants

    $stage = $manager->getCurrentTour();
    $tour_set = false;
    if (isset($_GET['tour'])) {
      $tour = $_GET['tour'];
      $tour_set = true;
      if ($tour > 0) {
        $data['HALF'][0]['X'] = 1;
        $data['HALF2'][0]['X'] = 1;
      }
    }
    else if ($stage > 1 && !empty($manager_tournament->start_tour)) {
	   if ($stage == $manager_tournament->start_tour && $manager->manager_trade_allow)
             $tour = $stage - $manager_tournament->start_tour;
	   elseif ($stage <= $manager_tournament->end_tour)
             $tour = $stage - $manager_tournament->start_tour + 1;
           else $tour = $manager_tournament->end_tour - $manager_tournament->start_tour + 1;
//echo $tour;
           $_GET['tour'] = $tour;
           $data['HALF'][0]['X'] = 1;
           $data['HALF2'][0]['X'] = 1;
      if (!$manager->manager_trade_allow)
        $tour_set = true;
    }
    else if ($stage == 1 && !$manager->manager_trade_allow && !empty($manager_tournament->start_tour)) {
      $sql = "SELECT max(tour) TOUR_ID FROM manager_tournament_users WHERE 
			 MT_ID=".$manager_tournament->mt_id;
      $db->query($sql);   
      $row3 = $db->nextRow();
      $tour = $row3['TOUR_ID'];
    } else $tour = 0;


    $sql = "SELECT * FROM manager_tournament_tours WHERE NUMBER=".$tour."
			AND MT_ID=".$manager_tournament->mt_id;
    $db->query($sql);   
    $row2 = $db->nextRow();


    
    $order_by = "";
    $order_by = "MTU.POINTS DESC, MS.WEALTH ASC, SEED DESC, U.USER_NAME";
    $sql = "SELECT U.USER_NAME, U.USER_ID, IF(MTR.USER_ID IS NULL, 1, 0) AS SEED, MTU.TOUR, MTU.POINTS, C.CCTLD, CD.COUNTRY_NAME, MU.ALLOW_VIEW, MS.WEALTH
			FROM manager_users MU, users U, countries C
				LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
				, manager_tournament_users MTU
			LEFT JOIN manager_tournament_results MTR ON MTR.MT_ID=MTU.MT_ID
							AND MTR.TOUR=".$tour."
							AND ROUND=1
							AND MTU.USER_ID=MTR.USER_ID
			LEFT JOIN manager_standings MS ON MS.USER_ID=MTU.USER_ID and MS.MSEASON_ID=".$manager->mseason_id."
		WHERE U.USER_ID=MTU.USER_ID 
			AND MTU.MT_ID=".$manager_tournament->mt_id." AND MTU.TOUR=".$tour."
		        AND U.COUNTRY = C.ID
			AND MU.USER_ID=MTU.USER_ID 
			AND MU.SEASON_ID=".$manager->mseason_id."
		ORDER BY ".$order_by;

    $db->query($sql);   
//echo $sql;
    $c = 0;
    $players = array();
    while ($row = $db->nextRow()) {
      $player = $row;
      $player['NUMBER'] = $c+1;
      if (!empty($row['CCTLD'])) {
        $player['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $player['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }

      if ($row['SEED'] == 1 && ($tour >= 1 && $tour_set && ($tour < ($stage - $manager_tournament->start_tour + 1) || !$manager->manager_trade_allow))) {
	$player['SEEDED'] = 1;
      }

      if (($auth->hasSupporter() && !$manager->manager_trade_allow) || $row['ALLOW_VIEW'] == '1')
        $player['ALLOW'] = $row;
      else 
        $player['NOTALLOW'] = $row;

      $players[] = $player;
      $c++;
    }
    $user_count =  count($players);

    $sql = "SELECT MTR.PAIR, MTR.SCORE as SCORE1, MTR2.SCORE as SCORE2, U1.USER_NAME as USER_NAME1, U2.USER_NAME as USER_NAME2
			FROM manager_tournament_results MTR
                          left join users U1 on U1.user_id=MTR.USER_ID,
			     manager_tournament_results MTR2
                          left join users U2 on U2.user_id=MTR2.USER_ID
			WHERE  MTR.MT_ID=".$manager_tournament->mt_id." 
			       AND MTR.TOUR=".$tour."
			       AND MTR.HOME =0
			       AND MTR2.MT_ID=".$manager_tournament->mt_id." 
			       AND MTR2.TOUR=".$tour."
			       AND MTR2.HOME =1
			       AND MTR.PAIR=MTR2.PAIR
				AND MTR.ROUND=MTR2.ROUND";


    $db->query($sql);   
    $pairs = array();
    while ($row = $db->nextRow()) {
      unset($pair);
      $pair['USER_NAME1'] = $row['USER_NAME1'];
      $pair['USER_NAME2'] = $row['USER_NAME2'];
      $pair['PAIR'] = $row['PAIR'] + 1;
  
      $pair['SCORE1'] = $row['SCORE1'] > 0 ? $row['SCORE1'] : '0';
      $pair['SCORE2'] = $row['SCORE2'] > 0 ? $row['SCORE2'] : '0';
      if ($row['SCORE1'] > $row['SCORE2']) {
        $pair['SCORE1_WON'] = 1;
        $pair['USER_NAME1_WON'] = 1;
      } else if ($row['SCORE1'] < $row['SCORE2']) {
        $pair['SCORE2_WON'] = 1;
        $pair['USER_NAME2_WON'] = 1;
      }

      if ($row2['DRAWN'] == 1 && $row2['COMPLETED'] == 0)
	$not_final = 1;
      $pairs[] = $pair;
    }

    if ($user_count > 0) {
       $sql = "SELECT DISTINCT MTR.TOUR
		 FROM manager_tournament_users MTR
		WHERE MTR.MT_ID=".$manager_tournament->mt_id."  
                ORDER BY MTR.TOUR";
       $db->query($sql);   
	$c = 0;
        $last_tour = 0;
        $tours = array();
        while ($row = $db->nextRow()) {
          if ($row['TOUR'] > 0) {
	     $last_tour = $row['TOUR'];
             $state = 'NORMAL'; 
             if ((isset($_GET['tour']) && $row['TOUR'] == $_GET['tour']) 
		|| $row['TOUR'] == $tour)
               $state = 'SELECTED'; 
             unset($ttour);
             $ttour[$state] = $row;
             $ttour[$state]['NUMBER'] = $row['TOUR'];
             $ttour[$state]['MT_ID'] = $manager_tournament->mt_id;
             $tours[] = $ttour;
          }
        }
        if (isset($_GET['tour']) && $_GET['tour'] != 0) {
          $tour_filter['NORMAL']['MT_ID'] = $manager_tournament->mt_id;
	} else if (!isset($_GET['tour']) && $last_tour != $tour) {
          $tour_filter['NORMAL']['MT_ID'] = $manager_tournament->mt_id;
        } else {
          $tour_filter['SELECTED'] = 1;
        }

     }
  }
  $db->query("commit");
  // add data

    $filtering = "";
    $tournaments_number = 0;
    $open_tournaments_number = 0;
    $open_tournaments = array();
    $past_tournaments_number = 0;
    $past_tournaments = array();
//$db->showquery=true;
    if (!isset($_GET['mt_id'])) {
      if (isset($_GET['all']) && $_GET['all'] == 'n' && $auth->userOn()) {
        $tournaments = $manager_user->getTournaments();
        $tournaments_number = $manager_user->tournaments;
        $filtering['MY_TOURNAMENTS'] = 1;
        foreach ($tournaments as $vtournament) {
          if ($vtournament['TOURNAMENT_STATUS'] == 1) {
            $open_tournaments[] = $vtournament;
	  $open_tournaments_number++;
          }
          if ($vtournament['TOURNAMENT_STATUS'] == 3) {
            $past_tournaments[] = $vtournament;
            $past_tournaments_number++;
          }
        }
      }
      else {
        $_GET['all'] = "y";
        $tournaments = $manager->getTournaments();
        $tournaments_number = $manager->tournaments;
        $filtering['ALL_TOURNAMENTS'] = 1;
        foreach ($tournaments as $vtournament) {
          if ($vtournament['TOURNAMENT_STATUS'] == 1) {
            $open_tournaments[] = $vtournament;
	  $open_tournaments_number++;
          }
          if ($vtournament['TOURNAMENT_STATUS'] == 3) {
            $past_tournaments[] = $vtournament;
            $past_tournaments_number++;
          }
        }
      }

      if ($tournaments_number > 0) {      
        $tournaments_data['TOURNAMENTS'] = $tournaments;
        $tournaments_data['TOURNAMENTS_PAGING'] = $pagingbox->getPagingBox($tournaments_number, isset($_GET['page2']) ? $_GET['page2'] : 1, $page_size, 'page2');
      }
      else if ($auth->userOn() && $manager_user->inited)
             $create_tournament_offer = 1;
    
      if ($open_tournaments_number > 0) {      
        $open_tournaments_data['TOURNAMENTS'] = $open_tournaments;
        $open_tournaments_data['TOURNAMENTS_PAGING'] = $pagingbox->getPagingBox($open_tournaments_number, isset($_GET['page2']) ? $_GET['page2'] : 1, $page_size, 'page2');
      }
  
      if ($past_tournaments_number > 0) {      
        $past_tournaments_data['TOURNAMENTS'] = $past_tournaments;
        $past_tournaments_data['TOURNAMENTS_PAGING'] = $pagingbox->getPagingBox($past_tournaments_number, isset($_GET['page2']) ? $_GET['page2'] : 1, $page_size, 'page2');
      }

    } else {
      $smarty->assign("registration", $registration);
      $smarty->assign("players", $players);
      if (isset($tours))
        $smarty->assign("tours", $tours);
      $smarty->assign("manager_tournament", $manager_tournament);
      if (isset($tour_filter))
        $smarty->assign("tour_filter", $tour_filter);
      if (count($pairs) > 0)
        $smarty->assign("pairs", $pairs);
      if (isset($not_final))
        $smarty->assign("not_final", $not_final);
      $tournament_item = $smarty->fetch('smarty_tpl/f_manager_tournament_item.smarty');    
      $smarty->assign("tournament_item", $tournament_item);
    }

  
    //print_r($league);
    if (isset($tournament['TITLE'])) {
      $smarty->assign("tournament", $tournament);
      $league_item = $smarty->fetch('smarty_tpl/f_manager_tournament_item.smarty');    
      $smarty->assign("tournament_item", $tournament_item);
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
    if (isset($tournaments_data))
      $smarty->assign("tournaments", $tournaments_data);
    if (isset($open_tournaments_data))
      $smarty->assign("open_tournaments", $open_tournaments_data);
    if (isset($past_tournaments_data))
      $smarty->assign("past_tournaments", $past_tournaments_data);
  
    $smarty->assign("filtering", $filtering);
  
    if (isset($error))  
      $smarty->assign("error", $error);
    $smarty->assign("manager_filter_box", $manager_filter_box);
    if (isset($create_tournament_offer))
      $smarty->assign("create_tournament_offer", $create_tournament_offer);
  
    $start = getmicrotime();
    $content .= $smarty->fetch('smarty_tpl/f_manager_tournaments.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/f_manager_tournaments.smarty'.($stop-$start);

    if (isset($_GET['mt_id'])) {
      $content .= $forumbox->getComments($_GET['mt_id'], 'MANAGER_TOURNAMENT', isset($_GET['page']) ? $_GET['page'] : 1);
    }
// ----------------------------------------------------------------------------
  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 4);
  define("FANTASY_TOURNAMENT", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager_tournament.inc.php');

// close connections
include('class/db_close.inc.php');

?>