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

class ManagerTournament {
  var $mt_id;
  var $mseason_id;
  var $fee;
  var $status;
  var $title;
  var $rules;
  var $participants;
  var $joined;
  var $prize_fund;
  var $invite_type;
  var $invite_code;
  var $owner;
  var $start_tour;
  var $end_tour;
  var $type;
  var $duration;
  var $seasonlist;
  var $registration_end_date;
  var $registration_allowed;
  
  function ManagerTournament($mt_id = '') {
    if (empty($mt_id))
      $this->getSeason();
    else $this->mt_id = $mt_id;
    $this->getSeasonDetails();
  }

  function getSeasonDetails() {
    global $db; 
    global $auth; 

    $sql = "SELECT *, NOW() > START_DATE AND PARTICIPANTS > JOINED AS REGISTRATION_ALLOWED, USER_NAME
		FROM manager_tournament MT, users U 
		WHERE MT.USER_ID = U.USER_ID
 		    AND MT.MT_ID=".$this->mt_id;
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $this->status = $row['STATUS'];
      $this->fee = $row['ENTRY_FEE'];
      $this->rules = $row['RULES'];
      $this->mseason_id = $row['SEASON_ID'];
      $this->participants = $row['PARTICIPANTS'];
      $this->joined = $row['JOINED'];
      $this->prize_fund = $row['PRIZE_FUND'];
      $this->title = $row['TITLE'];
      $this->invite_type = $row['INVITE_TYPE'];
      $this->invite_code = $row['INVITE_CODE'];
      $this->owner = $row['USER_NAME'];
      $this->user_id = $row['USER_ID'];
      $this->real_prizes = $row['REAL_PRIZES'];
      $this->start_tour = $row['START_TOUR'];
      $this->end_tour = $row['END_TOUR'];
      $this->type = $row['TOURNAMENT_TYPE'];
      $this->duration = $row['DURATION'];
      if ($auth->userOn()) {
        $_SESSION['_user']['MANAGER_SEASON_ID'] = $this->mseason_id;
      }
      $this->registration_allowed = $row['REGISTRATION_ALLOWED'];
      $this->registration_end_date = $row['REGISTRATION_END_DATE'];
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

  }

  function getSeason() {
    global $db;
    global $auth;
    global $_SESSION; 
    global $_GET; 

    if (isset($_SESSION['_user']['MANAGER_TOURNAMENT_SEASON_ID']) && !isset($_GET['mt_id'])) {
      $this->mt_id = $_SESSION['_user']['MANAGER_TOURNAMENT_SEASON_ID'];
    } else if (!isset($_GET['mt_id']) && isset($_COOKIE['mt_id'])) {
      $this->mt_id = $_COOKIE['mt_id'];
    } else if (!isset($_GET['mt_id'])) {
      $db->select("manager_tournament", "*", "END_DATE > NOW()");
      if ($row = $db->nextRow()) {
        $this->mt_id = $row['MT_ID'];
      }
    }
    else $this->mt_id = $_GET['mt_id'];

    if ($auth->userOn()) {
      $_SESSION['_user']['MANAGER_TOURNAMENT_SEASON_ID'] = $this->mt_id;
    }

    return $this->mt_id;
  }

  function getPrizes() {
    global $_SESSION;
    global $db;

    $sql = "SELECT FD.PRIZES
   	      FROM manager_tournament_details FD 
           WHERE  FD.LANG_ID=".$_SESSION['lang_id']." 
                AND FD.MT_ID=".$this->mt_id;

    $db->query($sql);
    if ($row = $db->nextRow()) {
      return $row['PRIZES'];
    }
    return '';
  }

  function getTitle() {
    global $_SESSION;
    global $db;

    $sql = "SELECT FD.SEASON_TITLE
   	      FROM manager_tournament_details FD 
           WHERE  FD.LANG_ID=".$_SESSION['lang_id']." 
                AND FD.MT_ID=".$this->mt_id;

    $db->query($sql);
    if ($row = $db->nextRow()) {
      return $row['SEASON_TITLE'];
    }
    return '';
  }

  function getToursSchedule() {
      global $db;
      global $auth;

      $data['TOURS'] = '';
      $utc = $auth->getUserTimezoneName();
      $sql="SELECT MT.NUMBER, MT.ROUND,
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
	     DATE_ADD(MT.END_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE
	     FROM manager_tournament_tours MT
	    WHERE MT.mt_id=".$this->mt_id."
            ORDER BY MT.NUMBER, MT.ROUND";
      $db->query($sql);

      $c = 0;
      while ($row = $db->nextRow()) {
         $data['TOURS'][$row['NUMBER']]['NUMBER'] = $row['NUMBER'];
         $data['TOURS'][$row['NUMBER']]['ROUNDS'][$row['ROUND']]['ROUND'] = $row['ROUND'];
         $data['TOURS'][$row['NUMBER']]['ROUNDS'][$row['ROUND']]['TOUR_START_DATE'] = $row['TOUR_START_DATE'];
         $data['TOURS'][$row['NUMBER']]['ROUNDS'][$row['ROUND']]['TOUR_END_DATE'] = $row['TOUR_END_DATE'];
         $data['TOURS'][$row['NUMBER']]['ROUNDS'][$row['ROUND']]['UTC'] = $utc;
         $c++;
       }
      $db->free();


     $sql="SELECT G.GAME_ID, 
	     DATE_ADD(G.START_DATE , INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE,
 	     IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2, 
	     MT.NUMBER, MT.ROUND,
             DATE_ADD(MT.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE, 
  	     DATE_ADD(MT.END_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_END_DATE,
	     G.SCORE1, G.SCORE2
	     FROM  manager_tournament_tours MT, seasons S, games G
		LEFT JOIN teams T1 ON G.TEAM_ID1 = T1.TEAM_ID
			LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN teams T2 ON G.TEAM_ID2 = T2.TEAM_ID
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."
	    WHERE MT.mt_id=".$this->mt_id."
	      and S.season_id in (".$this->seasonlist.") 
	      and G.season_id=S.SEASON_ID
		and G.PUBLISH='Y'
              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
            ORDER BY MT.NUMBER, MT.ROUND, G.START_DATE";

      $db->query($sql);

      $c = 0;
      while ($row = $db->nextRow()) {
         $data['TOURS'][$row['NUMBER']]['ROUNDS'][$row['ROUND']]['GAMES'][$c] = $row;
         $data['TOURS'][$row['NUMBER']]['ROUNDS'][$row['ROUND']]['GAMES'][$c]['UTC'] = $utc;
	 if ($row['SCORE1'] + $row['SCORE2'] >= 0)
	   $data['TOURS'][$row['NUMBER']]['ROUNDS'][$row['ROUND']]['GAMES'][$c]['RESULT'][0] = $row;
         $c++;
       }
      return $data['TOURS'];
  }


  function getUser($user_id) {
    global $db;
    global $_SESSION;

    $sql = "SELECT *
             FROM manager_tournament_users MU 
             WHERE MU.USER_ID=".$user_id." AND MU.MT_ID=".$this->mt_id;

    $db->query($sql); 
    if ($row = $db->nextRow()) {
      return $row;
    }
    return '';
  }

  function setTours() {
    global $manager;
    global $db;

    $sql = "SELECT *
             FROM manager_tours 
             WHERE NOW() <= START_DATE
                   AND SEASON_ID=".$manager->mseason_id;
    $db->query($sql);
    if ($row = $db->nextRow()) {
      // there are tours ahead
       $next_tour = $row['NUMBER'];
    }
    else $next_tour = 1;

    $tours_ahead = $db->rows();
    $sdata['START_TOUR'] = $next_tour;
    if ($this->type == 0) {
      $sdata['END_TOUR'] = $next_tour + ceil(log($this->participants, 2)) - 1;
    } else {
      if (empty($this->duration))
        $this->duration = 7;

      if ($this->duration > $tours_ahead)
        $this->duration = $tours_ahead;
      $sdata['END_TOUR'] = $next_tour + $this->duration - 1;
    }
    $db->update("manager_tournament", $sdata, "MT_ID=".$this->mt_id);
  }
  
  function draw($tour_id) {  
    if ($this->type == 0)
      $this->drawOlympic($tour_id);
    else if ($this->type == 1)
      $this->drawSwiss($tour_id);
    else if ($this->type == 2)
      $this->drawRoundRobin($tour_id);

  }

  function drawOlympic($tour_id) {  
    global $db;
    global $manager;

    if (!empty($this->start_tour) && $this->start_tour <= $tour_id && $this->end_tour >= $tour_id
	&& !$manager->manager_trade_allow) {
      $sql="SELECT * FROM manager_tournament_tours 
			WHERE MT_ID=".$this->mt_id."
				AND NUMBER=".($tour_id-$this->start_tour);    

      $db->query($sql);
      if (!($row = $db->nextRow()) || $row['COMPLETED'] == 1) {

        $sql="SELECT * FROM manager_tournament_tours 
			WHERE MT_ID=".$this->mt_id."
				AND NUMBER=".($tour_id-$this->start_tour+1);    

        $db->query($sql);
        if (!($row = $db->nextRow()) || (empty($row['DRAWN']) && !empty($row['NUMBER']))) {
          unset($sdata);
          $sdata['MT_ID'] = $this->mt_id;
          $sdata['NUMBER'] = $tour_id-$this->start_tour+1;
          $tour_id = $tour_id-$this->start_tour+1;
          $db->insert('manager_tournament_tours', $sdata);
    	  if ($tour_id == 1)
            $sql = "SELECT COUNT(*) PARTICIPANTS 
			FROM manager_tournament_users where mt_id=".$this->mt_id." AND tour=".($tour_id - 1);
          else 
	    $sql = "SELECT COUNT(*) PARTICIPANTS 
			FROM manager_tournament_users where mt_id=".$this->mt_id." AND tour=".($tour_id);
  	  $db->query ( $sql );
	  $row = $db->nextRow ();
	  $participants = $row['PARTICIPANTS'];
          $r = ((($participants & ($participants-1)) == 0) && ($participants > 0));
          $p = $participants;
          if ($r == 0) { // qual round
  //           echo "Qualification round<br>";
             // get number of players for qr
            $p = pow ( 2, floor( log( $participants)  / log( 2 ) ) );
 	    $p = 2*($participants - $p);
	   $sql = "select * from 
			(SELECT MTU.USER_ID, IF(PLACE > 0, PLACE, 100000000) AS PLACE FROM manager_tournament_users MTU
				LEFT JOIN manager_standings MS ON MS.MSEASON_ID=".$this->mseason_id."
								AND MTU.USER_ID=MS.USER_ID
			WHERE MTU.mt_id=".$this->mt_id." 
				AND MTU.tour=".($tour_id - 1)."
			ORDER BY PLACE DESC
			LIMIT ".$p.") qual
		order by rand()";
  
          } else {
           $where_tour = " AND MTU.tour=".$tour_id;
           if ($tour_id == 1)
             $where_tour = " AND MTU.tour=".($tour_id - 1);

	   $sql = "select * from 
			(SELECT MTU.USER_ID, IF(PLACE > 0, PLACE, 100000000) AS PLACE FROM manager_tournament_users MTU
				LEFT JOIN manager_standings MS ON MS.MSEASON_ID=".$this->mseason_id."
								AND MTU.USER_ID=MS.USER_ID
			WHERE MTU.mt_id=".$this->mt_id." 
				".$where_tour."
			ORDER BY PLACE DESC
			LIMIT ".$p.") qual
		order by rand()";
          }
//	echo "Number to participate in qr: $p <br>";
	  $db->query ( $sql );
          $pairs = '';
          $c = 0;
          $t = 0;
      	  while( $row = $db->nextRow ()) {
       	  //prepare pairs
            if ($c % 2 == 0) {
	    $pairs[$t][0] = $row['USER_ID'];
            } else {
	    $pairs[$t][1] = $row['USER_ID'];
              $t++;
            } 
            $c++;
          }
	  $rounds = 1; 
  
          for ($i = 0; $i < count($pairs); $i++) {
              unset ($sdata);
	      $sdata['MT_ID'] = $this->mt_id;
	      $sdata['TOUR'] = $tour_id;
              for ($r = 0; $r < $rounds; $r++) {
	        $sdata['ROUND'] = $r + 1;
	        $sdata['PAIR'] = $i;
	        $sdata['HOME'] = 0;
	        $sdata['USER_ID'] = $pairs[$i][0];
	        $db->insert("manager_tournament_results", $sdata);
	        $sdata['HOME'] = 1;
	        $sdata['USER_ID'] = $pairs[$i][1];
	        $db->insert("manager_tournament_results", $sdata);
              }
          } 
          if ($tour_id == 1) {
            $sql="INSERT INTO manager_tournament_users
		SELECT MT_ID, USER_ID, ".$tour_id.", NULL, POINTS FROM manager_tournament_users WHERE mt_id=".$this->mt_id." AND tour=".($tour_id-1);
            $db->query ( $sql );
          }
          unset ($sdata);
          $sdata['DRAWN'] = 1;
          $db->update("manager_tournament_tours", $sdata, "mt_id=".$this->mt_id." AND number=".$tour_id);
          $manager_tournament_log = new ManagerTournamentLog();
          $manager_tournament_log->logEvent('', 4, $tour_id, 1, $this->mt_id);
        }
      }
    }
  }

  function drawSwiss($tour_id) {  
    global $db;
    global $manager;

    if (!empty($this->start_tour) && $this->start_tour <= $tour_id && $this->end_tour >= $tour_id
	&& !$manager->manager_trade_allow) {
//$db->showquery=true;
      $sql="SELECT * FROM manager_tournament_tours 
			WHERE MT_ID=".$this->mt_id."
				AND NUMBER=".($tour_id-$this->start_tour);    

      $db->query($sql);
      if (!($row = $db->nextRow()) || $row['COMPLETED'] == 1) {

        $sql="SELECT * FROM manager_tournament_tours 
			WHERE MT_ID=".$this->mt_id."
				AND NUMBER=".($tour_id-$this->start_tour+1);    

        $db->query($sql);
        if (!($row = $db->nextRow()) || (empty($row['DRAWN']) && !empty($row['NUMBER']))) {
          unset($sdata);
          $sdata['MT_ID'] = $this->mt_id;
          $sdata['NUMBER'] = $tour_id-$this->start_tour+1;
          $tour_id = $tour_id-$this->start_tour+1;
          $db->insert('manager_tournament_tours', $sdata);
          $sql = "SELECT *
			FROM manager_tournament_users 
			WHERE mt_id=".$this->mt_id." AND tour=".($tour_id);
  	  $db->query ( $sql );
	  while ($row = $db->nextRow ()) {
            $participant_ids[] = $row['USER_ID'];
          }
  	  $participants = $db->rows();
          $r = $participants % 2;
          $p = $participants;

          $players = array();
          $pool = array();
          // get candidates
          $sql = "SELECT MTU.USER_ID, MTU.POINTS, GROUP_CONCAT(DISTINCT MTR2.USER_ID SEPARATOR ', ') AS OPPONENTS
			FROM manager_tournament_users MTU
				LEFT JOIN manager_tournament_results MTR ON MTR.mt_id= MTU.mt_id and MTR.USER_ID=MTU.USER_ID
				LEFT JOIN manager_tournament_results MTR2 ON MTR2.mt_id= MTU.mt_id and MTR2.USER_ID<>MTU.USER_ID AND MTR2.PAIR=MTR.PAIR and MTR2.TOUR=MTR.TOUR
			WHERE MTU.mt_id=".$this->mt_id." and MTU.tour=".($tour_id-1)."
			GROUP BY MTU.USER_ID
			ORDER by MTU.POINTS DESC";
	  $db->query ( $sql );
      	  while( $row = $db->nextRow ()) {
      	    $player = $row;
            $player['PAST_OPPONENTS'] = explode(',', $row['OPPONENTS']);
	    $players[] = $player;
            $pool[] = $player;
          }

          $pairs = array();
          $used_players = array();
          $free_player = array();
          $c = 0;
          $t = 0;
       	  //prepare pairs
      	  foreach($players as $player) {
       	    foreach($pool as &$pl) {  
       	      if ($player['USER_ID'] != $pl['USER_ID']
                  && !in_array($pl['USER_ID'], $player['PAST_OPPONENTS'])
                  && !in_array($pl['USER_ID'], $used_players)
                  && !in_array($player['USER_ID'], $used_players)) {
  	        $pairs[$t][0] = $player['USER_ID'];
  	        $pairs[$t][1] = $pl['USER_ID'];
		$used_players[] = $pl['USER_ID'];
		$used_players[] = $player['USER_ID'];
                $t++;
	        $assigned = true;
                break;
              } 
            }
            if (!in_array($player['USER_ID'], $used_players)) {
              $free_player = $player;
            }
          }
  

          for ($i = 0; $i < count($pairs); $i++) {
              unset ($sdata);
	      $sdata['MT_ID'] = $this->mt_id;
	      $sdata['TOUR'] = $tour_id;
	      $sdata['ROUND'] = 1;
	      $sdata['PAIR'] = $i;
	      $sdata['HOME'] = 0;
	      $sdata['USER_ID'] = $pairs[$i][0];
	      $db->insert("manager_tournament_results", $sdata);
	      $sdata['HOME'] = 1;
	      $sdata['USER_ID'] = $pairs[$i][1];
	      $db->insert("manager_tournament_results", $sdata);
          } 
          if ($tour_id == 1) {
            $sql="INSERT INTO manager_tournament_users
		SELECT MT_ID, USER_ID, ".$tour_id.", NULL, 0 FROM manager_tournament_users WHERE mt_id=".$this->mt_id." AND tour=".($tour_id-1);
            $db->query ( $sql );
          }
          unset ($sdata);	   
          if (isset($free_player['USER_ID']))
            $db->update("manager_tournament_users", "POINTS=POINTS+1", "USER_ID=".$free_player['USER_ID']." AND MT_ID=".$this->mt_id." AND tour=".$tour_id);

          unset ($sdata);
          $sdata['DRAWN'] = 1;
          $db->update("manager_tournament_tours", $sdata, "mt_id=".$this->mt_id." AND number=".$tour_id);
          $manager_tournament_log = new ManagerTournamentLog();
          $manager_tournament_log->logEvent('', 4, $tour_id, 1, $this->mt_id);
        }
      }
    }
  }

  function drawRoundRobin($tour_id) {  
    global $db;
    global $manager;

    if (!empty($this->start_tour) && $this->start_tour <= $tour_id && $this->end_tour >= $tour_id
	&& !$manager->manager_trade_allow) {
      $sql="SELECT * FROM manager_tournament_tours 
			WHERE MT_ID=".$this->mt_id."
				AND NUMBER=".($tour_id-$this->start_tour);    

      $db->query($sql);
      if (!($row = $db->nextRow()) || $row['COMPLETED'] == 1) {

        $sql="SELECT * FROM manager_tournament_tours 
			WHERE MT_ID=".$this->mt_id."
				AND NUMBER=".($tour_id-$this->start_tour+1);    

        $db->query($sql);
        if (!($row = $db->nextRow()) || (empty($row['DRAWN']) && !empty($row['NUMBER']))) {
          unset($sdata);
          $sdata['MT_ID'] = $this->mt_id;
          $sdata['NUMBER'] = $tour_id-$this->start_tour+1;
          $tour_id = $tour_id-$this->start_tour+1;
          $db->insert('manager_tournament_tours', $sdata);
          $sql = "SELECT *
			FROM manager_tournament_users 
			WHERE mt_id=".$this->mt_id." AND tour=0
			ORDER BY USER_ID";
  	  $db->query ( $sql );
          $players = array();
          $c = 0;
	  while ($row = $db->nextRow ()) {
            $participant_ids[] = $row['USER_ID'];
            $players[$c] = $row['USER_ID'];
            $c++;
          }
  	  $participants = $db->rows();
          $p = $participants;

          $half = $p / 2;
          if ($p % 2 == 1) {
            $players[$c] = "dummy";          
            $half = ($p + 1) / 2;
          }

          $indices = array();
	  $indices[0] = 0;
          for ($i = 1; $i < $tour_id; $i++) {
	    $indices[$i]  = $half*2 - $tour_id + $i + 1;
          }
          for ($i = $tour_id; $i < $half*2; $i++) {
	    $indices[$i]  = $i;
          }

          // create two arrays
          $top_array = array();
          $top_array[0] = $players[0];
          $bottom_array = array();
          for ($i = 0; $i < $half; $i++) {
            $top_array[$i] = $players[$indices[$i]];
          }
          for ($i = $half; $i < $participants; $i++) {
            $bottom_array[$i-$half] = $players[$indices[$i]];
          }

//print_r($top_array);
//print_r($bottom_array);
//exit;

          $where_tour = " AND MTU.tour=".$tour_id;
          if ($tour_id == 1)
             $where_tour = " AND MTU.tour=".($tour_id - 1);

          $pairs = '';
          $c = 0;
          $t = 0;
          for ($i = 0; $i < $half; $i++) {
       	  //prepare pairs
	    $pairs[$i][0] = $top_array[$i];
	    $pairs[$i][1] = $bottom_array[$i];
          }
	  $rounds = 1; 
//print_r($pairs);
//exit;
  
          for ($i = 0; $i < count($pairs); $i++) {
              unset ($sdata);
	      $sdata['MT_ID'] = $this->mt_id;
	      $sdata['TOUR'] = $tour_id;
              for ($r = 0; $r < $rounds; $r++) {
	        $sdata['ROUND'] = $r + 1;
	        $sdata['PAIR'] = $i;
	        $sdata['HOME'] = 0;
	        $sdata['USER_ID'] = $pairs[$i][0];
	        $db->insert("manager_tournament_results", $sdata);
	        $sdata['HOME'] = 1;
	        $sdata['USER_ID'] = $pairs[$i][1];
	        $db->insert("manager_tournament_results", $sdata);
              }
          } 
          if ($tour_id == 1) {
            $sql="INSERT INTO manager_tournament_users
		SELECT MT_ID, USER_ID, ".$tour_id.", NULL, POINTS FROM manager_tournament_users WHERE mt_id=".$this->mt_id." AND tour=".($tour_id-1);
            $db->query ( $sql );
          }
          unset ($sdata);
          $sdata['DRAWN'] = 1;
          $db->update("manager_tournament_tours", $sdata, "mt_id=".$this->mt_id." AND number=".$tour_id);
          $manager_tournament_log = new ManagerTournamentLog();
          $manager_tournament_log->logEvent('', 4, $tour_id, 1, $this->mt_id);
        }
      }
    }
  }

}

?>