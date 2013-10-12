<?php
ini_set('display_errors', 1);
error_reporting (E_ALL & ~E_NOTICE);
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

//$db->showquery=true;
  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);

  $manager = new Manager();
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
  $manager_user = new ManagerUser($manager->mseason_id);

  $manager_filter_box = $managerbox->getManagerFilterBox($manager->mseason_id);
  $pleague = new League("manager", $_GET['league_id']);
  $pleague->getLeagueInfo();

  if (isset($_POST['join_league'])) {
    $code = $pleague->league_info['INVITE_CODE'];
    if ($code != "" && $code == $_POST['league_code']) {
     // join
       unset($sdata);
       $sdata['LEAGUE_ID'] = $_GET['league_id'];
       $sdata['USER_ID'] = $auth->getUserId();
       $sdata['STATUS'] = 2;
       $sdata['START_DATE'] = "NOW()";

       if ($pleague->league_info['ENTRY_FEE'] == 0) {
         $db->delete('manager_leagues_members', "LEAGUE_ID=".$_GET['league_id']." AND USER_ID=".$auth->getUserId());
         $db->insert('manager_leagues_members', $sdata);
         unset($udata);

         $udata['STATUS'] = "1";  
         $udata['JOINED'] = "JOINED+1";  
         $db->update('manager_leagues', $udata, ' LEAGUE_ID='.$_GET['league_id']);

         $manager_user_log = new ManagerUserLog();
         $manager_user_log->logEvent($auth->getUserId(), 10, 0, $manager->mseason_id, '', $pleague->league_info['USER_ID']);
         $manager_user_log->logEvent($pleague->league_info['USER_ID'], 11, 0, $manager->mseason_id, '', $auth->getUserId());
         $league['SUCCESS']['MSG'] = $langs['LANG_MANAGER_LEAGUE_JOINED_U'];
       } else {
         if ($pleague->league_info['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] >= $pleague->league_info['ENTRY_FEE']) {
           $db->delete('manager_leagues_members', "LEAGUE_ID=".$_GET['league_id']." AND USER_ID=".$auth->getUserId());
           $db->insert('manager_leagues_members', $sdata);

           unset($udata);
           $udata['STATUS'] = "1";  
           $udata['JOINED'] = "JOINED+1";  
           $db->update('manager_leagues', $udata, ' LEAGUE_ID='.$_GET['league_id']);

           // transfer credits
           $credits = new Credits();
           $credit_log = new CreditsLog();
           $credits->updateCredits($pleague->league_info['USER_ID'], $pleague->league_info['ENTRY_FEE']*0.95);
           $credit_log->logEvent ($pleague->league_info['USER_ID'], 10, $pleague->league_info['ENTRY_FEE']*0.95, $auth->getUserId());
           $credits->updateCredits($auth->getUserId(), -1*$pleague->league_info['ENTRY_FEE']); 
           $credit_log->logEvent ($auth->getUserId(), 5, $pleague->league_info['ENTRY_FEE'], $pleague->league_info['USER_ID']);
           $manager_user_log = new ManagerUserLog();
           $manager_user_log->logEvent($auth->getUserId(), 10, 0, $manager->mseason_id, '', $pleague->league_info['USER_ID']);
           $manager_user_log->logEvent($pleague->league_info['USER_ID'], 11, 0, $manager->mseason_id, '', $auth->getUserId());
           $league['SUCCESS']['MSG'] = $langs['LANG_MANAGER_LEAGUE_JOINED_U'];
         }
       }     

       $sql="SELECT COUNT(USER_ID) USERS from manager_leagues_members WHERE STATUS in (1,2) and league_id =". $_GET['league_id'];
       $db->query($sql);     
       if ($row = $db->nextRow()) {
         if ($row['USERS'] >= $pleague->league_info['PARTICIPANTS'] && $pleague->league_info['PARTICIPANTS'] > 0) {
           $pleague->cancelAllInvites();
           unset($sdata);
           $sdata['RECRUITMENT_ACTIVE'] = "'N'";
           $db->update("manager_leagues", $sdata, "LEAGUE_ID=".$_GET['league_id']);   
         }
       }
    } else {
      // cannot verify code
      $league['ERROR']['MSG'] = $langs['LANG_ERROR_LEAGUE_INVITE_CODE_U'];
    }
  }

  $forumPermission = new ForumPermission();
  if (isset($_POST['post_comment']) && $auth->userOn() &&
     $forumPermission->canAddComment($_POST['topic_id']) == 0) {
     $forumbox->addPost($forums['MANAGER_LEAGUES'], $_POST['topic_id'], $_POST['item_id']);
  }

  if (!$auth->userOn()) {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_LOGIN');
  } 

     if (isset($_GET['league_id'])) {
       // particular tournament requested
       $last_tour_id = $manager->getLastTour();
       $sql= "SELECT ML.*, MLM.*, U.USER_NAME, MSS.POINTS, MSS.PLACE, 
		MUT.POINTS AS LAST_POINTS, MUT.POINTS_MAIN, MUT.MONEY, 
		C.CCTLD, CD.COUNTRY_NAME, MU.ALLOW_VIEW
            FROM manager_leagues ML, manager_leagues_members MLM, 
	       manager_seasons MS, users U
                 LEFT JOIN manager_standings MSS ON MSS.USER_ID=U.USER_ID AND MSS.MSEASON_ID=".$manager->mseason_id."
                 LEFT JOIN manager_users_tours MUT ON MUT.USER_ID=U.USER_ID AND MUT.SEASON_ID=".$manager->mseason_id." AND MUT.TOUR_ID=".$last_tour_id."
		 LEFT JOIN manager_users MU ON 	MU.USER_ID=U.USER_ID AND MU.SEASON_ID=".$manager->mseason_id."
	      , countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
           WHERE ML.LEAGUE_ID=".$_GET['league_id']."
	        AND MLM.LEAGUE_ID=".$_GET['league_id']."
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
	if (($auth->hasSupporter() && !$manager->manager_trade_allow) || $row['ALLOW_VIEW'] == '1')
          $member['ALLOW'] = $row;
        else 
          $member['NOTALLOW'] = $row;
        if ($auth->userOn() && $member['USER_ID'] == $auth->getUserId())
          $member['CURRENT'] = 1;


        if ($auth->userOn() && $auth->getUserId() == $row['USER_ID'])
	  $league['MEMBER'] = 1;

        $league['INVITE_TYPE'] = $row['INVITE_TYPE'];
        if ($row['STATUS'] ==1) {
          $member['OWNER'] = 1;
          $league['TITLE'] = $row['TITLE'];
          $league['OWNER'] = $row['USER_NAME'];

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
      if (!$manager_user->inited)
        $league['NO_TEAM'] = 1;
      else if (!isset($league['MEMBER']) && $auth->userOn() && ($league['ENTRY_FEE'] == 0 || ($league['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] >= $league['ENTRY_FEE'])))
        $league['CAN_JOIN'] = 1;
      else if (!isset($league['MEMBER']) && $auth->userOn() && $league['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] < $league['ENTRY_FEE']) {
        $league['NOT_ENOUGH_CREDITS'] = 1;
      }

      // get voting
      if ($auth->userOn()) { 
        $sql= "SELECT DISTINCT MLV.VOTE, MLM.LEAGUE_ID, MLM.USER_ID
            FROM manager_leagues_members MLM
			left join manager_leagues_votes MLV
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

//      $pleague = new League("manager", $_GET['league_id']);
      $rating = $pleague->getOwnerRating($owner);
      if ($rating != 0) 
        $league['OWNER_RATING'] = $rating;
      else $league['OWNER_RATING'] = $langs['LANG_NONE_U'];

      // get league rating
      $rating = $pleague->getLeagueRating();
      if ($rating != 0) 
        $league['LEAGUE_RATING'] = $rating;
      else $league['LEAGUE_RATING'] = $langs['LANG_NONE_U'];

      $league['REFUNDED'] = $pleague->league_info['REFUNDED'];
      if ($rating < 0 && $league['ENTRY_FEE'] > 0 && $league['REFUNDED'] == 0
		&& $manager->season_over && $auth->isAdmin()) {
        $sql= "SELECT MLM.USER_ID, MLM.STATUS, MLM.REFUNDED
	            FROM manager_leagues_members MLM
        	   WHERE MLM.LEAGUE_ID=".$_GET['league_id']."
	        	AND MLM.STATUS IN (2, 4)"; 

        $db->query($sql);     
        $elmembers = array(); 
        $eligible_members = 0;
        while ($row = $db->nextRow()) {
          $elmembers[] = $row;
          if ($row['REFUNDED'] == 0)
            $eligible_members++;
        }
        $league['CAN_REFUND']['LEAGUE_ID'] = $_GET['league_id'];
        $league_owner = new User();
        $league_owner->getUserIdFromId($pleague->league_info['USER_ID']);
	$owner_data = $league_owner->getUserData();
	$league['CAN_REFUND']['OWNER_CREDITS'] = $owner_data['CREDIT'];
	$league['CAN_REFUND']['OWED_CREDITS'] = $eligible_members*$pleague->league_info['ENTRY_FEE'];
	$league['CAN_REFUND']['REFUND'] = round($owner_data['CREDIT'] * 0.9 / $eligible_members, 2);
        if ($league['CAN_REFUND']['REFUND'] > $league['ENTRY_FEE'])
          $league['CAN_REFUND']['REFUND'] = $league['ENTRY_FEE'];
	if (isset($_POST['refund_league'])) {
          $credits = new Credits();
          unset($sdata);
          foreach( $elmembers as $member) {
            if (($member['STATUS'] == 2 || $member['STATUS'] == 4) && $member['REFUNDED'] == 0) {
              $credits->transferCredit($pleague->league_info['USER_ID'], $member['USER_ID'], $league['CAN_REFUND']['REFUND'], 10, 29);
              $sdata['REFUNDED'] = 1;
              $db->update('manager_leagues_members', $sdata, "LEAGUE_ID=".$_GET['league_id']." AND USER_ID=".$member['USER_ID']);
            }
          }
	  $db->update('manager_leagues', $sdata, "LEAGUE_ID=".$_GET['league_id']);
          unset($sdata);
	  $sdata['LEAGUE_OWNER_RATING'] = "LEAGUE_OWNER_RATING-10";
	  $db->update('users', $sdata, "USER_ID=".$pleague->league_info['USER_ID']);
          $league['CAN_REFUND']['REFUNDED'] = true;
        }
      }

      $sql = "SELECT DISTINCT MUT.TOUR_ID
	 FROM manager_users_tours MUT
	WHERE MUT.SEASON_ID=".$manager->mseason_id." 
             ORDER BY MUT.TOUR_ID";
      $db->query($sql);   
      $tours = array();
      while ($row = $db->nextRow()) {
        unset($tour);
        $state = 'NORMAL'; 
        $tour[$state] = $row;
        $tour[$state]['NUMBER'] = $row['TOUR_ID'];
        $tour[$state]['LEAGUE_ID'] = $_GET['league_id'];
        $tour[$state]['MSEASON_ID'] = $manager->mseason_id;
        $tours[] = $tour;
      }

    }
    
    $filtering = "";
    $leagues_number = 0;
    if (!isset($_GET['league_id'])) {
      if (isset($_POST['filter']) && $_POST['filter']=y) {
        $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['where_int'] = $_POST['where_int'];
        $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_title'] = $_POST['query_title'];
        $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_owner'] = $_POST['query_owner'];
        $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_less'] = $_POST['query_less'];
        $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_more'] = $_POST['query_more'];
      }
      else if (isset($_POST['filter']) && $_POST['filter']=n) {
        unset($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']);
      }

      $opt_int = array(
        'class' => 'input',
        'options' => array(
          'JOINED' => 'LANG_JOINED_U',
          'ENTRY_FEE' => 'LANG_ENTRY_FEE_U',
          'OWNER_RATING' => 'LANG_OWNER_RATING_U'
        )
      );

      $filtering['WHERE_INT'] = $frm->getInput(FORM_INPUT_SELECT, 'where_int', $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['where_int'], $opt_int, $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['where_int']);
      $filtering['QUERY_OWNER'] = $frm->getInput(FORM_INPUT_TEXT, 'query_owner', $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_owner'], array('class' => 'input'));
      $filtering['QUERY_TITLE'] = $frm->getInput(FORM_INPUT_TEXT, 'query_title', $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_title'], array('class' => 'input'));
      $filtering['QUERY_LESS'] = $frm->getInput(FORM_INPUT_TEXT, 'query_less', $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_less'], array('class' => 'input_short', 'maxlength' => '5'));
      $filtering['QUERY_MORE'] = $frm->getInput(FORM_INPUT_TEXT, 'query_more', $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_more'], array('class' => 'input_short', 'maxlength' => '5'));


      $param['where'] = '';
      if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_title'])) {
        $param['where'] = " AND UPPER(ML.TITLE) like UPPER('%".$_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_title']."%') ";
        $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
      }
 
      if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_owner'])) {
        $param['where'] .= " AND UPPER(USER_NAME) like UPPER('%".$_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_owner']."%') ";
        $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
      }
  
      if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_less']) ||
           !empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_more'])) {
         if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_less']))
           $param['where'] .= " AND ".$_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['where_int']." >= ".$_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_less'];
         if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_more']))
           $param['where'] .= " AND ".$_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['where_int']." <= ".$_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER_LEAGUE']['query_more'];
         $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
      }

      $page_size = 50;
      if ((isset($_GET['all']) && $_GET['all'] == 'y') || !$auth->userOn()) {
        $leagues = $manager->getLeagues($param['where']);
        $leagues_number = $manager->leagues;
        $filtering['ALL_LEAGUES'] = 1;
      }
      else {
        $leagues = $manager_user->getLeagues($param['where']);
        $leagues_number = $manager_user->leagues;
        $filtering['MY_LEAGUES'] = 1;
      }
    }

    $leagues_data['LEAGUES'] = $leagues;
    $leagues_data['LEAGUES_PAGING'] = $pagingbox->getPagingBox($leagues_number, isset($_GET['page2']) ? $_GET['page2'] : 1, $page_size, 'page2');

    if ($auth->userOn() && !$manager_user->hasLeague())
      $create_league_offer = 1;
    // add data

  $smarty->clearAllAssign();  
//print_r($league);
  if (isset($league)) {
    $smarty->assign("league_item", $league);
    $smarty->assign("tours", $tours);
  }
  if (isset($leagues_data))
    $smarty->assign("leagues", $leagues_data);
  $smarty->assign("filtering", $filtering);

  if (isset($error))  
    $smarty->assign("error", $error);
  $smarty->assign("manager_filter_box", $manager_filter_box);
  if (isset($create_league_offer))
    $smarty->assign("create_league_offer", $create_league_offer);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_league.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_league.smarty'.($stop-$start);

//$db->showquery=true;
  if (isset($_GET['league_id']) && $_GET['league_id'] != '') {
    $page_size = 40;
    $content .= $forumbox->getComments($_GET['league_id'], 'MANAGER_LEAGUES', isset($_GET['page']) ? $_GET['page'] : 1);
  }


// ----------------------------------------------------------------------------
    define("FANTASY_MANAGER", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager.inc.php');

// close connections
include('class/db_close.inc.php');
?>