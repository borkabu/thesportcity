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

class BracketUser {
  var $tseason_id;
  var $inited;
  var $leagues;
  
  function BracketUser($tseason_id) {
    $this->tseason_id = $tseason_id;

    $this->initUser();
  }


  function initUser() {
    global $auth;
    global $db;
    global $_SESSION;

    $sql = "SELECT *
             FROM users U LEFT JOIN bracket_users MU ON U.USER_ID=MU.USER_ID and MU.SEASON_ID=".$this->tseason_id."
			LEFT JOIN bracket_standings MS ON U.USER_ID=MS.USER_ID and MS.MSEASON_ID=".$this->tseason_id."
             WHERE U.USER_ID=".$auth->getUserId()." AND MU.SEASON_ID=".$this->tseason_id;
    $db->query($sql); 
    if ($row = $db->nextRow()) {
	  $_SESSION['_user']['ARRANGER'][$this->tseason_id]['IGNORE_LEAGUES'] = $row['IGNORE_LEAGUES'];
	  $_SESSION['_user']['ARRANGER'][$this->tseason_id]['USE_DRAGDROP'] = $row['USE_DRAGDROP'];
	  $_SESSION['_user']['ARRANGER'][$this->tseason_id]['ALLOW_VIEW'] = $row['ALLOW_VIEW'];
	  $_SESSION['_user']['ARRANGER'][$this->tseason_id]['PLACE'] = $row['PLACE'];
	  $_SESSION['_user']['ARRANGER'][$this->tseason_id]['POINTS'] = $row['POINTS'];

          $db->free();
          $this->inited = true;
    }
    else $this->inited = false;

  }

  function initLeagues() {

  }

  function getGames() {
  }

  function getRace($race_id) {
    global $db;
    global $auth;

    $data = "";
    $sql = "SELECT DISTINCT M.ID, M.USER_ID M_USER_ID, M.TEAM_ID M_TEAM_ID, M.NUM, 
               U.FIRST_NAME, U.LAST_NAME, T.TEAM_NAME, BA.PLACE
	        FROM busers U, team_seasons TS, teams T, games_races G, members M
        	    LEFT JOIN bracket_arrangements BA ON BA.SEASON_ID=".$this->tseason_id."
		        and BA.USER_ID=".$auth->getUserId()."
			AND BA.GAME_ID =".$race_id."
			AND BA.PILOT_ID=M.USER_ID 
	        WHERE TS.SEASON_ID=G.SEASON_ID
		  AND M.TEAM_ID =TS.TEAM_ID
        	  AND G.GAME_ID=".$race_id."
        	  AND M.USER_ID=U.USER_ID
	          AND M.TEAM_ID=T.TEAM_ID
        	  AND M.DATE_STARTED <= G.START_DATE 
	          AND (M.DATE_EXPIRED >= G.START_DATE OR M.DATE_EXPIRED IS NULL)
	        ORDER BY BA.PLACE, M.NUM+0";

    $db->query($sql); 
    $c = 0;
    while ($row = $db->nextRow()) {
      if (empty($row['PLACE']) || isset($data['ARRANGED_PILOTS'][$row['PLACE']])) {
        $data['PILOTS'][$c] = $row;
        $c++;
      } else {
        $data['ARRANGED_PILOTS'][$row['PLACE']] = $row; 
      }
    }
    $data['SEASON_ID'] = $this->tseason_id;
    $data['GAME_ID'] = $race_id;
    return $data;
  }


  function getRaceResult($race_id) {
    global $db;
    global $auth;

    $data = "";
    $sql = "SELECT DISTINCT M.ID, M.USER_ID M_USER_ID, M.TEAM_ID M_TEAM_ID, M.NUM, 
               U.FIRST_NAME, U.LAST_NAME, T.TEAM_NAME, BA.PLACE as ARRANGED_PLACE, R.PLACE,
		5 - ABS(BA.PLACE - R.PLACE) AS POINTS
	        FROM busers U, team_seasons TS, teams T, games_races G, members M
        	    LEFT JOIN bracket_arrangements BA ON BA.SEASON_ID=".$this->tseason_id."
		        and BA.USER_ID=".$auth->getUserId()."
			AND BA.GAME_ID =".$race_id."
			AND BA.PILOT_ID=M.USER_ID 
		    LEFT JOIN results_races R ON M.USER_ID=R.USER_ID 
			AND R.GAME_ID =".$race_id."
	        WHERE TS.SEASON_ID=G.SEASON_ID
		  AND M.TEAM_ID =TS.TEAM_ID
        	  AND G.GAME_ID=".$race_id."
        	  AND M.USER_ID=U.USER_ID
	          AND M.TEAM_ID=T.TEAM_ID
        	  AND M.DATE_STARTED <= G.START_DATE 
	          AND (M.DATE_EXPIRED >= G.START_DATE OR M.DATE_EXPIRED IS NULL)
	        ORDER BY BA.PLACE, M.NUM+0";
//echo $sql;
    $db->query($sql); 
    $c = 0;
    $total = 0;
    while ($row = $db->nextRow()) {
      $data['PILOTS'][$c] = $row;
      if ($row['POINTS'] < -5)
      $data['PILOTS'][$c]['POINTS'] = -5;
      $total += $data['PILOTS'][$c]['POINTS'];
      $c++;
    }
    $data['TOTAL'] = $total;
    $data['SEASON_ID'] = $this->tseason_id;
    $data['GAME_ID'] = $race_id;
    return $data;
  }

  function processArrangement() {
    global $_POST;
    global $db;
    global $auth;

    if (isset($_POST['save_arrangement'])) {
      // is user in season
      $db->select("bracket_users", "*", "SEASON_ID=".$this->tseason_id." AND USER_ID=".$auth->getUserId());
      if ($row = $db->nextRow()) {
        // is race active
        $db->select("games_races", "*", "GAME_ID=".$_POST['race_id']." AND START_DATE > NOW()");
        if ($row = $db->nextRow()) {
          if (sizeof($_POST['pilots']) == 22) {
            $db->delete("bracket_arrangements", "SEASON_ID=".$this->tseason_id." AND GAME_ID=".$_POST['race_id']." AND USER_ID=".$auth->getUserId());
            // find all empty races as well
	    $sql="SELECT G.GAME_ID
		FROM bracket_subseasons BS, bracket_tours BT, seasons S, games_races G 
		WHERE BT.season_id=".$this->tseason_id."
			and G.season_id=S.SEASON_ID 
			and G.PUBLISH='Y' 
			and BT.START_DATE < G.start_DATE 
			and BT.END_DATE > G.START_DATE 
			AND BS.WSEASON_ID=BT.SEASON_ID 
			AND BS.SEASON_ID=S.SEASON_ID
			AND G.START_DATE > NOW()
			AND G.GAME_ID NOT IN (SELECT GAME_ID FROM bracket_arrangements BA
						WHERE BA.SEASON_ID=".$this->tseason_id."
						AND BA.USER_ID=".$auth->getUserId().")
			AND G.GAME_ID <> ".$_POST['race_id'];
	    $db->query($sql);
	    $races = array();
            $races[] = $_POST['race_id'];
            while ($row = $db->nextRow()) {
              $races[] = $row['GAME_ID'];
            }

            for ($c = 0; $c < sizeof($_POST['pilots']); $c++) {
              unset($sdata);
              $sdata['season_id'] = $this->tseason_id;
              $sdata['user_id'] = $auth->getUserId();
              $sdata['pilot_id'] = $_POST['pilots'][$c];
              if (isset($_POST['pilot_'.$sdata['pilot_id']])) {
                $sdata['place'] = $_POST['pilot_'.$sdata['pilot_id']];
              }
              else
                $sdata['place'] = $c + 1;

              if (is_numeric($sdata['place']) 
  		&& $sdata['place'] > 0 
		&& $sdata['place'] < 23)            
	      foreach ($races as $race) {
                $sdata['game_id'] = $race;
                $db->insert("bracket_arrangements", $sdata);
              }
            }
  	    $bracket_user_log = new BracketUserLog();
	    $bracket_user_log->logEvent ($auth->getUserId(), 2, $this->tseason_id, $_POST['race_id']);
            return 1;
          }
          else return -3;
        } 
	else return -2;
      }
      else return -1;
    }
  }

  function processRandomArrangement() {
    global $_POST;
    global $db;
    global $auth;

    if (isset($_POST['random_arrangement'])) {
      // is user in season
      $db->select("bracket_users", "*", "SEASON_ID=".$this->tseason_id." AND USER_ID=".$auth->getUserId());
      if ($row = $db->nextRow()) {
        // is race active
        $db->select("games_races", "*", "GAME_ID=".$_POST['race_id']." AND START_DATE > NOW()");
        if ($row = $db->nextRow()) {
          $db->delete("bracket_arrangements", "SEASON_ID=".$this->tseason_id." AND GAME_ID=".$_POST['race_id']." AND USER_ID=".$auth->getUserId());
          $sql = "SELECT DISTINCT M.USER_ID 
	        FROM busers U, team_seasons TS, teams T, games_races G, members M
	        WHERE TS.SEASON_ID=G.SEASON_ID
		  AND M.TEAM_ID =TS.TEAM_ID
        	  AND G.GAME_ID=".$_POST['race_id']."
        	  AND M.USER_ID=U.USER_ID
	          AND M.TEAM_ID=T.TEAM_ID
        	  AND M.DATE_STARTED <= G.START_DATE 
	          AND (M.DATE_EXPIRED >= DATE_ADD(G.START_DATE, INTERVAL -1 WEEK) OR M.DATE_EXPIRED IS NULL)
	        ORDER BY RAND()";
          $db->query($sql);    
          $c = 0;
          $pilots = "";
          while ($row = $db->nextRow()) {
            $pilots[$c] = $row['USER_ID'];
            $c++;
          }
          for ($c = 0; $c < sizeof($pilots); $c++) {
            unset($sdata);
            $sdata['season_id'] = $this->tseason_id;
            $sdata['game_id'] = $_POST['race_id'];
            $sdata['user_id'] = $auth->getUserId();
            $sdata['place'] = $c + 1;
            $sdata['pilot_id'] = $pilots[$c];
            $db->insert("bracket_arrangements", $sdata);
          }
	  $bracket_user_log = new BracketUserLog();
	  $bracket_user_log->logEvent ($auth->getUserId(), 2, $this->tseason_id, $_POST['race_id']);
          return 1;
        } 
	else return -2;
      }
      else return -1;
    }
  }

  function copyLastRaceResults() {
    global $_POST;
    global $db;
    global $auth;

    if (isset($_POST['copy_results'])) {
      // is user in season
      $db->select("bracket_users", "*", "SEASON_ID=".$this->tseason_id." AND USER_ID=".$auth->getUserId());
      if ($row = $db->nextRow()) {
        // is race active
        $db->select("games_races", "*", "GAME_ID=".$_POST['race_id']." AND START_DATE > NOW()");
        if ($row = $db->nextRow()) {
          $db->delete("bracket_arrangements", "SEASON_ID=".$this->tseason_id." AND GAME_ID=".$_POST['race_id']." AND USER_ID=".$auth->getUserId());
          // get last race with rezults
          $sql = "SELECT DISTINCT RR.GAME_ID
	        FROM games_races G, results_races RR, bracket_subseasons BS
	        WHERE G.START_DATE < NOW()
		  AND RR.GAME_ID=G.GAME_ID
                  AND BS.SEASON_ID=G.SEASON_ID
                  AND BS.WSEASON_ID=".$_POST['season_id']."
                ORDER BY G.START_DATE DESC
                LIMIT 1";
          $db->query($sql);    
          if ($row = $db->nextRow()) {
            $race_id = $row['GAME_ID'];

            $sql = "SELECT DISTINCT RR.USER_ID
	        FROM games_races G, results_races RR
	        WHERE G.START_DATE < NOW()
		  AND RR.GAME_ID=G.GAME_ID
		  AND G.GAME_ID=".$race_id."
                ORDER BY RR.PLACE ASC";
            $db->query($sql);    
            $c = 0;
            $pilots = "";
            while ($row = $db->nextRow()) {
              $pilots[$c] = $row['USER_ID'];
              $c++;
            }
            for ($c = 0; $c < sizeof($pilots); $c++) {
              unset($sdata);
              $sdata['season_id'] = $this->tseason_id;
              $sdata['game_id'] = $_POST['race_id'];
              $sdata['user_id'] = $auth->getUserId();
              $sdata['place'] = $c + 1;
              $sdata['pilot_id'] = $pilots[$c];
              $db->insert("bracket_arrangements", $sdata);
            }
	    $bracket_user_log = new BracketUserLog();
	    $bracket_user_log->logEvent ($auth->getUserId(), 2, $this->tseason_id, $_POST['race_id']);
            return 1;
          } 
        } 
	else return -2;
      }
      else return -1;
    }
  }

  function copyLastRaceArrangement() {
    global $_POST;
    global $db;
    global $auth;

    if (isset($_POST['copy_arrangement'])) {
      // is user in season
      $db->select("bracket_users", "*", "SEASON_ID=".$this->tseason_id." AND USER_ID=".$auth->getUserId());
      if ($row = $db->nextRow()) {
        // is race active
        $db->select("games_races", "*", "GAME_ID=".$_POST['race_id']." AND START_DATE > NOW()");
        if ($row = $db->nextRow()) {
          $db->delete("bracket_arrangements", "SEASON_ID=".$this->tseason_id." AND GAME_ID=".$_POST['race_id']." AND USER_ID=".$auth->getUserId());
          // get last race with rezults
          $sql = "SELECT DISTINCT BA.GAME_ID
	        FROM games_races G, bracket_arrangements BA, bracket_subseasons BS
	        WHERE G.START_DATE < NOW()
		  AND BA.GAME_ID=G.GAME_ID
		  AND BA.SEASON_ID=BS.WSEASON_ID
		  AND BA.USER_ID=".$auth->getUserID()."
                  AND BS.SEASON_ID=G.SEASON_ID
                  AND BS.WSEASON_ID=".$_POST['season_id']."
                ORDER BY G.START_DATE DESC
                LIMIT 1";
          $db->query($sql);    
          if ($row = $db->nextRow()) {
            $race_id = $row['GAME_ID'];

            $sql = "SELECT DISTINCT BA.PILOT_ID
	        FROM bracket_arrangements BA
	        WHERE BA.SEASON_ID=".$_POST['season_id']."
		  AND BA.GAME_ID=".$race_id."
		  AND BA.USER_ID=".$auth->getUserID()."
                ORDER BY BA.PLACE ASC";
            $db->query($sql);    
            $c = 0;
            $pilots = "";
            while ($row = $db->nextRow()) {
              $pilots[$c] = $row['PILOT_ID'];
              $c++;
            }
            for ($c = 0; $c < sizeof($pilots); $c++) {
              unset($sdata);
              $sdata['season_id'] = $this->tseason_id;
              $sdata['game_id'] = $_POST['race_id'];
              $sdata['user_id'] = $auth->getUserId();
              $sdata['place'] = $c + 1;
              $sdata['pilot_id'] = $pilots[$c];
              $db->insert("bracket_arrangements", $sdata);
            }
	    $bracket_user_log = new BracketUserLog();
	    $bracket_user_log->logEvent ($auth->getUserId(), 2, $this->tseason_id, $_POST['race_id']);
            return 1;
          } 
        } 
	else return -2;
      }
      else return -1;
    }
  }


  function getLeagues() {
     global $db;
     global $auth;

     $mleagues = array();
     $permissions = new ForumPermission();
     $can_chat = $permissions->canChat();
     $sql="SELECT ML.LEAGUE_ID, ML.TOPIC_ID, ML.TITLE, ML.ENTRY_FEE, MLM.STATUS, ML.SEASON_ID, 'n' as ALL_LEAGUES, 
		U.USER_NAME, T.POSTS, COUNT(MLM2.USER_ID) USERS, TT.MARK_TIME, 
		TT.MARK_TIME < T.LAST_POSTED AS TRACKER, UNIX_TIMESTAMP( TT.MARK_TIME ) AS TSTMP, T.LAST_POSTER_ID, ML.RATING, U.LEAGUE_OWNER_RATING,
		ML.PARTICIPANTS, ML.RECRUITMENT_ACTIVE, ML.ACCEPT_NEWBIES, ML.REAL_PRIZES, ML.TYPE, C.CCTLD, CD.COUNTRY_NAME
             FROM bracket_leagues_members MLM, bracket_seasons M, bracket_leagues ML
                  LEFT JOIN bracket_leagues_members MLM1 ON ML.LEAGUE_ID=MLM1.LEAGUE_ID AND (MLM1.STATUS=1)
                  LEFT JOIN bracket_leagues_members MLM2 ON ML.LEAGUE_ID=MLM2.LEAGUE_ID AND (MLM2.STATUS in (1,2))
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
        if ($row['ACCEPT_NEWBIES'] == 'Y') {
          $row['NOVICES'] = 1;
        }
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

      if ($row['TYPE'] == 1) 
        $row['TOURNAMENT'] = 1; 

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

  function getLeaguesInvites() {
     global $db;
     global $auth;

     $league_invites = array();
   // get invitations
     $sql="SELECT ML.LEAGUE_ID, ML.TITLE, ML.ENTRY_FEE
             FROM bracket_leagues ML, bracket_leagues_members MLM
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

}

?>