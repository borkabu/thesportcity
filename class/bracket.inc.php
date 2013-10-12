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

class Bracket {
  var $tseason_id;
  var $utc;
  var $season_info;
  var $prize_fund;
  var $newsletter_id;
  var $season_over;
  var $title;
  var $seasonlist;
  var $last_tour;
  var $next_tour;
  var $next_tour_date;
  var $next_tour_date_utc;
  
  function Bracket($tseason_id = '') {
    if (empty($tseason_id))
      $this->getSeason();
    else $this->tseason_id = $tseason_id;
    $this->getSeasonDetails();
  }

  function getSeasonDetails() {
    global $db; 
    global $html_page;

    $sql = "SELECT WS.*, WS.END_DATE < NOW() ENDED, FD.TSEASON_TITLE, FD.PRIZES
   	      FROM bracket_seasons WS, bracket_seasons_details FD 
           WHERE WS.SEASON_ID=FD.SEASON_ID 
		AND FD.LANG_ID=".$_SESSION['lang_id']." 
                AND FD.SEASON_ID=".$this->tseason_id;

    $db->query($sql);
    if ($row = $db->nextRow()) {
      $this->season_info = $row;
      $this->prize_fund = $row['PRIZE_FUND'];
      $this->newsletter_id = $row['NEWSLETTER_ID'];
      $this->season_over= $row['ENDED'];
      $this->title = $row['TSEASON_TITLE'];
      $html_page->page_title = $this->title;
    }

   // get seasons
    $db->select('bracket_subseasons', 'SEASON_ID', 'WSEASON_ID='.$this->tseason_id);
    $c = 0;
    $this->seasonlist = '';
    $pre = '';
    while ($row = $db->nextRow()) {
      $this->seasonlist .= $pre.$row['SEASON_ID'];
      $pre = ',';

      $c++;
    }
    $db->free();

  }

  function getSeason() {
    global $db;
    global $auth;
    global $_SESSION; 
    global $_GET; 
    global $_COOKIE;

    if (isset($_SESSION['_user']['BRACKET_SEASON_ID']) && !isset($_GET['tseason_id'])) {
      $this->tseason_id = $_SESSION['_user']['BRACKET_SEASON_ID'];
    } else if (!isset($_GET['tseason_id']) && isset($_COOKIE['bracket_season'])) {
      $this->tseason_id = $_COOKIE['bracket_season'];
    } else if (!isset($_GET['tseason_id'])) {
      $db->select("bracket_seasons", "*", "START_DATE < NOW() AND END_DATE > NOW()");
      if ($row = $db->nextRow()) {
        $this->tseason_id = $row['SEASON_ID'];
      }
    }
    else {
	$this->tseason_id = $_GET['tseason_id'];
    }

    if ($auth->userOn()) {
      $_SESSION['_user']['BRACKET_SEASON_ID'] = $this->tseason_id;
    }

    setcookie('bracket_season', $this->tseason_id, time()+3600*24*365);
    return $this->tseason_id;
  }

  function getPrizes() {
    global $_SESSION;
    global $db;

    $sql = "SELECT FD.PRIZES
   	      FROM bracket_seasons_details FD 
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

  function getUser($user_id) {
    global $db;
    global $_SESSION;

    $sql = "SELECT MS.PLACE, L.ID, L.SHORT_CODE, U.USER_NAME, U.USER_ID
             FROM bracket_users MU 
			left join bracket_standings MS ON MU.user_id=MS.USER_ID and MS.SEASON_ID=MU.SEASON_ID and MU.SEASON_ID=".$this->tseason_id."
			left join users U on U.USER_ID=MU.USER_ID
			left join languages L on U.LAST_LANG=L.SHORT_CODE
             WHERE MU.USER_ID=".$user_id." AND MU.SEASON_ID=".$this->tseason_id;
    $db->query($sql); 
    if ($row = $db->nextRow()) {
      return $row;
    }
    return '';
  }

  function racesLeft() {
    global $db;
    $sql="SELECT COUNT(G.GAME_ID) AS GAMES
		FROM bracket_subseasons BS, bracket_tours BT, seasons S, games_races G 
		WHERE BT.season_id=".$this->tseason_id." 
			and G.season_id=S.SEASON_ID 
			and G.PUBLISH='Y' 
			and BT.START_DATE < G.start_DATE 
			and BT.END_DATE > G.START_DATE 
			AND BS.WSEASON_ID=BT.SEASON_ID 
			AND BS.SEASON_ID=S.SEASON_ID 
			AND BT.START_DATE > NOW( )";
    $db->query($sql); 
    $row = $db->nextRow();
    return $row['GAMES'];
  }

  function racesPast() {
    global $db;
    $sql="SELECT COUNT(G.GAME_ID) AS GAMES
		FROM bracket_subseasons BS, bracket_tours BT, seasons S, games_races G 
		WHERE BT.season_id=".$this->tseason_id." 
			and G.season_id=S.SEASON_ID 
			and G.PUBLISH='Y' 
			and BT.START_DATE < G.start_DATE 
			and BT.END_DATE > G.START_DATE 
			AND BS.WSEASON_ID=BT.SEASON_ID 
			AND BS.SEASON_ID=S.SEASON_ID 
			AND BT.START_DATE < NOW( )";
    $db->query($sql); 
    $row = $db->nextRow();
    return $row['GAMES'];
  }

  function getNextRaceID() {
    global $db;
    $sql="SELECT G.GAME_ID
		FROM bracket_subseasons BS, bracket_tours BT, seasons S, games_races G 
		WHERE BT.season_id=".$this->tseason_id." 
			and G.season_id=S.SEASON_ID 
			and G.PUBLISH='Y' 
			and BT.START_DATE < G.start_DATE 
			and BT.END_DATE > G.START_DATE 
			AND BS.WSEASON_ID=BT.SEASON_ID 
			AND BS.SEASON_ID=S.SEASON_ID 
			AND BT.START_DATE > NOW( )
		ORDER BY BT.START_DATE 
		LIMIT 1";
//echo $sql;
    $db->query($sql); 
    if ($row = $db->nextRow())
      return $row['GAME_ID'];
  }

  function getNextTour() {
    global $db;
    $sql="SELECT BT.NUMBER
		FROM bracket_subseasons BS, bracket_tours BT, seasons S, games_races G 
		WHERE BT.season_id=".$this->tseason_id." 
			and G.season_id=S.SEASON_ID 
			and G.PUBLISH='Y' 
			and BT.START_DATE < G.start_DATE 
			and BT.END_DATE > G.START_DATE 
			AND BS.WSEASON_ID=BT.SEASON_ID 
			AND BS.SEASON_ID=S.SEASON_ID 
			AND BT.START_DATE > NOW( )
		ORDER BY BT.START_DATE 
		LIMIT 1";
//echo $sql;
    $db->query($sql); 
    if ($row = $db->nextRow())
      return $row['NUMBER'];
  }

  function getPrevRaceID() {
    global $db;
    $sql="SELECT G.GAME_ID
		FROM bracket_subseasons BS, bracket_tours BT, seasons S, games_races G 
		WHERE BT.season_id=".$this->tseason_id." 
			and G.season_id=S.SEASON_ID 
			and G.PUBLISH='Y' 
			and BT.START_DATE < G.start_DATE 
			and BT.END_DATE > G.START_DATE 
			AND BS.WSEASON_ID=BT.SEASON_ID 
			AND BS.SEASON_ID=S.SEASON_ID 
			AND BT.START_DATE < NOW( )
		ORDER BY BT.START_DATE DESC
		LIMIT 1";
    $db->query($sql); 
    if ($row = $db->nextRow())
      return $row['GAME_ID'];
  }

  function getLastTour() {
    global $db;
    global $auth;

    $sql = "SELECT *
             FROM bracket_tours 
             WHERE NOW() >= START_DATE 
                   AND NOW() <= END_DATE
                   AND SEASON_ID=".$this->tseason_id;
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $this->last_tour = $row['NUMBER'];   
      return empty($row['NUMBER']) ? "0" : $row['NUMBER'];
    }
    else {
      $sql = "SELECT NUMBER, START_DATE,
                    DATE_ADD(START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE
               FROM bracket_tours 
               WHERE NOW() >= END_DATE
                     AND SEASON_ID=".$this->tseason_id."
              ORDER BY NUMBER DESC";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $this->last_tour = $row['NUMBER'];   
        $this->next_tour_date = $row['START_DATE'];   
        $this->next_tour_date_utc = $row['TOUR_START_DATE'];   
        $this->utc = $auth->getUserTimezoneName();
        return empty($row['NUMBER']) ? "0" : $row['NUMBER'];
      }

    }
    return "0";
  }

  function getToursSchedule($nearest_tour = '') {
      global $db;
      global $auth;

      $utc = $auth->getUserTimezoneName();
      $sql="SELECT MT.NUMBER, 
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
	     DATE_ADD(MT.END_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE
	     FROM bracket_tours MT
	    WHERE MT.season_id=".$this->tseason_id."
            ORDER BY MT.NUMBER";

      $db->query($sql);
      $c = 0;
      $tours = array();
      while ($row = $db->nextRow()) {
         unset($tour);
         $tour['NUMBER'] = $row['NUMBER'];
         $tour['TOUR_START_DATE'] = $row['TOUR_START_DATE'];
         $tour['TOUR_END_DATE'] = $row['TOUR_END_DATE'];
         $tour['UTC'] = $utc;
         $c++;
         $tours[] = $tour; 
       }
      $db->free();

     $sql="SELECT G.GAME_ID, G.TITLE,
	     DATE_ADD(G.START_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE,
	     MT.NUMBER, 
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
  	     DATE_ADD(MT.END_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE
	     FROM  bracket_tours MT, seasons S, games_races G
	    WHERE MT.season_id=".$this->tseason_id."
	      and S.season_id in (".$this->seasonlist.") 
	      and G.season_id=S.SEASON_ID
		and G.PUBLISH='Y'
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
            ORDER BY MT.NUMBER, G.START_DATE";
//echo $sql;
      $db->query($sql);
      $c = 0;
      while ($row = $db->nextRow()) {
         $tours[$row['NUMBER']-1]['TITLE'] = $row['TITLE'];
       }
      return $tours;
  }

  function getTourSchedule($nearest_tour = '') {
      global $db;
      global $auth;

      $utc = $auth->getUserTimezoneName();
      $sql="SELECT MT.NUMBER, 
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
	     DATE_ADD(MT.END_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE
	     FROM bracket_tours MT
	    WHERE MT.season_id=".$this->tseason_id."
                  AND MT.NUMBER =".$nearest_tour;
//echo $sql;
      $db->query($sql);
      $c = 0;
      $tour = '';
      if ($row = $db->nextRow()) {
        unset($tour);
        $tour['NUMBER'] = $row['NUMBER'];
        $tour['TOUR_START_DATE'] = $row['TOUR_START_DATE'];
        $tour['TOUR_END_DATE'] = $row['TOUR_END_DATE'];
        $tour['UTC'] = $utc;
      }
      $db->free();

      $sql="SELECT G.GAME_ID, G.TITLE,
	     DATE_ADD(G.START_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE,
	     MT.NUMBER, 
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
  	     DATE_ADD(MT.END_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE
	     FROM  bracket_tours MT, seasons S, games_races G
	    WHERE MT.season_id=".$this->tseason_id."
	      and S.season_id in (".$this->seasonlist.") 
	      and G.season_id=S.SEASON_ID
		and G.PUBLISH='Y'
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
              AND MT.NUMBER =".$nearest_tour."
            ORDER BY MT.NUMBER, G.START_DATE";
      $db->query($sql);
      $c = 0;
      while ($row = $db->nextRow()) {
         $game = $row;
         $game['UTC'] = $utc;
         $tour['GAMES'][] = $game;
       }
      return $tour;
  }


  function getLeagues() {
     global $db;
     global $auth;
     global $page_size;
     global $_GET;

     if (empty($_GET['page2']))
       $page = 1;
     else $page = $_GET['page2'];
     if (empty($perpage))
       $perpage = $page_size;
     else $perpage = $_GET['page_size'];
   
     $sql="SELECT COUNT(*) QUANT
             FROM bracket_seasons M, bracket_leagues ML
             WHERE ML.SEASON_ID=".$this->tseason_id." 
               AND M.SEASON_ID=".$this->tseason_id;
     $db->query($sql);    
     $count = 0;
     if ($row = $db->nextRow()) {
       $count = $row['QUANT'];
     }
     // show players
     $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;
    // leagues
     $mleagues = array();
     $sql="SELECT ML.LEAGUE_ID, ML.TOPIC_ID, ML.TITLE, ML.ENTRY_FEE, MLM.STATUS, ML.SEASON_ID, 'y' as ALL_LEAGUES, U.USER_NAME, T.POSTS, 
		  COUNT(MLM2.USER_ID) USERS, ML.RATING, U.LEAGUE_OWNER_RATING,
	          ML.PARTICIPANTS, ML.RECRUITMENT_ACTIVE, ML.ACCEPT_NEWBIES, ML.REAL_PRIZES, ML.TYPE, C.CCTLD, CD.COUNTRY_NAME
             FROM bracket_leagues_members MLM, bracket_seasons M, users U, 
		bracket_leagues ML
		  LEFT JOIN topic T ON ML.topic_id=T.topic_id 
                  LEFT JOIN bracket_leagues_members MLM2 ON ML.LEAGUE_ID=MLM2.LEAGUE_ID AND (MLM2.STATUS in (1,2))
                  LEFT JOIN countries C ON ML.COUNTRY = C.ID
		  LEFT JOIN countries_details CD ON C.ID=CD.ID aND CD.LANG_ID=".$_SESSION['lang_id']."
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
               AND ML.SEASON_ID=".$this->tseason_id." 
               AND M.SEASON_ID=".$this->tseason_id." 
               AND MLM.STATUS=1
               and MLM.USER_ID=U.USER_ID
	     GROUP BY MLM.LEAGUE_ID ORDER BY ML.TITLE, MLM.STATUS ASC ".
	     $limitclause;
//echo $sql; 
    $db->query($sql);    
 
    $c = 0;
    while ($row = $db->nextRow()) {
      if ($row['PARTICIPANTS'] == 0)
	$row['PARTICIPANTS'] = "&infin;";
      if ($row['RECRUITMENT_ACTIVE'] == 'Y') {
        $row['RECRUITMENT_ON'] = 1;
        if ($row['ACCEPT_NEWBIES'] == 'Y') {
          $row['NOVICES'] = 1;
        }
      } else
        $row['RECRUITMENT_OFF'] = 1;

      if ($row['REAL_PRIZES'] == 'Y') {
        $row['PRIZES'] = 1;
      }

      if (!empty($row['CCTLD'])) {
        $row['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $row['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }

      if ($row['PARTICIPANTS'] > 0)
	$row['PROGRESS']['PERCENTS'] = $row['USERS'] *100/ $row['PARTICIPANTS'];	

      if ($row['TYPE'] == 1) 
        $row['TOURNAMENT'] = 1; 

      $league = $row;
      if ($row['STATUS'] == 2) {
        $league['LEAGUE'] = $row;
      }
      else if ($row['STATUS'] == 1) {
        $league['OWN_LEAGUE'] = $row;
      }

      $c++;
      $mleagues[] = $league;
    }

    $this->leagues = $count;
    return $mleagues;
  }  
}

?>