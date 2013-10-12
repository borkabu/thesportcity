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

class Wager {
  var $tseason_id;
  var $default_money;
  var $season_info;
  var $utc;
  var $prize_fund;
  var $newsletter_id;
  var $season_over;
  var $leagues;
  var $title;
  
  function Wager($tseason_id = '') {
    if (empty($tseason_id))
      $this->getSeason();
    else $this->tseason_id = $tseason_id;
    $this->getSeasonDetails();
  }

  function getSeasonDetails() {
    global $db; 
    global $html_page;

    $sql = "SELECT WS.*, WS.END_DATE < NOW() ENDED, FD.TSEASON_TITLE, FD.PRIZES
   	      FROM wager_seasons WS, wager_seasons_details FD 
           WHERE WS.SEASON_ID=FD.SEASON_ID 
		AND FD.LANG_ID=".$_SESSION['lang_id']." 
                AND FD.SEASON_ID=".$this->tseason_id;

    $db->query($sql);
    if ($row = $db->nextRow()) {
      $this->season_info = $row;
      $this->default_money = $row['MONEY'];
      $this->prize_fund = $row['PRIZE_FUND'];
      $this->newsletter_id = $row['NEWSLETTER_ID'];
      $this->season_over= $row['ENDED'];
      $this->title = $row['TSEASON_TITLE'];
      $html_page->page_title = $this->title;
    }

  }

  function getSeason() {
    global $db;
    global $auth;
    global $_SESSION; 
    global $_GET; 
    global $_COOKIE;

    if (isset($_SESSION['_user']['WAGER_SEASON_ID']) && !isset($_GET['season_id'])) {
      $this->tseason_id = $_SESSION['_user']['WAGER_SEASON_ID'];
    } else if (!isset($_GET['season_id']) && isset($_COOKIE['wager_season'])) {
      $this->tseason_id = $_COOKIE['wager_season'];
    } else if (!isset($_GET['season_id'])) {
      $db->select("wager_seasons", "*", "START_DATE < NOW() AND END_DATE > NOW()");
      if ($row = $db->nextRow()) {
        $this->tseason_id = $row['SEASON_ID'];
      }
    }
    else {
	$this->tseason_id = $_GET['season_id'];
    }

    if ($auth->userOn()) {
      $_SESSION['_user']['WAGER_SEASON_ID'] = $this->tseason_id;
    }

    setcookie('wager_season', $this->tseason_id, time()+3600*24*365);
    return $this->tseason_id;
  }

  function getPrizes() {
    global $_SESSION;
    global $db;

    $sql = "SELECT FD.PRIZES
   	      FROM wager_seasons_details FD 
           WHERE  FD.LANG_ID=".$_SESSION['lang_id']." 
                AND FD.SEASON_ID=".$this->tseason_id;

    $db->query($sql);
    if ($row = $db->nextRow()) {
      return $row['PRIZES'];
    }
    return '';
  }

  function getTitle() {
    return $this->title;
  }

  function getAllStakes() {
    global $_SESSION;
    global $db;

    $sql = "SELECT ROUND(SUM(MU.STAKES), 2) STAKES
   	      FROM wager_users MU 
           WHERE  MU.SEASON_ID=".$this->tseason_id;

    $db->query($sql);
    if ($row = $db->nextRow()) {
      return $row['STAKES'];
    }
    return 0;
  }

  function getUser($user_id) {
    global $db;
    global $_SESSION;

    $sql = "SELECT MU.MONEY, MS.WEALTH, MS.PLACE, L.ID, L.SHORT_CODE, U.USER_NAME, U.USER_ID
             FROM wager_users MU 
			left join wager_standings MS ON MU.user_id=MS.USER_ID and MS.SEASON_ID=MU.SEASON_ID and MU.SEASON_ID=".$this->tseason_id."
			left join users U on U.USER_ID=MU.USER_ID
			left join languages L on U.LAST_LANG=L.SHORT_CODE
             WHERE MU.USER_ID=".$user_id." AND MU.SEASON_ID=".$this->tseason_id;
    $db->query($sql); 
    if ($row = $db->nextRow()) {
      return $row;
    }
    return '';
  }

  function getLeagues() {
     global $db;
     global $auth;
     global $page_size;
     global $_GET;
     global $wager_league_point_types;

     if (empty($_GET['page2']))
       $page = 1;
     else $page = $_GET['page2'];
     if (empty($perpage))
       $perpage = $page_size;
     else $perpage = $_GET['page_size'];
   
     $sql="SELECT COUNT(*) QUANT
             FROM wager_leagues_members MLM, wager_seasons M, wager_leagues ML
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
               AND MLM.STATUS=1
               AND ML.SEASON_ID=".$this->tseason_id." 
               AND M.SEASON_ID=".$this->tseason_id;
     $db->query($sql);    
     $count = 0;
     if ($row = $db->nextRow()) {
       $count = $row['QUANT'];
     }
     // show players
     $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;
    // leagues
     $sql="SELECT ML.LEAGUE_ID, ML.TOPIC_ID, ML.TITLE, MLM.STATUS, ML.SEASON_ID, 'y' as ALL_LEAGUES, U.USER_NAME, T.POSTS, 
                  COUNT(MLM2.USER_ID) USERS, ML.RATING, U.LEAGUE_OWNER_RATING, ML.PARTICIPANTS, ML.ENTRY_FEE, 
		  ML.RECRUITMENT_ACTIVE, ML.REAL_PRIZES, ML.INVITE_TYPE, ML.POINT_TYPE
             FROM wager_leagues_members MLM, wager_seasons M, users U, wager_leagues ML
		LEFT JOIN topic T ON ML.topic_id=T.topic_id 
                  LEFT JOIN wager_leagues_members MLM2 ON ML.LEAGUE_ID=MLM2.LEAGUE_ID AND (MLM2.STATUS in (1,2))
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
               AND ML.SEASON_ID=".$this->tseason_id." 
               AND M.SEASON_ID=".$this->tseason_id." 
               AND MLM.STATUS=1
               and MLM.USER_ID=U.USER_ID
	     GROUP BY MLM.LEAGUE_ID ORDER BY ML.TITLE, MLM.STATUS ASC ".
	     $limitclause;
//echo $sql; 
    $db->query($sql);    
 
    $wleagues = array();
    
    while ($row = $db->nextRow()) {
      unset($league);
      if ($row['PARTICIPANTS'] == 0)
	$row['PARTICIPANTS'] = "&infin;";

      if (!empty($row['CCTLD'])) {
        $row['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $row['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }
      if ($row['REAL_PRIZES'] == 'Y') {
        $row['PRIZES'] = 1;
      }

      if ($row['RECRUITMENT_ACTIVE'] == 'Y') {
        $row['RECRUITMENT_ON'] = 1;
      } else
        $row['RECRUITMENT_OFF'] = 1;

      if ($row['PARTICIPANTS'] > 0)
	$row['PROGRESS']['PERCENTS'] = $row['USERS'] *100/ $row['PARTICIPANTS'];	

      $league= $row;
      if ($row['STATUS'] == 2) {
        $league['LEAGUE'] = $row;
      }
      else if ($row['STATUS'] == 1) {
        $league['OWN_LEAGUE'] = $row;
      }

      if ($row['REAL_PRIZES'] == 'Y') {
        $league['PRIZES'] = 1;
      }

      if (empty($row['POSTS']))
       $league['POSTS'] = 0;

      $league['POINT_TYPE_DESCR'] = $wager_league_point_types[$row['POINT_TYPE']]; 
      $wleagues[] = $league;
    }

    $this->leagues = $count;
    return $wleagues;
  }  


  function setStatus($status, &$wager) {
    global $langs;
//    print_r($wager);
    if ($status == 1)
      $wager['MESSAGE']['MSG'] = $langs['LANG_WAGER_ACCEPTED_U'];
    else if ($status == 2)
      $wager['ERROR']['MSG'] = $langs['LANG_WAGER_NOT_ENOUGH_MONEY_U'];
    else if ($status == 3)
      $wager['ERROR']['MSG'] = $langs['LANG_WAGER_STAKE_TOO_HIGH_U'];
    else if ($status == 4)
      $wager['ERROR']['MSG'] = $langs['LANG_WAGER_NO_CHOICE_U'];
    else if ($status == 5)
      $wager['ERROR']['MSG'] = $langs['LANG_WAGER_NO_STAKE_U'];
    else if ($status == 6)
      $wager['ERROR']['MSG'] = $langs['LANG_WAGER_NO_BET_CHANGE_U'];
    else if ($status == 7)
      $wager['ERROR']['MSG'] = $langs['LANG_WAGER_HIGHER_BET_CHANGE_U'];

  }

  function getChallenges($type=1) {
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

     $where_type = " AND WC.STATUS < 4";
     if ($type == 2) {
       $where_type = " AND WC.STATUS = 4";
     }
     $sql="SELECT COUNT(*) QUANT
             FROM wager_seasons W, wager_challenges WC, games G
             WHERE WC.SEASON_ID=".$this->tseason_id." 
               AND W.SEASON_ID=".$this->tseason_id."	
	       AND WC.GAME_ID=G.GAME_ID
	       AND ((DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > NOW())
                    ".$add_cond.") ".$where_type;
//echo $sql;
     $db->query($sql);    
     $count = 0;
     if ($row = $db->nextRow()) {
       $count = $row['QUANT'];
     }
     // show players

     $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;
    // leagues
     if ($type==1) {
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
  	        AND ((DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > NOW())
                     ".$add_cond.") ".$where_type.$limitclause;
     } else {
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
                AND M.SEASON_ID=".$this->tseason_id.$where_type.
		" ORDER BY END_DATE DESC".$limitclause;
     } 
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
      $challenge['CHALLENGE'] = $text;
      if (empty($row['USER2_ID']) && $row['USER_ID'] != $auth->getUserId() && $row['USER2_ID'] != $auth->getUserId() &&
          $auth->getCredits() >= $row['STAKE'])
        $challenge['CAN_ACCEPT'] = 1;
      else if (!empty($row['USER2_ID'])) {
         $challenge['ACCEPTED'] = 1; 
      }
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

      $challenges[] = $challenge;
    }

    return $challenges;
  }

  function getGamesChallenges($page=0, $page_size=50, $type = 0) {
     global $db;
     global $auth;
     global $_GET;
     global $pagingbox;
     global $wager_challenge_events_descr;

     $where_stage = '';
     $where_vote = '';
     $stage_id = '';
     $order = "ASC";	

     $where_stage=' AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > NOW() ';

     // get number of different days
     $sql="SELECT COUNT(G.GAME_ID) GAMES
		FROM wager_games WG, wager_seasons WS, wager_subseasons WSS,
			seasons S, games G
		WHERE 
	  	   WG.GAME_ID=G.GAME_ID
	           AND G.SEASON_ID = S.SEASON_ID
		   AND WSS.SEASON_ID= S.SEASON_ID
                   AND WG.WSEASON_ID=WS.SEASON_ID
		   and G.START_DATE < WS.END_DATE
		   ".$where_stage."
		   AND WG.WSEASON_ID=".$this->tseason_id;
     $db->query($sql);    
     $row = $db->nextRow();
     $days_number = $row['GAMES'];

      if (empty($_GET['page']))
        $page = 1;
      else $page = $_GET['page'];
      if (empty($perpage))
        $perpage = $page_size;
      else $perpage = $_GET['page_size'];

     $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;

     $where_user = " and (WC.USER_ID=".$auth->getUserId()." OR WC.USER2_ID =".$auth->getUserId().") ";
     if ($auth->userOn() && $type == 2)
       $where_user = " and (WC.USER_ID=".$auth->getUserId()." OR WC.USER2_ID =".$auth->getUserId().") ";
     
     if ($type == 1 || $type == 3) {
       $where_stage .= " AND WC.STATUS in (1)";
     }

     if ($type == 2) {
       $where_stage .= " AND WC.STATUS in (2)";
     }

     if ($type == 3) {
       $where_user = "";
     }

     $utc = $auth->getUserTimezoneName();
     $sql = "SELECT WG.WAGER_ID, WG.GAME_ID, WG.PUBLISH, TSD.TSEASON_TITLE, WG.START_DATE, WC.STAKE, WC.USER_ID, WC.USER2_ID, WC.STATUS,
	  DATE_ADD(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) END_DATE,
          G.TEAM_ID1, G.TEAM_ID2, G.SCORE1, G.SCORE2, WC.OUTCOME, WC.STATUS,
          IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
          SD.SEASON_TITLE, S.SPORT_ID, G.SCORE1, G.SCORE2, U.USER_NAME, U2.USER_NAME as USER_NAME2
        FROM
          wager_games WG
		left join wager_challenges WC on WC.game_id=WG.GAME_ID ".$where_user."
                  left join users U on WC.USER_ID=U.USER_ID
                  left join users U2 on WC.USER2_ID=U2.USER_ID
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
          AND G.START_DATE > NOW()
          AND G.SEASON_ID = S.SEASON_ID
          AND WG.WSEASON_ID=TS.SEASON_ID
          AND WG.WSEASON_ID=".$this->tseason_id."
          ".$where_stage."
        ORDER BY G.START_DATE ".$order.", T1.TEAM_NAME ".$limitclause;

//    echo $sql;
     $db->query($sql);    
     $c = 0;
     $games = array();
     while ($row = $db->nextRow()) {
       $game = $row;

       if ($row['SPORT_ID'] == 2)
         $game['DRAWABLE'] = 1;

       $game['UTC'] = $utc;
       if (empty($row['USER_ID']))
         $game['CAN_CHALLENGE'] = 1;
       else if (empty($row['USER2_ID']) && $row['USER_ID']==$auth->getUserId()) {
         $game['CAN_WITHDRAW'] = 1;
         if ($row['OUTCOME'] == 0)
           $text = $wager_challenge_events_descr[2]; 
         else $text = $wager_challenge_events_descr[1]; 
         $text = str_replace("%u", $row['USER_NAME'], $text);
         if ($row['OUTCOME'] == 1)
           $text = str_replace("%t", $row['TEAM_NAME1'], $text);
         else if ($row['OUTCOME'] == -1)
           $text = str_replace("%t", $row['TEAM_NAME2'], $text);
         $text = str_replace("%m", $row['TEAM_NAME1']." - ".$row['TEAM_NAME2'], $text);
         $game['CHALLENGE'] = $text;
       }
       else {
         $game['ACCEPTED'] = 1;
         if ($row['OUTCOME'] == 0)
           $text = $wager_challenge_events_descr[2]; 
         else $text = $wager_challenge_events_descr[1]; 
         $text = str_replace("%u", $row['USER_NAME'], $text);
         if ($row['OUTCOME'] == 1)
           $text = str_replace("%t", $row['TEAM_NAME1'], $text);
         else if ($row['OUTCOME'] == -1)
           $text = str_replace("%t", $row['TEAM_NAME2'], $text);
         $text = str_replace("%m", $row['TEAM_NAME1']." - ".$row['TEAM_NAME2'], $text);
         $game['CHALLENGE'] = $text;
       }
       if (!empty($row['USER_ID']))
         $game['CHALLENGE_THROWN'] = 1;
       $games[] = $game;
     }
//print_r($games);
     $data['PAGING'] = $pagingbox->getPagingBox($days_number, isset($_GET['page']) ? $_GET['page'] : 0, $perpage);
     $data['GAMES'] = $games;

//$db->showquery=false;
//print_r($data);
     return $data;
  }

  function getGames($stage=2, $page=0, $page_size=10) {
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
      if (empty($_GET['page']))
        $page = 1;
      else $page = $_GET['page'];
      if (empty($perpage))
        $perpage = $page_size;
      else $perpage = $_GET['page_size'];

     $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;

     $utc = $auth->getUserTimezoneName();
     $sql = "SELECT WG.WAGER_ID, WG.GAME_ID, WG.PUBLISH, TSD.TSEASON_TITLE, 
	  WG.STAKES1, WG.STAKES0, WG.`STAKES-1` as STAKES_1, G.START_DATE, WG.STAKES,
	  DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d') GAME_DAY,
	  DATE_ADD(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) END_DATE,
          G.TEAM_ID1, G.TEAM_ID2, G.SCORE1, G.SCORE2,
          IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
          SD.SEASON_TITLE, S.SPORT_ID
        FROM
          wager_games WG
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
        ORDER BY G.START_DATE ".$order.", T1.TEAM_NAME ".$limitclause;

//	echo $sql;
     $db->query($sql);    
     $c = 0;
     $days = array();
     $games = array();
     while ($row = $db->nextRow()) {
       $game = $row;
       $game['UTC'] = $utc;
       $games[] = $game;
     }
     $data['GAMES'] = $games;

//$db->showquery=false;
     return $data;
  }

  function getChallengeSummary() {
     global $db;
     global $auth;

    $data = "";
   // get invitations
     $sql="SELECT COUNT(CHALLENGE_ID) CHALLENGES
             FROM wager_challenges WC, wager_games WG
             WHERE WC.GAME_ID=WG.GAME_ID
               AND WG.WSEASON_ID=".$this->tseason_id." 
               AND WC.STATUS=2";
    $db->query($sql);    
    if ($row = $db->nextRow()) {
      $data['ACCEPTED_CHALLENGES'] = $row['CHALLENGES'];
    }
    $db->free();
    $sql="SELECT COUNT(CHALLENGE_ID) CHALLENGES
             FROM wager_challenges WC, wager_games WG
             WHERE WC.GAME_ID=WG.GAME_ID
               AND WG.WSEASON_ID=".$this->tseason_id." 
               AND WC.STATUS=1";
 
    $db->query($sql);    
    if ($row = $db->nextRow()) {
      $data['OPEN_CHALLENGES'] = $row['CHALLENGES'];
    }

    return $data;
  }

  function generateTours($league_id, $duration, $tour_duration, $start_date) {
    global $db;

    for ($i = 0; $i < $duration; $i++) {
      unset($sdata);
      $sdata['START_DATE'] = "DATE_ADD('".$start_date."', INTERVAL ".$tour_duration*($i)." DAY)";
      $sdata['END_DATE'] = "DATE_ADD('".$start_date."', INTERVAL ".$tour_duration*($i+1)." DAY)";
      $sdata['TOUR_ID'] = $i+1;
      $sdata['LEAGUE_ID'] = $league_id;
      $db->insert("wager_league_tours", $sdata);
    } 
   
  }

  function getLeagueTours($league_id) {
    global $db;
    global $auth;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT WLT.TOUR_ID, 
  	        DATE_ADD(WLT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) START_DATE,
  	        DATE_ADD(WLT.END_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) END_DATE,
		'".$utc."' as UTC, G.TEAM_ID1, G.TEAM_ID2, G.SCORE1, G.SCORE2,
		DATE_ADD(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) GAME_END_DATE,
		IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
	        SD.SEASON_TITLE, S.SPORT_ID, 
		NOW() < WLT.END_DATE and NOW() > WLT.START_DATE as INTOUR,
		NOW() > WL.START_DATE as LEAGUE_STARTED
	   FROM wager_league_tours WLT, wager_leagues WL, wager_games WG, wager_seasons TS, seasons S
		left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id']."
		, games G
              left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
		LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
              left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
		LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."

	   WHERE WG.GAME_ID=G.GAME_ID	
		  AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > WLT.START_DATE
		  AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < WLT.END_DATE
	          AND G.SEASON_ID = S.SEASON_ID
        	  AND WG.WSEASON_ID=TS.SEASON_ID
	          AND WG.WSEASON_ID=WL.SEASON_ID
		  AND WL.LEAGUE_ID=WLT.LEAGUE_ID
		  AND WLT.LEAGUE_ID=".$league_id."

	   ORDER BY G.START_DATE";
//	echo $sql;

    $db->query($sql);        
    $tours = array();
    while ($row = $db->nextRow()) {
      $game = $row;         
      if (!isset($tours[$row['TOUR_ID']]))
        $tours[$row['TOUR_ID']] = $row;
      $tours[$row['TOUR_ID']]['GAMES'][] = $game;
      if ($row['LEAGUE_STARTED'] && $row['INTOUR']) {
        $tours[$row['TOUR_ID']]['INVISIBLE'] = 1;
        $tours[$row['TOUR_ID']]['VISIBLE_DIV'] = 1;
      } else if (!$row['LEAGUE_STARTED'] && $row['TOUR_ID'] == 1) {
        $tours[$row['TOUR_ID']]['INVISIBLE'] = 1;
        $tours[$row['TOUR_ID']]['VISIBLE_DIV'] = 1;
      } else if ($row['LEAGUE_STARTED'] && !$row['INTOUR']) {
        $tours[$row['TOUR_ID']]['VISIBLE'] = 1;
        $tours[$row['TOUR_ID']]['INVISIBLE_DIV'] = 1;
      } else {
        $tours[$row['TOUR_ID']]['VISIBLE'] = 1;
        $tours[$row['TOUR_ID']]['INVISIBLE_DIV'] = 1;
      }
    }
//print_r($tours);
    return $tours;
  }

  function checkLeagueActive($league_id) {
    global $db;

    $sql = "SELECT * FROM wager_leagues WL, wager_league_tours WLT 
		WHERE WL.LEAGUE_ID = WLT.LEAGUE_ID 
			and WLT.END_DATE < NOW()
			AND WL.STATUS = 2
			AND WL.LEAGUE_ID = ".$league_id."
			AND WLT.TOUR_ID=WL.DURATION";
    $db->query($sql);        
    if ($row = $db->nextRow()) {
      // it is past due
      // check that all results have been calculated
      $sql="SELECT COUNT(G.GAME_ID) GAMES
	   FROM wager_league_tours WLT, wager_leagues WL, wager_games WG, wager_seasons TS, seasons S
		, games G
	   WHERE WG.GAME_ID=G.GAME_ID	
		  AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > WLT.START_DATE
		  AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < WLT.END_DATE
	          AND G.SEASON_ID = S.SEASON_ID
        	  AND WG.WSEASON_ID=TS.SEASON_ID
	          AND WG.WSEASON_ID=WL.SEASON_ID
		  AND WL.LEAGUE_ID=WLT.LEAGUE_ID
		  AND WLT.LEAGUE_ID=".$league_id."
		  AND G.SCORE1 = -1 and G.SCORE2 = -1";
      $db->query($sql);        
      if ($row = $db->nextRow()) {
        if ($row['GAMES'] == 0) {
          // all scores are in
          unset($sdata);
          $sdata['STATUS']=3;
          $db->update("wager_leagues", $sdata, "LEAGUE_ID=".$league_id);
        }
 
      } 
    }
  }
}

?>