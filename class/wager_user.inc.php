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

class WagerUser {
  var $tseason_id;
  var $inited;
  var $leagues;
  
  function WagerUser($tseason_id) {
    $this->tseason_id = $tseason_id;

    $this->initUser();
  }


  function initUser() {
    global $auth;
    global $db;
    global $_SESSION;

    $sql = "SELECT SUM(STAKE) STAKES
             FROM wager_games WG, wager_votes WV
             WHERE WV.USER_ID=".$auth->getUserId()." 
		   AND WG.PROCESSED=0
		   AND WG.WAGER_ID=WV.WAGER_ID
		   AND WG.WSEASON_ID=".$this->tseason_id;

    $db->query($sql); 
    if ($row = $db->nextRow()) {
      $stakes=$row['STAKES'];
    }

    $sql = "SELECT *
             FROM users U LEFT JOIN wager_users MU ON U.USER_ID=MU.USER_ID and MU.SEASON_ID=".$this->tseason_id."
			LEFT JOIN wager_standings MS ON U.USER_ID=MS.USER_ID and MS.SEASON_ID=".$this->tseason_id."
		        LEFT JOIN wager_leagues ML ON U.USER_ID=ML.USER_ID and ML.SEASON_ID=".$this->tseason_id."
             WHERE U.USER_ID=".$auth->getUserId()." AND MU.SEASON_ID=".$this->tseason_id;
//echo $sql;
    $db->query($sql); 
    if ($row = $db->nextRow()) {
	  $_SESSION['_user']['WAGER'][$this->tseason_id]['IGNORE_LEAGUES'] = $row['IGNORE_LEAGUES'];
	  $_SESSION['_user']['WAGER'][$this->tseason_id]['STAKE_SLIDER'] = $row['STAKE_SLIDER'];
	  $_SESSION['_user']['WAGER'][$this->tseason_id]['MONEY'] = round($row['MONEY'], 2);
	  $_SESSION['_user']['WAGER'][$this->tseason_id]['WEALTH'] = round($row['MONEY'] + $row['STAKES'], 2);
//echo $row['MONEY'].$row['STAKES'];
	//  $_SESSION['_user']['WAGER'][$this->tseason_id]['POINTS'] = $row['POINTS'];
	  $_SESSION['_user']['WAGER'][$this->tseason_id]['PLACE'] = $row['PLACE'];
	  $_SESSION['_user']['WAGER'][$this->tseason_id]['MIN_STAKE'] = $this->getMinimalStake();
          $_SESSION['_user']['WAGER'][$this->tseason_id]['MAX_STAKE'] = $this->getMaximalStake();
	  $_SESSION['_user']['WAGER'][$this->tseason_id]['LEAGUE_ID'] = $row['LEAGUE_ID'];          

	  if ($_SESSION['_user']['WAGER'][$this->tseason_id]['WEALTH'] <= 100) {
	    $data['LOGGED'][0]['GET_MONEY'][0]['X'] = 1;
	  } 

          $db->free();
          $this->inited = true;
    }
    else $this->inited = false;
  }

  function initLeagues() {

  }

  function getMoney() {
    global $_SESSION;
    return $_SESSION['_user']['WAGER'][$this->tseason_id]['MONEY'];
  }

  function getWealth() {
    global $_SESSION;
    return $_SESSION['_user']['WAGER'][$this->tseason_id]['WEALTH'];
  }

  function getLeagues() {
     global $db;
     global $auth;
     global $wager_league_point_types;

     $mleagues = array();
     $permissions = new ForumPermission();
     $can_chat = $permissions->canChat();
     $sql="SELECT ML.LEAGUE_ID, ML.TOPIC_ID, ML.TITLE, ML.ENTRY_FEE, MLM.STATUS, ML.SEASON_ID, 'n' as ALL_LEAGUES, 
		U.USER_NAME, T.POSTS, COUNT(MLM2.USER_ID) USERS, TT.MARK_TIME, 
		TT.MARK_TIME < T.LAST_POSTED AS TRACKER, UNIX_TIMESTAMP( TT.MARK_TIME ) AS TSTMP, T.LAST_POSTER_ID, ML.RATING, U.LEAGUE_OWNER_RATING,
		ML.PARTICIPANTS, ML.RECRUITMENT_ACTIVE, ML.REAL_PRIZES, C.CCTLD, CD.COUNTRY_NAME, ML.POINT_TYPE
             FROM wager_leagues_members MLM, wager_seasons M, wager_leagues ML
                  LEFT JOIN wager_leagues_members MLM1 ON ML.LEAGUE_ID=MLM1.LEAGUE_ID AND (MLM1.STATUS=1)
                  LEFT JOIN wager_leagues_members MLM2 ON ML.LEAGUE_ID=MLM2.LEAGUE_ID AND (MLM2.STATUS in (1,2))
                  LEFT JOIN users U on MLM1.USER_ID=U.USER_Id and MLM1.STATUS=1 
                  LEFT JOIN topic T on ML.TOPIC_ID=T.TOPIC_ID
		  left join topic_track TT ON T.TOPIC_ID=TT.TOPIC_ID AND TT.USER_ID=".$auth->getUserId()."
                  LEFT JOIN countries C ON ML.COUNTRY = C.ID
		  LEFT JOIN countries_details CD ON C.ID=CD.ID aND CD.LANG_ID=".$_SESSION['lang_id']."
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
               AND ML.SEASON_ID=".$this->tseason_id." 
               AND M.SEASON_ID=".$this->tseason_id." 
               AND MLM.USER_ID=".$auth->getUserId()."
               AND (MLM.STATUS=2 OR MLM.STATUS=1)
	     GROUP BY MLM.LEAGUE_ID ORDER BY MLM.STATUS ASC, ML.TITLE ASC";
    $db->query($sql);    
//echo $sql; 
    $c = 0;
    while ($row = $db->nextRow()) {
      unset($league);
      if ($row['PARTICIPANTS'] == 0)
	$row['PARTICIPANTS'] = "&infin;";
      if ($row['RECRUITMENT_ACTIVE'] == 'Y') {
        $row['RECRUITMENT_ON'] = 1;
        /*if ($row['ACCEPT_NEWBIES'] == 'Y') {
          $row['NOVICES'] = 1;
        } */
      } else
        $row['RECRUITMENT_OFF'][0]['X'] = 1;

      if ($row['REAL_PRIZES'] == 'Y') {
        $row['PRIZES'] = 1;
      }

      if ($row['PARTICIPANTS'] > 0)
	$row['PROGRESS']['PERCENTS'] = $row['USERS'] *100/ $row['PARTICIPANTS'];	

      if (!empty($row['CCTLD'])) {
        $row['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $row['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }

      $row['POINT_TYPE_DESCR'] = $wager_league_point_types[$row['POINT_TYPE']]; 

/*      if ($row['TYPE'] == 1) 
        $row['TOURNAMENT'] = 1; 
  */
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
      $c++;
      $mleagues[] = $league;
    }

    $this->leagues = $c;
    return $mleagues;
  }

  function canBet($value) {
    global $_SESSION;
    global $auth;

//    return true;
    if ($auth->userOn() && $_SESSION['_user']['CREDIT'] >= $value) {
      if ($value <= 1000) {
        return true;
      } 
    }
    return false;
  } 

  function canChangeBet() {
    global $auth;
    if ($auth->userOn()) {
      return true;
    }
    return false;
  }

  function getMinimalStake() {
    global $_SESSION;
      if ($_SESSION['_user']['WAGER'][$this->tseason_id]['WEALTH'] >= 1500)
        return round($_SESSION['_user']['WAGER'][$this->tseason_id]['WEALTH'] / 200);
      else return 0;   
  }

  function getMaximalStake() {
    global $_SESSION;
      if ($_SESSION['_user']['WAGER'][$this->tseason_id]['WEALTH'] >= 1500)
        return round($_SESSION['_user']['WAGER'][$this->tseason_id]['WEALTH'] / 15) + 1;
      else return 100;   
  }

  function getWager($wager_id) {
     global $db;
     global $auth;

     $data = '';
     $utc = $auth->getUserTimezoneName();
     $sql = "SELECT T.WAGER_ID, T.GAME_ID, T.PUBLISH, TSD.TSEASON_TITLE, 
	  T.STAKES1, T.STAKES0, T.`STAKES-1` as STAKES_1, WV.DIFFERENCE,
		WV.HOST_SCORE, WV.VISITOR_SCORE, T.STAKES,
          T.START_DATE, 
	  DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d') GAME_DAY,
          DATE_ADD(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) END_DATE,
          G.TEAM_ID1, G.TEAM_ID2, G.SCORE1, G.SCORE2,
          IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
          SD.SEASON_TITLE, WV.STAKE, WV.CHOICE, S.SPORT_ID
        FROM
          wager_games T
       		left join wager_votes WV on T.WAGER_ID=WV.WAGER_ID AND WV.USER_ID=".$auth->getUserId()."
		, wager_seasons TS
		left JOIN wager_seasons_details TSD ON TS.SEASON_ID = TSD.SEASON_ID  AND TSD.LANG_ID=".$_SESSION['lang_id']."
		, seasons S
		left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id']."
		, games G
              left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
		LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
              left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
		LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
        WHERE
          T.GAME_ID=G.GAME_ID
          AND G.SEASON_ID = S.SEASON_ID
          AND T.WSEASON_ID=TS.SEASON_ID
          AND T.WSEASON_ID=".$this->tseason_id."	
	  AND T.WAGER_ID=".$wager_id;

//	echo $sql;
     $db->query($sql);    
     $c = 0;
     if ($row = $db->nextRow()) {
       $data = $row;
       $data['UTC'] = $utc;
       if ($row['CHOICE'] == -1)
         $row['CHOICE'] = '_1';
       if (empty($row['CHOICE']))  
         $row['CHOICE'] = 0;

       $data['CHOICE'.$row['CHOICE']] = 1;
       if ($row['CHOICE'] == 0 && $row['SPORT_ID'] == 2) 
	 $data['DRAW']['CHOICE0'] = 1;
     }
     return $data;
  }

  function makeBet($vote_id = '', $wager_id, $stake, $host_score, $visitor_score, $old_stake=0, $old_host_score='', $old_visitor_score='', $sport_id) {
    global $_POST;
    global $_SERVER;
    global $auth;
    global $db;

    if (empty($stake))
      $stake = empty($old_stake) ? 0 : $old_stake;

    if (!$this->canBet($stake)) {
        // report that user exceeded bet limit
        return 3; // stake value is too high
    }
    if ($host_score =='' || $visitor_score=='') {
        return 4; // no stake
    }

    if ($host_score == $visitor_score && $sport_id==1) {
        return 4; // no stake
    }
    if ($stake < $old_stake)
        return 7; // no decrease
    if ($stake == $old_stake && $host_score == $old_host_score && $visitor_score==$old_visitor_score)
        return 8; // nothing has changed

//$db->showquery=true;
    $difference = $visitor_score - $host_score;
    if ($difference > 0)
      $choice = -1;
    else if ($difference < 0)
      $choice = 1;
    else $choice = 0; 

    $old_difference = $old_host_score - $old_visitor_score;
    if ($old_difference > 0)
      $old_choice = -1;
    else if ($old_difference < 0)
      $old_choice = 1;
    else $old_choice = 0; 

    if(empty($vote_id)) {
      if (isset($stake) && 
	    $this->canBet($stake) &&
	    isset($difference)) {
      // add vote
        $db->query("start transaction");
        unset($sdata);
	  $sdata['WAGER_ID'] = $wager_id;
	  $sdata['USER_ID'] = $auth->getUserId();
          $sdata['CHOICE'] = $choice;
	  $sdata['DATE_VOTED'] = "NOW()";
	  $sdata['STAKE'] = $stake;
	  $sdata['DIFFERENCE'] = $difference;
	  $sdata['HOST_SCORE'] = $host_score;
	  $sdata['VISITOR_SCORE'] = $visitor_score;
	  $sdata['IP'] = "'".$_SERVER["REMOTE_ADDR"]."'";
        $db->insert("wager_votes", $sdata);
        unset($sdata);
        $sdata['STAKES'] = "STAKES+".$stake;
        $db->update("wager_games", $sdata, "WAGER_ID=".$wager_id);
	$this->transact($stake, true);
        $wager_user_log = new WagerUserLog();
        $wager_user_log->logEvent ($auth->getUserId(), 3-$choice, $stake, $choice, $this->tseason_id, $wager_id);
	$db->query("commit");
        return 1;
      }
    } else if ($this->canChangeBet()) { // bet was already made
      if (isset($stake) && 
	    $this->canBet($stake) &&
	    isset($difference)) {
        $db->query("start transaction");
        unset($sdata);
        $sdata['CHOICE'] = $choice;
        $sdata['DATE_VOTED'] = "NOW()";
	$sdata['CHANGES'] = "CHANGES+1";
	$sdata['STAKE'] = $stake;
        $sdata['DIFFERENCE'] = $difference;
	$sdata['HOST_SCORE'] = $host_score;
        $sdata['VISITOR_SCORE'] = $visitor_score;
        $sdata['IP'] = "'".$_SERVER["REMOTE_ADDR"]."'";
        $db->update("wager_votes", $sdata, 'WAGER_ID='.$wager_id. " AND USER_ID=".$auth->getUserId());
        unset($sdata);
        if ($old_choice != $choice) { 
          $sdata['STAKES'] = "STAKES-".($old_stake-$stake);
          $db->update("wager_games", $sdata, "WAGER_ID=".$wager_id);
        } 
	$this->transact($stake-$old_stake, false);
        $wager_user_log = new WagerUserLog();
        $wager_user_log->logEvent ($auth->getUserId(), 3-$choice, $stake, $choice, $this->tseason_id, $wager_id);
	$db->query("commit");
        return 1;
      }
    } else if (!$this->canChangeBet()) {
      return 6; // not allowed to change bet
    }
    return 0; // unknown state
  }

  function transact($stake, $new) {
    global $db;
    global $auth;
    global $_SESSION;

    unset($sdata);
    $sdata['STAKES'] = $stake;
    if ($new) {
      $sdata['GAMES'] = "GAMES+1";
      $sdata['TOTAL_STAKES'] = "TOTAL_STAKES+".$stake;
    }
    $db->update('wager_users', $sdata, "USER_ID=".$auth->getUserId()." AND SEASON_ID=".$this->tseason_id);
    if ($stake > 0 && $new) {
      $credits = new Credits();
      $credits->updateCredits($auth->getUserId(), -1*$stake);
      $credit_log = new CreditsLog();
      $credit_log->logEvent ($auth->getUserId(), 5, $stake);
    }
  }

  function processMultipleBets() {
    global $db;
    global $auth;
    global $_SESSION;
    global $_POST;

    // prepare wagers
    $wagers = '';
    $pre = '';
    for ($c = 0; $c < sizeof($_POST['wager_ids']); $c++) {
      if (isset($_POST['wager_'.$_POST['wager_ids'][$c].'_host_score']) &&
	  isset($_POST['wager_'.$_POST['wager_ids'][$c].'_visitor_score'])) {
        $wagers .= $pre.$_POST['wager_ids'][$c];
        $pre = ',';
      }
    }

    if ($c == 0)
      return;

    $existing_bets = '';
    $sql="SELECT WG.*, WV.VOTE_ID, WV.STAKE, WV.DIFFERENCE, WV.HOST_SCORE, WV.VISITOR_SCORE,
		DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < NOW() EXPIRED , S.SPORT_ID
		from games G, seasons S, wager_games WG
		left join wager_votes WV ON WV.WAGER_ID=WG.WAGER_ID and WV.USER_ID=".$auth->getUserId()."
		WHERE WG.wager_id in (".$wagers.")
			AND G.SEASON_ID=S.SEASON_ID
			AND WG.GAME_ID=G.GAME_ID";
    $db->query($sql);   
    while ($row = $db->nextRow()) {
      $existing_bets[$row['WAGER_ID']] = $row;
    }

    $processed_bets = '';
    // process wagers
    for ($c = 0; $c < sizeof($_POST['wager_ids']); $c++) {
      if (isset($_POST['wager_'.$_POST['wager_ids'][$c].'_host_score']) &&
	  isset($_POST['wager_'.$_POST['wager_ids'][$c].'_visitor_score']) &&
	  isset($existing_bets[$_POST['wager_ids'][$c]]) && 
	  $existing_bets[$_POST['wager_ids'][$c]]['EXPIRED'] == 0) {
        $processed_bets[$_POST['wager_ids'][$c]] = $this->makeBet(!empty($existing_bets[$_POST['wager_ids'][$c]]['VOTE_ID']) ? $existing_bets[$_POST['wager_ids'][$c]]['VOTE_ID'] : '', $_POST['wager_ids'][$c], $_POST['wager_'.$_POST['wager_ids'][$c].'_stake'], $_POST['wager_'.$_POST['wager_ids'][$c].'_host_score'], $_POST['wager_'.$_POST['wager_ids'][$c].'_visitor_score'], $existing_bets[$_POST['wager_ids'][$c]]['STAKE'], $existing_bets[$_POST['wager_ids'][$c]]['HOST_SCORE'], $existing_bets[$_POST['wager_ids'][$c]]['VISITOR_SCORE'], $existing_bets[$_POST['wager_ids'][$c]]['SPORT_ID']);
      }
    }
    return $processed_bets;
  }

  function getGames($stage=2, $page=0, $page_size=10, $processed_bids = '') {
     global $db;
     global $auth;
     global $_GET;
     global $pagingbox;
     global $wager;

     $where_stage = '';
     $where_vote = '';
     $stage_id = '';
     $order = "ASC";	
     if ($stage == 0) { // past
	$where_stage=' AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < NOW() 
			AND G.SCORE1 > -1 AND G.SCORE2 > -1';
	$where_vote='AND WV.VOTE_ID IS NOT NULL';
        $stage_id='PAST';
	$order = "DESC";	
     } else if ($stage == 1) { // present   
	$where_stage=' AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < NOW() 
			AND WG.PROCESSED=0 ';
	$where_vote='AND WV.VOTE_ID IS NOT NULL';
        $stage_id='PRESENT';
     } else if ($stage == 2) { // future   
	$where_stage=' AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > NOW() ';
        $stage_id='FUTURE';
     }
//$db->showquery=true;
     // get number of different days
     $sql="SELECT COUNT( 
                    DISTINCT DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR),
                    '%Y-%m-%d')) DAYS_NO
		FROM wager_games WG, wager_seasons WS, seasons S, games G
		WHERE 
	  	   WG.GAME_ID=G.GAME_ID
	           AND G.SEASON_ID = S.SEASON_ID
                   AND WG.WSEASON_ID=WS.SEASON_ID
		   and G.START_DATE < WS.END_DATE
		   ".$where_stage."
		   AND WG.WSEASON_ID=".$this->tseason_id;
     $db->query($sql);    
     $row = $db->nextRow();
     $days_number = $row['DAYS_NO'];

      if (empty($_GET['page']))
        $page = 1;
      else $page = $_GET['page'];
      if (empty($perpage))
        $perpage = $page_size;
      else $perpage = $_GET['page_size'];

     $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;

     $sql="SELECT DISTINCT DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR),
                    '%Y-%m-%d') GAME_DATE
		FROM wager_games WG, wager_seasons WS, seasons S, games G
		WHERE 
	  	   WG.GAME_ID=G.GAME_ID
	           AND G.SEASON_ID = S.SEASON_ID
                   AND WG.WSEASON_ID=WS.SEASON_ID
		   and G.START_DATE < WS.END_DATE
		   ".$where_stage."
		   AND WG.WSEASON_ID=".$this->tseason_id.
		  " ORDER BY G.START_DATE ".$order.$limitclause;
     $db->query($sql);    
     $dates='';
     $pre='';
     while ($row = $db->nextRow()) {
	$dates .= $pre. "'".$row['GAME_DATE']."'";
	$pre=',';
     }

     $utc = $auth->getUserTimezoneName();
     $sql = "SELECT WG.WAGER_ID, WG.GAME_ID, WG.PUBLISH, TSD.TSEASON_TITLE, 
	  WG.STAKES1, WG.STAKES0, WG.`STAKES-1` as STAKES_1, WV.DIFFERENCE,
	  WV.HOST_SCORE, WV.VISITOR_SCORE, WG.STAKES,
          WG.START_DATE, WV.RETURN, WG.KOEFF,
	  DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d') GAME_DAY,
	  DATE_ADD(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) END_DATE,
          G.TEAM_ID1, G.TEAM_ID2, G.SCORE1, G.SCORE2,
          IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
          SD.SEASON_TITLE, WV.STAKE, WV.CHOICE, WV.VOTE_ID, S.SPORT_ID, WV.POINTS
        FROM
          wager_games WG
		left join wager_votes WV on WG.WAGER_ID=WV.WAGER_ID AND WV.USER_ID=".$auth->getUserId()."
		, wager_seasons TS
		left JOIN wager_seasons_details TSD ON TS.SEASON_ID = TSD.SEASON_ID  AND TSD.LANG_ID=".$_SESSION['lang_id']."
		, seasons S
		left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id']."
		, games G
              left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
		LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
              left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
		LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
        WHERE
          WG.GAME_ID=G.GAME_ID
          AND G.SEASON_ID = S.SEASON_ID
          AND WG.WSEASON_ID=TS.SEASON_ID
          AND WG.WSEASON_ID=".$this->tseason_id."
          ".$where_stage."
          ".$where_vote."
	  AND DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR),
                    '%Y-%m-%d') IN (".$dates.")
        ORDER BY G.START_DATE ".$order.", T1.TEAM_NAME";

//	echo $sql;
     $db->query($sql);    
     $c = 0;
     $days = array();
     while ($row = $db->nextRow()) {
       if (empty($row['STAKE']))
         $row['STAKE'] = 0;
       $game = $row;
       $game['TOTAL_STAKES'] = $game['STAKES1'] + $game['STAKES0'] + $game['STAKES_1'];
       $days[$row['GAME_DAY']]['GAME_DAY'] = $row['GAME_DAY'];
       $days[$row['GAME_DAY']]['INVISIBLE'] = 1;
       if (isset($days[$row['GAME_DAY']]['COUNTER']))	
         $days[$row['GAME_DAY']]['COUNTER']++;
       else $days[$row['GAME_DAY']]['COUNTER'] = 1;

       $game['UTC'] = $utc;
       if ($row['CHOICE'] == -1)
         $row['CHOICE'] = '_1';
       $game['CHOICE'.$row['CHOICE']] = 1;
       if ($row['CHOICE'] == 0 && $row['SPORT_ID'] == 2) 
         $game['DRAW']['CHOICE'.$row['CHOICE']] = 1;
        
       if ($row['STAKES1'] + $row['STAKES_1'] + $row['STAKES0'] == 0)        
         $stake_sum = 1;
       else $stake_sum = $row['STAKES1'] + $row['STAKES_1'] + $row['STAKES0'];

       $game['STAKE1'] = $row['STAKES1'] > 0 ? round(1 + ($stake_sum - $row['STAKES1']) / ($row['STAKES1']), 2) : $stake_sum;
       if ($row['SPORT_ID'] == 2) {
         $game['DRAW']['STAKE0'] = $row['STAKES0'] > 0 ? round(1 + ($stake_sum - $row['STAKES0']) / ($row['STAKES0']), 2) : $stake_sum;
         $game['DRAW']['WAGER_ID'] = $row['WAGER_ID'];
       }
       $game['STAKE_1'] = $row['STAKES_1'] > 0 ? round(1 + ($stake_sum - $row['STAKES_1']) / ($row['STAKES_1']), 2) : $stake_sum;

       if ($stage == 2) {
         if (!empty($processed_bids) && isset($processed_bids[$row['WAGER_ID']])) {
	   $wager->setStatus($processed_bids[$row['WAGER_ID']], $game);
         }
       }

       if ($stage == 0) { // calc winnings       
         $choice = 0;
         $win_coeff = 1.1;
         if ($row['SCORE1'] > $row['SCORE2']) {
           $choice = 1;
           $game['WINNER1']['WIN'] = 1;
           $game['KOEFF'] = 1 + $row['KOEFF'];
           if ($row['SCORE2'] - $row['SCORE1'] == $row['DIFFERENCE']) {
             $game['WINNER1']['WIN2'] = 1;
           }
         }
         else if ($row['SCORE1'] < $row['SCORE2']) {
              $choice = '_1';
              $game['WINNER_1']['WIN'] = 1;
              $game['KOEFF'] = 1 + $row['KOEFF'];
              if ($row['SCORE2'] - $row['SCORE1'] == $row['DIFFERENCE']) {
                $game['WINNER_1']['WIN2'] = 1;
              }
         }
         else {
           $game['WINNER0'] = 1;
           if ($row['DIFFERENCE'] == 0) {
             $game['WINNER0']['WIN2'] = 1;
           }
         }
         if ($row['CHOICE'] != $choice)
           $game['WINNINGS'] = 0;
         else 
           $game['WINNINGS'] = $win_coeff * $row['STAKE'];
       }

       if ($stage == 2) { // calc winnings       
       }
       $game[$stage_id] = 1;
       $days[$row['GAME_DAY']][$stage_id] = 1;
       $days[$row['GAME_DAY']]['GAMES'][] = $game;
     }
     $data['PAGING'] = $pagingbox->getPagingBox($days_number, isset($_GET['page']) ? $_GET['page'] : 0, $perpage);
     $data['DAYS'] = $days;
     if ($_SESSION['_user']['WAGER'][$this->tseason_id]['STAKE_SLIDER'] == 1) {
       $data['STAKE_SLIDER'] = 1;
     }
//$db->showquery=false;
     return $data;
  }

  function getLeaguesInvites() {
     global $db;
     global $auth;

     $league_invites = array();
   // get invitations
     $sql="SELECT ML.LEAGUE_ID, ML.TITLE, ML.ENTRY_FEE
             FROM wager_leagues ML, wager_leagues_members MLM
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
               AND ML.SEASON_ID=".$this->tseason_id." 
               AND MLM.USER_ID=".$auth->getUserId()."
               AND MLM.STATUS=3";
 
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


  function hasLeague() {
     global $_SESSION;
     return !empty($_SESSION['_user']['WAGER'][$this->tseason_id]['LEAGUE_ID']);
  }


  function getChallenges() {
     global $db;
     global $auth;
     global $page_size;
     global $_GET;
     global $wager_challenge_events_descr;

     if (empty($_GET['page2']))
       $page = 1;
     else $page = $_GET['page2'];
     if (empty($perpage))
       $perpage = $page_size;
     else $perpage = $_GET['page_size'];

     $add_cond = "";
     if ($auth->userOn())
       $add_cond = " OR (WC.STATUS =2 AND WC.USER2_ID =".$auth->getUserId()." )";

     $sql="SELECT COUNT(*) QUANT
             FROM wager_seasons W, wager_challenges WC, games G
             WHERE WC.SEASON_ID=".$this->tseason_id." 
               AND W.SEASON_ID=".$this->tseason_id."	
	       AND WC.GAME_ID=G.GAME_ID
 	       AND (WC.USER_ID =".$auth->getUserId()." 
		     OR WC.USER2_ID =".$auth->getUserId()." )
		AND WC.STATUS != 3";
//echo $sql;
     $db->query($sql);    
     $count = 0;
     if ($row = $db->nextRow()) {
       $count = $row['QUANT'];
     }
     // show players

     $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;
    // leagues
     $sql="SELECT WC.CHALLENGE_ID, WC.STAKE, WC.OUTCOME, S.SPORT_ID, WC.STATUS,
	  DATE_ADD(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) END_DATE,
          G.TEAM_ID1, G.TEAM_ID2, G.SCORE1, G.SCORE2,
          IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2,
          G.SCORE1, G.SCORE2, U.USER_NAME, U2.USER_NAME as USER_NAME2, WC.USER_ID, WC.USER2_ID
             FROM wager_seasons M, wager_challenges WC
                  left join users U on WC.USER_ID=U.USER_ID
                  left join users U2 on WC.USER2_ID=U2.USER_ID,
		  seasons S, games G
	              left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
			LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
	              left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
             WHERE WC.SEASON_ID=".$this->tseason_id." 
		AND WC.GAME_ID=G.GAME_ID
		AND S.SEASON_ID=G.SEASON_ID
                AND M.SEASON_ID=".$this->tseason_id." 
  	        AND (WC.USER_ID =".$auth->getUserId()." 
		     OR WC.USER2_ID =".$auth->getUserId()." )
		AND WC.STATUS != 3
	         ".$limitclause;
//echo $sql; 
    $db->query($sql);    
 
    $challenges = array();
    while ($row = $db->nextRow()) {
      $challenge=$row;
      // get text of challenge
      if ($row['SPORT_ID'] == 2)
        $challenge['DRAWABLE'] = 1;
      if ($row['OUTCOME'] == 0)
        $text = $wager_challenge_events_descr[2]; 
      else $text = $wager_challenge_events_descr[1]; 
      $text = str_replace("%u", $row['USER_NAME'], $text);
      if ($row['OUTCOME'] == 1)
        $text = str_replace("%t", $row['TEAM_NAME1'], $text);
      else if ($row['OUTCOME'] == -1)
        $text = str_replace("%t", $row['TEAM_NAME2'], $text);
      $text = str_replace("%m", $row['TEAM_NAME1']." - ".$row['TEAM_NAME2'], $text);
      if ($row['STATUS'] == 4) {
        $outcome = 0;
        if ($row['SCORE1'] > $row['SCORE2']) {
          $outcome = 1;
        }
        else if ($row['SCORE1'] < $row['SCORE2']) {
          $outcome = -1;
        }       
        $challenge['FINAL_OUTCOME'] = $outcome;
      }
      $challenge['ME'] = $auth->getUserName();
      $challenge['CHALLENGE'] = $text;
      $challenges[] = $challenge;
    }

    return $challenges;
  }

}

?>