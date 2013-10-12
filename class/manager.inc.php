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

class Manager {
  var $mseason_id;
  var $sport_id;
  var $season_info;
  var $default_transactions;
  var $default_money;
  var $max_players;
  var $disabled_trade;
  var $disabled_trade_wrongday;
  var $manager_trade_allow;
  var $seasonlist;
  var $last_tour;
  var $next_tour;
  var $next_tour_date;
  var $next_tour_date_utc;
  var $utc;
  var $current_tour_end_date;
  var $market_size;
  var $market_stats_size;
  var $prize_fund;
  var $newsletter_id;
  var $market;
  var $captaincy;
  var $allow_substitutes;
  var $allow_rvs_leagues;
  var $allow_solo;
  var $title;
  var $season_over;
  var $leagues;
  var $team_limit;
  var $subs_team_limit;
  var $tournaments;
  
  function Manager($mseason_id = '', $mode = '') {
    if (empty($mseason_id))
      $this->getSeason($mode);
    else $this->mseason_id = $mseason_id;
    if (isset($this->mseason_id))
      $this->getSeasonDetails();
  }

  function getSeasonDetails() {
    global $db;   
    global $html_page;
    global $_POST;   

    $sql = "SELECT MS.*, MS.END_DATE < NOW() ENDED, FD.SEASON_TITLE, FD.PRIZES
   	      FROM manager_seasons MS, manager_seasons_details FD 
           WHERE  MS.SEASON_ID=FD.SEASON_ID
		AND FD.LANG_ID=".$_SESSION['lang_id']." 
                AND FD.SEASON_ID=".$this->mseason_id;

    $db->query($sql);
    if ($row = $db->nextRow()) {
      $this->season_info = $row;
      $this->default_money = $row['MONEY'];
      $this->default_transactions = $row['TRANSACTIONS'];
      $this->max_players = $row['MAX_PLAYERS'];
      $this->prize_fund = $row['PRIZE_FUND'];
      $this->newsletter_id = $row['NEWSLETTER_ID'];
      $this->sport_id = $row['SPORT_ID'];
      $this->captaincy= $row['CAPTAINCY'] == 'Y' ? 1 : 0;
      $this->allow_substitutes = $row['ALLOW_SUBSTITUTES'] == 'Y' ? 1 : 0;
      $this->allow_rvs_leagues = $row['ALLOW_RVS_LEAGUES'] == 'Y' ? 1 : 0;
      $this->allow_solo = $row['ALLOW_SOLO'] == 'Y' ? 1 : 0;
      $this->season_over= $row['ENDED'];
      $this->title= $row['SEASON_TITLE'];
      $html_page->page_title = $this->title;
    }

    $db->select("manager_statistics", "*", "SEASON_ID=".$this->mseason_id);

    if (!$row = $db->nextRow()) {
      $this->market = false;
      $this->disabled_trade = true;
      $this->manager_trade_allow = FALSE;
    }
    else {
//      $data['MSTATS'][0] = $row;
      if ($row['MARKET'] == 'Y') {
        $this->market = true;
        $this->disabled_trade = false;
        $this->manager_trade_allow = TRUE;
      } else {
        $this->market = false;
        $this->disabled_trade = true;
        $this->disabled_trade_wrongday = true;
        $this->manager_trade_allow = FALSE;
       }
    } 

   // get seasons
    $db->select('manager_subseasons', 'SEASON_ID', 'MSEASON_ID='.$this->mseason_id);
    $c = 0;
    $this->seasonlist = '';
    $pre = '';
    while ($row = $db->nextRow()) {
      $this->seasonlist .= $pre.$row['SEASON_ID'];
      $pre = ',';

      $c++;
    }
    $db->free();

    // PROCESS requests
    // accept invite && decline invite
    if (isset($_POST['challenge_id']) && isset($_POST['action']))
      $this->handleInvite($_POST['challenge_id'], $_POST['action']);

  }  

  function getSeason($mode='') {
    global $db;
    global $auth;
    global $_SESSION; 
    global $_GET; 
    global $_COOKIE;

    if ($mode == '') {
      if (isset($_SESSION['_user']['MANAGER_SEASON_ID']) && !isset($_GET['mseason_id'])) {        
        $this->mseason_id = $_SESSION['_user']['MANAGER_SEASON_ID'];
      } else if (!isset($_GET['mseason_id']) && isset($_COOKIE['manager_season'])) {
        $this->mseason_id = $_COOKIE['manager_season'];
      } else if (!isset($_GET['mseason_id'])) {
        $db->select("manager_seasons", "*", "START_DATE < NOW() AND END_DATE > NOW()");
        if ($row = $db->nextRow()) {
          $this->mseason_id = $row['SEASON_ID'];
        }
      } else {
	$this->mseason_id = $_GET['mseason_id'];
      }
      if ($auth->userOn()) {
        $_SESSION['_user']['MANAGER_SEASON_ID'] = $this->mseason_id;
      }
      setcookie('manager_season', $this->mseason_id, time()+3600*24*365);
    } else if ($mode == 'rvs') {
      if (isset($_SESSION['_user']['RVS_MANAGER_SEASON_ID']) && !isset($_GET['mseason_id'])) {        
        $this->mseason_id = $_SESSION['_user']['RVS_MANAGER_SEASON_ID'];
      } else if (!isset($_GET['mseason_id']) && isset($_COOKIE['rvs_manager_season'])) {
        $this->mseason_id = $_COOKIE['rvs_manager_season'];
      } else if (!isset($_GET['mseason_id'])) {
        $db->select("manager_seasons", "*", "START_DATE < NOW() AND END_DATE > NOW() and ALLOW_RVS_LEAGUES='Y'");
        if ($row = $db->nextRow()) {
          $this->mseason_id = $row['SEASON_ID'];
        }
      } else {
	$this->mseason_id = $_GET['mseason_id'];
      }

      if ($auth->userOn()) {
        $_SESSION['_user']['RVS_MANAGER_SEASON_ID'] = $this->mseason_id;
      }

      setcookie('rvs_manager_season', $this->mseason_id, time()+3600*24*365);
    }

    return $this->mseason_id;
  }

  function getTeamLimit() {
    global $db;

    if ($this->sport_id == 3) {
      $this->team_limit = 2;
      return;
    }

    $sql = "SELECT count(distinct team_id) TEAMS from manager_market where publish='Y' and season_id=".$this->mseason_id;
    $db->query($sql);
    if ($row = $db->nextRow()) {
      if (10 - $row['TEAMS'] < 4)
        $this->team_limit = 4;
      else $this->team_limit = 10 - $row['TEAMS'];

      if (6 - $row['TEAMS'] < 2)
        $this->subs_team_limit = 1;
      else $this->subs_team_limit = 6 - $row['TEAMS'];
    }
  }

  function getPrizes() {
    global $_SESSION;
    global $db;

    $sql = "SELECT FD.PRIZES
   	      FROM manager_seasons_details FD 
           WHERE  FD.LANG_ID=".$_SESSION['lang_id']." 
                AND FD.SEASON_ID=".$this->mseason_id;

    $db->query($sql);
    if ($row = $db->nextRow()) {
      return $row['PRIZES'];
    }
    return '';
  }

  function getTitle() {
    return $this->title;
  }

  function getLastTour() {
    global $db;
    global $auth;

    $sql = "SELECT *
             FROM manager_tours 
             WHERE NOW() >= START_DATE 
                   AND NOW() <= END_DATE
                   AND SEASON_ID=".$this->mseason_id;
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $this->last_tour = $row['NUMBER'];   
      return empty($row['NUMBER']) ? "0" : $row['NUMBER'];
    }
    else {
      $sql = "SELECT NUMBER, START_DATE,
                    DATE_ADD(START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE
               FROM manager_tours 
               WHERE NOW() >= END_DATE
                     AND SEASON_ID=".$this->mseason_id."
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
    $this->last_tour =  0;
    return "0";
  }

  function getNextTour() {
    global $db;
    global $auth;

    $sql = "SELECT NUMBER, START_DATE,
                    DATE_ADD(START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE
             FROM manager_tours 
             WHERE NOW() >= START_DATE 
                   AND NOW() <= END_DATE
                   AND SEASON_ID=".$this->mseason_id;
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $this->next_tour = $row['NUMBER'];   
      $this->next_tour_date = $row['START_DATE'];   
      $this->next_tour_date_utc = $row['TOUR_START_DATE'];   
      $this->utc = $auth->getUserTimezoneName();
      return empty($row['NUMBER']) ? "0" : $row['NUMBER'];
    }
    else {
      $sql = "SELECT NUMBER, START_DATE,
                    DATE_ADD(START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE
               FROM manager_tours 
               WHERE NOW() <= START_DATE
                     AND SEASON_ID=".$this->mseason_id."
              ORDER BY NUMBER ASC
	    LIMIT 1";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $this->next_tour = $row['NUMBER'];   
        $this->next_tour_date = $row['START_DATE'];   
        $this->next_tour_date_utc = $row['TOUR_START_DATE'];   
        $this->utc = $auth->getUserTimezoneName();
        return empty($row['NUMBER']) ? "0" : $row['NUMBER'];
      }
    }
  }

  function getCurrentTour() {
    global $db;
    global $auth;

    $sql = "SELECT NUMBER, 
                    DATE_ADD(END_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS END_DATE
             FROM manager_tours 
             WHERE NOW() >= START_DATE 
                   AND NOW() <= END_DATE
                   AND SEASON_ID=".$this->mseason_id;
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $this->disabled_trade = true;
      $this->disabled_trade_wrongday = true;
      $this->manager_trade_allow = FALSE;
      $this->current_tour_end_date =  $row['END_DATE'];
      $this->utc = $auth->getUserTimezoneName();
      return empty($row['NUMBER']) ? "0" : $row['NUMBER'];
    }
    else {
      $sql = "SELECT NUMBER, START_DATE,
                    DATE_ADD(START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE
               FROM manager_tours 
               WHERE NOW() <= END_DATE
                     AND SEASON_ID=".$this->mseason_id."
              ORDER BY NUMBER ASC";
      $db->query($sql);
      if ($row = $db->nextRow()) {
       // there are tours ahead
        $this->next_tour_date = $row['START_DATE'];   
        $this->next_tour_date_utc = $row['TOUR_START_DATE'];   
        $this->utc = $auth->getUserTimezoneName();

//        $data['MARKET'][0]['START_DATE'][0]['START_DATE'] = $row['START_DATE'];   
        return empty($row['NUMBER']) ? "0" : $row['NUMBER'];
      }
      else {
         // get the state of day
          $sql = "SELECT G.START_DATE
                  FROM games G
                  WHERE G.SEASON_ID IN (".$this->seasonlist.")
                        AND TO_DAYS(G.START_DATE)=TO_DAYS(CURDATE())";
          $db->query($sql);
          if (($row = $db->nextRow()) || !$this->manager_trade_allow)
            $this->disabled_trade_wrongday = true;
          else $this->disabled_trade_wrongday = false;
     
          $sql = "SELECT *
                  FROM manager_tours 
                  WHERE SEASON_ID=".$this->mseason_id."
                  ORDER BY NUMBER DESC
                  LIMIT 1";
          $db->query($sql);
          if ($row = $db->nextRow()) {
            return empty($row['NUMBER']) ? "0" : $row['NUMBER'];
          } 
       }   
    }
  }
  
  function closeMarket() {
    global $db;
    if ($this->market == true) {    
      $db->query("start_transaction");
      unset($sdata);
      $sdata['STATUS'] = 1;    
      $db->update('manager_battles', $sdata, "STATUS=0 AND PARTICIPANTS=0 AND JOINED > 1 and SEASON_ID=".$this->mseason_id);
      $db->select('manager_statistics', "MARKET", "SEASON_ID=".$this->mseason_id);
      $row = $db->nextRow();
      if ($row['MARKET'] == 'Y') {
        $db->update('manager_statistics', "MARKET='N'", "SEASON_ID=".$this->mseason_id);
        $manager_log = new ManagerLog();
        $manager_log->logEvent('', 2, 0, $this->mseason_id, '', '');
        // remove unaccepted invitations
        $db->query("commit");
        $this->generateTeamsForBattles();
        $this->removeUnacceptedChallenges();
        $this->removeUnfinishedBattles();
        $this->removeUnacceptedTransfers();
	$this->market = false;
      } else {
        $db->query("commit");
      }
    }
  }

  function removeUnacceptedChallenges() {
     global $db;

     $db->delete('manager_challenges', "STATUS=1 and TYPE=1 AND SEASON_ID=".$this->mseason_id);
     unset($sdata);
     $sdata['STATUS'] = 5;
     $db->update('manager_challenges', $sdata, "STATUS=1 and TYPE=2 AND SEASON_ID=".$this->mseason_id);
     $manager_log = new ManagerLog();
     $manager_log->logEvent('', 8, 0, $this->mseason_id, '', '');
  }

  function removeUnacceptedTransfers() {
     global $db;

     $sql="DELETE FROM rvs_manager_players_exchange_contract 
		WHERE STATUS=0 AND LEAGUE_ID  
			IN (SELECT league_id FROM rvs_manager_leagues WHERE season_id=".$this->mseason_id.")";
     $db->query($sql);
//     $manager_log = new ManagerLog();
//     $manager_log->logEvent('', 8, 0, $this->mseason_id, '', '');
  }

  function openMarket() {
    global $db;

    $db->update('manager_statistics', "MARKET='Y'", "SEASON_ID=".$this->mseason_id);
    $manager_log = new ManagerLog();
    $manager_log->logEvent('', 1, 0, $this->mseason_id, '', '');
  }

  function getToursAmount() {
     global $db;

     $sql="SELECT COUNT(MT.NUMBER) TOURS 
		     FROM manager_tours MT
	       WHERE MT.season_id=".$this->mseason_id;

     $db->query($sql);
     $row = $db->nextRow();

     return $row['TOURS'];
  }

  function getToursSchedule($nearest_tour = '') {
      global $db;
      global $auth;

      $utc = $auth->getUserTimezoneName();
      $sql="SELECT MT.NUMBER, 
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
	     DATE_ADD(MT.END_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE
	     FROM manager_tours MT
	    WHERE MT.season_id=".$this->mseason_id."
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
         if ($nearest_tour != '') {
           if ($nearest_tour == $row['NUMBER']) {
             $tour['INVISIBLE'] = 1;
             $tour['VISIBLE_DIV'] = 1;
           } else {
             $tour['VISIBLE'] = 1;
             $tour['INVISIBLE_DIV'] = 1;
           }
         }
         $c++;
         $tours[] = $tour; 
       }
      $db->free();

     if ($this->sport_id == 1 || $this->sport_id == 2) {
       $sql="SELECT G.GAME_ID, 
	     DATE_ADD(G.START_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE,
 	     IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
	     MT.NUMBER, 
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
  	     DATE_ADD(MT.END_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE,
	     G.SCORE1, G.SCORE2
	     FROM  manager_tours MT, seasons S, games G
		LEFT JOIN teams T1 ON G.TEAM_ID1 = T1.TEAM_ID
			LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN teams T2 ON G.TEAM_ID2 = T2.TEAM_ID
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
	    WHERE MT.season_id=".$this->mseason_id."
	      and S.season_id in (".$this->seasonlist.") 
	      and G.season_id=S.SEASON_ID
		and G.PUBLISH='Y'
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
            ORDER BY MT.NUMBER, G.START_DATE";
      } else if ($this->sport_id == 4) {
        $sql="SELECT G.GAME_ID, G.TITLE,
	     DATE_ADD(G.START_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE,
	     MT.NUMBER, 
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
  	     DATE_ADD(MT.END_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE
	     FROM  manager_tours MT, seasons S, games_races G
	    WHERE MT.season_id=".$this->mseason_id."
	      and S.season_id in (".$this->seasonlist.") 
	      and G.season_id=S.SEASON_ID
		and G.PUBLISH='Y'
              AND MT.START_DATE > G.start_DATE AND MT.START_DATE < DATE_ADD( G.START_DATE, INTERVAL 6 DAY )
            ORDER BY MT.NUMBER, G.START_DATE";
      } else {
        $sql="SELECT G.GAME_ID, G.TITLE,
	     DATE_ADD(G.START_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE,
	     MT.NUMBER, 
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
  	     DATE_ADD(MT.END_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE,
		COUNT(R.RESULT_ID) as SCORE1, -1 as SCORE2
	     FROM  manager_tours MT, seasons S, games_races G
		   LEFT JOIN results_races R ON G.GAME_ID=R.GAME_ID
	    WHERE MT.season_id=".$this->mseason_id."
	      and S.season_id in (".$this->seasonlist.") 
	      and G.season_id=S.SEASON_ID
		and G.PUBLISH='Y'
              AND MT.START_DATE < G.start_DATE AND MT.END_DATE > G.START_DATE
            GROUP BY G.GAME_ID
            ORDER BY MT.NUMBER, G.START_DATE";
      }

//echo $sql;
      $db->query($sql);
      $c = 0;
      while ($row = $db->nextRow()) {
         unset($game);
         $game = $row;
         $game['UTC'] = $utc;
	 if ($row['SCORE1'] + $row['SCORE2'] >= 0)
	   $game['RESULT'] = $row;
         $c++;
         $tours[$row['NUMBER']-1]['GAMES'][] = $game;
       }
      return $tours;
  }

  function getTourSchedule($nearest_tour) {
      global $db;
      global $auth;

      $utc = $auth->getUserTimezoneName();
      $sql="SELECT MT.NUMBER, 
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
	     DATE_ADD(MT.END_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE
	     FROM manager_tours MT
	    WHERE MT.season_id=".$this->mseason_id."
                  AND MT.NUMBER =".$nearest_tour;

      $db->query($sql);
      $tour = '';
      if ($row = $db->nextRow()) {
         unset($tour);
         $tour['NUMBER'] = $row['NUMBER'];
         $tour['TOUR_START_DATE'] = $row['TOUR_START_DATE'];
         $tour['TOUR_END_DATE'] = $row['TOUR_END_DATE'];
         $tour['UTC'] = $utc;
       }

     if ($this->sport_id == 1 || $this->sport_id== 2) {
       $sql="SELECT G.GAME_ID, 
	     DATE_ADD(G.START_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE,
 	     IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
	     MT.NUMBER, G.START_DATE > NOW() NOT_STARTED,
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
  	     DATE_ADD(MT.END_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE,
	     G.SCORE1, G.SCORE2
	     FROM  manager_tours MT, seasons S, games G
		LEFT JOIN teams T1 ON G.TEAM_ID1 = T1.TEAM_ID
			LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN teams T2 ON G.TEAM_ID2 = T2.TEAM_ID
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
	    WHERE MT.season_id=".$this->mseason_id."
	      and S.season_id in (".$this->seasonlist.") 
	      and G.season_id=S.SEASON_ID
              AND MT.NUMBER = ".$nearest_tour."
	      and G.PUBLISH='Y'
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
            ORDER BY MT.NUMBER, G.START_DATE";
      } else if ($this->sport_id == 4) {
        $sql="SELECT G.GAME_ID, G.TITLE,
	     DATE_ADD(G.START_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE,
	     MT.NUMBER, G.START_DATE > NOW() NOT_STARTED,
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
  	     DATE_ADD(MT.END_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE
	     FROM  manager_tours MT, seasons S, games_races G
	    WHERE MT.season_id=".$this->mseason_id."
	      and S.season_id in (".$this->seasonlist.") 
	      and G.season_id=S.SEASON_ID
              AND MT.NUMBER = ".$nearest_tour."
              and G.PUBLISH='Y'
              AND MT.START_DATE > G.start_DATE AND MT.START_DATE < DATE_ADD( G.START_DATE, INTERVAL 6 DAY )
            ORDER BY MT.NUMBER, G.START_DATE";
      } else if ($this->sport_id == 3) {
        $sql="SELECT G.GAME_ID, G.TITLE,
	     DATE_ADD(G.START_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE,
	     MT.NUMBER, G.START_DATE > NOW() NOT_STARTED,
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
  	     DATE_ADD(MT.END_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE,
		COUNT(R.RESULT_ID) as SCORE1, 0 as SCORE2
	     FROM  manager_tours MT, seasons S, games_races G
		   LEFT JOIN results_races R ON G.GAME_ID=R.GAME_ID
	    WHERE MT.season_id=".$this->mseason_id."
	      and S.season_id in (".$this->seasonlist.") 
	      and G.season_id=S.SEASON_ID
		and G.PUBLISH='Y'
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
              AND MT.NUMBER =".$nearest_tour."
            ORDER BY MT.NUMBER, G.START_DATE";
      }
//echo $sql;
      $db->query($sql);
      while ($row = $db->nextRow()) {
         unset($game);
         $game = $row;
         $game['UTC'] = $utc;
	 if (isset($row['SCORE1']) && $row['SCORE1'] + $row['SCORE2'] >= 0)
	   $game['RESULT'] = $row;
         if ($row['NOT_STARTED'] == 1)
           $game['CAN_REPORT'] = 1;
         $tour['GAMES'][] = $game;
       }

      return $tour;
  }
 
  function getMarketSize() {
     global $db;
     if (empty($this->market_size) || $this->market_size == 0) {
       $sql = "SELECT COUNT(USER_ID) ROWS
             FROM manager_market MM 
            WHERE MM.SEASON_ID=".$this->mseason_id." 
                  AND PUBLISH='Y'";

       $db->query($sql);
       while ($row = $db->nextRow()) {
         $count = $row['ROWS'];
       }
       $this->market_size = $count;
     } 
     return $this->market_size;
  }

  function getMarketTeams() {
     global $db;
     $sql = "SELECT COUNT(DISTINCT TEAM_ID) ROWS
             FROM manager_market MM 
            WHERE MM.SEASON_ID=".$this->mseason_id." 
                  AND PUBLISH='Y'";

       $db->query($sql);
       if ($row = $db->nextRow()) {
         return $row['ROWS'];
       }
     return 0;
  }

  function getMarketStatsSize() {
     return $this->market_stats_size;
  }

  function getMarket($where = '', $where_int ='', $order = '') {
      global $_GET;
      global $db;
      global $manager_user;
      global $position_types;
      global $position_limits;
      global $position_jokers;
      global $auth;
      global $_SESSION;
      global $page_size;
//      global $managerbox;
      global $smarty;

      $players_count = $manager_user->team_size;
      if (empty($_GET['page']))
        $page = 1;
      else $page = $_GET['page'];
      if (empty($perpage))
        $perpage = $page_size;
      else $perpage = $_GET['page_size'];
   
     // show players

      $listcond = '';
      if (isset($manager_user->team_players_list) && $manager_user->team_players_list != '')
        $listcond .= " AND MM.USER_ID NOT IN (".$manager_user->team_players_list.")";

      if (isset($manager_user->team_substitutes_list) && $manager_user->team_substitutes_list != '')
        $listcond .= " AND MM.USER_ID NOT IN (".$manager_user->team_substitutes_list.")";

      $where_mark = "";
      $where_mark_fields = "";
      if ($auth->userOn()) {
        $where_mark = " LEFT JOIN manager_players_marked MPM ON MPM.PLAYER_ID=MM.USER_ID and MPM.USER_ID=".$auth->getUserId()." and MPM.SEASON_ID=".$this->mseason_id;
        $where_mark_fields = ", MPM.USER_ID as MARKED";
      }

      if ($auth->hasSupporter()) {
        $sql = "SELECT COUNT(MM.USER_ID) ROWS
            FROM manager_market MM 
                 LEFT JOIN manager_player_stats MPS ON MPS.PLAYER_ID=MM.USER_ID AND MPS.SEASON_ID=".$this->mseason_id." and MPS.TOUR_ID=".$this->last_tour."
                  ".$where_mark.", teams T
		 LEFT JOIN manager_tour_games_team MTGD ON MTGD.TEAM_ID=T.TEAM_ID
            WHERE MM.SEASON_ID=".$this->mseason_id." 
		      AND T.TEAM_ID=MM.TEAM_ID
                  AND MM.PUBLISH='Y' ".$where.$where_int."
                 ".$listcond."";
      } else {
        $sql = "SELECT COUNT(MM.USER_ID) ROWS
            FROM manager_market MM 
                 LEFT JOIN manager_player_stats MPS ON MPS.PLAYER_ID=MM.USER_ID AND MPS.SEASON_ID=".$this->mseason_id." and MPS.TOUR_ID=".$this->last_tour."
                  ".$where_mark."
            WHERE MM.SEASON_ID=".$this->mseason_id." 
                  AND MM.PUBLISH='Y' ".$where.$where_int."
                 ".$listcond."";
      }
      $db->query($sql);
      while ($row = $db->nextRow()) {
         $count = $row['ROWS'];
      }
      $this->market_size = $count;

      if ($count < ($page-1)*$perpage)
        $page = 1;
      $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;

      // get user list list
      $where_prev = " MM.TOTAL_POINTS, if(MPS.PLAYED = 0, 0, MM.TOTAL_POINTS - MM.TOTAL_POINTS_PREV) AS TOTAL_POINTS_PREV1";
      if ($this->sport_id == 4) 
        $where_prev = " MM.CURRENT_VALUE_MONEY - MM.PREV_VALUE_MONEY AS TOTAL_POINTS_PREV1";


      $user_ids = "";
      $pre = "";
      $sql = "SELECT MM.USER_ID, ".$where_prev.", MM.TURNING_POINT, MTGD.TIMES,
		MM.CURRENT_VALUE_MONEY as CURRENT_VALUE_MONEY
                FROM teams T
			LEFT JOIN manager_tour_games_team MTGD ON MTGD.TEAM_ID=T.TEAM_ID
			, manager_market MM
                        LEFT JOIN manager_player_stats MPS ON MPS.PLAYER_ID=MM.USER_ID AND MPS.SEASON_ID=".$this->mseason_id." and MPS.TOUR_ID=".$this->last_tour."
                        ".$where_mark."
                WHERE MM.SEASON_ID=".$this->mseason_id." 
		      AND T.TEAM_ID=MM.TEAM_ID
                      AND MM.PUBLISH='Y' ".$where.$where_int."
                 ".$listcond."
		ORDER BY ".$order.$limitclause;

//echo $sql;
      $db->query($sql);

      while ($row = $db->nextRow()) {
         $user_ids .= $pre.$row['USER_ID'];
         $pre = ","; 
      }
    
      if ($auth->hasSupporter()) {  
       $sql = "SELECT MM.*, ".$where_prev.", CD.COUNTRY_NAME, MTGD.TIMES, T.PIC_LOCATION, MPS.PLAYED as PLAYED_LAST_TOUR,
		MM.TURNING_POINT, MPS.TOTAL_POINTS_PREV as TOTAL_POINTS_PREV_REAL,
                MPRT.TIMES as REPORTS ". $where_mark_fields."
                FROM manager_market MM
                        LEFT JOIN manager_player_reports_temp MPRT ON MPRT.PLAYER_ID=MM.USER_ID
                        LEFT JOIN manager_player_stats MPS ON MPS.PLAYER_ID=MM.USER_ID AND MPS.SEASON_ID=".$this->mseason_id." and MPS.TOUR_ID=".$this->last_tour."
                        ".$where_mark."
			, teams T 
			LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
			LEFT JOIN manager_tour_games_team MTGD ON MTGD.TEAM_ID=T.TEAM_ID
                WHERE MM.SEASON_ID=".$this->mseason_id." 
		      AND T.TEAM_ID=MM.TEAM_ID
		      AND MM.USER_ID IN (".$user_ids.")
                      AND MM.PUBLISH='Y' ".$where.$where_int."
                 ".$listcond."
            ORDER BY ".$order;
      } else {
       $sql = "SELECT MM.*, ".$where_prev.", CD.COUNTRY_NAME, MPRT.TIMES AS REPORTS, T.PIC_LOCATION, MPS.PLAYED as PLAYED_LAST_TOUR, MPS.TOTAL_POINTS_PREV as TOTAL_POINTS_PREV_REAL
		". $where_mark_fields."
                FROM manager_market MM
                        LEFT JOIN manager_player_reports_temp MPRT ON MPRT.PLAYER_ID=MM.USER_ID
                        LEFT JOIN manager_player_stats MPS ON MPS.PLAYER_ID=MM.USER_ID AND MPS.SEASON_ID=".$this->mseason_id." and MPS.TOUR_ID=".$this->last_tour."
                        ".$where_mark."
			, teams T 
			LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
                WHERE MM.SEASON_ID=".$this->mseason_id." 
		      AND T.TEAM_ID=MM.TEAM_ID
		      AND MM.USER_ID IN (".$user_ids.")
                      AND MM.PUBLISH='Y' ".$where.$where_int."
                 ".$listcond."
            ORDER BY ".$order;
      }

      $db->query($sql);
//echo $sql;
      $c = 0;
      $players = array();
      $market = array();
      while ($row = $db->nextRow()) {
        $player = $row;
        if ($auth->hasSupporter()) {  
          $market['TIMES_SUPPORT_H'] = 1;
           
	  if (isset($_GET['order']) && $_GET['order'] == 'TURNING_POINT'.' desc') {
	    $market['TURN_POINT_H']['TURNING_POINT'.'_DESC_A']['URL'] = 'xxx';
	    $market['TURN_POINT_H']['TURNING_POINT'.'_ASC']['URL'] = url('order', 'TURNING_POINT'.' asc');
	  }
	  elseif (isset($_GET['order']) && $_GET['order'] == 'TURNING_POINT'.' asc') {
	    $market['TURN_POINT_H']['TURNING_POINT'.'_DESC']['URL'] = url('order', 'TURNING_POINT'.' desc');
	    $market['TURN_POINT_H']['TURNING_POINT'.'_ASC_A']['URL'] = 'xxx';
	  }
	  else {
	    $market['TURN_POINT_H']['TURNING_POINT'.'_DESC']['URL'] = url('order', 'TURNING_POINT'.' desc');
	    $market['TURN_POINT_H']['TURNING_POINT'.'_ASC']['URL'] = url('order', 'TURNING_POINT'.' asc');
	  }

          $player['WILL_PLAY'] = $row['TIMES'];
          $player['TURNING_POINT'] = $row['TURNING_POINT']; //round(($row['CURRENT_VALUE_MONEY']*($row['PLAYED']+2) - ($row['START_VALUE'] + $row['TOTAL_POINTS']+1) * 1000)/1000, 2);
        } else {
	  unset($player['TURNING_POINT']);
        }

        if ($row['TEAM_TYPE'] == 2)
	  $player['TEAM_NAME2'] = $row['COUNTRY_NAME'];
        if (isset($row['TOTAL_POINTS']))
          $player['POINTS'] = $row['TOTAL_POINTS'];
        else $player['POINTS'] = 0;

        $player['TOTAL_POINTS_PREV1'] = $row['TOTAL_POINTS'] - $row['TOTAL_POINTS_PREV_REAL'];
    
        if (isset($row['START_VALUE_MONEY']))
          $player['START_VALUE_MONEY'] = $row['START_VALUE_MONEY'];
        else $player['START_VALUE_MONEY'] = 7000;
    
        if (isset($row['CURRENT_VALUE_MONEY']))
          $player['CURRENT_VALUE_MONEY'] = $row['CURRENT_VALUE_MONEY'];
        else $player['CURRENT_VALUE_MONEY'] = 7000;
        if ($row['CURRENT_VALUE_MONEY'] < 0)
          $player['CURRENT_VALUE_MONEY'] = 100;
    
        $player['KOEFF']=round($row['KOEFF'], 2);

        if ($row['PLAYED'] > 0) {
          $player['PLAYER_SEASON_STATS']['USER_ID'] = $row['USER_ID'];
          $player['PLAYER_SEASON_STATS']['SUBSEASONS'] = $this->seasonlist;
          $player['PLAYER_SEASON_STATS']['PLAYED'] = $row['PLAYED'];
        } 
        else {
          if ($this->sport_id != 4)
            $player['PREV_VALUE_MONEY'] = $player['START_VALUE_MONEY'];
          $player['PLAYER_SEASON_STATS']['USER_ID'] = $row['USER_ID'];
          $player['PLAYER_SEASON_STATS']['SUBSEASONS'] = $this->seasonlist;
          $player['PLAYER_SEASON_STATS']['PLAYED'] = $row['PLAYED'];
        }

        if ($this->sport_id == 4)
          $player['POINTS'] = $player['CURRENT_VALUE_MONEY'] - $player['PREV_VALUE_MONEY'];
    
        if ($player['CURRENT_VALUE_MONEY'] > $player['PREV_VALUE_MONEY'])
          $player['UP'] = 1;
        else if ($player['CURRENT_VALUE_MONEY'] < $player['PREV_VALUE_MONEY'])
             $player['DOWN'] = 1;

        if (!empty($position_types[$this->sport_id][$row['POSITION_ID2']]))
          $player['TYPE_NAME'] = $position_types[$this->sport_id][$row['POSITION_ID1']]."/".$position_types[$this->sport_id][$row['POSITION_ID2']];
        else $player['TYPE_NAME'] = $position_types[$this->sport_id][$row['POSITION_ID1']];

        if ($row['REPORTS'] == '')
          $player['REPORTS'] = 0;

        $player['PLAYER_STATE_DIV'] = $this->getPlayerStateSmarty($row['USER_ID'], $this->mseason_id, $row['PLAYER_STATE']);

        if ($auth->userOn()) {
          if ($players_count == $this->max_players)
            $disabled_trade_12 = true;
          if ($this->disabled_trade_wrongday) {
            $player['BUY_DISABLED']['WRONG_DAY'] = 1;
          }
          else if ($_SESSION['_user']['MANAGER'][$this->mseason_id]['TRANSACTIONS'] == 0) {
            $player['BUY_DISABLED']['LOW_TRANSACTIONS'] = 1;
          } 
          else if ($disabled_trade_12) {
            $player['BUY_DISABLED']['FULL_TEAM'] = 1;
          } 
          elseif ($this->noAvailableSlots($row['POSITION_ID1'], $row['POSITION_ID2']))   
            $player['BUY_DISABLED']['AMPLUA'] = 1;
          elseif ($this->noAvailableTeams($row['TEAM_ID']))   
            $player['BUY_DISABLED']['TEAM_LIMIT'] = 1;
          elseif ($_SESSION['_user']['MANAGER'][$this->mseason_id]['MONEY'] >= $player['CURRENT_VALUE_MONEY']) {
            $player['BUY']['USER_ID'] = $row['USER_ID'];
            $player['BUY']['START_VALUE'] = $row['START_VALUE'];
            $player['BUY']['START_VALUE_MONEY'] = $player['START_VALUE_MONEY'];
            $player['BUY']['CURRENT_VALUE_MONEY'] = $player['CURRENT_VALUE_MONEY'];
          } 
          else {
           $player['BUY_DISABLED']['LOW_MONEY'] = 1;
          }

          if (isset($row['MARKED']) && !empty($row['MARKED']))
            $player['UNMARK'] = 1;
          else if (isset($row['MARKED']) && empty($row['MARKED']))
            $player['MARK'] = 1;

          if ($this->allow_substitutes &&
               !$this->disabled_trade_wrongday && 
		!$this->noAvailableSubstituteSlots($row['POSITION_ID1']) &&
                !$this->noAvailableSubsTeams($row['TEAM_ID'])) {
            $player['SUBSTITUTE']['USER_ID'] = $row['USER_ID'];
          }
        }
        $c++;
        $players[] = $player;
      }

      $so_fields = array('CURRENT_VALUE_MONEY', 'START_VALUE_MONEY', 'PREV_VALUE_MONEY', 'LAST_NAME', 'TOTAL_POINTS', 'TOTAL_POINTS_PREV1', 'TURNING_POINT');
      $sop_fields = array('TIMES');
      for ($c=0; $c<sizeof($so_fields); $c++) {
        if (isset($_GET['order']) && $_GET['order'] == $so_fields[$c].' desc') {
          $market[$so_fields[$c].'_DESC_A']['URL'] = 'xxx';
          $market[$so_fields[$c].'_ASC']['URL'] = url('order', $so_fields[$c].' asc');
        }
        elseif (isset($_GET['order']) && $_GET['order'] == $so_fields[$c].' asc') {
          $market[$so_fields[$c].'_DESC']['URL'] = url('order', $so_fields[$c].' desc');
          $market[$so_fields[$c].'_ASC_A']['URL'] = 'xxx';
        }
        else {
          $market[$so_fields[$c].'_DESC']['URL'] = url('order', $so_fields[$c].' desc');
          $market[$so_fields[$c].'_ASC']['URL'] = url('order', $so_fields[$c].' asc');
        }
      }

      $smarty->clearAllAssign();
      if ($this->allow_substitutes == true)
        $smarty->assign("allow_substitutes", 1);
      if ($auth->userOn()) {
        $smarty->assign("user_on", 1);
      }

      $smarty->assign('players', $players);
      $smarty->assign('market', $market);
      $smarty->assign('sport_id', $this->sport_id);
  }

  function getMarketStats($query='',$page=1,$perpage=PAGE_SIZE) {
      global $_GET;
      global $_POST;
      global $db;
      global $page_size;
      global $position_types;
   
      if (isset($_GET['tour_id'])) {
        $sql = "SELECT * FROM manager_tours MTR
		WHERE MTR.SEASON_ID=" . $this->mseason_id . "
		      AND MTR.NUMBER=" . $_GET['tour_id'];
        $db->query ( $sql );
        $row = $db->nextRow ();
        $tour_start_date = $row['START_DATE'];
        $tour_end_date = $row['END_DATE'];
      }
      if (isset($_GET['tour_id']) && $_GET['tour_id'] > 1) {
        $sql = "SELECT * FROM manager_tours MTR
		WHERE MTR.SEASON_ID=" . $this->mseason_id . "
		      AND MTR.NUMBER=" . ($_GET['tour_id'] - 1);
        $db->query ( $sql );
        $row = $db->nextRow ();
        $prev_tour_start_date = $row['START_DATE'];
        $prev_tour_end_date = $row['END_DATE'];
      }

      if (isset($_GET['tour_id'])) {
        $filter = " and MT.buying_date  <= '".$tour_start_date."' 
		   AND (MT.selling_date >= '".$tour_end_date."' OR MT.selling_date is null)";        
	$filter_captain = " and MC.start_date <= '".$tour_start_date."' 
		   AND (MC.end_date >= '".$tour_end_date."' OR MC.end_date is null)";        
      } else  {
	$filter = " and MT.selling_date is null";
	$filter_captain = " and MC.end_date is null";
      }

      if (isset($_POST['query'])) {
        $filter .= ' AND LOWER(MM.LAST_NAME) like "%'.strtolower($_POST['query']).'%"';
      }

      $sql = "SELECT COUNT(DISTINCT PLAYER_ID) ROWS
          FROM manager_teams MT, manager_market MM
          WHERE MT.SEASON_ID=".$this->mseason_id."
		and MT.player_id=MM.user_id
		and MM.season_id=MT.season_id ".$filter;

      $db->query($sql);
      while ($row = $db->nextRow()) {
         $count = $row['ROWS'];
      }
      $this->market_stats_size = $count;
     // show players
      $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;

      $sql="SELECT count(MT.entry_id) CNT, sum(if (MC.ENTRY_ID is not null, 1, 0)) as CCNT, MT.PLAYER_ID, MM.FIRST_NAME, MM.LAST_NAME, 
		IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME, MM.POSITION_ID1, MM.POSITION_ID2
		FROM manager_teams MT
			left join manager_captain MC ON MT.entry_id=MC.entry_id ".$filter_captain.", 
		     manager_market MM, teams T 
			LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
		where MT.season_id=".$this->mseason_id."	
		and MT.player_id=MM.user_id
		and MM.season_id=MT.season_id
 	        AND T.TEAM_ID=MM.TEAM_ID
		".$filter."
		group by MT.player_id
		order by cnt desc ".$limitclause;
//echo $sql;
      $db->query($sql);
      $c=0;
      $players['PLAYERS'] = array();
      while ($row = $db->nextRow()) {
        $player = $row;
        if (isset($position_types[$this->sport_id]))
         if (!empty($position_types[$this->sport_id][$row['POSITION_ID2']]))
           $player['TYPE_NAME'] = $position_types[$this->sport_id][$row['POSITION_ID1']]."/".$position_types[$this->sport_id][$row['POSITION_ID2']];
         else $player['TYPE_NAME'] = $position_types[$this->sport_id][$row['POSITION_ID1']];

        $players['PLAYERS'][] = $player;
      }

      if (isset($_GET['tour_id']) && $_GET['tour_id'] == 1) {
	$filter = " left join manager_tours MT1 on MT1.season_id=".$this->mseason_id." and MT1.number=".$_GET['tour_id']."
		    where MUL.season_id=".$this->mseason_id." and MUL.event_date <= MT1.start_date";
      } else if (isset($_GET['tour_id']) && $_GET['tour_id'] > 1) {
	$filter = " where MUL.season_id=".$this->mseason_id." and MUL.event_date >= '".$prev_tour_end_date."' and MUL.event_date <= '".$tour_start_date."'";
      } else  {
	$filter = " where MUL.season_id=".$this->mseason_id;
      }

      $sql = "SELECT count(distinct user_id) CHANGED
		FROM manager_users_log MUL".$filter;
//echo $sql;
      $db->query($sql); 
      $row = $db->nextRow();
      $players['CHANGED'] = $row['CHANGED'];
      $players['COUNT'] = $count;
 
      return $players;
  }

  function countTourGamesPerTeam($tour_id) {
      global $db;

      $sql="SELECT MT.START_DATE, MT.END_DATE
	FROM manager_tours MT 
	WHERE MT.SEASON_ID = ".$this->mseason_id."
		AND MT.NUMBER=".$tour_id;
      $db->query($sql);
      $row=$db->nextRow();
  
      $sql = "CREATE TEMPORARY TABLE manager_tour_games_team (
      	times int NOT NULL, 
	team_id int NOT NULL)";
      $db->query($sql);

      if (!empty($tour_id)) {  
        $sql = "insert into manager_tour_games_team 
		select sum(times) TIMES, TEAM_ID from
		(SELECT count(G.TEAM_ID1) times, G.TEAM_ID1 team_id
			FROM games G LEFT JOIN teams T1 ON G.TEAM_ID1 = T1.TEAM_ID 
			 WHERE G.season_id in (".$this->seasonlist.") and G.PUBLISH='Y' 
				and '".$row['START_DATE']."' < G.start_DATE and '".$row['END_DATE']."' > G.START_DATE 
			GROUP BY G.TEAM_ID1
			union all
			SELECT count(G.TEAM_ID2) times, G.TEAM_ID2 team_id
			FROM games G LEFT JOIN teams T1 ON G.TEAM_ID2 = T1.TEAM_ID 
			 WHERE G.season_id in (".$this->seasonlist.") 
                                and G.PUBLISH='Y' 
			      and '".$row['START_DATE']."' < G.start_DATE and '".$row['END_DATE']."' > G.START_DATE 
			GROUP BY G.TEAM_ID2) T
		group by team_id";
        $db->query($sql);
      } 
  }

  function noAvailableSlots($position, $position2) {
    global $position_limits;
    global $position_jokers;
    global $manager_user;

//print_r($manager_user->posit);
    if ($this->sport_id == 4)
      return false;


    if (!empty($position) && !empty($position2)) {
//print_r($manager_user->posit);
//echo $position_limits[$this->sport_id][$position] + $position_jokers[$this->sport_id][$position];
      // double position, use halves
       if ((($manager_user->posit[$position] < 
             $position_limits[$this->sport_id][$position] + $position_jokers[$this->sport_id][$position]
              && $manager_user->left_jokers >= 0.5) 
	    || $position_limits[$this->sport_id][$position] - $manager_user->posit[$position] >= 0.5)
           && 
           (($manager_user->posit[$position2] <
             $position_limits[$this->sport_id][$position2] + $position_jokers[$this->sport_id][$position2]
              && $manager_user->left_jokers >= 0.5) 
 	    || $position_limits[$this->sport_id][$position2] - $manager_user->posit[$position2] >= 0.5)) {

//echo 1;
	 return false;  
       }
    }
    
    if (empty($position2)) {
       if (($manager_user->posit[$position] < 
             $position_limits[$this->sport_id][$position] + $position_jokers[$this->sport_id][$position]
	     && $manager_user->left_jokers >= 1)
           || $position_limits[$this->sport_id][$position] - $manager_user->posit[$position] >= 1)
         return false;  
    }
    return true;
  }

  function noAvailableSubstituteSlots($position) {
    global $position_substitutes;
    global $manager_user;

    $subs = !empty($manager_user->substitutes[$position]) ? $manager_user->substitutes[$position] : 0 ;
    if ($subs < 
          $position_substitutes[$this->sport_id][$position])
       return false;  
    return true;
  }

  function noAvailableTeams($team_id) {
    global $manager_user;

    if ($this->sport_id == 4)
      return false;
    if ($manager_user->teams[$team_id] + $manager_user->substeams[$team_id] >= $this->team_limit)
      return true;
    return false;
  }

  function noAvailableSubsTeams($team_id) {
    global $manager_user;

    if ($this->sport_id == 4)
      return false;
    if ($manager_user->substeams[$team_id] >= $this->subs_team_limit ||
        $manager_user->teams[$team_id] + $manager_user->substeams[$team_id] >= $this->team_limit)
      return true;
    return false;
  }

  function getInjuriedList() {
      global $db;
      global $manager_user;
      global $position_types;
      global $auth;
      global $_SESSION;

     // show players
      $players = array();
      $listcond = '';
      if ($manager_user->team_players_list != '')
        $listcond = " AND USER_ID NOT IN (".$manager_user->team_players_list.")";
       
       $sql = "SELECT MM.*, MPRT.TIMES as REPORTS
                FROM manager_market MM 
                        LEFT JOIN manager_player_reports_temp MPRT ON MPRT.PLAYER_ID=MM.USER_ID
                WHERE MM.SEASON_ID=".$this->mseason_id." 
                      AND PUBLISH='Y' ".$listcond."
		      AND PLAYER_STATE > 0	
            ORDER BY LAST_NAME";
      $db->query($sql);
    
      $c = 0;
      while ($row = $db->nextRow()) {
        $player = $row;

        if (isset($row['TOTAL_POINTS']))
          $player['POINTS'] = $row['TOTAL_POINTS'];
        else $player['POINTS'] = 0;
    
        $player['TOTAL_POINTS_PREV1'] = $row['TOTAL_POINTS'] - $row['TOTAL_POINTS_PREV'];
        $player['PLAYER_STATE_DIV'] = $this->getPlayerStateSmarty($row['USER_ID'], $this->mseason_id, $row['PLAYER_STATE']);
        
        $data['PLAYERS'][$c]['KOEFF']=round($row['KOEFF'], 2);
        
        if ($row['PLAYED'] > 0) {
           $player['STATS']['USER_ID']=$row['USER_ID'];
           $player['STATS']['SEASON_ID']=$this->seasonlist;
           $player['STATS']['MSEASON_ID']=$this->mseason_id;
        }
    
        if ($player['CURRENT_VALUE_MONEY'] > $player['PREV_VALUE_MONEY'])
          $player['UP'] = 1;
        else if ($player['CURRENT_VALUE_MONEY'] < $player['PREV_VALUE_MONEY'])
             $player['DOWN'] = 1;

        if ($row['REPORTS'] == '')
          $player['REPORTS'] = 0;

        if (!empty($position_types[$this->sport_id][$row['POSITION_ID2']]))
          $player['TYPE_NAME'] = $position_types[$this->sport_id][$row['POSITION_ID1']]."/".$position_types[$this->sport_id][$row['POSITION_ID2']];
        else $player['TYPE_NAME'] = $position_types[$this->sport_id][$row['POSITION_ID1']];
        if ($c & 2 > 0)
          $player['ODD'] = 1;
        else
          $player['EVEN'] = 1;  
        $players[] = $player;
      }
      
      return $players;
  }

  function getUser($user_id) {
    global $db;
    global $_SESSION;

    $sql = "SELECT MU.TRANSACTIONS, MU.MONEY, MS.POINTS, MS.PLACE, L.ID, L.SHORT_CODE, U.USER_NAME, U.USER_ID
             FROM manager_users MU 
			left join manager_standings MS ON MU.user_id=MS.USER_ID and MS.MSEASON_ID=MU.SEASON_ID and MU.SEASON_ID=".$this->mseason_id."
			left join users U on U.USER_ID=MU.USER_ID
			left join languages L on U.LAST_LANG=L.SHORT_CODE
             WHERE MU.USER_ID=".$user_id." AND MU.SEASON_ID=".$this->mseason_id;
    $db->query($sql); 
    if ($row = $db->nextRow()) {
      return $row;
    }
    return '';
  } 

  function getLeagues($where="", $prefix="") {
     global $db;
     global $auth;
     global $page_size;
     global $_GET;
     global $_SESSION;

     if (empty($_GET['page2']))
       $page = 1;
     else $page = $_GET['page2'];
     if (empty($perpage))
       $perpage = $page_size;
     else $perpage = $_GET['page_size'];
   
     $sql="SELECT COUNT(*) QUANT
             FROM manager_seasons M, ".$prefix."manager_leagues ML, users U
             WHERE ML.SEASON_ID=".$this->mseason_id." 
               AND M.SEASON_ID=".$this->mseason_id."
	       and ML.USER_ID=U.USER_ID".$where;
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
		  ML.JOINED as USERS, ML.RATING, U.LEAGUE_OWNER_RATING,
	          ML.PARTICIPANTS, ML.RECRUITMENT_ACTIVE, ML.ACCEPT_NEWBIES, ML.REAL_PRIZES, ML.TYPE, 
		  C.CCTLD, CD.COUNTRY_NAME, ML.INVITE_TYPE
             FROM ".$prefix."manager_leagues_members MLM, manager_seasons M, users U, ".$prefix."manager_leagues ML
		  LEFT JOIN topic T ON ML.topic_id=T.topic_id 
                  LEFT JOIN countries C ON ML.COUNTRY = C.ID
		  LEFT JOIN countries_details CD ON C.ID=CD.ID aND CD.LANG_ID=".$_SESSION['lang_id']."
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
               AND ML.SEASON_ID=".$this->mseason_id." 
               AND M.SEASON_ID=".$this->mseason_id." 
               and ML.USER_ID=U.USER_ID 
               and MLM.USER_ID=ML.USER_ID
		".$where."
		ORDER BY ML.TITLE, MLM.STATUS ASC ".
	     $limitclause;
//echo "<!--".$sql."-->"; 	     GROUP BY MLM.LEAGUE_ID 
    $db->query($sql);    
 
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

      if (empty($row['POSTS']))
       $league['POSTS'] = 0;

      $mleagues[] = $league;
    }

    $this->leagues = $count;
//echo "<!--".print_r($mleagues)."-->"; 
    return $mleagues;
  }  

  function canBeChallenged($market_open_date, $current_tour, $user_id) {
    global $db;
    global $auth;  
    global $_SESSION;  

    $place = $_SESSION['_user']['MANAGER'][$this->mseason_id]['PLACE'];

    $where_standings = " AND MUT.PLACE BETWEEN ". ($place - 50) ." AND ". ($place + 50);

    $sql = "SELECT U.USER_NAME, MS.PLACE, MS.POINTS, MC.STATUS, MU.season_id, MU.USER_ID
            FROM manager_users MU, manager_standings MS, manager_users_tours MUT
			left join users U on U.user_id=MUT.user_id 
			left join manager_challenges MC on MC.user_id=".$auth->getUserId()."
				 and MC.user2_id=MUT.user_id and MC.season_id=".$this->mseason_id."
		WHERE MU.season_id=".$this->mseason_id."
		      AND MU.user_id=".$user_id."
		      AND MS.mseason_id=MU.season_id
		      AND MU.user_id=MS.user_id			
		      AND MUT.season_id=MU.season_id
		      AND MUT.user_id=MU.user_id		      	
		      AND MUT.tour_id=".($current_tour -1).$where_standings;
//echo $sql;
    $db->query($sql);
    if ($row = $db->nextRow()) {
      return true;
    }
    return false;
  }

  function canBeChallenged2($current_tour, $user_id) {
    global $db;
    global $auth;  
    global $_SESSION;  

    $sql = "SELECT *
		from manager_challenges MC
		where MC.season_id =".$this->mseason_id."
			AND MC.tour_id =".$current_tour."
			and MC.status in (1, 2)
			AND ((MC.user_id=".$user_id." AND
				MC.user_id2=".$auth->getUserId().")
                              OR
			     (MC.user_id2=".$user_id." AND
				MC.user_id=".$auth->getUserId()."))";
    $db->query($sql);
    if ($row = $db->nextRow()) {
        return false;
    }
    return true;
  }

  function transferMoney($user_to, $user_from, $season_id, $stake) {
     global $db;

     $sdata['MONEY'] = "MONEY+".$stake;
     $db->update('manager_users', $sdata, "USER_ID=".$user_to." AND SEASON_ID=".$season_id);
     $manager_user_log = new ManagerUserLog();
     $manager_user_log->logEvent ($user_to, 4, $stake, $season_id, '', $user_from);

     $sdata['MONEY'] = "MONEY-".$stake;
     $db->update('manager_users', $sdata, "USER_ID=".$user_from." AND SEASON_ID=".$season_id);
     $manager_user_log->logEvent ($user_from, 5, $stake, $season_id, '', $user_to);

  }

  function setPrice($player_id, $start_value) {
    global $db;
    global $_SESSION;

    if (empty($start_value))
      return false;

    $sql = "SELECT *
             FROM manager_tours 
             WHERE NOW() <= START_DATE
                   AND SEASON_ID=".$this->mseason_id;
     $db->query($sql);
     if ($row = $db->nextRow()) {
      // there are tours ahead
       $tours = $row['NUMBER'];
       $db->free();
     }
     else $tours = 1;

    $sdata['PLAYER_ID'] = $player_id;
    $sdata['SEASON_ID'] = $this->mseason_id;
    $sdata['START_VALUE'] = $start_value;
    $db->select('manager_players', "*", "PLAYER_ID=".$player_id." AND SEASON_ID=".$this->mseason_id);
    if (!$row = $db->nextRow()) {
        $db->insert('manager_players', $sdata);
        $new = true; 
    } else {
        $db->update('manager_players', $sdata, "SEASON_ID=".$this->mseason_id." AND PLAYER_ID=".$player_id);
        $new = false;
    }
    // redirect to news page
    $db->free();
    
    for ($k = 0; $k < $tours; $k++) {
      $sql="REPLACE DELAYED INTO manager_player_stats
         (PLAYER_ID, TOTAL_POINTS, KOEFF, CURRENT_VALUE_MONEY, START_VALUE_MONEY, PLAYED, 
		TOTAL_POINTS_PREV, SEASON_ID, TOUR_ID, PLAYED_PREV)
         SELECT MP.PLAYER_ID, 0 AS TOTAL_POINTS, 0 AS KOEFF, 
                MP.START_VALUE*1000 + 1000 CURRENT_VALUE_MONEY,
                MP.START_VALUE*1000 + 1000 START_VALUE_MONEY,
               0 PLAYED, 0, ".$this->mseason_id.", ".$k.", 0
          FROM manager_players MP, seasons S
         WHERE S.SEASON_ID  IN (".$this->seasonlist.") AND MP.PLAYER_ID = ".$player_id." 
               AND MP.SEASON_ID=".$this->mseason_id."
         GROUP BY MP.PLAYER_ID";   
//echo $sql;

     $db->query($sql);
    } 

    $sql="REPLACE DELAYED INTO manager_market
             SELECT DISTINCT M.USER_ID, M.NUM, M.POSITION_ID1, 
               M.POSITION_ID2, M.USER_TYPE, U.FIRST_NAME FIRST_NAME, 
               U.LAST_NAME LAST_NAME, U.MALE, IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME), T.TEAM_TYPE, MP.START_VALUE,
               M.DATE_EXPIRED, S.END_DATE, MPS.KOEFF, MPS.TOTAL_POINTS, MPS.START_VALUE_MONEY, 
               MPS.CURRENT_VALUE_MONEY, MPS.PLAYED, 0, 'Y', ".$this->mseason_id.", 0, 'N', T.TEAM_ID, 0, 0
        FROM team_seasons TS, teams T
 		LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id'].", seasons S, members M
             LEFT JOIN busers U ON M.USER_ID = U.USER_ID 
             LEFT JOIN manager_players MP ON MP.PLAYER_ID = U.USER_ID AND MP.SEASON_ID=".$this->mseason_id." 
             LEFT JOIN manager_player_stats MPS ON MPS.PLAYER_ID=M.USER_ID AND MPS.SEASON_ID=".$this->mseason_id." AND MPS.TOUR_ID = 0
        WHERE TS.SEASON_ID  IN (".$this->seasonlist.") 
             AND S.SEASON_ID = TS.SEASON_ID 
             AND M.TEAM_ID = TS.TEAM_ID 
             AND M.USER_ID = ".$player_id."
             AND T.TEAM_ID = TS.TEAM_ID 
             AND ((M.DATE_STARTED >= S.START_DATE AND M.DATE_STARTED <= S.END_DATE) 
                  OR (M.DATE_EXPIRED >= S.START_DATE AND M.DATE_EXPIRED <= S.END_DATE)  
                  OR (M.DATE_STARTED < S.START_DATE 
                     AND (M.DATE_EXPIRED > S.END_DATE  OR M.DATE_EXPIRED IS NULL) )
                 )  
             AND U.PUBLISH =  'Y'
        ORDER BY M.DATE_STARTED DESC LIMIT 1";
//echo $sql;
    $db->query($sql);

    if ($new) {
      $db->select('manager_market', '*', "USER_ID=".$player_id." AND SEASON_ID=".$this->mseason_id);
      $row = $db->nextRow();
      $manager_log = new ManagerLog();
      $manager_log->logEvent($player_id, 4, $start_value*1000+1000, $this->mseason_id, $row['TEAM_ID'], '');
    } 
    return true;
  }

  function setHealth($player_id, $injured) {
    global $db;

    if ($injured)
      $db->update('manager_market', array('INJURY' => "'Y'"), "USER_ID=".$player_id);
    else
      $db->update('manager_market', array('INJURY' => "'N'"), "USER_ID=".$player_id);
  }

  function countReportsPerPlayer() {
      global $db;

      $sql = "CREATE TEMPORARY TABLE manager_player_reports_temp (
    		times int NOT NULL, 
		player_id int NOT NULL)";
      $db->query($sql);

      $sql = "insert into manager_player_reports_temp 
		select count(MPL.REPORT_ID) TIMES, MPL.PLAYER_ID
			from manager_player_reports MPL
		where (MPL.season_id=".$this->mseason_id."
			     OR MPL.season_id=0)
			AND finished=0
			and report_state >= 0
		GROUP BY MPL.PLAYER_ID";
      $db->query($sql);
  }

  function getPlayerReports($player_id, $season_id, &$opt='') {
    global $db;
    global $_SESSION;
    global $player_state;
    global $report_state;
    global $auth;

    $sql="SELECT MPL.*, U.USER_NAME from manager_player_reports MPL
		 left join users U on U.user_id = MPL.user_id
		where MPL.player_id=".$player_id."
			AND (MPL.season_id=".$this->mseason_id."
			     OR MPL.season_id=0)
			AND finished=0 and report_state >=0
  	  ORDER BY VALID_TILL ASC";
    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
        
        $report = $row;
        $report['STATE'] = $player_state[$row['STATUS']];
        $report['REPORT_STATUS'] = $report_state[$row['REPORT_STATE']];
        if (substr($row['LINK'], 0, 4) != 'http')
  	  $report['LINK'] = "http://".$row['LINK'];

        if ($row['USER_ID'] == $auth->getUserId() && $opt!='') {
          unset($opt['options'][$row['STATUS']]);
        }
        $reports['REPORTS'][] = $report;
    }
    $reports['PLAYER_ID'] = $player_id;
    $reports['SEASON_ID'] = $season_id;
    return $reports;
  }

  function handleInvite($challenge_id, $action) {
    global $db;
    global $_SESSION;
    global $auth;
  
    $sql= "SELECT * FROM manager_challenges WHERE status=1 AND challenge_id=".$challenge_id; 
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $user_id1 = $row['USER_ID'];
      if ($action == 'accept_invite'
		&& isset($challenge_id)
		&& (($row['TYPE'] == 1) || ($row['TYPE'] == 2 && $_SESSION['_user']['CREDIT']>= $row['STAKE']))) {
        $udata['STATUS'] = 2;  
        $udata['DATE_ACCEPTED'] = "NOW()";
        $db->update('manager_challenges', $udata, 'STATUS=1 AND USER2_ID='.$auth->getUserId().' AND CHALLENGE_ID='.$challenge_id);
        unset($udata);
        if ($row['TYPE'] == 2) {
	  $credits = new Credits();
          $credits->freezeCredits($auth->getUserId(), $row['STAKE']);
        }
        if ($row['TYPE'] == 1) {
          // check limit and clean if necessary
	    $sql = "SELECT sum(MC.STAKE) STAKES
	              FROM manager_challenges MC
        	     WHERE MC.SEASON_ID=".$this->mseason_id." 
               	       AND (MC.USER_ID=".$auth->getUserId()."
		       	    OR MC.USER2_ID=".$auth->getUserId().")
        	       AND MC.STATUS=2	
	               AND MC.TYPE=1";
            $db->query($sql);
            if ($row = $db->nextRow()) {
              if ($row['STAKES'] >= $this->default_money/100) {
                // remove all left challenges 
                $db->delete('manager_challenges', "STATUS=1 AND TYPE=1
						and SEASON_ID=".$this->mseason_id. " 
						AND (USER_ID=".$auth->getUserId()."
					       	    OR USER2_ID=".$auth->getUserId().")");
	        $manager_user_log = new ManagerUserLog();
	        $manager_user_log->logEvent($auth->getUserId(), 9, 0, $this->mseason_id);

              }
            }
	    $sql = "SELECT sum(MC.STAKE) STAKES
	              FROM manager_challenges MC
        	     WHERE MC.SEASON_ID=".$this->mseason_id." 
               	       AND (MC.USER_ID=".$user_id1."
		       	    OR MC.USER2_ID=".$user_id1.")
        	       AND MC.STATUS=2	
	               AND MC.TYPE=1";
            $db->query($sql);           
            if ($row = $db->nextRow()) {
              if ($row['STAKES'] >= $this->default_money/100) {
                // remove all left challenges 
                $db->delete('manager_challenges', "STATUS=1 AND TYPE=1
						and SEASON_ID=".$this->mseason_id. " 
						AND (USER_ID=".$user_id1."
					       	    OR USER2_ID=".$user_id1.")");
	        $manager_user_log = new ManagerUserLog();
	        $manager_user_log->logEvent($user_id1, 9, 0, $this->mseason_id);
              }
            }          
          } else if ($row['TYPE'] == 2) {
	    $sql = "SELECT CREDIT
	              FROM users 
        	     WHERE USER_ID=".$user_id1;
            $db->query($sql);
            unset($sdata);
            $sdata['STATUS'] = 5;
            if ($row = $db->nextRow()) {
                $db->update('manager_challenges', $sdata, "STATUS=1 AND TYPE=2
			and SEASON_ID=".$this->mseason_id. " 
			AND USER2_ID=".$user_id1."
			AND STAKE > ". $row['CREDIT']);
            }

            $db->update('manager_challenges', $sdata, "STATUS=1 AND TYPE=2
				and SEASON_ID=".$this->mseason_id. " 
				AND USER2_ID=".$auth->getUserId()."
				AND STAKE > ". $_SESSION["_user"]["CREDIT"]);
          }
        }
        if ($action=='decline_invite' && isset($challenge_id) && $row['STATUS'] == 1) {
          $udata['STATUS'] = 3;  
          $udata['DATE_REJECTED'] = "NOW()";
          $db->update('manager_challenges', $udata, 'STATUS=1 AND USER2_ID='.$auth->getUserId().' AND CHALLENGE_ID='.$challenge_id);
          unset($udata);
          if ($row['TYPE'] == 2) {
  	    $credits = new Credits();
            $credits->unfreezeCredits($row['USER_ID'], $row['STAKE']);
          }
        }
      }
  } 

  function getChallengesStakes($user_id) {
     global $db;
    
     $sql="SELECT SUM(S.STAKES) AS SUM_STAKES from (
           SELECT SUM(MC.STAKE) as STAKES
             FROM manager_challenges MC
             WHERE MC.SEASON_ID=".$this->mseason_id." 
               AND MC.USER2_ID=".$user_id."
               AND MC.STATUS=2
               AND MC.TYPE=1
	    UNION 
	    SELECT SUM(MC.STAKE) as STAKES
             FROM manager_challenges MC
             WHERE MC.SEASON_ID=".$this->mseason_id." 
               AND MC.USER_ID=".$user_id."
               AND MC.STATUS=2
               AND MC.TYPE=1) S";

      $db->query($sql);    
      $row = $db->nextRow();
      return $row['SUM_STAKES'];
  }


  function getPlayerState($player_id, $season_id, $player_state, $admin = false, $show_grey = false) {
   
    $data= '';
    if (($player_state & 1) == 1) {
      if ($admin) {
        $data['INJURY_STATE'][0]['ON'][0]['ADMIN'][0]['PLAYER_ID'] = $player_id;
        $data['INJURY_STATE'][0]['ON'][0]['ADMIN'][0]['SEASON_ID'] = $season_id;
      } else {
        $data['INJURY_STATE'][0]['ON'][0]['X'] = 1;
      }
    }
    else {
      if ($admin) {
	$data['INJURY_STATE'][0]['OFF'][0]['ADMIN'][0]['PLAYER_ID'] = $player_id;
        $data['INJURY_STATE'][0]['OFF'][0]['ADMIN'][0]['SEASON_ID'] = $season_id;
      } else if ($show_grey) {
        $data['INJURY_STATE'][0]['OFF'][0]['X'] = 1;        
      } else {
        $data['INJURY_STATE'][0]['DEFAULT'][0]['X'] = 1;
      }
    }

    if (($player_state & 2) == 2) {
      if ($admin) {
        $data['SUSPENSION_STATE'][0]['ON'][0]['ADMIN'][0]['PLAYER_ID'] = $player_id;
        $data['SUSPENSION_STATE'][0]['ON'][0]['ADMIN'][0]['SEASON_ID'] = $season_id;
      } else {
        $data['SUSPENSION_STATE'][0]['ON'][0]['X'] = 1;
      }
    }
    else {
      if ($admin) {
	$data['SUSPENSION_STATE'][0]['OFF'][0]['ADMIN'][0]['PLAYER_ID'] = $player_id;
        $data['SUSPENSION_STATE'][0]['OFF'][0]['ADMIN'][0]['SEASON_ID'] = $season_id;
      } else if ($show_grey) {
        $data['SUSPENSION_STATE'][0]['OFF'][0]['X'] = 1;        
      } else {
        $data['SUSPENSION_STATE'][0]['DEFAULT'][0]['X'] = 1;
      }
    }

    if (($player_state & 4) == 4) {
      if ($admin) {
        $data['QUESTIONABLE_STATE'][0]['ON'][0]['ADMIN'][0]['PLAYER_ID'] = $player_id;
        $data['QUESTIONABLE_STATE'][0]['ON'][0]['ADMIN'][0]['SEASON_ID'] = $season_id;
      } else {
        $data['QUESTIONABLE_STATE'][0]['ON'][0]['X'] = 1;
      }
    }
    else {
      if ($admin) {
	$data['QUESTIONABLE_STATE'][0]['OFF'][0]['ADMIN'][0]['PLAYER_ID'] = $player_id;
        $data['QUESTIONABLE_STATE'][0]['OFF'][0]['ADMIN'][0]['SEASON_ID'] = $season_id;
      } else if ($show_grey) {
        $data['QUESTIONABLE_STATE'][0]['OFF'][0]['X'] = 1;        
      } else {
        $data['QUESTIONABLE_STATE'][0]['DEFAULT'][0]['X'] = 1;
      }
    }

    $data['PLAYER_ID'] = $player_id;
    $data['SEASON_ID'] = $season_id;
    return $data;
  }


  function getPlayerStateSmarty($player_id, $season_id, $player_state, $admin = false, $show_grey = false) {
   
    $data= '';
    if (($player_state & 1) == 1) {
      if ($admin) {
        $data['INJURY_STATE_ON']['ADMIN']['PLAYER_ID'] = $player_id;
        $data['INJURY_STATE_ON']['ADMIN']['SEASON_ID'] = $season_id;
      } else {
        $data['INJURY_STATE_ON'] = 1;
      }
    }
    else {
      if ($admin) {
	$data['INJURY_STATE_OFF']['ADMIN']['PLAYER_ID'] = $player_id;
        $data['INJURY_STATE_OFF']['ADMIN']['SEASON_ID'] = $season_id;
      } else if ($show_grey) {
        $data['INJURY_STATE_OFF'] = 1;        
      } else {
        $data['INJURY_STATE_DEFAULT'] = 1;
      }
    }

    if (($player_state & 2) == 2) {
      if ($admin) {
        $data['SUSPENSION_STATE_ON']['ADMIN']['PLAYER_ID'] = $player_id;
        $data['SUSPENSION_STATE_ON']['ADMIN']['SEASON_ID'] = $season_id;
      } else {
        $data['SUSPENSION_STATE_ON'] = 1;
      }
    }
    else {
      if ($admin) {
	$data['SUSPENSION_STATE_OFF']['ADMIN']['PLAYER_ID'] = $player_id;
        $data['SUSPENSION_STATE_OFF']['ADMIN']['SEASON_ID'] = $season_id;
      } else if ($show_grey) {
        $data['SUSPENSION_STATE_OFF'] = 1;        
      } else {
        $data['SUSPENSION_STATE_DEFAULT'] = 1;
      }
    }

    if (($player_state & 4) == 4) {
      if ($admin) {
        $data['QUESTIONABLE_STATE_ON']['ADMIN']['PLAYER_ID'] = $player_id;
        $data['QUESTIONABLE_STATE_ON']['ADMIN']['SEASON_ID'] = $season_id;
      } else {
        $data['QUESTIONABLE_STATE_ON'] = 1;
      }
    }
    else {
      if ($admin) {
	$data['QUESTIONABLE_STATE_OFF']['ADMIN']['PLAYER_ID'] = $player_id;
        $data['QUESTIONABLE_STATE_OFF']['ADMIN']['SEASON_ID'] = $season_id;
      } else if ($show_grey) {
        $data['QUESTIONABLE_STATE_OFF'] = 1;        
      } else {
        $data['QUESTIONABLE_STATE_DEFAULT'] = 1;
      }
    }

    $data['PLAYER_ID'] = $player_id;
    $data['SEASON_ID'] = $season_id;
//print_r($data);
    return $data;
  }


  function getRvsLeagues($open = false) {
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

     $where_open = "";
     if ($open)
       $where_open = " AND ML.STATUS=1";
   
     $sql="SELECT COUNT(*) QUANT
             FROM manager_seasons M, rvs_manager_leagues ML
             WHERE ML.SEASON_ID=".$this->mseason_id." 
               AND M.SEASON_ID=".$this->mseason_id.$where_open;
     $db->query($sql);    
     $count = 0;
     if ($row = $db->nextRow()) {
       $count = $row['QUANT'];
     }
     // show players
     $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;
    // leagues
     $mleagues = array();
     $sql="SELECT ML.LEAGUE_ID, ML.TOPIC_ID, ML.TITLE, ML.ENTRY_FEE, MLM.STATUS, ML.SEASON_ID, 'y' as ALL_LEAGUES, 
 		  U.USER_NAME, T.POSTS, ML.JOINED as USERS, ML.FREE_TRANSFER_FEE, DRAFT_START_DATE, MLM2.USER_ID as USER_IN,
		  ML.PARTICIPANTS, ML.DISCARDS, ML.FREE_TRANSFERS, ML.DURATION, ML.STATUS as LEAGUE_STATUS, ML.TEAM_SIZE, 
		  C.CCTLD, CD.COUNTRY_NAME, ML.INVITE_TYPE, ML.STATUS as LEAGUE_STATUS, ML.PRIZE_FUND,  ML.RESERVE_SIZE, 
		  ML.REAL_PRIZES, ML.DRAFT_STATE, ML.LEAGUE_TYPE FORMAT, ML.DRAFT_TYPE, ML.DRAFT_START_DATE > NOW() as DRAFTING,
		  DATE_ADD(DRAFT_START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS DRAFT_START_DATE_UTC,
		   ML.MODERATE_TRANSFERS
             FROM rvs_manager_leagues_members MLM, manager_seasons M, users U, rvs_manager_leagues ML
		  LEFT JOIN topic T ON ML.topic_id=T.topic_id 
                  LEFT JOIN rvs_manager_leagues_members MLM2 ON ML.LEAGUE_ID=MLM2.LEAGUE_ID AND (MLM2.STATUS in (1,2))
						AND MLM2.USER_ID = ".($auth->userOn() ? $auth->getUserId() : -1)."
                  LEFT JOIN countries C ON ML.COUNTRY = C.ID
		  LEFT JOIN countries_details CD ON C.ID=CD.ID aND CD.LANG_ID=".$_SESSION['lang_id']."
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
               AND ML.SEASON_ID=".$this->mseason_id." 
               AND M.SEASON_ID=".$this->mseason_id." 
               AND MLM.STATUS=1
               and MLM.USER_ID=U.USER_ID ".$where_open."
	     GROUP BY MLM.LEAGUE_ID 
	     ORDER BY ML.CREATED_DATE, ML.TITLE, MLM.STATUS ASC ".
	     $limitclause;
//echo $sql; 
    $db->query($sql);    
 
    while ($row = $db->nextRow()) {
      if ($row['PARTICIPANTS'] == 0)
	$row['PARTICIPANTS'] = "&infin;";

      if (!empty($row['CCTLD'])) {
        $row['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $row['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }

      if ($row['PARTICIPANTS'] > 0)
	$row['PROGRESS']['PERCENTS'] = $row['USERS'] *100/ $row['PARTICIPANTS'];	

      $league = $row;
      if ($row['STATUS'] == 2) {
        $league['LEAGUE'] = $row;
      }
      else if ($row['STATUS'] == 1) {
        $league['OWN_LEAGUE'] = $row;
      }
      $league['LEAGUE_STATUS'] = $row['LEAGUE_STATUS'];

      if ($row['REAL_PRIZES'] == 'Y') {
        $league['PRIZES'] = 1;
      }

      if (empty($row['POSTS']))
       $league['POSTS'] = 0;

      $league['DRAFT_START_DATE'] = $row['DRAFT_START_DATE'];
      $league['UTC'] = $auth->getUserTimezoneName();
      if ($row['USER_IN'] != "")
        $league['IN_LEAGUE'] = 1;
      $mleagues[] = $league;
    }

    $this->leagues = $count;
    return $mleagues;
  }  

  function cleanBattles() {
     global $db;

     $sql = "SELECT battle_id FROM manager_battles WHERE ((participants > 0 and PARTICIPANTS > JOINED) OR (PARTICIPANTS==0 and JOINED=1)) and STATUS=0 and SEASON_ID=".$this->mseason_id;
     $sdata['STATUS'] = 5;
     $db->update("manager_battles", $sdata, "BATTLE_ID in (".$sql.")");
  }

  function commitBattles() {
     global $db;

     $sql = "SELECT battle_id FROM manager_battles WHERE participants=0 and STATUS=0 and SEASON_ID=".$this->mseason_id;
     $sdata['STATUS'] = 1;
     $db->update("manager_battles_members", $sdata, "BATTLE_ID in (".$sql.")");
  }

  function generateTeamsForBattles() {
     global $db;
     global $auth;

     $sql = "SELECT BATTLE_ID, STAKE FROM manager_battles WHERE STATUS=1 and SEASON_ID=".$this->mseason_id;
     $db->query($sql);
     $battles = array();
     while ($row = $db->nextRow()) {
       $battles[] = $row;
     }

     foreach ($battles as $battle) {
       $this->generateTeamsForBattle($battle['BATTLE_ID'], $battle['STAKE']);
     }    
  }
 
  function generateTeamsForBattle($battle_id, $stake) {
     global $db;

     $sql = "SELECT COUNT(MBM.USER_ID) MEMBERS, MAX(MBM.DATE_JOINED) LASTONE FROM manager_battles_members MBM WHERE MBM.BATTLE_ID=".$battle_id;
     $db->query($sql);     
     $row = $db->nextRow();
     if ($row['MEMBERS'] % 2 == 1) {
       $db->select("manager_battles_members", "USER_ID", "BATTLE_ID=".$battle_id." AND DATE_JOINED='".$row['LASTONE']."'");
       $row = $db->nextRow();
       if ($stake > 0) {
         $credits = new Credits();
         $credit_log = new CreditsLog();
         $credits->updateCredits($row['USER_ID'], $stake); 
         $credit_log->logEvent ($row['USER_ID'], 7, $stake);
         unset($sdata);
	 $sdata['PRIZE_FUND'] = 'PRIZE_FUND-'.$stake;
	 $sdata['JOINED'] = 'JOINED-1';
         $db->update("manager_battles", $sdata, "BATTLE_ID =".$battle_id);
       }
       $db->delete("manager_battles_members", "BATTLE_ID=".$battle_id." AND USER_ID='".$row['USER_ID']."'");
     }

     $sql = "SELECT MBM.USER_ID, IFNULL(MS.POINTS, 0) POINTS, MS.PLACE FROM manager_battles_members MBM
			left join manager_standings MS ON MS.USER_ID=MBM.USER_ID
							AND MS.MSEASON_ID=".$this->mseason_id."
                     WHERE MBM.BATTLE_ID=".$battle_id."
			ORDER BY IFNULL(MS.PLACE, 10000) DESC";
     $db->query($sql);
     $c = 0;
     $participants = array();
     while ($row = $db->nextRow()) {
       $participants[] = $row;
     }
    
     foreach ($participants as $participant) {
       unset($sdata);
       if ($c % 4 == 0 || $c % 4 == 3) 
         $sdata['TEAM_ID'] = 1;
       else $sdata['TEAM_ID'] = 2;
       $db->update("manager_battles_members", $sdata, "BATTLE_ID =".$battle_id." and USER_ID=".$participant['USER_ID']);
       $c++;
     }

     unset($sdata);
     $sdata['DATE_COMMITED'] = "NOW()";
     $sdata['STATUS'] = 2;
     $db->update("manager_battles", $sdata, "BATTLE_ID =".$battle_id);

  }


  function removeUnfinishedBattles() {
     global $db;
 
     unset($sdata);   
     $sdata['STATUS'] = 5;
     $db->update('manager_battles', $sdata, "STATUS=0 and (PARTICIPANTS > JOINED OR JOINED <= 1) AND SEASON_ID=".$this->mseason_id);
  }

  function getCompletedBattles($user_id = '') {
    global $db;
    global $auth;
    global $_GET;
    global $smarty;
    global $pagingbox;

    if (empty($_GET['page']))
      $page = 1;
    else $page = $_GET['page'];
    if (empty($perpage))
      $perpage = 50; //$page_size;

    $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;

    if ($user_id == '') {
      $sql="SELECT COUNT(MC.BATTLE_ID) BATTLES
             FROM manager_battles MC
             WHERE MC.SEASON_ID=".$this->mseason_id." 
                AND MC.STATUS IN (4)";
    } else {
      $sql="SELECT COUNT(distinct MC.BATTLE_ID) BATTLES
             FROM manager_battles MC, manager_battles_members MBM
             WHERE MC.SEASON_ID=".$this->mseason_id." 
		AND MBM.USER_ID=".$user_id."
		AND MBM.BATTLE_ID=MC.BATTLE_ID
                AND MC.STATUS IN (4)";
    }
    $db->query($sql);    
    $row = $db->nextRow();
    $battles_count=$row['BATTLES'];

    if ($user_id == '') {
      $sql="SELECT MC.BATTLE_ID
             FROM manager_battles MC
             WHERE MC.SEASON_ID=".$this->mseason_id." 
                AND MC.STATUS IN (4)
	  ORDER BY BATTLE_ID DESC ".$limitclause;
    } else {
      $sql="SELECT MC.BATTLE_ID
             FROM manager_battles MC, manager_battles_members MBM
             WHERE MC.SEASON_ID=".$this->mseason_id." 
                AND MC.STATUS IN (4)
		AND MBM.USER_ID=".$user_id."
		AND MBM.BATTLE_ID=MC.BATTLE_ID
	  ORDER BY BATTLE_ID DESC ".$limitclause;
    }
    $db->query($sql);    
    $battle_ids = "";
    $pre = "";
    while($row = $db->nextRow()){
      $battle_ids .= $pre.$row['BATTLE_ID'];
      $pre = ",";
    }
  // show list of completed battles
    $sql="SELECT MC.BATTLE_ID, U.USER_NAME, U.USER_ID, MC.DATE_INITIATED, MC.DATE_COMMITED, 
		C.CCTLD, CD.COUNTRY_NAME, MU.ALLOW_VIEW, MC.STAKE, MC.TYPE, MC.STATUS, MC.PARTICIPANTS, MC.JOINED,
		MC.STAKE, MC.PRIZE_FUND, MC.TOUR_ID, IFNULL(MUT.POINTS, 0) as POINTS, MC.SCORE1, MC.SCORE2,
		U2.USER_ID as USER_ID2, U2.USER_NAME as USER_NAME2, NULL as IN_BATTLE, MBM.TEAM_ID
             FROM users U, manager_users MU, countries C
		LEFT JOIN countries_details CD ON C.ID=CD.ID AND CD.LANG_ID=".$_SESSION['lang_id']."
		, manager_battles MC
		LEFT JOIN manager_battles_members MBM ON MC.BATTLE_ID=MBM.BATTLE_ID
                    left join users U2 on U2.USER_ID=MBM.USER_ID
		    left join manager_users_tours MUT on MUT.season_id=MC.SEASON_ID
 							AND MUT.TOUR_ID=MC.TOUR_ID
							AND MUT.USER_ID=MBM.USER_ID
             WHERE MC.SEASON_ID=".$this->mseason_id." 
	       AND MC.USER_ID=U.USER_ID	
		AND MU.USER_ID=U.USER_ID
		AND MU.SEASON_ID=MC.SEASON_ID
   	        AND U2.COUNTRY = C.ID
                AND MC.STATUS IN (4) 
		AND MC.BATTLE_ID IN (".$battle_ids.")
	      ORDER BY MC.BATTLE_ID DESC ";
      $db->query($sql);    

      $c = 0;
      $completed_battles = array();
      while ($row = $db->nextRow()) {
        $completed_battle = $row;
        if (!empty($row['CCTLD'])) {
          $completed_battle['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
          $completed_battle['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
        }

        if (!isset($completed_battles[$completed_battle['BATTLE_ID']]))
          $completed_battles[$completed_battle['BATTLE_ID']] = $completed_battle;
        $completed_battles[$completed_battle['BATTLE_ID']]['TEAM'.$completed_battle['TEAM_ID']][] = $completed_battle;
      }

      $paging = $pagingbox->getPagingBox($battles_count);


    if (isset($completed_battles) && count($completed_battles) > 0)
      $smarty->assign("completed_battles", $completed_battles);

    $smarty->assign("paging", $paging);
  }


  function getTournaments($open = false, $active= false) {
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

     $where_open = "";
     $where_active = "";
     if ($open)
       $where_open = " AND ML.STATUS=1";
     if ($active)
       $where_active = " AND ML.STATUS <> 3";
   
     $sql="SELECT COUNT(*) QUANT
             FROM manager_seasons M, manager_tournament ML
             WHERE ML.SEASON_ID=".$this->mseason_id." 
               AND M.SEASON_ID=".$this->mseason_id.$where_open.$where_active;
     $db->query($sql);    
     $count = 0;
     if ($row = $db->nextRow()) {
       $count = $row['QUANT'];
     }
     // show players
     $limitclause = " LIMIT ".(($page-1)*$perpage).",".$perpage;
    // leagues
     $mtournaments = array();
     $sql="SELECT ML.MT_ID, ML.TOPIC_ID, ML.TITLE, ML.ENTRY_FEE, ML.SEASON_ID, 'y' as ALL_LEAGUES, 
 		  U.USER_NAME, T.POSTS, ML.JOINED USERS, ML.PARTICIPANTS, ML.STATUS as TOURNAMENT_STATUS, 
		  C.CCTLD, CD.COUNTRY_NAME, ML.INVITE_TYPE, ML.PRIZE_FUND, ML.TOURNAMENT_TYPE, ML.REAL_PRIZES, ML.DURATION
             FROM manager_seasons M, users U, manager_tournament ML
		  LEFT JOIN topic T ON ML.topic_id=T.topic_id 
                  LEFT JOIN countries C ON ML.COUNTRY = C.ID
		  LEFT JOIN countries_details CD ON C.ID=CD.ID aND CD.LANG_ID=".$_SESSION['lang_id']."
             WHERE ML.SEASON_ID=".$this->mseason_id." 
               AND M.SEASON_ID=".$this->mseason_id." 
               and ML.USER_ID=U.USER_ID ".$where_open.$where_active."
	     ORDER BY ML.CREATED_DATE, ML.TITLE ".
	     $limitclause;
//echo $sql; 
    $db->query($sql);    
 
    while ($row = $db->nextRow()) {
      if ($row['PARTICIPANTS'] == 0)
	$row['PARTICIPANTS'] = "&infin;";

      if (!empty($row['CCTLD'])) {
        $row['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $row['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }

      if ($row['PARTICIPANTS'] > 0)
	$row['PROGRESS']['PERCENTS'] = $row['USERS'] *100/ $row['PARTICIPANTS'];	

      $tournament = $row;
      $tournament['TOURNAMENT'] = $row;
      $tournament['TOURNAMENT_STATUS'] = $row['TOURNAMENT_STATUS'];

      if ($row['REAL_PRIZES'] == 'Y') {
        $tournament['PRIZES'] = 1;
      }

      if (empty($row['POSTS']))
       $tournament['POSTS'] = 0;

      $mtournaments[] = $tournament;
    }

    $this->tournaments = $count;
    return $mtournaments;
  }  

  function sendDraftStartEmail($info) {
      global $_SESSION;
      global $langs;
      global $conf_site_url;
      global $conf_home_dir;

      $user = new User(); 
      include($conf_home_dir.'class/ss_lang_'.$info['LAST_LANG'].'.inc.php');

      $to = $info['EMAIL'];
      $subject = $langs['LANG_EMAIL_RVS_DRAFT_START_SUBJECT'].": ".$info['TITLE'];

      $email = new Email($langs, $info['LAST_LANG']);
      $sdata['USER_NAME'] = $info['USER_NAME'];
      $sdata['TITLE'] = $info['TITLE'];
      $sdata['DRAFT_START_DATE'] = $info['DRAFT_START_DATE_UTC']." ".$user->getUserTimezoneName($info['TIMEZONE']);
      $sdata['URL'] = $conf_site_url.'rvs_manager_drafts.php?league_id='.$info['LEAGUE_ID'].'&lang_id='.$info['LAST_LANG'];
      $email->getEmailFromTemplate ('email_rvs_draft_start', $sdata) ;
      return $email->send($to, $subject);

  }

  function sendDraftTimeSetEmail($info) {
      global $_SESSION;
      global $langs;
      global $conf_site_url;
      global $conf_home_dir;

      $user = new User(); 
      include($conf_home_dir.'class/ss_lang_'.$info['LAST_LANG'].'.inc.php');

      $to = $info['EMAIL'];
      $subject = $langs['LANG_EMAIL_RVS_DRAFT_TIME_SET_SUBJECT'].": ".$info['TITLE'];

      $email = new Email($langs, $info['LAST_LANG']);
      $sdata['USER_NAME'] = $info['USER_NAME'];
      $sdata['TITLE'] = $info['TITLE'];
      $sdata['DRAFT_START_DATE'] = $info['DRAFT_START_DATE_UTC']." ".$user->getUserTimezoneName($info['TIMEZONE']);
      $sdata['URL'] = $conf_site_url.'rvs_manager_league.php?league_id='.$info['LEAGUE_ID'].'&lang_id='.$info['LAST_LANG'];
      $sdata['URL2'] = $conf_site_url.'rvs_manager_drafts_list.php?league_id='.$info['LEAGUE_ID'].'&lang_id='.$info['LAST_LANG'];
      $email->getEmailFromTemplate ('email_rvs_draft_time_set', $sdata) ;
      return $email->send($to, $subject);

  }

  function sendDraftEndEmail($info) {
      global $_SESSION;
      global $langs;
      global $conf_site_url;
      global $conf_home_dir;

      $user = new User(); 
      include($conf_home_dir.'class/ss_lang_'.$info['LAST_LANG'].'.inc.php');

      $to = $info['EMAIL'];
      $subject = $langs['LANG_EMAIL_RVS_DRAFT_END_SUBJECT'].": ".$info['TITLE'];

      $email = new Email($langs, $info['LAST_LANG']);
      $sdata['USER_NAME'] = $info['USER_NAME'];
      $sdata['TITLE'] = $info['TITLE'];
      $sdata['DRAFT_DATE'] = $info['DRAFT_DATE_UTC']." ".$user->getUserTimezoneName($info['TIMEZONE']);
      $sdata['URL'] = $conf_site_url.'rvs_manager_league.php?league_id='.$info['LEAGUE_ID'].'&lang_id='.$info['LAST_LANG'];
      $email->getEmailFromTemplate ('email_rvs_draft_end', $sdata) ;
      return $email->send($to, $subject);

  }

  function getSoloTours() {
      global $db;
      global $manager_user;
      global $position_types;
      global $auth;
      global $_SESSION;
      global $page_size;
//      global $managerbox;
      global $smarty;
        
//$db->showquery=true;              
      $sql = "SELECT DISTINCT DATE_FORMAT(G.START_DATE, '%Y-%m-%d') GAME_DAY, 
			DATE_ADD(DATE_FORMAT(G.START_DATE, '%Y-%m-%d'), INTERVAL 1 DAY) > NOW() FUTURE
		FROM seasons S, manager_tours MT, games G
		WHERE MT.season_id=".$this->mseason_id."
		      and S.season_id in (".$this->seasonlist.") 
		      and G.season_id=S.SEASON_ID
		      and G.PUBLISH='Y'
	              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
		ORDER BY GAME_DAY";
      $db->query($sql);    
//echo $sql;
      $days = array();  
      $dates ="";
      $pre = "";
      while ($row = $db->nextRow()) {
        $day = $row; 
	$days[$row['GAME_DAY']] = $day;
	$days[$row['GAME_DAY']]['SEASON_ID'] = $this->mseason_id;
        if ($row['FUTURE'])
 	  $days[$row['GAME_DAY']]['CAN_CHANGE'] = true;
        else 
 	  $days[$row['GAME_DAY']]['CAN_CHANGE'] = false;
	$dates .= $pre."'".$row['GAME_DAY']."'";
        $pre = ",";
      }

      $sql = "SELECT SMP.PLAYER_ID, MM.FIRST_NAME, MM.LAST_NAME, SMP.GAME_DAY,
		MM.TEAM_NAME2 as TEAM_NAME3, MM.POSITION_ID1, MM.POSITION_ID2, SMP.KOEFF,
		MM.PLAYER_STATE, NOW() < DATE_ADD(SMP.GAME_DAY, INTERVAL 1 DAY) FUTURE, MM.TEAM_ID, MM.SEASON_ID
		FROM solo_manager_players SMP, manager_market MM
			WHERE MM.user_id=SMP.PLAYER_ID 
				AND MM.season_id=".$this->mseason_id."
				AND SMP.SEASON_ID=".$this->mseason_id."
				AND SMP.GAME_DAY in (".$dates.")
				AND SMP.USER_ID=".$auth->getUserId();

      $db->query($sql);    
//      echo $sql;
      $games = array();
      while ($row = $db->nextRow()) {
        $day = $row;
        $day['SUBSEASONS'] = $this->seasonlist;
        $day['PLAYER_STATE_DIV'] = $this->getPlayerStateSmarty($row['USER_ID'], $this->mseason_id, $row['PLAYER_STATE']);
        if (!$row['FUTURE']) {
          $day['CAN_CHANGE'] = false;
          $day['CAN_VIEW'] = true;
        } else if ($row['FUTURE'])
          $day['CAN_CHANGE'] = true;      

        if (!empty($position_types[$this->sport_id][$row['POSITION_ID2']]))
          $day['TYPE_NAME'] = $position_types[$this->sport_id][$row['POSITION_ID1']]."/".$position_types[$this->sport_id][$row['POSITION_ID2']];
        else $day['TYPE_NAME'] = $position_types[$this->sport_id][$row['POSITION_ID1']];

	$days[$row['GAME_DAY']] = $day;
	$games[$row['GAME_DAY']]['TEAM_ID'] = $row['TEAM_ID'];
	$games[$row['GAME_DAY']]['GAME_DAY'] = $row['GAME_DAY'];
      }

      $where = "";
      $pre = "";
      foreach ($games as $game) {
        $where .= $pre."((G.TEAM_ID1 = ".$game['TEAM_ID']." OR G.TEAM_ID2 = ".$game['TEAM_ID'].") 
					 AND DATE_FORMAT(G.START_DATE, '%Y-%m-%d') = '".$game['GAME_DAY']."')";
        $pre = " OR ";
      }
      $sql="SELECT DATE_FORMAT(G.START_DATE, '%Y-%m-%d') GAME_DAY, G.GAME_ID, G.START_DATE,
  	                IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, 
			IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2,
			DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < NOW() as LOCKED,
			DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > NOW() as FUTURE
		FROM games G
		    LEFT JOIN teams T1 ON G.TEAM_ID1 = T1.TEAM_ID
			LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
		    LEFT JOIN teams T2 ON G.TEAM_ID2 = T2.TEAM_ID
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
 	    WHERE ".$where;
      $db->query($sql);    
//echo $sql;
      while ($row = $db->nextRow()) {
        $day = $row; 
        if ($row['LOCKED'])
          $day['CAN_CHANGE'] = false;
        else if ($row['LOCKED'])
          $day['CAN_CHANGE'] = true;      
        if (!$row['FUTURE'])
          $day['CAN_CHANGE'] = false;
        else if ($row['FUTURE'])
          $day['CAN_CHANGE'] = true;      


	$days[$row['GAME_DAY']]['TEAM_NAME1'] = $day['TEAM_NAME1'];
	$days[$row['GAME_DAY']]['TEAM_NAME2'] = $day['TEAM_NAME2'];
	$days[$row['GAME_DAY']]['GAME_ID'] = $day['GAME_ID'];
	$days[$row['GAME_DAY']]['CAN_CHANGE'] = $day['CAN_CHANGE'];
      }	

      // find out if days is set
      $sql="SELECT MAX(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR)) < NOW() as FUTURE, 
			DATE_FORMAT(NOW(), '%Y-%m-%d') as GAME_DAY
		FROM seasons S, manager_tours MT, games G
		WHERE MT.season_id=".$this->mseason_id."
		      and S.season_id in (".$this->seasonlist.") 
		      and G.season_id=S.SEASON_ID
		      and G.PUBLISH='Y'
	              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
 	              AND DATE_FORMAT(G.START_DATE, '%Y-%m-%d') = DATE_FORMAT(NOW(), '%Y-%m-%d')";
      $db->query($sql);    
//echo $sql;
      if ($row = $db->nextRow()) {
        if ($row['FUTURE'] == 1) {
   	  $days[$row['GAME_DAY']]['CAN_CHANGE'] = false;
   	  $days[$row['GAME_DAY']]['CAN_VIEW'] = true;
        }
      }
//echo $sql;


$db->showquery=false;
      return $days;
  }

  function appointPlayer() {
     global $_POST;
     global $auth;
     global $manager;
     global $db;
     
//$db->showquery=true;
     // check that player hasn't been selected before
     $sql = "SELECT * FROM solo_manager_players 
		WHERE user_id=".$auth->getUserId()."
			AND SEASON_ID=".$_POST['season_id']."
			AND PLAYER_ID=".$_POST['player'];
     $db->query($sql);    
     if ($row = $db->nextRow()) {
     } else {
       // check if player available
       $sql = "SELECT * 
		FROM seasons S, games G, manager_tours MT, manager_market MM
	       WHERE MT.season_id=".$manager->mseason_id."
		      and S.season_id in (".$manager->seasonlist.") 
		      and G.season_id=S.SEASON_ID
		      and G.PUBLISH='Y'
	              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
	              AND (G.TEAM_ID1 = MM.TEAM_ID OR G.TEAM_ID2 = MM.TEAM_ID) 
		      AND MM.user_id=".$_POST['player']." 
		      AND MM.season_id=".$manager->mseason_id."
		      AND DATE_FORMAT(G.START_DATE, '%Y-%m-%d') = '".$_POST['game_day']."'
                      AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > NOW()";
       $db->query($sql);    
       if ($row = $db->nextRow()) {
         // check that there are no locked players
          $sql = "SELECT * 
		FROM seasons S, games G, manager_tours MT, manager_market MM, solo_manager_players SMP
	       WHERE MT.season_id=".$manager->mseason_id."
		      and S.season_id in (".$manager->seasonlist.") 
		      and G.season_id=S.SEASON_ID
		      and G.PUBLISH='Y'
	              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
	              AND (G.TEAM_ID1 = MM.TEAM_ID OR G.TEAM_ID2 = MM.TEAM_ID) 
		      AND MM.user_id=SMP.PLAYER_ID
		      AND MM.season_id=".$manager->mseason_id."
		      AND SMP.season_id=".$manager->mseason_id."
		      AND SMP.user_id=".$auth->getUserId()."
		      AND SMP.GAME_DAY= '".$_POST['game_day']."'
		      AND DATE_FORMAT(G.START_DATE, '%Y-%m-%d') = '".$_POST['game_day']."'
                      AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < NOW()";
         $db->query($sql);    
         if ($row = $db->nextRow()) {
         } else {
           $db->delete("solo_manager_players", "SEASON_ID=".$_POST['season_id']." AND USER_ID=".$auth->getUserId()." AND GAME_DAY='".$_POST['game_day']."'");
           unset($sdata);
           $sdata['SEASON_ID']=$_POST['season_id'];
	   $sdata['PLAYER_ID']=$_POST['player'];
	   $sdata['USER_ID']=$auth->getUserId();
	   $sdata['GAME_DAY']="'".$_POST['game_day']."'";
	   $sdata['DATE_SELECTED']="NOW()";
           $db->insert("solo_manager_players", $sdata);
           
           $soloManagerUserLog = new SoloManagerUserLog();
           $soloManagerUserLog->logEvent ($auth->getUserId(), 2, 0, $_POST['season_id'], $_POST['player'], '', '', $_POST['game_day']);
         }
       }
     }   
  }

  function getSoloToursSchedule($today) {
      global $db;
      global $auth;

      $utc = $auth->getUserTimezoneName();
      $sql="SELECT DISTINCT DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d') NUMBER
	     FROM seasons S, manager_tours MT, games G
	    WHERE MT.season_id=".$this->mseason_id."
		  and S.season_id in (".$this->seasonlist.") 
		  and G.season_id=S.SEASON_ID
		  and G.PUBLISH='Y'
	          and MT.START_DATE < G.start_DATE 
		  and MT.END_DATE > G.START_DATE
            ORDER BY DATE_FORMAT(G.START_DATE, '%Y-%m-%d')";

      $db->query($sql);
      $tours = array();
      while ($row = $db->nextRow()) {
         unset($tour);
         $tour['NUMBER'] = $row['NUMBER'];
         if ($today != '') {
           if ($today == $row['NUMBER']) {
             $tour['INVISIBLE'] = 1;
             $tour['VISIBLE_DIV'] = 1;
           } else {
             $tour['VISIBLE'] = 1;
             $tour['INVISIBLE_DIV'] = 1;
           }
         }
         $tours[$row['NUMBER']] = $tour; 
       }
      $db->free();

       $sql="SELECT G.GAME_ID, 
             DATE_FORMAT(G.START_DATE, '%Y-%m-%d') NUMBER, 
	     DATE_ADD(G.START_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE,
 	     IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
	     G.START_DATE > NOW() NOT_STARTED,
	     G.SCORE1, G.SCORE2
	     FROM  manager_tours MT, seasons S, games G
		LEFT JOIN teams T1 ON G.TEAM_ID1 = T1.TEAM_ID
			LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN teams T2 ON G.TEAM_ID2 = T2.TEAM_ID
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
	    WHERE MT.season_id=".$this->mseason_id."
	      and S.season_id in (".$this->seasonlist.") 
	      and G.season_id=S.SEASON_ID
		and G.PUBLISH='Y'
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
            ORDER BY DATE_FORMAT(G.START_DATE, '%Y-%m-%d'), G.START_DATE";

      $db->query($sql);
      while ($row = $db->nextRow()) {
         unset($game);
         $game = $row;
         $game['UTC'] = $utc;
	 if ($row['SCORE1'] + $row['SCORE2'] >= 0)
	   $game['RESULT'] = $row;
         if ($row['NOT_STARTED'] == 1)
           $game['CAN_REPORT'] = 1;
         $tours[$row['NUMBER']]['GAMES'][] = $game;
       }

      return $tours;
  }


  function getSoloTourSchedule($nearest_tour) {
      global $db;
      global $auth;

      $utc = $auth->getUserTimezoneName();
      $sql="SELECT DISTINCT DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d') NUMBER
	     FROM seasons S, manager_tours MT, games G
	    WHERE MT.season_id=".$this->mseason_id."
		  and S.season_id in (".$this->seasonlist.") 
		  and G.season_id=S.SEASON_ID
		  and G.PUBLISH='Y'
	          and MT.START_DATE < G.start_DATE 
		  and MT.END_DATE > G.START_DATE
                  and G.START_DATE >= DATE_FORMAT(NOW(), '%Y-%m-%d')
                  and G.START_DATE < DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL 48 HOUR), '%Y-%m-%d')
		  AND MT.NUMBER =".$nearest_tour."
            ORDER BY DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d')";

      $db->query($sql);
      $tours = array();
      while ($row = $db->nextRow()) {
         unset($tour);
         $tour['NUMBER'] = $row['NUMBER'];
         $tours[$row['NUMBER']] = $tour; 
       }
      $db->free();

       $sql="SELECT G.GAME_ID, 
             DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d') NUMBER, 
	     DATE_ADD(G.START_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE,
 	     IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
	     G.START_DATE > NOW() NOT_STARTED,
	     G.SCORE1, G.SCORE2
	     FROM  manager_tours MT, seasons S, games G
		LEFT JOIN teams T1 ON G.TEAM_ID1 = T1.TEAM_ID
			LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN teams T2 ON G.TEAM_ID2 = T2.TEAM_ID
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
	    WHERE MT.season_id=".$this->mseason_id."
	      and S.season_id in (".$this->seasonlist.") 
	      and G.season_id=S.SEASON_ID
		and G.PUBLISH='Y'
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
              and G.START_DATE >= DATE_FORMAT(NOW(), '%Y-%m-%d')
              and G.START_DATE < DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL 48 HOUR), '%Y-%m-%d')
 	      AND MT.NUMBER =".$nearest_tour."
           ORDER BY DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d'), G.START_DATE";

      $db->query($sql);
      while ($row = $db->nextRow()) {
         unset($game);
         $game = $row;
         $game['UTC'] = $utc;
	 if ($row['SCORE1'] + $row['SCORE2'] >= 0)
	   $game['RESULT'] = $row;
         if ($row['NOT_STARTED'] == 1)
           $game['CAN_REPORT'] = 1;
         $tours[$row['NUMBER']]['GAMES'][] = $game;
       }
      return $tours;
  }

  function getSoloSingleTourSchedule($day) {
      global $db;
      global $auth;

      $utc = $auth->getUserTimezoneName();


       $sql="SELECT G.GAME_ID, 
             DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d') NUMBER, 
	     DATE_ADD(G.START_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE,
 	     IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
	     G.START_DATE > NOW() NOT_STARTED,
	     G.SCORE1, G.SCORE2
	     FROM  manager_tours MT, seasons S, games G
		LEFT JOIN teams T1 ON G.TEAM_ID1 = T1.TEAM_ID
			LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN teams T2 ON G.TEAM_ID2 = T2.TEAM_ID
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
	    WHERE MT.season_id=".$this->mseason_id."
	      and S.season_id in (".$this->seasonlist.") 
	      and G.season_id=S.SEASON_ID
		and G.PUBLISH='Y'
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
              and G.START_DATE >= DATE_FORMAT('".$day."', '%Y-%m-%d')
              and G.START_DATE < DATE_FORMAT(DATE_ADD('".$day."', INTERVAL 1 DAY), '%Y-%m-%d')
           ORDER BY DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d'), G.START_DATE";
//echo $sql;
      $tour['NUMBER'] = $day;	
      $db->query($sql);
      while ($row = $db->nextRow()) {
         unset($game);
         $game = $row;
         $game['UTC'] = $utc;
	 if ($row['SCORE1'] + $row['SCORE2'] >= 0)
	   $game['RESULT'] = $row;
         if ($row['NOT_STARTED'] == 1)
           $game['CAN_REPORT'] = 1;
         $tour['GAMES'][] = $game;
       }
      return $tour;
  }

}

?>