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
include('class/wager.inc.php');
include('class/wager_user.inc.php');
// --- build content data -----------------------------------------------------

// include common header
$content = '';

//$db->showquery=true;

  $wager = new Wager();
  $wagerbox = new WagerBox($langs, $_SESSION["_lang"]);
  $wager_user = new WagerUser($wager->tseason_id);

  $wager_filter_box = $wagerbox->getWagerFilterBox($wager->tseason_id );

  if (isset($_POST['join_league'])) {

    $pleague = new League("wager", $_GET['league_id']);
    $pleague->getLeagueInfo();
    $code = $pleague->league_info['INVITE_CODE'];
    if ($code != "" && $code == $_POST['league_code']) {
       unset($sdata);
       $sdata['JOINED'] = 'JOINED+1';  
       $db->update('wager_leagues', $sdata, "LEAGUE_ID=".$_GET['league_id']);

     // join
       unset($sdata);
       $sdata['LEAGUE_ID'] = $_GET['league_id'];
       $sdata['USER_ID'] = $auth->getUserId();
       $sdata['STATUS'] = 2;
       $sdata['START_DATE'] = "NOW()";

       if ($pleague->league_info['ENTRY_FEE'] == 0) {
         $db->delete('wager_leagues_members', "LEAGUE_ID=".$_GET['league_id']." AND USER_ID=".$auth->getUserId());
         $db->insert('wager_leagues_members', $sdata);
         unset($udata);
         $wager_user_log = new WagerUserLog();
         $wager_user_log->logEvent($auth->getUserId(), 8, 0, 0, $wager->tseason_id, '', $pleague->league_info['USER_ID']);
         $wager_user_log->logEvent($pleague->league_info['USER_ID'], 9, 0, 0, $wager->tseason_id, '', $auth->getUserId());
         $league['SUCCESS']['MSG'] = $langs['LANG_MANAGER_LEAGUE_JOINED_U'];
       } else {
         if ($pleague->league_info['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] >= $pleague->league_info['ENTRY_FEE']) {
           $db->delete('wager_leagues_members', "LEAGUE_ID=".$_GET['league_id']." AND USER_ID=".$auth->getUserId());
           $db->insert('wager_leagues_members', $sdata);

           // transfer credits
           $credits = new Credits();
           $credit_log = new CreditsLog();
           $credits->updateCredits($pleague->league_info['USER_ID'], $pleague->league_info['ENTRY_FEE']*0.95);
           $credit_log->logEvent ($pleague->league_info['USER_ID'], 10, $pleague->league_info['ENTRY_FEE']*0.95, $auth->getUserId());
           $credits->updateCredits($auth->getUserId(), -1*$pleague->league_info['ENTRY_FEE']); 
           $credit_log->logEvent ($auth->getUserId(), 5, $pleague->league_info['ENTRY_FEE'], $pleague->league_info['USER_ID']);
           $wager_user_log = new WagerUserLog();
           $wager_user_log->logEvent($auth->getUserId(), 8, 0, 0, $wager->tseason_id, '', $pleague->league_info['USER_ID']);
           $wager_user_log->logEvent($pleague->league_info['USER_ID'], 9, 0, 0, $wager->tseason_id, '', $auth->getUserId());
           $league['SUCCESS']['MSG'] = $langs['LANG_MANAGER_LEAGUE_JOINED_U'];
         }
       }     

       $sql="SELECT COUNT(USER_ID) USERS from wager_leagues_members WHERE STATUS in (1,2) and league_id =". $_GET['league_id'];
       $db->query($sql);     
       if ($row = $db->nextRow()) {
         if ($row['USERS'] >= $pleague->league_info['PARTICIPANTS'] && $pleague->league_info['PARTICIPANTS'] > 0) {
           $pleague->cancelAllInvites();
           unset($sdata);
           $sdata['RECRUITMENT_ACTIVE'] = "'N'";
           $db->update("wager_leagues", $sdata, "LEAGUE_ID=".$_GET['league_id']);   
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
     $forumbox->addPost($forums['WAGER_LEAGUES'], $_POST['topic_id'], $_POST['item_id']);
  }

  if (!$auth->userOn()) {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_WAGER_LOGIN');
  } 

     if (isset($_GET['league_id'])) {
       $wager->checkLeagueActive($_GET['league_id']);

       $sql="SELECT  ML.*, U.USER_NAME as OWNER, ML.START_DATE as LEAGUE_START_DATE
		FROM wager_leagues ML, wager_leagues_members MLM, users U
		WHERE MLM.STATUS=1
		      AND MLM.LEAGUE_ID = ML.LEAGUE_ID
		      AND MLM.USER_ID = U.USER_ID
		      AND ML.LEAGUE_ID=".$_GET['league_id'];
       $db->query($sql);     
       if ($row = $db->nextRow()) {
         $league = $row; 
         $league['POINT_TYPE_DESCR'] = $wager_league_point_types[$row['POINT_TYPE']]; 
         $owner=$row['USER_ID'];
         $league['CHAT']['CHAT_CHANNEL'] = $row['TITLE']."_".$row['LEAGUE_ID']."_".$row['SEASON_ID'];
         if (!empty($row['RULES']))  
           $league['RULES'] = $row['RULES'];
       }

       // particular tournament requested
       $sql= "SELECT ML.*, MLM.*, U.USER_NAME, MSS.WEALTH, MSS.PLACE, ML.START_DATE as LEAGUE_START_DATE,
		C.CCTLD, CD.COUNTRY_NAME
            FROM wager_leagues ML, wager_leagues_members MLM, wager_users WU, 
                seasons S, games G, wager_games WG, wager_seasons MS, users U
                 LEFT JOIN wager_standings MSS ON MSS.USER_ID=U.USER_ID AND MSS.SEASON_ID=".$wager->tseason_id ."
	      , countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
           WHERE ML.LEAGUE_ID=".$_GET['league_id']."
	        AND MLM.LEAGUE_ID=".$_GET['league_id']."
                AND MS.SEASON_ID=ML.SEASON_ID   
                AND MLM.USER_ID=U.USER_ID
	        AND MLM.STATUS IN (1,2)
                AND WG.WSEASON_ID = MS.SEASON_ID
		and WU.SEASON_ID= MS.SEASON_ID
                AND G.SEASON_ID = S.SEASON_ID
                AND G.GAME_ID=WG.GAME_ID
   	        AND U.COUNTRY = C.ID
                AND MLM.USER_ID=WU.USER_ID
           GROUP BY MLM.USER_ID"; 
//echo $sql;
      $db->query($sql);     
      $c=1;    
      $members = array(); 
      $all_members = "";
      $pre = "";
      while ($row = $db->nextRow()) {
        $member = $row;
        $all_members .= $pre.$row['USER_ID'];
        $pre = ",";
        if (!empty($row['CCTLD'])) {
          $member['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
          $member['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
        }

        if ($auth->userOn() && $auth->getUserId() == $row['USER_ID'])
	  $league['MEMBER'] = 1;

        if ($row['STATUS'] ==1) {
          $member['OWNER'] = 1;
        }
        else if ($row['STATUS'] == 2) {
          $member['CURRENT_MEMBERS'] = 1;
        } 
        $members[$member['USER_ID']] = $member;
      }

      $profit = "SUM(WV.RETURN - IF(WV.RETURN>0,WV.STAKE,0)) as PROFIT";
      if ($league['POINT_TYPE'] == 1)
        $profit = "SUM(WV.RETURN) - SUM(IF(WV.RETURN>0,0,WV.STAKE)) as PROFIT";
      else if ($league['POINT_TYPE'] == 2)
        $profit = "SUM(WV.POINTS) as PROFIT";
      else if ($league['POINT_TYPE'] == 3)
        $profit = "SUM(WV.POINTS + WV.RETURN - IF(WV.RETURN>0,WV.STAKE,0)) as PROFIT";
      else if ($league['POINT_TYPE'] == 4)
        $profit = "SUM(WV.POINTS) + SUM(WV.RETURN) - SUM(IF(WV.RETURN>0,0,WV.STAKE)) as PROFIT";


       $sql= "SELECT MLM.USER_ID,
		".$profit.", WU.REFILLED
            FROM wager_leagues ML 
		  left join wager_league_tours WLT on WLT.LEAGUE_ID=ML.LEAGUE_ID,
		 wager_leagues_members MLM, wager_users WU, 
                seasons S, games G, wager_games WG, wager_votes WV,
		wager_seasons MS, users U
           WHERE ML.LEAGUE_ID=".$_GET['league_id']."
	        AND MLM.LEAGUE_ID=".$_GET['league_id']."
                AND MS.SEASON_ID=ML.SEASON_ID   
                AND MLM.USER_ID=U.USER_ID
	        AND MLM.STATUS IN (1,2)
                AND WG.WSEASON_ID = MS.SEASON_ID
		and WU.SEASON_ID= MS.SEASON_ID
                AND G.SEASON_ID = S.SEASON_ID
                AND G.GAME_ID=WG.GAME_ID
                AND MLM.USER_ID=WU.USER_ID
 	        AND WV.WAGER_ID=WG.WAGER_ID
		AND WV.USER_ID=MLM.USER_ID
                AND MLM.USER_ID IN (".$all_members.")
      	        AND WLT.LEAGUE_ID=MLM.LEAGUE_ID
                AND ML.START_DATE IS NOT NULL
		AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > WLT.START_DATE
		AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < WLT.END_DATE
		AND G.SCORE1 > -1 AND G.SCORE2 > -1
           GROUP BY MLM.USER_ID
	   ORDER BY PROFIT DESC"; 
//echo $sql;
         $db->query($sql);   
         $tourstats = array();
         $c = 0;
         while ($row = $db->nextRow()) {
           $members[$row['USER_ID']]['LOCAL_PLACE'] = ++$c;
           $members[$row['USER_ID']]['PROFIT'] = $row['PROFIT'];
           $members[$row['USER_ID']]['REFILLED'] = $row['REFILLED'];
         }

      foreach ($members as &$member) {
        if (!isset($member['LOCAL_PLACE'])) {
          $member['LOCAL_PLACE'] = ++$c;
          $member['PROFIT'] = 0;
        }
      }

      function custom_sort($a,$b) {
          return $a['LOCAL_PLACE']>$b['LOCAL_PLACE'];
      }

      usort($members, "custom_sort");
      // Define the custom sort function

      $league['SEASON_TITLE'] = $wager->getTitle();
      $league['MEMBERS'] = $members;
      if (!$wager_user->inited)
        $league['NO_TEAM'] = 1;
      else if (!isset($league['MEMBER']) && $auth->userOn() && ($league['ENTRY_FEE'] == 0 || ($league['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] >= $league['ENTRY_FEE'])))
        $league['CAN_JOIN'] = 1;
      else if (!isset($league['MEMBER']) && $auth->userOn() && $league['ENTRY_FEE'] > 0 && $_SESSION['_user']['CREDIT'] < $league['ENTRY_FEE']) {
        $league['NOT_ENOUGH_CREDITS'] = 1;
      }
      $league['TOURS'] = $wager->getLeagueTours($_GET['league_id']);
      // get voting
      if ($auth->userOn()) { 
        $sql= "SELECT DISTINCT MLV.VOTE, MLM.LEAGUE_ID, MLM.USER_ID
            FROM wager_leagues_members MLM
			left join wager_leagues_votes MLV
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

      $pleague = new League("wager", $_GET['league_id']);
      $rating = $pleague->getOwnerRating($owner);
      if ($rating > -1) 
        $league['OWNER_RATING'] = $rating;
      else $league['OWNER_RATING'] = $langs['LANG_NONE_U'];

      // get league rating
      $rating = $pleague->getLeagueRating();
      if ($rating > -1) 
        $league['LEAGUE_RATING'] = $rating;
      else $league['LEAGUE_RATING'] = $langs['LANG_NONE_U'];

      $sql = "SELECT DISTINCT MUT.TOUR_ID
	 FROM wager_league_tours MUT
	WHERE MUT.LEAGUE_ID=".$_GET['league_id']." 
		AND MUT.START_DATE < NOW()		
             ORDER BY MUT.TOUR_ID";
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
    } else {
    
      $filtering = "";
      $leagues_number = 0;
      if ((isset($_GET['all']) && $_GET['all'] == 'y') || !$auth->userOn()) {
        $_GET['all'] = 'y';
        $leagues = $wager->getLeagues();
        $leagues_number = $wager->leagues;
        $filtering['ALL_LEAGUES'] = 1;
      }
      else {
        $_GET['all'] = 'n';
        $leagues = $wager_user->getLeagues();
        $leagues_number = $wager_user->leagues;
        $filtering['MY_LEAGUES'] = 1;
      }

      if ($leagues_number > 0) {      
        $leagues_data['LEAGUES'] = $leagues;
        $leagues_data['LEAGUES_PAGING'] = $pagingbox->getPagingBox($leagues_number, isset($_GET['page2']) ? $_GET['page2'] : 1, $page_size, 'page2');
      }
      if ($auth->userOn() && !$wager_user->hasLeague())
        $create_league_offer = 1;

    
    // add data
   }

  $smarty->clearAllAssign();  
//print_r($league);
  if (isset($league)) {
    $smarty->assign("league_item", $league);
    $smarty->assign("tours", $tours);
  }
  if (isset($leagues_data))
    $smarty->assign("leagues", $leagues_data);
  if (isset($filtering))
    $smarty->assign("filtering", $filtering);

  if (isset($error))  
    $smarty->assign("error", $error);
  $smarty->assign("wager_filter_box", $wager_filter_box);
  if (isset($create_league_offer))
    $smarty->assign("create_league_offer", $create_league_offer);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/wager_league.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/wager_league.smarty'.($stop-$start);

//$db->showquery=true;
  if (isset($_GET['league_id']) && $_GET['league_id'] != '') {
    $content .= $forumbox->getComments($_GET['league_id'], 'WAGER_LEAGUES', isset($_GET['page']) ? $_GET['page'] : 1);
  }

  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 6);
// ----------------------------------------------------------------------------

  define("WAGER", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_wager.inc.php');

// close connections
include('class/db_close.inc.php');
?>