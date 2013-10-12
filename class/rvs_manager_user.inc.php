<?php
/*
===============================================================================
events.inc.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - generate event list data

TABLES USED: 
  - BASKET.EVENTS

STATUS:
  - [STAT:INPRGS] in progress

TODO:
  - [TODO:PUBVAR] main functionality
===============================================================================
*/

class RvsManagerUser {
  var $mseason_id;
  var $league_id;
  var $team_players_list;
  var $team_substitutes_list;
  var $team_main_players;
  var $team_substitutes_players;

  var $posit;
  var $substitutes;
  var $user_jokers;
  var $left_jokers;
  var $inited;
  var $team_size;
  var $active_team_size;
  var $substitutes_size;
  var $leagues;
  var $teams;
  var $free_transfer_fee;
  var $league_over;
  var $notify;
  
  function RvsManagerUser($mseason_id, $league_id='') {
    $this->mseason_id = $mseason_id;
    $this->league_id = $league_id;
    $this->team_players_list = '';
    $this->discards = 0;
    $this->free_transfers = 0;
    $this->free_transfer_fee = 0;
    $this->league_over = false;
    $this->team_main_players = array();

    $this->user_jokers = 0;
    $this->left_jokers = 0;

    $this->getLeague();
    $this->initUser();
    $this->team_size=0;
    $this->active_team_size=0;
  }

  function initUser() {
    global $auth;
    global $db;
    global $_SESSION;

    if ($auth->getUserId()) {
      $sql = "SELECT MU.*, RMS.POINTS, RMS.PLACE, RVL.DISCARDS, RVL.FREE_TRANSFERS, RVL.FREE_TRANSFER_FEE
             FROM rvs_manager_leagues RVL, users U LEFT JOIN rvs_manager_leagues_members MU ON U.USER_ID=MU.USER_ID and MU.LEAGUE_ID=".$this->league_id."
                     LEFT JOIN rvs_manager_standings RMS ON U.USER_ID=MU.USER_ID and RMS.LEAGUE_ID=".$this->league_id."
             WHERE U.USER_ID=".$auth->getUserId()."
		   AND RVL.LEAGUE_ID=".$this->league_id;
//echo $sql;
      $db->query($sql); 
      if ($row = $db->nextRow()) {
	  $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['POINTS'] = $row['POINTS'];
	  $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['PLACE'] = $row['PLACE'];
	  $this->discards = $row['DISCARDS'];
	  $this->free_transfers = $row['FREE_TRANSFERS'];
	  $this->free_transfer_fee = $row['FREE_TRANSFER_FEE'];
	  $this->notify = $row['NOTIFY'];

          $db->free();

          $this->inited = true;
       }
       else $this->inited = false;
    }
    else $this->inited = false;
  }

  function getLeague() {
    global $db;
    global $auth;
    global $_SESSION; 
    global $_GET; 
    global $_COOKIE;
    global $manager;

    if (empty($this->league_id)) {
      if (isset($_SESSION['_user']['RVS_LEAGUE_ID'][$manager->mseason_id]) && !isset($_GET['league_id'])) {
        $this->league_id = $_SESSION['_user']['RVS_LEAGUE_ID'][$manager->mseason_id];
      } else if (!isset($_GET['league_id']) && isset($_COOKIE['rvs_league_id'.$manager->mseason_id])) {
        $this->league_id = $_COOKIE['rvs_league_id'.$manager->mseason_id];
      } else if (!isset($_GET['league_id'])) {
        $db->select("rvs_manager_leagues", "*", "STATUS in (1,2) AND SEASON_ID=".$manager->mseason_id);
        if ($row = $db->nextRow()) {
          $this->league_id = $row['LEAGUE_ID'];
        }
      }
      else {
  	$this->league_id = $_GET['league_id'];
      }
    }

    if ($auth->userOn()) {
      $_SESSION['_user']['RVS_LEAGUE_ID'][$manager->mseason_id] = $this->league_id;
    }

    setcookie('rvs_league_id', $this->league_id, time()+3600*24*365);
    return $this->league_id;
  }

  function initLeagues() {

  }

  function getRvsLeagues($open = false) {
     global $db;
     global $auth;

     $where_open = "";
     if ($open)
       $where_open = " AND ML.STATUS=1";

     $mleagues = array();
     $permissions = new ForumPermission();
     $can_chat = $permissions->canChat();
     $sql="SELECT ML.LEAGUE_ID, ML.TOPIC_ID, ML.TITLE, ML.ENTRY_FEE, MLM.STATUS, ML.SEASON_ID, 'n' as ALL_LEAGUES, 
		U.USER_NAME, T.POSTS, ML.JOINED USERS, TT.MARK_TIME, ML.FREE_TRANSFER_FEE, DRAFT_START_DATE,
		TT.MARK_TIME < T.LAST_POSTED AS TRACKER, UNIX_TIMESTAMP( TT.MARK_TIME ) AS TSTMP, T.LAST_POSTER_ID, 
		ML.PARTICIPANTS, ML.DISCARDS, ML.FREE_TRANSFERS, ML.DURATION, ML.STATUS as LEAGUE_STATUS, ML.TEAM_SIZE, 
		C.CCTLD, CD.COUNTRY_NAME, ML.INVITE_TYPE, ML.PRIZE_FUND, ML.REAL_PRIZES, ML.DRAFT_STATE, ML.RESERVE_SIZE, 
		ML.LEAGUE_TYPE FORMAT, ML.DRAFT_TYPE, ML.DRAFT_START_DATE > NOW() as DRAFTING,
		DATE_ADD(DRAFT_START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS DRAFT_START_DATE_UTC,
 	        ML.MODERATE_TRANSFERS
             FROM rvs_manager_leagues_members MLM, manager_seasons M, rvs_manager_leagues ML
                  LEFT JOIN rvs_manager_leagues_members MLM1 ON ML.LEAGUE_ID=MLM1.LEAGUE_ID AND (MLM1.STATUS=1)
                  LEFT JOIN users U on MLM1.USER_ID=U.USER_Id and MLM1.STATUS=1 
                  LEFT JOIN topic T on ML.TOPIC_ID=T.TOPIC_ID
		  left join topic_track TT ON T.TOPIC_ID=TT.TOPIC_ID AND TT.USER_ID=".$auth->getUserId()."
                  LEFT JOIN countries C ON ML.COUNTRY = C.ID
		  LEFT JOIN countries_details CD ON C.ID=CD.ID aND CD.LANG_ID=".$_SESSION['lang_id']."
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
               AND ML.SEASON_ID=".$this->mseason_id." 
               AND M.SEASON_ID=".$this->mseason_id." 
               AND MLM.USER_ID=".$auth->getUserId()."
               AND (MLM.STATUS=2 OR MLM.STATUS=1)
		".$where_open."
	     GROUP BY MLM.LEAGUE_ID 
	     ORDER BY ML.CREATED_DATE, MLM.STATUS ASC, ML.TITLE ASC";
    $db->query($sql);    
//echo $sql; 
    while ($row = $db->nextRow()) {
      unset($league);
      if ($row['PARTICIPANTS'] == 0)
	$row['PARTICIPANTS'] = "&infin;";

      if ($row['PARTICIPANTS'] > 0)
	$row['PROGRESS']['PERCENTS'] = $row['USERS'] *100/ $row['PARTICIPANTS'];	

      if (!empty($row['CCTLD'])) {
        $row['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $row['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }

      $league = $row;
      if ($row['STATUS'] == 2) {
        $league['LEAGUE'] = 1;
        if ($can_chat) 
          $league['CHAT']['CHAT_CHANNEL'] = $row['TITLE']."_".$row['LEAGUE_ID']."_".$row['SEASON_ID'];
      }
      else if ($row['STATUS'] == 1) {
        $league['OWN_LEAGUE'] = $row;
        $league['OWN_LEAGUE']['TITLE'] = $row['TITLE'];
        if ($can_chat) 
          $league['CHAT']['CHAT_CHANNEL'] = $row['TITLE']."_".$row['LEAGUE_ID']."_".$row['SEASON_ID'];
      }

      if ($row['REAL_PRIZES'] == 'Y') {
        $league['PRIZES'] = 1;
      }

      if ((empty($row['MARK_TIME']) || !isset($row['TRACKER']) || (isset($row['TRACKER']) && $row['TRACKER'] != 0 && $row['TRACKER'] != '')) && $row['LAST_POSTER_ID'] != $auth->getUserId() && !empty($row['LAST_POSTER_ID'])) {
        if ($row['STATUS'] == 1) {
          $league['TRACK']['TOPIC_ID'] = $row['TOPIC_ID'];
          $league['TRACK']['LEAGUE_ID'] = $row['LEAGUE_ID'];
          if (!empty($row['TSTMP']))
            $league['TRACK']['TSTMP'] = $row['TSTMP'];
          else $league['TRACK']['TSTMP'] = -1;
        }
        else if ($row['STATUS'] == 2) {
          $league['TRACK']['TOPIC_ID'] = $row['TOPIC_ID'];
          $league['TRACK']['LEAGUE_ID'] = $row['LEAGUE_ID'];
          if (!empty($row['TSTMP']))
            $league['TRACK']['TSTMP'] = $row['TSTMP'];
          else $league['TRACK']['TSTMP'] = -1;
        }
      }
      if (empty($row['POSTS']))
        $league['POSTS'] = 0;

      $league['IN_LEAGUE'] = 1;
      $league['DRAFT_START_DATE'] = $row['DRAFT_START_DATE'];
      $league['UTC'] = $auth->getUserTimezoneName();
      $mleagues[] = $league;
    }

    $this->leagues = count($mleagues);
    return $mleagues;
  }

  function getRvsLeaguesInvites() {
     global $db;
     global $auth;

     $league_invites = array();
   // get invitations
     $sql="SELECT ML.LEAGUE_ID, ML.TITLE, ML.ENTRY_FEE
             FROM rvs_manager_leagues ML, rvs_manager_leagues_members MLM
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
               AND MLM.USER_ID=".$auth->getUserId()."
	       AND ML.SEASON_ID=".$this->mseason_id."
               AND MLM.STATUS=3";
//echo $sql; 
//               AND ML.LEAGUE_ID=".$this->league_id." 
    $db->query($sql);    
    $c = 0;
    while ($row = $db->nextRow()) {
      $league_invite = $row;
      if ($row['ENTRY_FEE'] > 0 ) {
        $league_invite['ENTRY'] = $row;
        if ($_SESSION['_user']['CREDIT'] >= $row['ENTRY_FEE']) {
           $league_invite['ENOUGH_CREDITS'] = $row;
           $league_invite['BUTTONS'] = $row;
        } else {
           $league_invite['NOT_ENOUGH_CREDITS'] = $row;
        }  
      }
      else {
        $league_invite['BUTTONS'] = $row;
      }
      $c++;
      $league_invites[] = $league_invite;
    }
    $db->free(); 

    return $league_invites;
  }

  function getTeam($tour, $last_tour) {
    global $db;
    global $auth;    
    global $manager;
    global $smarty;
    global $position_types;
    global $pleague;

    $this->active_team_size = 0;
    $data='';
    $summary = array();

    $sql = "SELECT * FROM rvs_manager_users_tours 
		WHERE LEAGUE_ID=".$this->league_id."
			AND USER_ID=".$auth->getUserId()."
			AND TOUR_ID=".$tour;
   $db->query($sql);

   if ($row = $db->nextRow()) {
     $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['USED_DISCARDS'] = $row['USED_DISCARDS']; 
     $summary['USED_DISCARDS'] = $row['USED_DISCARDS'];     
     $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['USED_FREE_TRANSFERS'] = $row['USED_FREE_TRANSFERS']; 
     $summary['USED_FREE_TRANSFERS'] = $row['USED_FREE_TRANSFERS'];     
   } else {
     $sql = "SELECT * FROM rvs_manager_leagues RML, rvs_manager_leagues_members RMLM
		WHERE RML.LEAGUE_ID=".$this->league_id."
                        AND RML.DRAFT_DATE is not null
                        AND RML.STATUS = 2
			AND RMLM.LEAGUE_ID=RML.LEAGUE_ID
			AND RMLM.USER_ID=".$auth->getUserId();
     $db->query($sql);
     if ($row = $db->nextRow()) {
       unset($sdata);
       $sdata['USER_ID'] = $auth->getUserId();
       $sdata['TOUR_ID'] = $tour;
       $sdata['LEAGUE_ID'] = $this->league_id;
       $sdata['USED_DISCARDS'] = 0;
       $sdata['USED_FREE_TRANSFERS'] = 0;
       $sdata['PLACE'] = 0;
       $sdata['PLACE_TOUR'] = 0;
       $sdata['POINTS'] = 0;
       $db->insert("rvs_manager_users_tours", $sdata);
     }
     $summary['USED_DISCARDS'] = 0;
     $summary['USED_FREE_TRANSFERS'] = 0;
     $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['USED_DISCARDS'] = 0; 
     $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['USED_FREE_TRANSFERS'] = 0; 
   }
   $summary['DISCARDS'] = $pleague->league_info['DISCARDS'];
   $summary['RESERVE_SIZE'] = $pleague->league_info['RESERVE_SIZE'];
   $summary['TEAM_SIZE'] = $pleague->league_info['TEAM_SIZE'];
   $summary['LEAGUE_ID'] = $this->league_id;
   $summary['FREE_TRANSFERS'] = $pleague->league_info['FREE_TRANSFERS'];
   $summary['DRAFT_STATE'] = $pleague->league_info['DRAFT_STATE'];

   $current_tour = $manager->getCurrentTour();
    // get team list
    $supporter_times = array('','','');
    if ($auth->hasSupporter()) {  
      $supporter_times[0] = ", MTGD.TIMES";
      $supporter_times[1] = "LEFT JOIN manager_tour_games_team MTGD ON MTGD.TEAM_ID=MM.TEAM_ID";
      $supporter_times[2] = ", 0 AS TIMES";
    }

    $sql = "SELECT DISTINCT MU.LEAGUE_ID, MT.ENTRY_ID, MT.PLAYER_ID, B.LAST_NAME, B.FIRST_NAME, MM.TEAM_ID, MP.PUBLISH,
                MM.USER_ID, MM.POSITION_ID1, MM.POSITION_ID2, IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME2,
                SUBSTRING( MT.BUYING_DATE, 1, 16 ) BUYING_DATE, MPS.TOTAL_POINTS as TOTAL_POINTS_PREV2,
                MPS1.TOTAL_POINTS TOTAL1, MPS.TOTAL_POINTS + MPS.TOTAL_POINTS_PREV AS TOTAL_POINTS, MT.BUYING_PRICE, 
                MPS.CURRENT_VALUE_MONEY AS SELLING_PRICE, MPS1.CURRENT_VALUE_MONEY AS SELLING_PRICE2, 
                '1' AS INTOURN, MM.PLAYED, MPS.TOTAL_POINTS_PREV, MPS.KOEFF, MM.INJURY, MM.START_VALUE, MM.TOTAL_POINTS TP, 
		MM.CURRENT_VALUE_MONEY ".$supporter_times[0].", MPRT.TIMES AS REPORTS, MM.PLAYER_STATE, MT.PROTECTED, MT.MODERATED,
		RMTT.LEAGUE_ID IS_IN_TOUR
		FROM  rvs_manager_leagues_members MU, rvs_manager_teams MT
		LEFT JOIN manager_market MM ON MM.USER_ID = MT.PLAYER_ID AND MM.SEASON_ID =".$this->mseason_id."
		 ".$supporter_times[1]."
                  LEFT JOIN manager_player_reports_temp MPRT ON MPRT.PLAYER_ID=MM.USER_ID
		LEFT JOIN teams T ON MM.TEAM_ID=T.TEAM_ID 
		LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN busers B ON B.USER_ID = MT.PLAYER_ID
		LEFT JOIN manager_player_stats MPS ON MT.PLAYER_ID = MPS.PLAYER_ID AND MPS.SEASON_ID =".$this->mseason_id." AND MPS.TOUR_ID=".($tour-1)."
                LEFT JOIN manager_player_stats MPS1 ON MT.PLAYER_ID = MPS1.PLAYER_ID AND MPS1.SEASON_ID =".$this->mseason_id." AND MPS1.TOUR_ID=".($tour)."
		LEFT JOIN manager_players MP ON MT.PLAYER_ID = MP.PLAYER_ID AND MP.SEASON_ID =".$this->mseason_id."
                LEFT JOIN rvs_manager_teams_tours RMTT on MT.PLAYER_ID = RMTT.PLAYER_ID AND RMTT.TOUR_ID=".$tour." AND RMTT.LEAGUE_ID=MT.LEAGUE_ID and RMTT.USER_ID=MT.USER_ID
          WHERE 
            MU.USER_ID=".$auth->getUserId()."
	    AND MU.LEAGUE_ID=".$this->league_id."
            AND MT.USER_ID=MU.USER_ID
            AND MT.SELLING_DATE IS NULL             
	    AND MT.LEAGUE_ID=".$this->league_id."
            AND MP.PUBLISH='Y'    
          UNION

          SELECT DISTINCT MU.LEAGUE_ID, MT.ENTRY_ID, MT.PLAYER_ID, B.LAST_NAME, B.FIRST_NAME, MM.TEAM_ID, MP.PUBLISH,
                MM.USER_ID, MM.POSITION_ID1, MM.POSITION_ID2, IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME2,
                SUBSTRING( MT.BUYING_DATE, 1, 16 ) BUYING_DATE, MPS.TOTAL_POINTS as TOTAL_POINTS_PREV2,
                MPS1.TOTAL_POINTS TOTAL1, MPS.TOTAL_POINTS + MPS.TOTAL_POINTS_PREV AS TOTAL_POINTS, MT.BUYING_PRICE, 
                MPS.CURRENT_VALUE_MONEY AS SELLING_PRICE, MPS1.CURRENT_VALUE_MONEY AS SELLING_PRICE2,
                '0' AS INTOURN, MM.PLAYED, MPS.TOTAL_POINTS_PREV, MPS.KOEFF, MM.INJURY, MM.START_VALUE, MM.TOTAL_POINTS as TP, 
		MM.CURRENT_VALUE_MONEY ".$supporter_times[2].", -1 AS REPORTS, MM.PLAYER_STATE, MT.PROTECTED, MT.MODERATED,
		RMTT.LEAGUE_ID IS_IN_TOUR
		FROM  rvs_manager_leagues_members MU, rvs_manager_teams MT
		LEFT JOIN manager_market MM ON MM.USER_ID = MT.PLAYER_ID AND MM.SEASON_ID =".$this->mseason_id."
		LEFT JOIN teams T ON MM.TEAM_ID=T.TEAM_ID 
		LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN busers B ON B.USER_ID = MT.PLAYER_ID
		LEFT JOIN manager_player_stats MPS ON MT.PLAYER_ID = MPS.PLAYER_ID AND MPS.SEASON_ID =".$this->mseason_id." AND MPS.TOUR_ID=".($tour-1)."
                LEFT JOIN manager_player_stats MPS1 ON MT.PLAYER_ID = MPS1.PLAYER_ID AND MPS1.SEASON_ID =".$this->mseason_id." AND MPS1.TOUR_ID=".($tour)."
		LEFT JOIN manager_players MP ON MT.PLAYER_ID = MP.PLAYER_ID AND MP.SEASON_ID =".$this->mseason_id."
                LEFT JOIN rvs_manager_teams_tours RMTT on MT.PLAYER_ID = RMTT.PLAYER_ID AND RMTT.TOUR_ID=".$tour." AND RMTT.LEAGUE_ID=MT.LEAGUE_ID and RMTT.USER_ID=MT.USER_ID
          WHERE 
            MU.USER_ID=".$auth->getUserId()."
	    AND MU.LEAGUE_ID=".$this->league_id."
            AND MT.USER_ID=MU.USER_ID
            AND MT.SELLING_DATE IS NULL             
	    AND MT.LEAGUE_ID=".$this->league_id."
            AND MP.PUBLISH='N'    
           ORDER BY TOTAL1 DESC, IS_IN_TOUR DESC, TOTAL_POINTS DESC, SELLING_PRICE DESC, POSITION_ID1 DESC, BUYING_PRICE DESC, LAST_NAME DESC";
//echo $sql;
   $db->query($sql);
//echo $db->getNativeErrorText();
   $c = 0;
   $pre = '';
   $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['TEAM_PRICE'] = 0;
   $team_players = array();
   $team = array();
   while ($row = $db->nextRow()) {
     if ($row['SELLING_PRICE2'] == 0)
       $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['TEAM_PRICE'] += $row['SELLING_PRICE'];
     else $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['TEAM_PRICE'] += $row['SELLING_PRICE2'];

     $player = $row;
     $player['KOEFF'] = round($row['KOEFF'], 2);
     $player['SEASON_ID'] = $this->mseason_id;
     $player['COVERED'] = 0;

     if ($auth->hasSupporter()) {  
       $team['TURN_POINT_H'] = 1;
       $team['TIMES_SUPPORT_H'] = 1;
       $player['WILL_PLAY'] = $row['TIMES'];
       $player['TURNING_POINT'] = round(($row['CURRENT_VALUE_MONEY']*($row['PLAYED']+2)- ($row['START_VALUE'] + $row['TP']+1) * 1000)/1000, 2);
     }

     if (isset($row['SELLING_PRICE'])) {
       $player['SELLING_PRICE'] = $row['SELLING_PRICE'];
     } else {
       $player['SELLING_PRICE'] = 7000;
     }

     if ($row['SELLING_PRICE2'] > 0)     
       $player['SELLING_PRICE'] = $row['SELLING_PRICE2'];
     else $player['SELLING_PRICE'] = $row['SELLING_PRICE'];

     if ($player['SELLING_PRICE'] > 
          $player['BUYING_PRICE'])
        $player['UP'] = 1;
     else if ($player['SELLING_PRICE'] < 
              $player['BUYING_PRICE'])
            $player['DOWN'] = 1;

     if (isset($row['TOTAL_POINTS']))
       $player['TOTAL_POINTS'] = $row['TOTAL_POINTS'] + $row['TOTAL1'];
     else $player['TOTAL_POINTS'] = 0;

     if ($last_tour == $tour)
       $player['TOTAL_POINTS_PREV1'] = $row['TOTAL1'];    
     else 
       $player['TOTAL_POINTS_PREV1'] = $row['TOTAL_POINTS_PREV2'];
  
     if ($row['PLAYED'] > 0) {
        $player['PLAYER_SEASON_STATS']['USER_ID'] = $row['USER_ID'];
        $player['PLAYER_SEASON_STATS']['SUBSEASONS'] = $manager->seasonlist;
        $player['PLAYER_SEASON_STATS']['PLAYED'] = $row['PLAYED'];
     } 

     if ($manager->sport_id == 4)
       $player['TOTAL_POINTS'] = $player['TOTAL_POINTS_PREV1'];
     
     if ($row['REPORTS'] == '')
       $player['PLAYER_REPORTS']['REPORTS'] = 0;
     else if ($row['REPORTS'] > 0)
       $player['PLAYER_REPORTS']['REPORTS'] = $row['REPORTS'];

     if ($row['REPORTS'] != -1) {
       $player['PLAYER_REPORTS']['USER_ID'] = $row['USER_ID'];
       $player['PLAYER_REPORTS']['SEASON_ID'] = $this->mseason_id;
     }

     $player['PLAYER_STATE_DIV'] = $manager->getPlayerStateSmarty($row['USER_ID'], $this->mseason_id, $row['PLAYER_STATE']);
   
      if ($row['PROTECTED'] == 1) {
           $player['PROTECTED']=1;
      }
      else $player['PROTECTED']=0;

      if ($row['MODERATED'] == 1) {
           $player['MODERATED']=1;
      }
      else $player['MODERATED']=0;



     if ($row['INTOURN']=='0') 
       $player['INT'] = 1;
     else $this->active_team_size++;
     if ($manager->disabled_trade_wrongday == true) {
       $player['WRONG_DAY'] = 1;
     }
     else {
       if ($pleague->league_info['STATUS'] != 3) {
         if ($this->discards <= $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['USED_DISCARDS'] &&
	     $player['PLAYER_STATE'] == 0 && $player['PUBLISH'] == 'Y') {
           $summary['NO_DISCARDS']=1;
         } else if ($summary['DRAFT_STATE'] == 3 ){
             $player['CAN_DISCARD']=1;
         }

         if ($this->free_transfers <= $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['USED_FREE_TRANSFERS'] &&
	     $player['PLAYER_STATE'] == 0 && $player['PUBLISH'] == 'Y') {
           $summary['NO_FREE_TRANSFER']=1;
         } else {
           if ($summary['DRAFT_STATE'] == 3)
             $player['CAN_FREE_TRANSFER']=1;
         }

/*         if ($row['RMPE_STATUS'] == '') {
           $player['CAN_TRANSFER']=1;
         }
         else if ($row['RMPE_STATUS'] == 0) {
          $player['ON_TRANSFER']=1;
         }  */
       }
     }
   
     $this->team_players_list .= $pre.$row['PLAYER_ID'];
     $pre = ',';

     if (!isset($this->teams[$row['TEAM_ID']]))
       $this->teams[$row['TEAM_ID']] = 1;
     else $this->teams[$row['TEAM_ID']]++;

     if (!empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
       $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
     else $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];

     $c++;    
     $this->team_size = $c;
     $team_players[$row['PLAYER_ID']] = $player;
   }
   $this->team_main_players = $team_players;

   $smarty->assign('team_players', $this->team_main_players);
   if ($this->team_size > 0)
     $smarty->assign('team', $team);

   $smarty->assign('summary', $summary);
  }
  
  function discardPlayer($current_tour) {
    global $db;
    global $_POST;
    global $manager;
    global $auth;
//$db->showquery=true;
    if (isset($_POST['player'])) {     
      $sql = "SELECT *
            FROM manager_market MM 
            WHERE MM.SEASON_ID=".$this->mseason_id." 
                  AND USER_ID =".$_POST['player'];
     $db->query($sql);
     $row = $db->nextRow();
     $price = $row['CURRENT_VALUE_MONEY'];
     $selling_price = $row['CURRENT_VALUE_MONEY'];
     $player_state = $row['PLAYER_STATE'];
     $publish = $row['PUBLISH'];

     if ($row['CURRENT_VALUE_MONEY'] > -1) {     
       if (isset($_POST['discard']) && $manager->manager_trade_allow 
		&& ($this->discards > $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['USED_DISCARDS']
		    || $row['PLAYER_STATE'] != 0 || $row['PUBLISH'] == 'N')) {
          // check that user is still in a team
          $sql = "SELECT ENTRY_ID, USER_ID, BUYING_DATE
              FROM rvs_manager_teams 
              WHERE 
                USER_ID=".$auth->getUserId()."
                AND SELLING_DATE IS NULL 
                AND PLAYER_ID=".$_POST['player']."
		AND LEAGUE_ID=".$this->league_id;
          $db->query($sql);     
          if ($row = $db->nextRow()) {
            $buying_date = $row['BUYING_DATE'];
            // get selling price   
            $where_played = "";
            if ($current_tour > 1)
              $where_played = "AND PLAYED > 0" ;

	    // get random player from similar range.
            $sql= "SELECT USER_ID, POSITION_ID1, CURRENT_VALUE_MONEY 
			FROM manager_market 
			WHERE season_id=".$this->mseason_id."
			AND PLAYER_STATE = 0
			".$where_played."
			AND PUBLISH='Y'
			AND CURRENT_VALUE_MONEY >= ".$selling_price."
			AND USER_ID NOT IN 
			(SELECT PLAYER_ID FROM rvs_manager_teams
				WHERE LEAGUE_ID=".$this->league_id."
					AND SELLING_DATE IS NULL)
                    ORDER BY CURRENT_VALUE_MONEY ASC
		    LIMIT 5";
            $db->query($sql); 
            $c = 0;
            while ($row = $db->nextRow()) {
              $market[$c] = $row;
              $c++;
            }

            $sql= "SELECT USER_ID, POSITION_ID1, CURRENT_VALUE_MONEY FROM manager_market 
			WHERE season_id=".$this->mseason_id."
			AND PLAYER_STATE = 0
			".$where_played."
			AND PUBLISH='Y'
			AND CURRENT_VALUE_MONEY < ".$selling_price."
			AND USER_ID NOT IN 
			(SELECT PLAYER_ID FROM rvs_manager_teams
				WHERE LEAGUE_ID=".$this->league_id."
					AND SELLING_DATE IS NULL)
                       ORDER BY CURRENT_VALUE_MONEY DESC
		LIMIT 5";
            $db->query($sql); 
            while ($row = $db->nextRow()) {
              $market[$c] = $row;
              $c++;
            }

            if (isset($market) && count($market) > 0) {
              $selected = rand(0, count($market)-1);
              if (isset($market[$selected]['USER_ID']) && $market[$selected]['USER_ID'] > 0) {
                unset($sdata);
                $sdata['USER_ID'] = $auth->getUserId();
                $sdata['LEAGUE_ID'] = $this->league_id;
                $sdata['PLAYER_ID'] = $market[$selected]['USER_ID'];
                $sdata['BUYING_PRICE'] = $market[$selected]['CURRENT_VALUE_MONEY'];
                $sdata['SELLING_PRICE'] = $market[$selected]['CURRENT_VALUE_MONEY'];
                $sdata['BUYING_DATE'] = "NOW()";
                $db->insert("rvs_manager_teams", $sdata);
                $manager_user_log = new RvsManagerUserLog();
	        $manager_log = new RvsManagerLog();
                
                unset($sdata);
       	        $sdata['SELLING_DATE']="NOW()";
                $sdata['SELLING_PRICE']=$selling_price; //$row['SELLING_PRICE'];
                $db->update('rvs_manager_teams', $sdata, "USER_ID=".$auth->getUserId()." AND PLAYER_ID=".$_POST['player']." AND BUYING_DATE='".$buying_date."'");         
                
     	        $manager_user_log->logEvent ($auth->getUserId(), 3, $this->mseason_id, $this->league_id, $_POST['player'], '', $market[$selected]['USER_ID']);
     	        $manager_log->logEvent ($auth->getUserId(), 9, $this->mseason_id, $this->league_id, $_POST['player'], '', $market[$selected]['USER_ID']);
                
                // remove player from player exchange               
                $this->deletePlayerExchangeEntryForPlayer($_POST['player']);

                if ($player_state == 0 && $publish == 'Y') {
                  unset($sdata);
                  $sdata['USED_DISCARDS']="USED_DISCARDS+1";
                  $db->update('rvs_manager_users_tours', $sdata, "USER_ID=".$auth->getUserId()." AND LEAGUE_ID=".$this->league_id." AND TOUR_ID=".$current_tour);             
  	          $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['USED_DISCARDS']++;
                }
                
                return 1; 
               } else return -1;
            } else return -1;
          }
       }
       else return -1;
     }
   }
   return 0;
  }

  function requestPlayerTransfer() {
    global $db;
    global $_POST;
    global $manager;
    global $auth;

    if (isset($_POST['player'])) {     
      $sql = "SELECT *
            FROM manager_market MM 
            WHERE MM.SEASON_ID=".$this->mseason_id." 
                  AND USER_ID =".$_POST['player'];
      $db->query($sql);
      $row = $db->nextRow();
      $price = $row['CURRENT_VALUE_MONEY'];
      $selling_price = $row['CURRENT_VALUE_MONEY'];

      $sql = "SELECT ENTRY_ID, USER_ID
              FROM rvs_manager_players_exchange 
              WHERE 
                USER_ID=".$auth->getUserId()."
                AND STATUS = 0
                AND PLAYER_ID=".$_POST['player']."
		AND LEAGUE_ID=".$this->league_id;
          $db->query($sql);     
      if ($row == $db->nextRow()) {
      } else {
        unset($sdata);
        $sdata['USER_ID'] = $auth->getUserId();
        $sdata['LEAGUE_ID'] = $this->league_id;
        $sdata['PLAYER_ID'] = $_POST['player'];
        $sdata['TREQUEST_DATE'] = "NOW()";
//$db->showquery=true;
        $db->insert("rvs_manager_players_exchange", $sdata);        
      }
    }
  }

  function suggestPlayerTransfer() {
    global $db;
    global $_POST;
    global $manager;
    global $auth;

//$db->showquery=true;
    if (isset($_POST['user_id']) 
         && isset($_POST["mine_".$_POST['user_id']])
         && isset($_POST["not_mine_".$_POST['user_id']])
         && count($_POST["not_mine_".$_POST['user_id']]) == count($_POST["mine_".$_POST['user_id']])) {     
      // get mine player ids
      $pre = "";
      $mine_sum = 0;
      $mine_players = "";
      foreach($_POST["mine_".$_POST['user_id']] as $player) {
        $mp = explode("_", $player);
        $mine_players .= $pre.$mp[0];
        $mine_sum += $mp[1];
        $pre = ",";
      }
      $pre = "";
      $not_mine_sum = 0;
      $not_mine_players = "";
      foreach($_POST["not_mine_".$_POST['user_id']] as $player) {
        $mp = explode("_", $player);
        $not_mine_players .= $pre.$mp[0];
        $not_mine_sum += $mp[1];
        $pre = ",";
      }

      // validate ids
      $sql = "SELECT COUNT(a.PLAYER_ID) PLAYERS
             FROM (
                   SELECT MM.PLAYER_ID
                   FROM rvs_manager_teams MM
                    WHERE  MM.LEAGUE_ID=".$this->league_id." 
                    AND MM.USER_ID = ".$auth->getUserId()."
                    AND MM.SELLING_DATE IS NULL
                    AND MM.PLAYER_ID IN (".$mine_players.")
                  UNION
                   SELECT MM1.PLAYER_ID
                   FROM rvs_manager_teams MM1
                    WHERE  MM1.LEAGUE_ID=".$this->league_id." 
                    AND MM1.USER_ID = ".$_POST['user_id']."
                    AND MM1.SELLING_DATE IS NULL
                    AND MM1.PLAYER_ID IN (".$not_mine_players.")) a";
//echo $sql;
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $validation = $row['PLAYERS'];
      }

      if ($validation == count($_POST["not_mine_".$_POST['user_id']]) + count($_POST["mine_".$_POST['user_id']])) {
      // check if such transfer already exists
        $fee = round(($not_mine_sum-$mine_sum)/1000, 0);
        $sql = "SELECT COUNT(MM.LEAGUE_ID) as ENTRIES
              FROM rvs_manager_teams MM, rvs_manager_teams MM1, rvs_manager_players_exchange RMPL, 
		  rvs_manager_players_exchange_contract RMPEC, rvs_manager_players_exchange_contract RMPEC1 
              WHERE MM.LEAGUE_ID=".$this->league_id." 
                    AND MM.USER_ID = ".$auth->getUserId()."
                    AND MM.SELLING_DATE IS NULL
                    AND MM.PLAYER_ID IN (".$mine_players.")
		    AND MM1.LEAGUE_ID=".$this->league_id." 
                    AND MM1.USER_ID = ".$_POST['user_id']."
                    AND MM1.SELLING_DATE IS NULL
                    AND MM1.PLAYER_ID IN (".$not_mine_players.")
		    AND RMPL.LEAGUE_ID=".$this->league_id."
                    AND RMPL.USER_ID in (".$auth->getUserId().",". $_POST['user_id'].")
                    AND RMPL.USER_ID2 in (".$auth->getUserId().",". $_POST['user_id'].")
		    AND RMPL.ENTRY_ID=RMPEC.ENTRY_ID
		    AND MM.PLAYER_ID = RMPEC.PLAYER_ID
		    AND RMPEC.LEAGUE_ID=".$this->league_id."
	            AND RMPEC.PLAYER_ID in (".$mine_players.")
		    AND RMPEC.USER_ID =".$auth->getUserId()."
		    AND RMPEC.STATUS IN (0)
		    AND RMPL.ENTRY_ID=RMPEC1.ENTRY_ID
		    AND MM1.PLAYER_ID = RMPEC1.PLAYER_ID
		    AND RMPEC1.LEAGUE_ID=".$this->league_id."
	            AND RMPEC1.PLAYER_ID in (".$not_mine_players.")
		    AND RMPEC1.USER_ID =".$_POST['user_id']."
		    AND RMPEC1.STATUS IN (0)
		    AND RMPL.STATUS=0
    		    AND RMPEC.ENTRY_ID=RMPEC1.ENTRY_ID";
        //echo $sql;
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $entries = $row['ENTRIES'];
	  if ($entries != $validation) {
            unset($sdata);
            $sdata['USER_ID'] = $auth->getUserId();
            $sdata['USER_ID2'] = $_POST['user_id'];
            $sdata['LEAGUE_ID'] = $this->league_id;
            $sdata['FEE'] = $fee;
            $sdata['TREQUEST_DATE'] = "NOW()";
            $db->insert("rvs_manager_players_exchange", $sdata);        
            $entry_id = $db->id();
 
            foreach($_POST["mine_".$_POST['user_id']] as $player) {
              $mp = explode("_", $player);
 
              unset($sdata);
              $sdata['USER_ID'] = $auth->getUserId();
              $sdata['LEAGUE_ID'] = $this->league_id;
              $sdata['PLAYER_ID'] = $mp[0];
              $sdata['ENTRY_ID'] = $entry_id;
              $sdata['STATUS'] = 0;
              $db->insert("rvs_manager_players_exchange_contract ", $sdata);        
            }
            foreach($_POST["not_mine_".$_POST['user_id']] as $player) {
              $mp = explode("_", $player);
 
              unset($sdata);
              $sdata['USER_ID'] = $_POST['user_id'];
              $sdata['LEAGUE_ID'] = $this->league_id;
              $sdata['PLAYER_ID'] = $mp[0];
              $sdata['ENTRY_ID'] = $entry_id;
              $sdata['STATUS'] = 0;
              $db->insert("rvs_manager_players_exchange_contract ", $sdata);        
            }
 
            unset($sdata);
            $sdata['USER_ID'] = $_POST['user_id'];
            $sdata['LEAGUE_ID'] = $this->league_id;
            $sdata['ENTRY_ID'] = $entry_id;
            $sdata['CREATED_DATE'] = "NOW()";
            $sdata['TYPE'] = 1;
            $db->replace("rvs_manager_players_exchange_notification", $sdata);        
          }
        }
      }
    }
  }

  function retreatPlayerTransfer() {
    global $db;
    global $_POST;
    global $manager;
    global $auth;

//$db->showquery=true;
    if (isset($_POST['entry_id'])) {     
      $sql = "SELECT *
            FROM rvs_manager_players_exchange RMPL
            WHERE RMPL.ENTRY_ID=".$_POST['entry_id']." 
		  AND RMPL.USER_ID2=".$_POST['user_id']."
                  AND RMPL.USER_ID = ".$auth->getUserId()."
		  AND RMPL.STATUS=0";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $db->delete("rvs_manager_players_exchange", "ENTRY_ID=".$_POST['entry_id']);        
        $db->delete("rvs_manager_players_exchange_contract", "ENTRY_ID=".$_POST['entry_id']);        
        $db->delete("rvs_manager_players_exchange_notification", "ENTRY_ID=".$_POST['entry_id']);        
      }
    }
  }

  function returnPlayerTransfer() {
    global $db;
    global $_POST;
    global $manager;
    global $auth;

//$db->showquery=true;
    if (isset($_POST['host'])) {     
      $sql = "SELECT MM.SELLING_PRICE, RMPL.ENTRY_ID
            FROM rvs_manager_teams MM, rvs_manager_players_exchange RMPL
            WHERE MM.LEAGUE_ID=".$this->league_id." 
                  AND MM.USER_ID =".$auth->getUserId()."
                  AND MM.SELLING_DATE IS NULL
                  AND MM.PLAYER_ID =".$_POST['host']."
		  AND RMPL.PLAYER_ID=".$_POST['host']."
		  AND RMPL.LEAGUE_ID=".$this->league_id."
                  AND RMPL.USER_ID =".$auth->getUserId()."
		  AND RMPL.STATUS=0";
//echo $sql;
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $entry_id = $row['ENTRY_ID'];
        $db->delete("rvs_manager_players_exchange", "LEAGUE_ID=".$this->league_id." AND PLAYER_ID=".$_POST['host']." AND USER_ID=".$auth->getUserId()." AND ENTRY_ID=".$entry_id);        
      }
    }
  }

  function rejectPlayerTransfer() {
    global $db;
    global $_POST;
    global $manager;
    global $auth;

//$db->showquery=true;
    if (isset($_POST['entry_id'])) {     
      $sql = "SELECT *
            FROM rvs_manager_players_exchange RMPL
            WHERE RMPL.ENTRY_ID=".$_POST['entry_id']." 
		  AND RMPL.USER_ID=".$_POST['user_id']."
                  AND RMPL.USER_ID2 = ".$auth->getUserId()."
		  AND RMPL.STATUS=0";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $db->delete("rvs_manager_players_exchange", "ENTRY_ID=".$_POST['entry_id']);        
        $db->delete("rvs_manager_players_exchange_contract", "ENTRY_ID=".$_POST['entry_id']);        
        $db->delete("rvs_manager_players_exchange_notification", "ENTRY_ID=".$_POST['entry_id']);        
      }
    }
  }

  function rejectPlayerTransferModerate() {
    global $db;
    global $_POST;
    global $manager;
    global $auth;

    if (isset($_POST['entry_id']) && isset($_POST['user1']) && isset($_POST['user2'])) {     
      $sql = "SELECT *
            FROM rvs_manager_players_exchange RMPL
            WHERE RMPL.ENTRY_ID=".$_POST['entry_id']." 
		  AND RMPL.USER_ID=".$_POST['user1']."
                  AND RMPL.USER_ID2 = ".$_POST['user2']."
		  AND RMPL.STATUS=2";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $sql="UPDATE rvs_manager_teams 
                  SET MODERATED=0
                          WHERE LEAGUE_ID=".$this->league_id." 
			  AND PLAYER_ID in (SELECT PLAYER_ID from rvs_manager_players_exchange_contract 
							WHERE ENTRY_ID=".$_POST['entry_id'].")
				  AND USER_ID in (".$_POST['user1'].",".$_POST['user2'].")
				  AND SELLING_DATE IS NULL";
        $db->query($sql); 

        $sql = "SELECT MM.LAST_NAME, MM.FIRST_NAME, MM.CURRENT_VALUE_MONEY AS SELLING_PRICE, 
			RMPL.ENTRY_ID, RMPEC.PLAYER_ID
                  FROM manager_market MM, rvs_manager_teams RMT, 
			rvs_manager_players_exchange RMPL, rvs_manager_players_exchange_contract RMPEC
              WHERE MM.SEASON_ID= ".$manager->mseason_id."
		    AND MM.USER_ID= RMT.PLAYER_ID
                    AND RMT.LEAGUE_ID=".$this->league_id." 
                AND RMT.USER_ID =".$_POST['user1']."
                AND RMT.SELLING_DATE IS NULL
                AND RMPL.ENTRY_ID =".$_POST['entry_id']."
		  AND RMPL.STATUS in (0, 2)
		  AND RMPL.ENTRY_ID=RMPEC.ENTRY_ID
		  AND RMPEC.LEAGUE_ID=".$this->league_id."
		  AND RMPEC.USER_ID =".$_POST['user1']."
		  AND RMPEC.PLAYER_ID = RMT.PLAYER_ID
		  AND RMPEC.STATUS IN (0, 3)";
        $db->query($sql);
        $mine_players = array();
        $pre = "";
        $players1 = "";
        while ($row = $db->nextRow()) {
          $players1 .= $pre.$row['LAST_NAME']." ".$row['FIRST_NAME'];
          $pre = ",";
        }
  
        $sql = "SELECT MM.LAST_NAME, MM.FIRST_NAME, MM.CURRENT_VALUE_MONEY AS SELLING_PRICE, 
			RMPL.ENTRY_ID, RMPEC.PLAYER_ID
                  FROM manager_market MM, rvs_manager_teams RMT, 
			rvs_manager_players_exchange RMPL, rvs_manager_players_exchange_contract RMPEC
              WHERE MM.SEASON_ID= ".$manager->mseason_id."
		    AND MM.USER_ID= RMT.PLAYER_ID
            	  AND RMT.LEAGUE_ID=".$this->league_id." 
                  AND RMT.USER_ID =".$_POST['user2']."
                  AND RMT.SELLING_DATE IS NULL
                  AND RMPL.ENTRY_ID =".$_POST['entry_id']."
		  AND RMPL.STATUS in (0, 2)
		  AND RMPL.ENTRY_ID=RMPEC.ENTRY_ID
		  AND RMPEC.LEAGUE_ID=".$this->league_id."
		  AND RMPEC.USER_ID =".$_POST['user2']."
		  AND RMPEC.PLAYER_ID = RMT.PLAYER_ID
		  AND RMPEC.STATUS IN (0, 3)";
        $db->query($sql);
        $players2 = "";
        $pre = "";
        while ($row = $db->nextRow()) {
          $players2 .= $pre.$row['LAST_NAME']." ".$row['FIRST_NAME'];
          $pre = ",";
        }

        $db->delete("rvs_manager_players_exchange", "ENTRY_ID=".$_POST['entry_id']);        
        $db->delete("rvs_manager_players_exchange_contract", "ENTRY_ID=".$_POST['entry_id']);        
        $db->delete("rvs_manager_players_exchange_notification", "ENTRY_ID=".$_POST['entry_id']);        

        $manager_user_log = new RvsManagerUserLog();            
        $manager_user_log->logEvent($_POST['user1'], 7, $this->mseason_id, $this->league_id, "", $_POST['user2'], "", $players1, $players2);
        $manager_user_log->logEvent($_POST['user2'], 7, $this->mseason_id, $this->league_id, "", $_POST['user2'], "", $players2, $players1);
        $manager_log = new RvsManagerLog();            
        $manager_log->logEvent($_POST['user1'], 11, $this->mseason_id, $this->league_id, "", $_POST['user2'], "", $players1, $players2);
      } 
    }
  }

  function acceptPlayerTransferForModeration() {
    global $db;
    global $_POST;
    global $manager;
    global $auth;
    global $langs;
    global $pleague;

//$db->showquery=true;
    if (isset($_POST['entry_id'])) {     
      $sql = "SELECT RMPL.*, U.CREDIT, U.USER_NAME as USER_NAME2, U2.CREDIT as CREDIT2, U2.USER_NAME as USER_NAME3, 
		RML.USER_ID as OWNER, RML.TITLE, U3.USER_NAME, MSD.SEASON_TITLE, U3.LAST_LANG
            FROM languages L, manager_seasons_details MSD, rvs_manager_leagues RML
                 left join users U3 on RML.USER_ID = U3.USER_ID
		, rvs_manager_players_exchange RMPL
                 left join users U on RMPL.USER_ID = U.USER_ID
                 left join users U2 on RMPL.USER_ID2 = U2.USER_ID
            WHERE RML.LEAGUE_ID=RMPL.LEAGUE_ID
		  AND L.SHORT_CODE=U.LAST_LANG
		  AND MSD.SEASON_ID=RML.SEASON_ID AND MSD.LANG_ID=L.ID                  
		  AND RMPL.ENTRY_ID=".$_POST['entry_id']." 
		  AND RMPL.USER_ID=".$_POST['user_id']."
                  AND RMPL.USER_ID2 = ".$auth->getUserId()."
		  AND RMPL.STATUS=0";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $contract = $row;        
        if ((abs($contract['FEE']) <= $contract['CREDIT'] && $contract['FEE'] > 0)
            || (abs($contract['FEE']) <=  $contract['CREDIT2'] && $contract['FEE'] < 0)
            || $contract['FEE'] == 0) {

          $sql = "SELECT MM.LAST_NAME, MM.FIRST_NAME, MM.CURRENT_VALUE_MONEY AS SELLING_PRICE, 
			RMPL.ENTRY_ID, RMPEC.PLAYER_ID
                  FROM manager_market MM, rvs_manager_teams RMT, 
			rvs_manager_players_exchange RMPL, rvs_manager_players_exchange_contract RMPEC
              WHERE MM.SEASON_ID= ".$manager->mseason_id."
		    AND MM.USER_ID= RMT.PLAYER_ID
                    AND RMT.LEAGUE_ID=".$this->league_id." 
                  AND RMT.USER_ID =RMPL.USER_ID
                  AND RMT.SELLING_DATE IS NULL
                  AND RMPL.ENTRY_ID =".$_POST['entry_id']."
		  AND RMPL.STATUS in (0, 2)
		  AND RMPL.ENTRY_ID=RMPEC.ENTRY_ID
		  AND RMPEC.LEAGUE_ID=".$this->league_id."
		  AND RMPEC.USER_ID =RMPL.USER_ID
		  AND RMPEC.PLAYER_ID = RMT.PLAYER_ID
		  AND RMPEC.STATUS IN (0, 3)";
          $db->query($sql);
          $mine_players = array();
          $pre = "";
          $players = "";
          while ($row = $db->nextRow()) {
            $mine_players[] = $row; 
          }
  
          $sql = "SELECT MM.LAST_NAME, MM.FIRST_NAME, MM.CURRENT_VALUE_MONEY AS SELLING_PRICE, 
			RMPL.ENTRY_ID, RMPEC.PLAYER_ID
              FROM manager_market MM, rvs_manager_teams RMT, 
		rvs_manager_players_exchange RMPL, 
		rvs_manager_players_exchange_contract RMPEC
              WHERE MM.SEASON_ID= ".$manager->mseason_id."
		    AND MM.USER_ID= RMT.PLAYER_ID
                    AND RMT.LEAGUE_ID=".$this->league_id." 
                    AND RMT.USER_ID =RMPL.USER_ID2
                    AND RMT.SELLING_DATE IS NULL
                    AND RMPL.ENTRY_ID =".$_POST['entry_id']."
		  AND RMPL.STATUS in (0, 2)
		  AND RMPL.ENTRY_ID=RMPEC.ENTRY_ID
		  AND RMPEC.LEAGUE_ID=".$this->league_id."
		  AND RMPEC.USER_ID =RMPL.USER_ID2
		  AND RMPEC.PLAYER_ID = RMT.PLAYER_ID
		  AND RMPEC.STATUS IN (0, 3)";
          $db->query($sql);
          $not_mine_players = array();
          while ($row = $db->nextRow()) {
            $not_mine_players[] = $row;
          }

          // mark players moderated
          foreach ($mine_players as $player) { 
            unset($sdata);
            $sdata['MODERATED'] = 1;
            $db->update("rvs_manager_teams", $sdata, "LEAGUE_ID=".$this->league_id." AND PLAYER_ID=".$player['PLAYER_ID']." AND USER_ID=".$_POST['user_id']." AND SELLING_DATE IS NULL");   
          }
  
          foreach ($not_mine_players as $player) { 
            unset($sdata);
            $sdata['MODERATED'] = 1;
            $db->update("rvs_manager_teams", $sdata, "LEAGUE_ID=".$this->league_id." AND PLAYER_ID=".$player['PLAYER_ID']." AND USER_ID=".$auth->getUserId()." AND SELLING_DATE IS NULL");   
          }

          unset($sdata);
          $sdata['STATUS'] = 2;
          $sdata['TRANSFER_DATE'] = "NOW()";
          $db->update("rvs_manager_players_exchange", $sdata, "ENTRY_ID=".$_POST['entry_id']);   
          // send email to moderator

          $this->cleanPlayerExchange($_POST['entry_id'], $auth->getUserId(), $_POST['user_id']);

  	  unset($sdata);
          $sdata['STATUS'] = 3;
          $db->update("rvs_manager_players_exchange_contract", $sdata, "ENTRY_ID=".$_POST['entry_id']);   
        
	  $email = new Email($langs, $_SESSION['_lang']);

	  $edata = $contract;
 	  $descr = $email->getEmailFromTemplate ('email_rvs_moderate_transfer', $edata) ;
	  $subject = $langs['LANG_EMAIL_RVS_MODERATE_TRANSFER_SUBJECT'];
          $pm = new PM();
	  $pm->createSystemPM($contract['OWNER'], $subject, $descr);
        }
      }
    }   
  }

  function acceptPlayerTransfer() {
    global $db;
    global $_POST;
    global $manager;
    global $auth;

//$db->showquery=true;
    if (isset($_POST['entry_id'])) {     
      $sql = "SELECT RMPL.*, U.CREDIT, U2.CREDIT as CREDIT2
            FROM rvs_manager_players_exchange RMPL
                 left join users U on RMPL.USER_ID = U.USER_ID
                 left join users U2 on RMPL.USER_ID2 = U2.USER_ID
            WHERE RMPL.ENTRY_ID=".$_POST['entry_id']." 
		  AND RMPL.USER_ID=".$_POST['user_id']."
                  AND RMPL.USER_ID2 = ".$auth->getUserId()."
		  AND RMPL.STATUS=0";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $contract = $row;        
        if ((abs($contract['FEE']) <= $contract['CREDIT'] && $contract['FEE'] > 0)
            || (abs($contract['FEE']) <=  $contract['CREDIT2'] && $contract['FEE'] < 0)
            || $contract['FEE'] == 0) {

        $sql = "SELECT MM.LAST_NAME, MM.FIRST_NAME, MM.CURRENT_VALUE_MONEY AS SELLING_PRICE, 
			RMPL.ENTRY_ID, RMPEC.PLAYER_ID
                  FROM manager_market MM, rvs_manager_teams RMT, 
			rvs_manager_players_exchange RMPL, rvs_manager_players_exchange_contract RMPEC
              WHERE MM.SEASON_ID= ".$manager->mseason_id."
		    AND MM.USER_ID= RMT.PLAYER_ID
		  AND RMT.LEAGUE_ID=".$this->league_id."
                  AND RMT.USER_ID =".$auth->getUserId()."
                  AND RMT.SELLING_DATE IS NULL
                  AND RMPL.ENTRY_ID =".$_POST['entry_id']."
		  AND RMPL.STATUS in (0, 2)
		  AND RMPL.ENTRY_ID=RMPEC.ENTRY_ID
		  AND RMPEC.LEAGUE_ID=".$this->league_id."
		  AND RMPEC.USER_ID =".$auth->getUserId()."
		  AND RMPEC.PLAYER_ID = RMT.PLAYER_ID
		  AND RMPEC.STATUS IN (0)";
          $db->query($sql);
          $mine_players = array();
          $pre = "";
          $pre2 = "";
          $players = "";
	  $players1 = "";
          while ($row = $db->nextRow()) {
            $mine_players[] = $row;
            $players .= $pre.$row['PLAYER_ID'];
            $players1 .= $pre2.$row['LAST_NAME']." ".$row['FIRST_NAME'];
            $pre2 = ",";
            $pre = ",";
          }
  
        $sql = "SELECT MM.LAST_NAME, MM.FIRST_NAME, MM.CURRENT_VALUE_MONEY AS SELLING_PRICE, 
			RMPL.ENTRY_ID, RMPEC.PLAYER_ID
                  FROM manager_market MM, rvs_manager_teams RMT, 
			rvs_manager_players_exchange RMPL, rvs_manager_players_exchange_contract RMPEC
              WHERE MM.SEASON_ID= ".$manager->mseason_id."
		    AND MM.USER_ID= RMT.PLAYER_ID
  		  AND RMT.LEAGUE_ID=".$this->league_id."
                    AND RMT.USER_ID =".$_POST['user_id']."
                    AND RMT.SELLING_DATE IS NULL
                    AND RMPL.ENTRY_ID =".$_POST['entry_id']."
		  AND RMPL.STATUS in (0, 2)
		  AND RMPL.ENTRY_ID=RMPEC.ENTRY_ID
		  AND RMPEC.LEAGUE_ID=".$this->league_id."
		  AND RMPEC.USER_ID =".$_POST['user_id']."
		  AND RMPEC.PLAYER_ID = RMT.PLAYER_ID
		  AND RMPEC.STATUS IN (0)";
          $db->query($sql);
          $not_mine_players = array();
          $players2 = "";
          $pre2 = "";
          while ($row = $db->nextRow()) {
            $not_mine_players[] = $row;
            $players .= $pre.$row['PLAYER_ID'];
            $players2 .= $pre2.$row['LAST_NAME']." ".$row['FIRST_NAME'];
            $pre2 = ",";
          }
  
          unset($sdata);
          $sdata['STATUS'] = 1;
          $sdata['TRANSFER_DATE'] = "NOW()";
          $db->update("rvs_manager_players_exchange", $sdata, "ENTRY_ID=".$_POST['entry_id']);   
          unset($sdata);
  
	  $sdata['LEAGUE_ID'] = $this->league_id;
	  $sdata['USER_ID'] = $contract['USER_ID'];
	  $sdata['ENTRY_ID'] = $_POST['entry_id'];
	  $sdata['CREATED_DATE'] = "NOW()";
          $sdata['TYPE'] = 2;
          $db->insert("rvs_manager_players_exchange_notification", $sdata);
  
          $this->cleanPlayerExchange($_POST['entry_id'], $auth->getUserId(), $_POST['user_id']);
//        exit;
          unset($sdata);
          $sdata['STATUS'] = 1;
          $db->update("rvs_manager_players_exchange_contract", $sdata, "ENTRY_ID=".$_POST['entry_id']);   
  
          // actually move players
          foreach ($mine_players as $player) { 
            unset($sdata);
            $sdata['SELLING_DATE'] = "NOW()";
            $db->update("rvs_manager_teams", $sdata, "LEAGUE_ID=".$this->league_id." AND PLAYER_ID=".$player['PLAYER_ID']." AND USER_ID=".$auth->getUserId()." AND SELLING_DATE IS NULL");   
          }
  
          foreach ($not_mine_players as $player) { 
            unset($sdata);
            $sdata['SELLING_DATE'] = "NOW()";
            $db->update("rvs_manager_teams", $sdata, "LEAGUE_ID=".$this->league_id." AND PLAYER_ID=".$player['PLAYER_ID']." AND USER_ID=".$_POST['user_id']." AND SELLING_DATE IS NULL");   
          }
  
          foreach ($not_mine_players as $player) { 
            unset($sdata);
            $sdata['USER_ID'] = $auth->getUserId();
            $sdata['LEAGUE_ID'] = $this->league_id;
            $sdata['PLAYER_ID'] = $player['PLAYER_ID'];
            $sdata['BUYING_PRICE'] = $player['SELLING_PRICE'];
            $sdata['SELLING_PRICE'] = $player['SELLING_PRICE'];
            $sdata['BUYING_DATE'] = "NOW()";
            $db->insert("rvs_manager_teams", $sdata);
          }
  
          foreach ($mine_players as $player) { 
            unset($sdata);
            $sdata['USER_ID'] = $_POST['user_id'];
            $sdata['LEAGUE_ID'] = $this->league_id;
            $sdata['PLAYER_ID'] = $player['PLAYER_ID'];
            $sdata['BUYING_PRICE'] = $player['SELLING_PRICE'];
            $sdata['SELLING_PRICE'] = $player['SELLING_PRICE'];
            $sdata['BUYING_DATE'] = "NOW()";
            $db->insert("rvs_manager_teams", $sdata);
          }
  
          // log it  
          $manager_user_log = new RvsManagerUserLog();            
          $manager_user_log->logEvent($auth->getUserId(), 6, $this->mseason_id, $this->league_id, "", $contract['USER_ID'], "", $players1, $players2);
          $manager_user_log->logEvent($contract['USER_ID'], 6, $this->mseason_id, $this->league_id, "", $auth->getUserId(), "", $players2, $players1);
          $manager_log = new RvsManagerLog();            
          $manager_log->logEvent($auth->getUserId(), 8, $this->mseason_id, $this->league_id, "", $contract['USER_ID'], "", $players1, $players2);
  
          if ($contract['FEE'] != 0) {
      	    if ($contract['FEE'] > 0) {
    	      $user_id = $contract['USER_ID'];
    	      $user_id2 = $contract['USER_ID2'];
	    } else if ($contract['FEE'] < 0) {
    	      $user_id = $contract['USER_ID2'];
    	      $user_id2 = $contract['USER_ID'];
	    }
    	    $credits = new Credits();
            $credit_log = new CreditsLog();
	    $credits->updateRvsLeagueCredits($this->league_id, abs($contract['FEE'])/2);
   	    $credits->updateCredits($user_id, -1*abs($contract['FEE'])); 
	    $credit_log->logEvent ($user_id, 22, abs($contract['FEE']));
   	    $credits->updateCredits($user_id2, abs($contract['FEE'])/2); 
	    $credit_log->logEvent ($user_id2, 16, abs($contract['FEE'])/2, $user_id);
  	  }        
        } 
      }
    }
  }


  function acceptPlayerTransferModerate() {
    global $db;
    global $_POST;
    global $manager;
    global $auth;

    if (isset($_POST['entry_id']) && isset($_POST['user1']) && isset($_POST['user2'])) {     
      $sql = "SELECT RMPL.*, U.CREDIT, U2.CREDIT as CREDIT2
            FROM rvs_manager_players_exchange RMPL
                 left join users U on RMPL.USER_ID = U.USER_ID
                 left join users U2 on RMPL.USER_ID2 = U2.USER_ID
            WHERE RMPL.ENTRY_ID=".$_POST['entry_id']." 
		  AND RMPL.USER_ID=".$_POST['user1']."
                  AND RMPL.USER_ID2 = ".$_POST['user2']."
		  AND RMPL.STATUS=2";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $contract = $row;        

        if ((abs($contract['FEE']) <= $contract['CREDIT'] && $contract['FEE'] > 0)
            || (abs($contract['FEE']) <=  $contract['CREDIT2'] && $contract['FEE'] < 0)
            || $contract['FEE'] == 0) {
        $sql = "SELECT MM.LAST_NAME, MM.FIRST_NAME, MM.CURRENT_VALUE_MONEY AS SELLING_PRICE, 
			RMPL.ENTRY_ID, RMPEC.PLAYER_ID
                  FROM manager_market MM, rvs_manager_teams RMT, 
			rvs_manager_players_exchange RMPL, rvs_manager_players_exchange_contract RMPEC
              WHERE MM.SEASON_ID= ".$manager->mseason_id."
		    AND MM.USER_ID= RMT.PLAYER_ID
		  AND RMT.LEAGUE_ID=".$this->league_id."
                  AND RMT.USER_ID =".$_POST['user1']."
                  AND RMT.SELLING_DATE IS NULL
                  AND RMPL.ENTRY_ID =".$_POST['entry_id']."
		  AND RMPL.STATUS in (2)
		  AND RMPL.ENTRY_ID=RMPEC.ENTRY_ID
		  AND RMPEC.LEAGUE_ID=".$this->league_id."
		  AND RMPEC.USER_ID =".$_POST['user1']."
		  AND RMPEC.PLAYER_ID = RMT.PLAYER_ID
		  AND RMPEC.STATUS IN (3)";
          $db->query($sql);
          $mine_players = array();
          $pre = "";
          $pre2 = "";
          $players1 = "";
          $players = "";
          while ($row = $db->nextRow()) {
            $mine_players[] = $row;
            $players .= $pre.$row['PLAYER_ID'];
            $players1 .= $pre.$row['LAST_NAME']." ".$row['FIRST_NAME'];
            $pre = ",";
            $pre2 = ",";
          }
  
        $sql = "SELECT MM.LAST_NAME, MM.FIRST_NAME, MM.CURRENT_VALUE_MONEY AS SELLING_PRICE, 
			RMPL.ENTRY_ID, RMPEC.PLAYER_ID
                  FROM manager_market MM, rvs_manager_teams RMT, 
			rvs_manager_players_exchange RMPL, rvs_manager_players_exchange_contract RMPEC
              WHERE MM.SEASON_ID= ".$manager->mseason_id."
		    AND MM.USER_ID= RMT.PLAYER_ID
		  AND RMT.LEAGUE_ID=".$this->league_id."
                    AND RMT.USER_ID =".$_POST['user2']."
                    AND RMT.SELLING_DATE IS NULL
                    AND RMPL.ENTRY_ID =".$_POST['entry_id']."
		  AND RMPL.STATUS in (0, 2)
		  AND RMPL.ENTRY_ID=RMPEC.ENTRY_ID
		  AND RMPEC.LEAGUE_ID=".$this->league_id."
		  AND RMPEC.USER_ID =".$_POST['user2']."
		  AND RMPEC.PLAYER_ID = RMT.PLAYER_ID
		  AND RMPEC.STATUS IN (3)";
          $db->query($sql);
          $not_mine_players = array();
          $pre = "";
          $pre2 = "";
          $players2 = "";
          $players = "";
          while ($row = $db->nextRow()) {
            $not_mine_players[] = $row;
            $players .= $pre.$row['PLAYER_ID'];
            $players2 .= $pre.$row['LAST_NAME']." ".$row['FIRST_NAME'];
            $pre = ",";
            $pre2 = ",";
          }
          unset($sdata);

          $sdata['STATUS'] = 1;
          $sdata['TRANSFER_DATE'] = "NOW()";
          $db->update("rvs_manager_players_exchange", $sdata, "LEAGUE_ID=".$this->league_id." AND USER_ID=".$_POST['user1']." AND ENTRY_ID=".$_POST['entry_id']);   
          unset($sdata);
          $sdata['STATUS'] = 1;
          $db->update("rvs_manager_players_exchange_contract", $sdata, "LEAGUE_ID=".$this->league_id." AND ENTRY_ID=".$_POST['entry_id']);   
          unset($sdata);

	  $sdata['LEAGUE_ID'] = $this->league_id;
	  $sdata['USER_ID'] = $_POST['user2'];
	  $sdata['ENTRY_ID'] = $_POST['entry_id'];
	  $sdata['CREATED_DATE'] = "NOW()";
          $sdata['TYPE'] = 2;
          $db->insert("rvs_manager_players_exchange_notification", $sdata);

          $this->cleanPlayerExchange($_POST['entry_id'], $_POST['user1'], $_POST['user2']);

          // actually move players
          // actually move players
          foreach ($mine_players as $player) { 
            unset($sdata);
            $sdata['SELLING_DATE'] = "NOW()";
            $sdata['MODERATED'] = 0;
            $db->update("rvs_manager_teams", $sdata, "LEAGUE_ID=".$this->league_id." AND PLAYER_ID=".$player['PLAYER_ID']." AND USER_ID=".$_POST['user1']." AND SELLING_DATE IS NULL");   
          }
  
          foreach ($not_mine_players as $player) { 
            unset($sdata);
            $sdata['SELLING_DATE'] = "NOW()";
            $sdata['MODERATED'] = 0;
            $db->update("rvs_manager_teams", $sdata, "LEAGUE_ID=".$this->league_id." AND PLAYER_ID=".$player['PLAYER_ID']." AND USER_ID=".$_POST['user2']." AND SELLING_DATE IS NULL");   
          }
  
          foreach ($not_mine_players as $player) { 
            unset($sdata);
            $sdata['USER_ID'] = $_POST['user1'];
            $sdata['LEAGUE_ID'] = $this->league_id;
            $sdata['PLAYER_ID'] = $player['PLAYER_ID'];
            $sdata['BUYING_PRICE'] = $player['SELLING_PRICE'];
            $sdata['SELLING_PRICE'] = $player['SELLING_PRICE'];
            $sdata['BUYING_DATE'] = "NOW()";
            $db->insert("rvs_manager_teams", $sdata);
          }
  
          foreach ($mine_players as $player) { 
            unset($sdata);
            $sdata['USER_ID'] = $_POST['user2'];
            $sdata['LEAGUE_ID'] = $this->league_id;
            $sdata['PLAYER_ID'] = $player['PLAYER_ID'];
            $sdata['BUYING_PRICE'] = $player['SELLING_PRICE'];
            $sdata['SELLING_PRICE'] = $player['SELLING_PRICE'];
            $sdata['BUYING_DATE'] = "NOW()";
            $db->insert("rvs_manager_teams", $sdata);
          }

          // log it  
          $manager_user_log = new RvsManagerUserLog();            
          $manager_user_log->logEvent($_POST['user1'], 8, $this->mseason_id, $this->league_id, "", $_POST['user2'], "", $players1, $players2);
          $manager_user_log->logEvent($_POST['user2'], 8, $this->mseason_id, $this->league_id, "", $_POST['user1'], "", $players2, $players1);
          $manager_log = new RvsManagerLog();            
          $manager_log->logEvent($_POST['user1'], 12, $this->mseason_id, $this->league_id, "", $_POST['user2'], "", $players1, $players2);

          if ($contract['FEE'] != 0) {
      	    if ($contract['FEE'] > 0) {
    	      $user_id = $contract['USER_ID'];
    	      $user_id2 = $contract['USER_ID2'];
	    } else if ($contract['FEE'] < 0) {
    	      $user_id = $contract['USER_ID2'];
    	      $user_id2 = $contract['USER_ID'];
	    }
    	    $credits = new Credits();
            $credit_log = new CreditsLog();
	    $credits->updateRvsLeagueCredits($this->league_id, abs($contract['FEE'])/2);
   	    $credits->updateCredits($user_id, -1*abs($contract['FEE'])); 
	    $credit_log->logEvent ($user_id, 22, abs($contract['FEE']));
   	    $credits->updateCredits($user_id2, abs($contract['FEE'])/2); 
	    $credit_log->logEvent ($user_id2, 16, abs($contract['FEE'])/2, $user_id);
  	  }        
        }
      } 
    }
  }

  function getPlayerExchange() {
    global $db;
    global $manager;
    global $auth;
    global $position_types;
    global $smarty;
    global $pleague;
    
    $players['ME'] = $auth->getUserName();
    $players['ME_ID'] = $auth->getUserId();

    // get not mine players

    $sql = "SELECT U.USER_NAME, U.USER_ID, RMT.PLAYER_ID,
		MM.LAST_NAME, MM.FIRST_NAME, MM.POSITION_ID1, MM.POSITION_ID2, MM.CURRENT_VALUE_MONEY,
		IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME2
             FROM users U, rvs_manager_teams RMT
			LEFT JOIN manager_market MM ON MM.USER_ID=RMT.PLAYER_ID
						AND MM.SEASON_ID=".$manager->mseason_id."
			LEFT JOIN teams T ON MM.TEAM_ID=T.TEAM_ID 
			LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
             WHERE RMT.LEAGUE_ID=".$this->league_id."
	           AND RMT.USER_ID <> ".$auth->getUserId()."
		   and RMT.SELLING_DATE IS NULL 
		   and RMT.PROTECTED=0
		   and RMT.MODERATED=0
		   and U.USER_ID=RMT.USER_ID
		ORDER BY U.USER_NAME";
    $db->query($sql);
//echo $sql;
    while($row = $db->nextRow()) {
       $unset_sugg = false;
       $index = $row['USER_ID'];
       $player = $row;       
       if (!empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
         $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
       else $player['TYPE_NAME'] = isset($position_types[$manager->sport_id][$row['POSITION_ID1']]) ? $position_types[$manager->sport_id][$row['POSITION_ID1']] : '';
                 
       $player['PLAYER_SEASON_STATS']['SUBSEASONS'] = $manager->seasonlist;
       $players['NOT_MINE'][$index]['USER_ID'] = $row['USER_ID'];
       $players['NOT_MINE'][$index]['USER_NAME'] = $row['USER_NAME'];
       $players['NOT_MINE'][$index]['PLAYERS'][] = $player;
    }

    // get pex contracts
    $sql = "SELECT U.USER_NAME, U.USER_ID, U1.USER_NAME as USER_NAME2, U1.USER_ID as USER2_ID2, 
		U.CREDIT, U1.CREDIT as CREDIT2,
		MM1.LAST_NAME, MM1.FIRST_NAME, MM1.POSITION_ID1, 
		MM1.POSITION_ID2, MM1.CURRENT_VALUE_MONEY, 
		RMPE.*, RMPEC.USER_ID as OWNER, RMPEC.PLAYER_ID AS CPLAYER_ID, RMPEC.STATUS as CSTATUS, 
		IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2
	    FROM rvs_manager_players_exchange RMPE
			LEFT JOIN users U ON U.USER_ID=RMPE.USER_ID
		        LEFT JOIN users U1 ON U1.USER_ID=RMPE.USER_ID2,
		 rvs_manager_players_exchange_contract RMPEC
			LEFT JOIN manager_market MM1 ON MM1.USER_ID=RMPEC.PLAYER_ID
						AND MM1.SEASON_ID=".$manager->mseason_id."
			LEFT JOIN teams T2 ON MM1.TEAM_ID=T2.TEAM_ID 
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
	    WHERE RMPE.LEAGUE_ID=".$this->league_id."
		      AND (RMPE.USER_ID = ".$auth->getUserId()." or RMPE.USER_ID2 = ".$auth->getUserId().")
                      AND RMPE.STATUS in (0)
                      AND RMPEC.ENTRY_ID=RMPE.ENTRY_ID
		      AND RMPEC.LEAGUE_ID = RMPE.LEAGUE_ID
		      AND RMPEC.STATUS in (0)
 	    ORDER BY RMPE.TREQUEST_DATE"; 
//echo $sql;
    $db->query($sql);
    while($row = $db->nextRow()) {
      if ($row['USER_ID'] == $auth->getUserId()) {
        $other = $row['USER_ID2'];
        $row['CANCEL'] = 1;
	$row['OWNER_USER_NAME'] = $row['USER_NAME2'];;
      } else {
        $other = $row['USER_ID'];
	$row['OWNER_USER_NAME'] = $row['USER_NAME'];;
        $row['CAN_REJECT'] = 1;
        if ($manager->manager_trade_allow 
		&& (abs($row['FEE']) <= $auth->getCredits() || $row['FEE'] > 0) 
		&& (abs($row['FEE']) <= $row['CREDIT2']))
          $row['CAN_ACCEPT'] = 1;
        else if ($manager->manager_trade_allow 
		&& (abs($row['FEE']) <= $auth->getCredits() || $row['FEE'] > 0) 
		&& (abs($row['FEE']) > $row['CREDIT2']))
	  $row['CANT_BE_ACCEPTED'] = 1;
        else if (!$manager->manager_trade_allow)      
	  $row['CANT_ACCEPT_DAY'] = 1;
        else if (abs($row['FEE']) > $auth->getCredits())      
	  $row['CANT_ACCEPT_MONEY'] = 1;
      }

      if ($row['FEE'] > 0)
        $row['PAYER'] = $row['USER_NAME'];
      else if ($row['FEE'] < 0)
        $row['PAYER'] = $row['USER_NAME2'];

      if (!empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
        $row['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
      else $row['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];

      $players['TRANSFERS'][$other][$row['ENTRY_ID']]['ROW'] = $row;
      $players['TRANSFERS'][$other][$row['ENTRY_ID']][$row['OWNER']][] = $row;
    }

    // get pending transfers
    if ($pleague->league_info['MODERATE_TRANSFERS'] == 'Y') {
//echo $sql;
      $sql = "SELECT U.USER_NAME, U.USER_ID, U1.USER_NAME as USER_NAME2, U1.USER_ID as USER2_ID2, 
		U.CREDIT, U1.CREDIT as CREDIT2,
		MM1.LAST_NAME, MM1.FIRST_NAME, MM1.POSITION_ID1, 
		MM1.POSITION_ID2, MM1.CURRENT_VALUE_MONEY, 
		RMPE.*, RMPEC.USER_ID as OWNER, RMPEC.PLAYER_ID AS CPLAYER_ID, RMPEC.STATUS as CSTATUS, 
		IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2
	    FROM rvs_manager_players_exchange RMPE
			LEFT JOIN users U ON U.USER_ID=RMPE.USER_ID
		        LEFT JOIN users U1 ON U1.USER_ID=RMPE.USER_ID2,
		 rvs_manager_players_exchange_contract RMPEC
			LEFT JOIN manager_market MM1 ON MM1.USER_ID=RMPEC.PLAYER_ID
						AND MM1.SEASON_ID=".$manager->mseason_id."
			LEFT JOIN teams T2 ON MM1.TEAM_ID=T2.TEAM_ID 
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
	    WHERE RMPE.LEAGUE_ID=".$this->league_id."
		      AND (RMPE.USER_ID = ".$auth->getUserId()." or RMPE.USER_ID2 = ".$auth->getUserId().")
                      AND RMPE.STATUS in (2)
                      AND RMPEC.ENTRY_ID=RMPE.ENTRY_ID
		      AND RMPEC.LEAGUE_ID = RMPE.LEAGUE_ID
		      AND RMPEC.STATUS in (3)
 	    ORDER BY RMPE.TREQUEST_DATE"; 
      $db->query($sql);
      while($row = $db->nextRow()) {
        $index = $row['ENTRY_ID'];
        if ($row['USER_ID'] == $auth->getUserId()) {
          $other = $row['USER_ID2'];
          $row['CANCEL'] = 1;
  	  $row['OWNER_USER_NAME'] = $row['USER_NAME2'];;
        } else {
          $other = $row['USER_ID'];
	  $row['OWNER_USER_NAME'] = $row['USER_NAME'];;
	}

        if ($row['FEE'] > 0)
          $row['PAYER'] = $row['USER_NAME'];
        else if ($row['FEE'] < 0)
          $row['PAYER'] = $row['USER_NAME2'];

        if (!empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
          $row['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
        else $row['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];

        $players['PENDING'][$other][$row['ENTRY_ID']]['ROW'] = $row;
        $players['PENDING'][$other][$row['ENTRY_ID']][$row['OWNER']][] = $row;
      }
    }


//print_r($players['TRANSFERS']);
    $players['TEAM'] = $this->team_main_players;

    return $players;
  }

  function inSamePriceRange($price1, $price2, $price_ranges) {
    for ($i = 0; $i < count($price_ranges) ; $i++) {
      if ($price_ranges[$i]['FROM'] >= $price1 && $price_ranges[$i]['TO'] < $price1 &&
	  $price_ranges[$i]['FROM'] >= $price2 && $price_ranges[$i]['TO'] < $price2)
        return true;
    }
    return false;
  }

  function finishRVSLeague($current_tour, $league_info) {
    global $manager;
    global $db;

    if ($manager->manager_trade_allow || $manager->season_over) {
      if (isset($league_info['START_TOUR']) && $league_info['START_TOUR'] > 0 && ($league_info['END_TOUR'] < $current_tour || $manager->season_over) && $league_info['STATUS'] == 2) {
        unset($sdata);
	$sdata['STATUS'] = 3;
        $db->update("rvs_manager_leagues", $sdata, "LEAGUE_ID=".$this->league_id);
// log it
        $rvs_manager_log = new RvsManagerLog();
        $rvs_manager_log->logEvent ('', 4, $manager->mseason_id, $this->league_id);

// distribute prize
        if ($league_info['REAL_PRIZES'] == 'N') {
          $prize1 = $league_info['PRIZE_FUND'] / 2;
          $prize2 = $league_info['PRIZE_FUND'] * 0.3;
          $prize3 = $league_info['PRIZE_FUND'] / 10;
  
          $sql = "SELECT * FROM rvs_manager_standings RMS
			WHERE PLACE IN (1,2)
				AND LEAGUE_ID=".$this->league_id;
          $db->query($sql);
          $places = array();
          while($row = $db->nextRow()) {
            $places[$row['PLACE']] = $row;
          }
  
          $credits = new Credits();
          $credit_log = new CreditsLog();
          $credits->updateCredits($places[1]['USER_ID'], $prize1); 
          $credit_log->logEvent ($places[1]['USER_ID'], 23, $prize1);
          $credits->updateCredits($places[2]['USER_ID'], $prize2); 
          $credit_log->logEvent ($places[2]['USER_ID'], 23, $prize2);
          $credits->updateCredits($league_info['USER_ID'], $prize3); 
          $credit_log->logEvent ($league_info['USER_ID'], 23, $prize3);   
        } else {
          $credits = new Credits();
          $credit_log = new CreditsLog();
          $prize3 = $league_info['PRIZE_FUND'] * 0.9;
          $credits->updateCredits($league_info['USER_ID'], $prize3); 
          $credit_log->logEvent ($league_info['USER_ID'], 23, $prize3);   
        }
      }
    }
  }

  function startManualDraft($league_id) {
    global $db;
    global $manager;

    $sql = "SELECT ML.*, DRAFT_START_DATE < NOW() as DRAFT_STARTED, NOW() as NOW
		from rvs_manager_leagues ML
		WHERE ML.league_ID =".$league_id; 
    $db->query($sql); 
    if ($row = $db->nextRow()) {
      if ($row['DRAFT_STARTED']==1 && $row['DRAFT_STATE'] == 0) {
        unset($sdata);
        $sdata['LEAGUE_ID'] = $league_id;
        $sdata['ROUND'] = 1;
        $sdata['STEP'] = 1;
        if ($row['DRAFT_INTERVAL'] > 0)
          $sdata['NEXT_STEP'] = "DATE_ADD(NOW(), INTERVAL ".($row['DRAFT_INTERVAL']*60 + 20)." SECOND)";
        $sdata['STATE'] = 0;
        $db->insert('rvs_manager_draft', $sdata);      

        $league = new League('rvs_manager', $league_id);
        $league->getLeagueInfo();

        $this->setDraftPickOrder($league);
  
        unset($sdata);
        $sdata['DRAFT_STATE'] = 1;
        $db->update("rvs_manager_leagues", $sdata, "LEAGUE_ID=".$league_id);
        $this->informDraftStart($league_id);
        return true;
      } else if (!$row['DRAFT_STARTED'] && $row['DRAFT_STATE'] == 0) {
        return false;
      }
    } 
    return false;
  }

  function informDraftStart($league_id) {
    global $db;
    global $manager;

    $sql="SELECT MLM.USER_ID, U.USER_NAME, RML.TITLE, RML.DRAFT_START_DATE, 
		RML.DRAFT_DATE, RML.LEAGUE_ID, U.TIMEZONE, U.LAST_LANG, U.EMAIL,
                DATE_ADD(DRAFT_START_DATE, INTERVAL U.TIMEZONE*60 MINUTE) AS DRAFT_START_DATE_UTC
 	        FROM rvs_manager_leagues_members MLM, users U, rvs_manager_leagues RML
        	 WHERE MLM.LEAGUE_ID=".$league_id."		
		           AND MLM.STATUS in (1,2)
			   AND U.USER_ID=MLM.USER_ID
			   AND U.EMAIL_VERIFIED='Y'
			   AND RML.LEAGUE_ID=MLM.LEAGUE_ID";
    
    $db->query($sql);    
    $u = 0;
    unset($players);
    while ($row = $db->nextRow()) {
      $players[$u] = $row;
      $u++;
    }
    
    for ($p = 0; $p< $u; $p++) {
       $manager->sendDraftStartEmail($players[$p]);
    }
  }

  function informDraftTimeSet($league_id) {
    global $db;
    global $manager;

    $sql="SELECT MLM.USER_ID, U.USER_NAME, RML.TITLE, RML.DRAFT_START_DATE, 
		RML.DRAFT_DATE, RML.LEAGUE_ID, U.TIMEZONE, U.LAST_LANG, U.EMAIL,
                DATE_ADD(DRAFT_START_DATE, INTERVAL U.TIMEZONE*60 MINUTE) AS DRAFT_START_DATE_UTC
 	        FROM rvs_manager_leagues_members MLM, users U, rvs_manager_leagues RML
        	 WHERE MLM.LEAGUE_ID=".$league_id."		
		           AND MLM.STATUS in (1,2)
			   AND U.USER_ID=MLM.USER_ID
			   AND U.EMAIL_VERIFIED='Y'
			   AND RML.LEAGUE_ID=MLM.LEAGUE_ID";
    
    $db->query($sql);    
    $u = 0;
    unset($players);
    while ($row = $db->nextRow()) {
      $players[$u] = $row;
      $u++;
    }
    
    for ($p = 0; $p< $u; $p++) {
       $manager->sendDraftTimeSetEmail($players[$p]);
    }
  }

  function performManualDraft($league_id) {  
    global $db;
    global $position_types;
    global $auth;
    global $smarty;
    global $langs;
    global $draft_intervals;
    global $draft_pick_order_types;
    global $_POST;
    global $manager;

    $draft_started = $this->startManualDraft($league_id);

    $league = new League('rvs_manager', $league_id);
    $league->getLeagueInfo();
    $sql = "SELECT *, UNIX_TIMESTAMP(NEXT_STEP) - UNIX_TIMESTAMP(NOW()) as TIMELEFT FROM rvs_manager_draft RMD
			WHERE RMD.LEAGUE_ID=".$league_id;
    $db->query($sql); 
    $row = $db->nextRow();
    $draft_info = $row;
//$db->showquery=true;

    $in_draft = false;
    $sql = "SELECT U.USER_NAME, U.USER_ID FROM rvs_manager_leagues_members RMLM, users U
		WHERE RMLM.LEAGUE_ID=".$league_id."
  			AND RMLM.USER_ID=U.USER_ID
                           AND RMLM.STATUS in (1,2)
  		ORDER BY DRAFT_ORDER ASC";
    $users = array();
    $db->query($sql);
    while ($row = $db->nextRow()) {
      $user = $row;
      if ($row['USER_ID'] == $auth->getUserId())
        $in_draft = true;
      $users[] = $user;
    }   

     if ($league->league_info['DRAFT_TYPE'] == 1 &&
	 $league->league_info['DRAFT_STATE'] == 3) {
       $draft_message = $langs['LANG_RVS_MANAGER_DRAFTS_OVER_U'];
     } else if ($league->league_info['DRAFT_TYPE'] == 1 &&
	 $league->league_info['DRAFT_STATE'] == 0) {
	    $sql = "SELECT ML.*, DRAFT_START_DATE < NOW() as DRAFT_STARTED,
				DATE_ADD(DRAFT_START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS DRAFT_START_DATE_UTC
			from rvs_manager_leagues ML
			WHERE ML.league_ID =".$league_id; 
	    $db->query($sql); 
            if ($row = $db->nextRow()) {            		
              $draft_message = $langs['LANG_RVS_LEAGUE_DRAFT_START_SET_U']." <b>".$row['DRAFT_START_DATE_UTC']."</b> ". $auth->getUserTimezoneName();
            }
     } else if ($league->league_info['DRAFT_TYPE'] == 1 &&
                $league->league_info['DRAFT_STATE'] > 0 &&
		$league->league_info['DRAFT_STATE'] < 3) {

       $user_id = $auth->getUserId();
       $db->query("start transaction");
       if ($draft_info['TIMELEFT'] <= 1 && $league->league_info['DRAFT_INTERVAL'] > 0) {
         $_GET['draft_pick'] = 'Y';
           // get player id
         $sql= "SELECT USER_ID
	     FROM manager_market 
	     WHERE season_id=".$league->league_info['SEASON_ID']."
		AND PLAYER_STATE = 0
  		AND PUBLISH='Y'
                  AND USER_ID NOT IN (SELECT PLAYER_ID FROM rvs_manager_teams WHERE LEAGUE_ID=".$league_id." AND SELLING_DATE IS NULL)
             ORDER BY CURRENT_VALUE_MONEY DESC LIMIT ".(30 + $draft_info['ROUND']*5);
//echo $sql;
         $db->query($sql); 
         $c = 0; 
         while ($row = $db->nextRow()) {
           $market[$c] = $row;
           $c++;
         }

         $user_id = $users[$draft_info['STEP']-1]['USER_ID'];

         $_GET['player_id'] = $this->getDraftPlayer($league, $user_id, $draft_info, $market);
         $sql = "SELECT * from rvs_manager_draft_steps
			WHERE ROUND=".$draft_info['ROUND']."
				AND LEAGUE_ID=".$league->league_info['LEAGUE_ID']."
				AND USER_ID=".$user_id;
         $db->query($sql);
         if ($row = $db->nextRow()) {
           unset($_GET['draft_pick']);
         }
       }
       if (isset($_GET['draft_pick']) && $users[$draft_info['STEP']-1]['USER_ID'] == $user_id) {
         $sql = "SELECT count(PLAYER_ID) PLAYERS FROM rvs_manager_teams WHERE LEAGUE_ID=".$league_id."
			AND USER_ID=".$user_id." AND SELLING_DATE IS NULL";
         $db->query($sql);
         $row = $db->nextRow();
         if ($row['PLAYERS'] < $league->league_info['TEAM_SIZE'] 
             && $row['PLAYERS'] < $draft_info['ROUND']) {
             $sql= "SELECT USER_ID, POSITION_ID1, CURRENT_VALUE_MONEY, LAST_NAME, FIRST_NAME, TEAM_NAME2, 
		POSITION_ID1, POSITION_ID2, PLAYED
	     FROM manager_market 
	     WHERE season_id=".$manager->mseason_id."
		AND PLAYER_STATE = 0
		AND USER_ID = ".$_GET['player_id']."
    		AND PUBLISH='Y'
                    AND USER_ID NOT IN (SELECT PLAYER_ID FROM rvs_manager_teams WHERE LEAGUE_ID=".$league_id." AND SELLING_DATE IS NULL)";
            $db->query($sql);
            if ($row = $db->nextRow()) {
   
              unset($sdata);       
              $sdata['USER_ID'] = $user_id;
              $sdata['LEAGUE_ID'] = $league_id;
              $sdata['PLAYER_ID'] = $_GET['player_id'];
              $sdata['STEP'] = $draft_info['STEP'];
              $sdata['ROUND'] = $draft_info['ROUND'];
              if ($db->insert("rvs_manager_draft_steps", $sdata)) {
                unset($sdata);       
                $sdata['USER_ID'] = $user_id;
                $sdata['LEAGUE_ID'] = $league_id;
                $sdata['PLAYER_ID'] = $_GET['player_id'];
                $sdata['BUYING_PRICE'] = $row['CURRENT_VALUE_MONEY'];
                $sdata['SELLING_PRICE'] = $row['CURRENT_VALUE_MONEY'];
                $sdata['BUYING_DATE'] = "NOW()";
                $db->insert("rvs_manager_teams", $sdata);

                $rvs_manager_user_log = new RvsManagerUserLog();
                $rvs_manager_user_log->logEvent($user_id, 2, $manager->mseason_id, $league_id, $_GET['player_id']);
                $rvs_manager_log = new RvsManagerLog();
                $rvs_manager_log->logEvent ($user_id, 10, $manager->mseason_id, $league_id, $_GET['player_id']); 

                $this->updateDraftStatus($league, $draft_info);
            // update draft status
      
                $sql = "SELECT *, UNIX_TIMESTAMP(NEXT_STEP) - UNIX_TIMESTAMP(NOW()) as TIMELEFT FROM rvs_manager_draft RMD
			WHERE RMD.LEAGUE_ID=".$league_id;
                $db->query($sql); 
                $row = $db->nextRow();
                $draft_info = $row;
	      }
            }          
          } 
       }
       $db->query("commit");
     }

     $sql = "SELECT U.USER_NAME, U.USER_ID FROM rvs_manager_leagues_members RMLM, users U
		WHERE RMLM.LEAGUE_ID=".$league_id."
  			AND RMLM.USER_ID=U.USER_ID
                           AND RMLM.STATUS in (1,2)
  		ORDER BY DRAFT_ORDER ASC";
     $users = array();
     $db->query($sql);
     while ($row = $db->nextRow()) {
       $user = $row;
       if ($row['USER_ID'] == $auth->getUserId())
         $in_draft = true;
       $users[] = $user;
     }   

     if ($league->league_info['DRAFT_TYPE'] == 1 &&
  	 $league->league_info['DRAFT_STATE'] > 0 && 
	 $league->league_info['DRAFT_STATE'] < 3) {
       $users[$draft_info['STEP']-1]['DRAFTER'] = 1;
       $draft_message = $langs['LANG_RVS_MANAGER_DRAFTS_ONGOING_STAGE_U'];
       $draft_message = str_replace("%u", $users[$draft_info['STEP']-1]['USER_NAME'], $draft_message);
       $draft_message = str_replace("%s", $draft_info['STEP'], $draft_message);
       $draft_message = str_replace("%t", $league->league_info['TEAM_SIZE'], $draft_message);
       $draft_message = str_replace("%r", $draft_info['ROUND'], $draft_message);
     } 
 
     $manager_logbox = new LogBox($langs, $_SESSION["_lang"]);
     $manager_log = $manager_logbox->getRvsManagerLogBox($league_id, 1, 150);
  
     // get players for current step
     $sql= "SELECT USER_ID, POSITION_ID1, CURRENT_VALUE_MONEY, LAST_NAME, FIRST_NAME, TEAM_NAME2, 
		POSITION_ID1, POSITION_ID2, PLAYED
	     FROM manager_market 
	     WHERE season_id=".$league->league_info['SEASON_ID']."
		AND PLAYER_STATE = 0
  		AND PUBLISH='Y'
                AND USER_ID NOT IN (SELECT PLAYER_ID FROM rvs_manager_teams WHERE LEAGUE_ID=".$league_id." AND SELLING_DATE IS NULL)
           ORDER BY CURRENT_VALUE_MONEY DESC";
  //eo $sql;
     $db->query($sql); 
     $c = 0;

     while ($row = $db->nextRow()) {
       if (!empty($row['POSITION_ID2']) && empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
         $row['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
       else $row['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];
  
       $market[$c] = $row;
       $c++;
     }
  
     if (!isset($draft_info['ROUND']))
       $draft_info['ROUND'] = 0;
     for ($i = 0; $i < 150 + $draft_info['ROUND']*5; $i++) {
        if (isset($market[$i]))
          $players[] = $market[$i];
     }

     $sql = "SELECT *, UNIX_TIMESTAMP(NEXT_STEP) - UNIX_TIMESTAMP(NOW()) as TIMELEFT FROM rvs_manager_draft RMD
			WHERE RMD.LEAGUE_ID=".$league_id;
     $db->query($sql); 
     $row = $db->nextRow();
     $draft_info = $row;
  
     if (isset($draft_info['STEP']) && $users[$draft_info['STEP']-1]['USER_ID'] == $auth->getUserId()) {
       $smarty->assign("allow_draft", 1);
     }
                                              
     if ($league->league_info['DRAFT_INTERVAL'] > 0 && $draft_info['TIMELEFT'] > 0 && $users[$draft_info['STEP']-1]['USER_ID'] == $auth->getUserId()) {
       $draft_info['TIMELEFT_MESSAGE'] = $langs['LANG_RVS_MANAGER_DRAFTS_NEXT_MOVE_YOU_U'];
     } else if ($league->league_info['DRAFT_INTERVAL'] > 0 && $draft_info['TIMELEFT'] > 1) {
       $draft_info['TIMELEFT_MESSAGE'] = $langs['LANG_RVS_MANAGER_DRAFTS_NEXT_MOVE_U'];
       $draft_info['TIMELEFT_MESSAGE'] = str_replace("%u", $users[$draft_info['STEP']-1]['USER_NAME'], $draft_info['TIMELEFT_MESSAGE']);
     } else {
       $draft_info['TIMELEFT_MESSAGE'] = $langs['LANG_RVS_MANAGER_DRAFTS_TIMEOUT_U'];
     }

     $draft_info['TIMEOUT'] = $draft_intervals[$league->league_info['DRAFT_INTERVAL']];
//echo $league->league_info['DRAFT_PICK_ORDER_TYPE'];
     $draft_info['PICK_ORDER'] = $draft_pick_order_types[$league->league_info['DRAFT_PICK_ORDER_TYPE']];
//echo $draft_info['PICK_ORDER'];
     $smarty->assign("draft_info", $draft_info);    
     $smarty->assign("draft_message", $draft_message);
     $smarty->assign("league", $league->league_info);
     $smarty->assign("users", $users);
     if ($auth->userOn())
       $smarty->assign("allow_chat", 1);
     $smarty->assign("user_name", $auth->getUserName());
     $smarty->assign("players", $players);
     $smarty->assign("log", $manager_log);

     $start = getmicrotime();
     $content = $smarty->fetch('smarty_tpl/rvs_manager_perform_drafts.smarty');    
     $stop = getmicrotime();
     if (isset($_GET['debugphp']))
       echo 'smarty_tpl/rvs_manager_perform_drafts.smarty'.($stop-$start);
     return $content;
  }

  function updateDraftStatus($league, $draft_info) {
    global $db;
    global $manager;
    global $auth;

    unset($sdata);       
    if ($league->league_info['PARTICIPANTS'] > $draft_info['STEP']) {
      $sdata['STEP'] = $draft_info['STEP'] + 1;
      $draft_info['STEP'] = $draft_info['STEP'] + 1;
    } else if ($league->league_info['PARTICIPANTS'] <= $draft_info['STEP'] 
    	&& $league->league_info['TEAM_SIZE'] > $draft_info['ROUND']) {
      $sdata['STEP'] = 1;
      $draft_info['STEP'] = 1;
      $sdata['ROUND'] = $draft_info['ROUND'] + 1;
      $draft_info['ROUND'] = $draft_info['ROUND'] + 1;
      $this->setDraftPickOrder($league);
    } else {
      $sdata['STATE'] = 1;
      $draft_info['STATE'] = 1;
      unset($udata);
       // update start tour and end tour
      $tours = $manager->getToursAmount();
      $current_tour = $manager->getCurrentTour();

      if ($manager->season_info['RVS_LEAGUES_LAST_TOUR'] > 0)
        $tours = $manager->season_info['RVS_LEAGUES_LAST_TOUR'];       

      unset($sdata);
      $udata['START_TOUR'] = $current_tour;  
      if ($tours >= $current_tour + $duration - 1)
        $udata['END_TOUR'] = $current_tour + $league->league_info['DURATION'] - 1;  
      else {
        $udata['END_TOUR'] = $tours;  
        $udata['DURATION'] = $tours - $current_tour + 1;  
      }


      $udata['DRAFT_STATE'] = 3;
      $udata['DRAFT_DATE'] = "NOW()";
      $db->update("rvs_manager_leagues", $udata, "LEAGUE_ID=". $league->league_info['LEAGUE_ID']);
      $rvs_manager_log = new RvsManagerLog();
      $rvs_manager_log->logEvent ($user_id, 3, $manager->mseason_id, $league->league_info['LEAGUE_ID']); 
    
      $sql="SELECT MLM.USER_ID, U.USER_NAME, RML.TITLE, RML.DRAFT_START_DATE, RML.DRAFT_DATE, 
			RML.LEAGUE_ID, U.TIMEZONE, U.LAST_LANG, U.EMAIL,
 		        DATE_ADD(DRAFT_DATE, INTERVAL U.TIMEZONE*60 MINUTE) AS DRAFT_DATE_UTC
           FROM rvs_manager_leagues_members MLM, users U, rvs_manager_leagues RML
           WHERE MLM.LEAGUE_ID=".$league->league_info['LEAGUE_ID']."		
             AND MLM.STATUS in (1,2)
	     AND U.USER_ID=MLM.USER_ID
	     AND U.EMAIL_VERIFIED='Y'
	     AND RML.LEAGUE_ID=MLM.LEAGUE_ID";
     
      $db->query($sql);    
      $u = 0;
      unset($players);
      while ($row = $db->nextRow()) {
        $players[$u] = $row;
        $u++;
      }
    
      for ($p = 0; $p< count($players); $p++) {
         $manager->sendDraftEndEmail($players[$p]);
      }
    }
    if ($league->league_info['DRAFT_INTERVAL'] > 0)
      $sdata['NEXT_STEP'] = 'DATE_ADD(NOW(), INTERVAL '.$league->league_info['DRAFT_INTERVAL'].' MINUTE)';
    else $sdata['NEXT_STEP'] = 'NOW()';
    $db->update("rvs_manager_draft", $sdata, "LEAGUE_ID =". $league->league_info['LEAGUE_ID']);       
  }

  function getDraftPlayer($league, $user_id, $draft_info, $market) {
    global $db;
 
    //check if there is a candidate from a list
    $sql="SELECT * FROM rvs_manager_draft_candidates RMDC, manager_market MM
		  WHERE RMDC.LEAGUE_ID=".$league->league_info['LEAGUE_ID']." 
			 AND RMDC.USER_ID=".$user_id."
		        AND MM.season_id=".$league->league_info['SEASON_ID']."
			 AND MM.USER_ID=RMDC.PLAYER_ID
			 AND MM.PLAYER_STATE = 0
		  	 AND MM.PUBLISH='Y'
		             AND RMDC.PLAYER_ID NOT IN 
		(SELECT PLAYER_ID FROM rvs_manager_teams WHERE LEAGUE_ID=".$league->league_info['LEAGUE_ID'].")
	       ORDER BY RMDC.ORDER_ID ASC, MM.CURRENT_VALUE_MONEY DESC LIMIT 1";
//echo $sql;
     $db->query($sql);
     if ($row = $db->nextRow()) {
       $player_id = $row['PLAYER_ID'];
     }
     else {
       //get random player
       $id = rand(5, 25 + $draft_info['ROUND']*5); 
       $player_id = $market[$id]['USER_ID'];      
     }

     return $player_id;
  }
  
  function getDraftsLists($league) {
     global $db;
     global $smarty;
     global $auth;
     global $langs;
     global $manager;
     global $position_types;

     $sql="SELECT * FROM manager_market MM 
		WHERE MM.season_id=".$league->league_info['SEASON_ID']."
		AND MM.PLAYER_STATE = 0
  		AND MM.PUBLISH='Y'
                AND MM.USER_ID NOT IN (SELECT PLAYER_ID FROM rvs_manager_draft_candidates WHERE LEAGUE_ID=".$league->league_info['LEAGUE_ID']." AND USER_ID=".$auth->getUserId().")
           ORDER BY CURRENT_VALUE_MONEY DESC";
//echo $sql;
     $db->query($sql); 
     while ($row = $db->nextRow()) {
       if (!empty($row['POSITION_ID2']) && empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
         $row['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
       else $row['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];
  
       $candidates[] = $row;
     }

     $sql="SELECT RMDC.*, MM.*, RMDT.ENTRY_ID FROM manager_market MM, rvs_manager_draft_candidates RMDC
                 left join rvs_manager_teams RMDT ON RMDT.LEAGUE_ID=RMDC.LEAGUE_ID
							AND RMDT.PLAYER_ID=RMDC.PLAYER_ID
							AND RMDT.SELLING_DATE IS NULL
	   WHERE RMDC.LEAGUE_ID=".$league->league_info['LEAGUE_ID']." 
		 AND RMDC.USER_ID=".$auth->getUserId()."
	         AND MM.season_id=".$league->league_info['SEASON_ID']."
		 AND MM.USER_ID=RMDC.PLAYER_ID
		 AND MM.PLAYER_STATE = 0
  		 AND MM.PUBLISH='Y'
           ORDER BY RMDC.ORDER_ID ASC, MM.CURRENT_VALUE_MONEY DESC";
//echo $sql;
     $db->query($sql); 
     while ($row = $db->nextRow()) {
       if (!empty($row['POSITION_ID2']) && empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
         $row['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
       else $row['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];
  
       $my_candidates[] = $row;
     }

     $smarty->assign("candidates", $candidates);
     if (isset($my_candidates))
       $smarty->assign("my_candidates", $my_candidates);
//     else $smarty->assign("my_candidates", 1); 

  }

  function setDraftPickOrder($league) {
     global $db;
     global $manager;

//$db->showquery = true;
     if (($league->league_info['DRAFT_PICK_ORDER_TYPE'] == 0 || $league->league_info['DRAFT_PICK_ORDER_TYPE'] == 2)
		&& $league->league_info['DRAFT_STATE'] == 0) {
     // order teams     
        $sql = "SELECT RMLM.USER_ID, MR.POINTS FROM rvs_manager_leagues_members RMLM
    		LEFT JOIN manager_ratings MR ON MR.USER_ID=RMLM.USER_ID
    						AND MR.TOURNAMENT_ID=0
						AND MR.SPORT_ID = ".$manager->sport_id."
		WHERE RMLM.LEAGUE_ID=".$league->league_info['LEAGUE_ID']."
				AND RMLM.STATUS in (1,2)
		ORDER BY MR.POINTS DESC";
        $db->query($sql); 
        $c = 0;
        $users = array();
        while ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['USER_ID'] = $row['USER_ID'];
          $sdata['DRAFT_ORDER'] = $league->league_info['PARTICIPANTS'] - $c;
          $users[] = $sdata;
          $c++; 
        }
        foreach ($users as $user) {
          $db->update('rvs_manager_leagues_members', $user, "LEAGUE_ID=".$league->league_info['LEAGUE_ID']." AND USER_ID=".$user['USER_ID']);  
        }
        return;
     }

     if ($league->league_info['DRAFT_PICK_ORDER_TYPE'] == 1) {
        $sql = "SELECT RMLM.USER_ID FROM rvs_manager_leagues_members RMLM
		WHERE RMLM.LEAGUE_ID=".$league->league_info['LEAGUE_ID']."
				AND RMLM.STATUS in (1,2)
		ORDER BY RAND()";

        $db->query($sql); 
        $c = 0;
        $users = array();
        while ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['USER_ID'] = $row['USER_ID'];
          $sdata['DRAFT_ORDER'] = $c;
          $users[] = $sdata;
          $c++; 
        }
        foreach ($users as $user) {
          $db->update('rvs_manager_leagues_members', $user, "LEAGUE_ID=".$league->league_info['LEAGUE_ID']." AND USER_ID=".$user['USER_ID']);  
        }
        return;
     }

     if (($league->league_info['DRAFT_PICK_ORDER_TYPE'] == 2 
         || $league->league_info['DRAFT_PICK_ORDER_TYPE'] == 3)
		&& $league->league_info['DRAFT_STATE'] == 1) {
        $sql = "SELECT RMLM.USER_ID, RMLM.DRAFT_ORDER FROM rvs_manager_leagues_members RMLM
		WHERE RMLM.LEAGUE_ID=".$league->league_info['LEAGUE_ID']."
				AND RMLM.STATUS in (1,2)";
        $db->query($sql); 
        $c = 0;
        $users = array();
        while ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['USER_ID'] = $row['USER_ID'];
          $sdata['DRAFT_ORDER'] = ($row['DRAFT_ORDER'] + 1) % $league->league_info['PARTICIPANTS'];
          $users[] = $sdata;
          $c++; 
        }
        foreach ($users as $user) {
          $db->update('rvs_manager_leagues_members', $user, "LEAGUE_ID=".$league->league_info['LEAGUE_ID']." AND USER_ID=".$user['USER_ID']);  
        }
        return;
     }

  }

  function getModerateTransfers() {
    global $db;
    global $auth;    
    global $manager;
    global $smarty;
    global $position_types;
    global $pleague;
    global $_SESSION;

      $sql = "SELECT U.USER_NAME, U.USER_ID, U1.USER_NAME as USER_NAME2, U1.USER_ID as USER2_ID2, 
		U.CREDIT, U1.CREDIT as CREDIT2,
		MM1.LAST_NAME, MM1.FIRST_NAME, MM1.POSITION_ID1, 
		MM1.POSITION_ID2, MM1.CURRENT_VALUE_MONEY, 
		RMPE.*, RMPEC.USER_ID as OWNER, RMPEC.PLAYER_ID AS CPLAYER_ID, RMPEC.STATUS as CSTATUS, 
		IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2
	    FROM rvs_manager_players_exchange RMPE
			LEFT JOIN users U ON U.USER_ID=RMPE.USER_ID
		        LEFT JOIN users U1 ON U1.USER_ID=RMPE.USER_ID2,
		 rvs_manager_players_exchange_contract RMPEC
			LEFT JOIN manager_market MM1 ON MM1.USER_ID=RMPEC.PLAYER_ID
						AND MM1.SEASON_ID=".$manager->mseason_id."
			LEFT JOIN teams T2 ON MM1.TEAM_ID=T2.TEAM_ID 
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
	    WHERE RMPE.LEAGUE_ID=".$this->league_id."
                      AND RMPE.STATUS in (2)
                      AND RMPEC.ENTRY_ID=RMPE.ENTRY_ID
		      AND RMPEC.LEAGUE_ID = RMPE.LEAGUE_ID
		      AND RMPEC.STATUS in (3)
 	    ORDER BY RMPE.TREQUEST_DATE"; 
      $db->query($sql);
      while($row = $db->nextRow()) {
        $index = $row['ENTRY_ID'];
        if ($row['USER_ID'] == $row['OWNER']) {
          $owner = 'USER_ID';
  	  $row['OWNER_USER_NAME'] = $row['USER_NAME2'];;
        } else {
          $owner = 'USER_ID2';
	  $row['OWNER_USER_NAME'] = $row['USER_NAME'];;
	}

        if ($row['FEE'] > 0)
          $row['PAYER'] = $row['USER_NAME'];
        else if ($row['FEE'] < 0)
          $row['PAYER'] = $row['USER_NAME2'];

        if (!empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
          $row['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
        else $row['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];
        $row['PLAYER_SEASON_STATS']['SUBSEASONS'] = $manager->seasonlist;

        $transfers[$row['ENTRY_ID']]['ROW'] = $row;
        $transfers[$row['ENTRY_ID']][$owner][] = $row;
      }
    
    if (isset($transfers) && count($transfers) > 0)
      $smarty->assign('transfers', $transfers);

  }


  function freeTransferPlayer($current_tour) {
    global $db;
    global $_POST;
    global $manager;
    global $auth;
//$db->showquery=true;
    if (isset($_POST['player'])) {     
      $sql = "SELECT *
            FROM manager_market MM 
            WHERE MM.SEASON_ID=".$this->mseason_id." 
                  AND USER_ID =".$_POST['player'];
     $db->query($sql);
     $row = $db->nextRow();
     $price = $row['CURRENT_VALUE_MONEY'];
     $selling_price = $row['CURRENT_VALUE_MONEY'];
     $player_state = $row['PLAYER_STATE'];
     $publish = $row['PUBLISH'];

     if ($row['CURRENT_VALUE_MONEY'] > -1) {     
       if (isset($_POST['free_transfer']) && $manager->manager_trade_allow 
		&& ($this->free_transfers > $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['USED_FREE_TRANSFERS'] || $row['PLAYER_STATE'] != 0 || $row['PUBLISH'] == 'N')
		&& $auth->getCredits() >= $this->free_transfer_fee) {
          // check that user is still in a team
          $sql = "SELECT ENTRY_ID, USER_ID, BUYING_DATE
              FROM rvs_manager_teams 
              WHERE 
                USER_ID=".$auth->getUserId()."
                AND SELLING_DATE IS NULL 
                AND PLAYER_ID=".$_POST['player']."
		AND LEAGUE_ID=".$this->league_id;
          $db->query($sql);     
          if ($row = $db->nextRow()) {
            $buying_date = $row['BUYING_DATE'];
            // get selling price   

	    // get random player from similar range.
            $sql= "SELECT USER_ID, POSITION_ID1, CURRENT_VALUE_MONEY 
			FROM manager_market 
			WHERE season_id=".$this->mseason_id."
			AND PUBLISH='Y'
			AND USER_ID = ".$_POST['new_player']."
			AND USER_ID NOT IN 
			(SELECT PLAYER_ID FROM rvs_manager_teams
				WHERE LEAGUE_ID=".$this->league_id."
					AND SELLING_DATE IS NULL
					AND PLAYER_ID=".$_POST['new_player'].")";
            $db->query($sql); 
            $c = 0;
            if ($row = $db->nextRow()) {
                unset($sdata);
                $sdata['USER_ID'] = $auth->getUserId();
                $sdata['LEAGUE_ID'] = $this->league_id;
                $sdata['PLAYER_ID'] = $_POST['new_player'];
                $sdata['BUYING_PRICE'] = $row['CURRENT_VALUE_MONEY'];
                $sdata['SELLING_PRICE'] = $row['CURRENT_VALUE_MONEY'];
                $sdata['BUYING_DATE'] = "NOW()";
                $db->insert("rvs_manager_teams", $sdata);
                $manager_user_log = new RvsManagerUserLog();
	        $manager_log = new RvsManagerLog();
                
                unset($sdata);
       	        $sdata['SELLING_DATE']="NOW()";
                $sdata['SELLING_PRICE']=$selling_price; //$row['SELLING_PRICE'];
                $db->update('rvs_manager_teams', $sdata, "USER_ID=".$auth->getUserId()." AND PLAYER_ID=".$_POST['player']." AND BUYING_DATE='".$buying_date."'");         
                
     	        $manager_user_log->logEvent ($auth->getUserId(), 3, $this->mseason_id, $this->league_id, $_POST['player'], '', $_POST['new_player']);
     	        $manager_log->logEvent ($auth->getUserId(), 14, $this->mseason_id, $this->league_id, $_POST['player'], '', $_POST['new_player'], '' , '', abs($this->free_transfer_fee)/2);
                
                // remove player from player exchange
        
		$this->deletePlayerExchangeEntryForPlayer($_POST['player']);

                if ($player_state == 0 && $publish == 'Y') {
                  unset($sdata);
                  $sdata['USED_FREE_TRANSFERS']="USED_FREE_TRANSFERS+1";
                  $db->update('rvs_manager_users_tours', $sdata, "USER_ID=".$auth->getUserId()." AND LEAGUE_ID=".$this->league_id." AND TOUR_ID=".$current_tour);             
  	          $_SESSION['_user']['RVS_MANAGER'][$this->league_id]['USED_FREE_TRANSFERS']++;
                }

		if ($auth->getCredits() >= $this->free_transfer_fee 
		    && $this->free_transfer_fee  > 0) {
		    $credits = new Credits();
		    $credit_log = new CreditsLog();                    
	            $credits->updateRvsLeagueCredits($this->league_id, abs($this->free_transfer_fee)/2);
 	 	    $credits->updateCredits($auth->getUserId(), -1*$this->free_transfer_fee ); 
		    $credit_log->logEvent ($auth->getUserId(), 22, $this->free_transfer_fee );
	        }
                
                return 1; 
             } else return -1;
          }
       }
       else return -1;
     }
   }
   return 0;
  }

  function protectPlayer() {
    global $auth;
    global $db;

    global $_POST;

      $sql = "update rvs_manager_teams 
                set protected = 1
              WHERE 
                USER_ID=".$auth->getUserId()."
                AND SELLING_DATE IS NULL 
                AND PLAYER_ID=".$_POST['player']."
		AND LEAGUE_ID=".$this->league_id;
     $db->query($sql);     
  }

  function unprotectPlayer() {
    global $auth;
    global $db;
    global $_POST;

      $sql = "update rvs_manager_teams 
                set protected = 0
              WHERE 
                USER_ID=".$auth->getUserId()."
                AND SELLING_DATE IS NULL 
                AND PLAYER_ID=".$_POST['player']."
		AND LEAGUE_ID=".$this->league_id;
     $db->query($sql);     

  }

  function cleanPlayerExchange($entry_id, $user_id1, $user_id2='') {
    global $db;

    if (isset($entry_id)) {
      $sql = "SELECT MM.SELLING_PRICE, RMPL.ENTRY_ID, RMPEC.PLAYER_ID
              FROM rvs_manager_teams MM, rvs_manager_players_exchange RMPL, rvs_manager_players_exchange_contract RMPEC
              WHERE MM.LEAGUE_ID=".$this->league_id." 
              AND MM.USER_ID =".$user_id1."
              AND MM.SELLING_DATE IS NULL
              AND RMPL.ENTRY_ID =".$entry_id."
	  AND RMPL.STATUS in (0, 1, 2)
	  AND RMPL.ENTRY_ID=RMPEC.ENTRY_ID
	  AND RMPEC.LEAGUE_ID=".$this->league_id."
	  AND RMPEC.USER_ID =".$user_id1."
	  AND RMPEC.PLAYER_ID = MM.PLAYER_ID
	  AND RMPEC.STATUS IN (0, 3)";
      $db->query($sql);
      $players = array();
      while ($row = $db->nextRow()) {
        $players[] = $row['PLAYER_ID'];
      }
 
      if (isset($user_id2)) {
        $sql = "SELECT MM.SELLING_PRICE, RMPL.ENTRY_ID, RMPEC.PLAYER_ID
 	          FROM rvs_manager_teams MM, rvs_manager_players_exchange RMPL, rvs_manager_players_exchange_contract RMPEC
	          WHERE MM.LEAGUE_ID=".$this->league_id." 
        	        AND MM.USER_ID =".$user_id2."
                	AND MM.SELLING_DATE IS NULL
	                AND RMPL.ENTRY_ID =".$entry_id."
			AND RMPL.STATUS in (0, 1, 2)
			AND RMPL.ENTRY_ID=RMPEC.ENTRY_ID
		  	AND RMPEC.LEAGUE_ID=".$this->league_id."
	  		AND RMPEC.USER_ID =".$user_id2."
	  		AND RMPEC.PLAYER_ID = MM.PLAYER_ID
	  		AND RMPEC.STATUS IN (0, 3)";
	$db->query($sql);
	while ($row = $db->nextRow()) {
          $players[] = $row['PLAYER_ID'];
        }
      }

      foreach ($players as $player) {
        $this->deletePlayerExchangeEntryForPlayer($player, $entry_id);
      }
    }    
  }

  function deletePlayerExchangeEntryForPlayer($player_id, $ignore_entry_id = '') {
    global $db;

    $ignore_eid = "";
    if (!empty($ignore_entry_id)) {
      $ignore_eid = "AND ENTRY_ID <> ".$ignore_entry_id;
    }
    
    $sql = "SELECT ENTRY_ID
		   FROM rvs_manager_players_exchange_contract 
		  WHERE LEAGUE_ID=".$this->league_id."
			".$ignore_eid."
			AND PLAYER_ID in (".$player_id.")";
    $db->query($sql);     
    if ($row = $db->nextRow()) {
        $sql="UPDATE rvs_manager_teams 
                  SET MODERATED=0
                          WHERE LEAGUE_ID=".$this->league_id." 
			  AND PLAYER_ID in (SELECT PLAYER_ID from rvs_manager_players_exchange_contract 
							WHERE ENTRY_ID=".$row['ENTRY_ID'].")

				  AND SELLING_DATE IS NULL";
        $db->query($sql); 
    }

    $sql="DELETE FROM rvs_manager_players_exchange 
		WHERE STATUS in (0,2)
		      AND LEAGUE_ID =".$this->league_id."
		      AND ENTRY_ID IN (SELECT ENTRY_ID
					   FROM rvs_manager_players_exchange_contract 
					  WHERE LEAGUE_ID=".$this->league_id."
						".$ignore_eid."
						AND PLAYER_ID in (".$player_id."))";

    $db->query($sql);     
    $db->delete("rvs_manager_players_exchange_contract", "ENTRY_ID NOT IN (SELECT ENTRY_ID FROM rvs_manager_players_exchange)");       
    $db->delete("rvs_manager_players_exchange_notification", "ENTRY_ID NOT IN (SELECT ENTRY_ID FROM rvs_manager_players_exchange)");        
  }
}
    
?>_ID
		   FROM rvs_manager_players_exchange_contract 
		  WHERE LEAGUE_ID=".$this->league_id."
			".$ignore_eid."
			AND PLAYER_ID in (".$player_id.")";
    $db->query($sql);     
    if ($row = $db->nextRow()) {
        $sql="UPDATE rvs_manager_teams 
                  SET MODERATED=0
                          WHERE LEAGUE_ID=".$this->league_id." 
			  AND PLAYER_ID in (SELECT PLAYER_ID from rvs_manager_players_exchange_contract 
							WHERE ENTRY_ID=".$row['ENTRY_ID'].")

				  AND SELLING_DATE IS NULL";
        $db->query($sql); 
    }

    $sql="DELETE FROM rvs_manager_players_exchange 
		WHERE STATUS in (0,2)
		      AND LEAGUE_ID =".$this->league_id."
		      AND ENTRY_ID IN (SELECT ENTRY_ID
					   FROM rvs_manager_players_exchange_contract 
					  WHERE LEAGUE_ID=".$this->league_id."
						".$ignore_eid."
						AND PLAYER_ID in (".$player_id."))";

    $db->query($sql);     
    $db->delete("rvs_manager_players_exchange_contract", "ENTRY_ID NOT IN (SELECT ENTRY_ID FROM rvs_manager_players_exchange)");       
    $db->delete("rvs_manager_players_exchange_notification", "ENTRY_ID NOT IN (SELECT ENTRY_ID FROM rvs_manager_players_exchange)");        
  }
}
    
?>