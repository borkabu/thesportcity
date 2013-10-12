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

class ManagerUser {
  var $mseason_id;
  var $team_players_list;
  var $team_substitutes_list;
  var $team_main_players;
  var $team_substitutes_players;

  var $posit;
  var $substitutes;
  var $user_jokers;
  var $left_jokers;
  var $inited;
  var $solo_inited;
  var $team_size;
  var $active_team_size;
  var $substitutes_size;
  var $leagues;
  var $tournaments;
  var $teams;
  var $substeams;
  
  function ManagerUser($mseason_id) {
    $this->mseason_id = $mseason_id;
    $this->team_players_list = '';
    $this->team_substitutes_list = '';
    $this->posit = '';
    $this->substitutes = array();
    $this->team_main_players = array();
    $this->team_substitutes_players = array();

    $this->user_jokers = 0;
    $this->left_jokers = 0;

    $this->initUser();
    $this->initSoloUser();
    $this->team_size=0;
    $this->active_team_size=0;
  }


  function initUser() {
    global $auth;
    global $db;
    global $_SESSION;

    $where_external="";
    $field_external="";
    if (isset($_SESSION['external_user'])) {
      $where_external = " LEFT JOIN manager_standings_external MSE ON U.USER_ID=MSE.USER_ID and MSE.MSEASON_ID=".$this->mseason_id." AND MSE.SOURCE='".$_SESSION['external_user']['SOURCE']."'";
      $field_external = ", MSE.PLACE as EXTERNAL_PLACE";
    }

    $sql = "SELECT MU.TRANSACTIONS, MU.MONEY, if (date_add(MU.LAST_TRANSFER_DATE, INTERVAL 24 HOUR) < NOW() OR MU.LAST_TRANSFER_DATE is NULL, 1, 0) CAN_TRANSFER, 
		MU.ALLOW_VIEW, SMU.ALLOW_VIEW ALLOW_VIEW_SOLO, MU.IGNORE_LEAGUES, MU.IGNORE_CHALLENGES, SMU.IGNORE_LEAGUES IGNORE_LEAGUES_SOLO, MS.PLACE, MS.POINTS, 
		SMS.PLACE PLACE_SOLO, SMS.POINTS POINTS_SOLO, if (RS.SEASON_ID IS NULL , -1, 1) as REMIND, MU.LAST_REVIEWED, ML.LEAGUE_ID, SML.LEAGUE_ID as SOLO_LEAGUE_ID ".$field_external."
             FROM users U LEFT JOIN manager_users MU ON U.USER_ID=MU.USER_ID and MU.SEASON_ID=".$this->mseason_id."
			LEFT JOIN solo_manager_users SMU ON U.USER_ID=MU.USER_ID and MU.SEASON_ID=".$this->mseason_id."
			LEFT JOIN manager_standings MS ON U.USER_ID=MS.USER_ID and MS.MSEASON_ID=".$this->mseason_id."
			LEFT JOIN solo_manager_standings SMS ON U.USER_ID=SMS.USER_ID and SMS.SEASON_ID=".$this->mseason_id."
			".$where_external."
			LEFT JOIN reminder_subscribe RS ON U.USER_ID=RS.USER_ID and RS.SEASON_ID=".$this->mseason_id." AND RS.TYPE=1
		        LEFT JOIN manager_leagues ML ON U.USER_ID=ML.USER_ID and ML.SEASON_ID=".$this->mseason_id."
		        LEFT JOIN solo_manager_leagues SML ON U.USER_ID=SML.USER_ID and ML.SEASON_ID=".$this->mseason_id."
             WHERE U.USER_ID=".$auth->getUserId()." AND MU.SEASON_ID=".$this->mseason_id;
//echo $sql;
    $db->query($sql); 
    if ($row = $db->nextRow()) {
	  $_SESSION['_user']['MANAGER'][$this->mseason_id]['ALLOW_VIEW'] = $row['ALLOW_VIEW'];
	  $_SESSION['_user']['SOLO_MANAGER'][$this->mseason_id]['ALLOW_VIEW'] = $row['ALLOW_VIEW_SOLO'];
	  $_SESSION['_user']['MANAGER'][$this->mseason_id]['IGNORE_LEAGUES'] = $row['IGNORE_LEAGUES'];
	  $_SESSION['_user']['SOLO_MANAGER'][$this->mseason_id]['IGNORE_LEAGUES'] = $row['IGNORE_LEAGUES_SOLO'];
	  $_SESSION['_user']['MANAGER'][$this->mseason_id]['IGNORE_CHALLENGES'] = $row['IGNORE_CHALLENGES'];
	  $_SESSION['_user']['MANAGER'][$this->mseason_id]['TRANSACTIONS'] = $row['TRANSACTIONS'];
	  $_SESSION['_user']['MANAGER'][$this->mseason_id]['MONEY'] = $row['MONEY'];
	  $_SESSION['_user']['MANAGER'][$this->mseason_id]['POINTS'] = $row['POINTS'];
	  $_SESSION['_user']['MANAGER'][$this->mseason_id]['PLACE'] = $row['PLACE'];
	  $_SESSION['_user']['SOLO_MANAGER'][$this->mseason_id]['POINTS'] = $row['POINTS_SOLO'];
	  $_SESSION['_user']['SOLO_MANAGER'][$this->mseason_id]['PLACE'] = $row['PLACE_SOLO'];
          if (isset($row['EXTERNAL_PLACE']))
  	    $_SESSION['_user']['MANAGER'][$this->mseason_id]['EXTERNAL_PLACE'] = $row['EXTERNAL_PLACE'];
	  $_SESSION['_user']['MANAGER'][$this->mseason_id]['CAN_TRANSFER'] = $row['CAN_TRANSFER'];
	  $_SESSION['_user']['MANAGER'][$this->mseason_id]['REMINDER'] = $row['REMIND'];          
	  $_SESSION['_user']['MANAGER'][$this->mseason_id]['LAST_REVIEWED'] = $row['LAST_REVIEWED'];          
	  $_SESSION['_user']['MANAGER'][$this->mseason_id]['LEAGUE_ID'] = $row['LEAGUE_ID'];          
	  $_SESSION['_user']['SOLO_MANAGER'][$this->mseason_id]['LEAGUE_ID'] = $row['SOLO_LEAGUE_ID'];          

	  if ($_SESSION['_user']['CREDIT'] > 0) {
	    $data['LOGGED'][0]['GET_TRANSACTIONS'][0]['X'] = 1;
	  } else {
            $data['LOGGED'][0]['LOW_CREDITS'][0]['X'] = 1;
	  }

	  if ($_SESSION['_user']['MANAGER'][$this->mseason_id]['TRANSACTIONS'] == 0) {
		$data['LOGGED'][0]['LOW_TRANSACTIONS'][0]['X'] = 1;
		$data['LOGGED'][0]['GET_TRANSACTIONS'][0]['LOW_TRANSACTIONS'][0]['X'] = 1;
	  }
          $db->free();

          $this->inited = true;
    }
    else {
       $this->inited = false;
    }
  }

  function initSoloUser() {
    global $auth;
    global $db;
    global $_SESSION;

    $where_external="";
    $field_external="";
    if (isset($_SESSION['external_user'])) {
      $where_external = " LEFT JOIN solo_manager_standings_external MSE ON U.USER_ID=MSE.USER_ID and MSE.MSEASON_ID=".$this->mseason_id." AND MSE.SOURCE='".$_SESSION['external_user']['SOURCE']."'";
      $field_external = ", MSE.PLACE as EXTERNAL_PLACE";
    }

    $sql = "SELECT MU.IGNORE_LEAGUES, MU.IGNORE_CHALLENGES, MS.PLACE, MS.POINTS, ML.LEAGUE_ID ".$field_external."
             FROM users U LEFT JOIN solo_manager_users MU ON U.USER_ID=MU.USER_ID and MU.SEASON_ID=".$this->mseason_id."
			LEFT JOIN solo_manager_standings MS ON U.USER_ID=MS.USER_ID and MS.SEASON_ID=".$this->mseason_id."
			".$where_external."
		        LEFT JOIN solo_manager_leagues ML ON U.USER_ID=ML.USER_ID and ML.SEASON_ID=".$this->mseason_id."
             WHERE U.USER_ID=".$auth->getUserId()." AND MU.SEASON_ID=".$this->mseason_id;
    $db->query($sql); 
    if ($row = $db->nextRow()) {
	  $_SESSION['_user']['SOLO_MANAGER'][$this->mseason_id]['IGNORE_LEAGUES'] = $row['IGNORE_LEAGUES'];
	  $_SESSION['_user']['SOLO_MANAGER'][$this->mseason_id]['IGNORE_CHALLENGES'] = $row['IGNORE_CHALLENGES'];
	  $_SESSION['_user']['SOLO_MANAGER'][$this->mseason_id]['POINTS'] = $row['POINTS'];
	  $_SESSION['_user']['SOLO_MANAGER'][$this->mseason_id]['PLACE'] = $row['PLACE'];
          if (isset($row['EXTERNAL_PLACE']))
  	    $_SESSION['_user']['SOLO_MANAGER'][$this->mseason_id]['EXTERNAL_PLACE'] = $row['EXTERNAL_PLACE'];
	  $_SESSION['_user']['SOLO_MANAGER'][$this->mseason_id]['LEAGUE_ID'] = $row['LEAGUE_ID'];          

          $this->solo_inited = true;
    }
    else {
       $this->solo_inited = false;
    }
  }

  function initLeagues() {

  }

  function hasLeague() {
     global $_SESSION;
     return !empty($_SESSION['_user']['MANAGER'][$this->mseason_id]['LEAGUE_ID']);
  }

  function hasSoloLeague() {
     global $_SESSION;
     return !empty($_SESSION['_user']['SOLO_MANAGER'][$this->mseason_id]['LEAGUE_ID']);
  }


  function getTeam($tour, $last_tour) {
    global $db;
    global $auth;    
    global $manager;
    global $position_types;
    global $jokers;
    global $smarty;

    unset($this->posit);
    if ($manager->allow_substitutes == 1) {
      $this->getTeamSubstitutes($tour, $last_tour);
    }
    $this->active_team_size = 0;
    $this->teams=array();
    $data='';
    $summary = array();
    $this->left_jokers = $jokers[$manager->sport_id];
        // get team list
    $supporter_times = array('','','');
    if ($auth->hasSupporter()) {  
      $supporter_times[0] = ", MTGD.TIMES";
      $supporter_times[1] = "LEFT JOIN manager_tour_games_team MTGD ON MTGD.TEAM_ID=MM.TEAM_ID";
      $supporter_times[2] = ", 0 AS TIMES";
    }

    $sql = "SELECT DISTINCT MT.ENTRY_ID, MU.MONEY, MT.PLAYER_ID, B.LAST_NAME, B.FIRST_NAME, MM.TEAM_ID,
                MM.USER_ID, MM.POSITION_ID1, MM.POSITION_ID2, IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME2,
                SUBSTRING( MT.BUYING_DATE, 1, 16 ) BUYING_DATE, MPS.TOTAL_POINTS as TOTAL_POINTS_PREV2,
                MPS1.TOTAL_POINTS TOTAL1, MPS.TOTAL_POINTS + MPS.TOTAL_POINTS_PREV AS TOTAL_POINTS, MT.BUYING_PRICE, 
                MPS.CURRENT_VALUE_MONEY AS SELLING_PRICE, MPS1.CURRENT_VALUE_MONEY AS SELLING_PRICE2,
                '1' AS INTOURN, MM.PLAYED, MPS.TOTAL_POINTS_PREV, MPS.KOEFF, MM.INJURY, MM.START_VALUE, MM.TOTAL_POINTS TP, MM.CURRENT_VALUE_MONEY,
		MC.ENTRY_ID AS CAPTAIN ".$supporter_times[0].", MPRT.TIMES AS REPORTS, MM.PLAYER_STATE, MM.TURNING_POINT
		FROM  manager_users MU, manager_teams MT
		LEFT JOIN manager_market MM ON MM.USER_ID = MT.PLAYER_ID AND MM.SEASON_ID =".$this->mseason_id."
		 ".$supporter_times[1]."
                  LEFT JOIN manager_player_reports_temp MPRT ON MPRT.PLAYER_ID=MM.USER_ID
		LEFT JOIN teams T ON MM.TEAM_ID=T.TEAM_ID 
		LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN busers B ON B.USER_ID = MT.PLAYER_ID
		LEFT JOIN manager_player_stats MPS ON MT.PLAYER_ID = MPS.PLAYER_ID AND MPS.SEASON_ID =".$this->mseason_id." AND MPS.TOUR_ID=".($tour-1)."
                LEFT JOIN manager_player_stats MPS1 ON MT.PLAYER_ID = MPS1.PLAYER_ID AND MPS1.SEASON_ID =".$this->mseason_id." AND MPS1.TOUR_ID=".($tour)."
		LEFT JOIN manager_players MP ON MT.PLAYER_ID = MP.PLAYER_ID AND MP.SEASON_ID =".$this->mseason_id."
		LEFT JOIN manager_captain MC ON MT.ENTRY_ID=MC.ENTRY_ID AND MC.END_DATE IS NULL 
          WHERE 
            MU.USER_ID=".$auth->getUserId()."
	    AND MU.SEASON_ID=".$this->mseason_id."
            AND MT.USER_ID=MU.USER_ID
            AND MT.SELLING_DATE IS NULL             
	    AND MT.SEASON_ID=".$this->mseason_id."
            AND MP.PUBLISH='Y'    
          UNION

          SELECT DISTINCT MT.ENTRY_ID, MU.MONEY, MT.PLAYER_ID, B.LAST_NAME, B.FIRST_NAME, MM.TEAM_ID,
                MM.USER_ID, MM.POSITION_ID1, MM.POSITION_ID2, IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME2,
                SUBSTRING( MT.BUYING_DATE, 1, 16 ) BUYING_DATE, MPS.TOTAL_POINTS as TOTAL_POINTS_PREV2,
                MPS1.TOTAL_POINTS TOTAL1, MPS.TOTAL_POINTS + MPS.TOTAL_POINTS_PREV AS TOTAL_POINTS, MT.BUYING_PRICE, 
                MPS.CURRENT_VALUE_MONEY AS SELLING_PRICE, MPS1.CURRENT_VALUE_MONEY AS SELLING_PRICE2,
                '0' AS INTOURN, MM.PLAYED, MPS.TOTAL_POINTS_PREV, MPS.KOEFF, MM.INJURY, MM.START_VALUE, MM.TOTAL_POINTS as TP, MM.CURRENT_VALUE_MONEY,
		MC.ENTRY_ID AS CAPTAIN ".$supporter_times[2].", -1 AS REPORTS, MM.PLAYER_STATE, MM.TURNING_POINT
		FROM  manager_users MU, manager_teams MT
		LEFT JOIN manager_market MM ON MM.USER_ID = MT.PLAYER_ID AND MM.SEASON_ID =".$this->mseason_id."
		LEFT JOIN teams T ON MM.TEAM_ID=T.TEAM_ID 
		LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN busers B ON B.USER_ID = MT.PLAYER_ID
		LEFT JOIN manager_player_stats MPS ON MT.PLAYER_ID = MPS.PLAYER_ID AND MPS.SEASON_ID =".$this->mseason_id." AND MPS.TOUR_ID=".($tour-1)."
                LEFT JOIN manager_player_stats MPS1 ON MT.PLAYER_ID = MPS1.PLAYER_ID AND MPS1.SEASON_ID =".$this->mseason_id." AND MPS1.TOUR_ID=".($tour)."
		LEFT JOIN manager_players MP ON MT.PLAYER_ID = MP.PLAYER_ID AND MP.SEASON_ID =".$this->mseason_id."
		LEFT JOIN manager_captain MC ON MT.ENTRY_ID=MC.ENTRY_ID AND MC.END_DATE IS NULL 
          WHERE 
            MU.USER_ID=".$auth->getUserId()."
	    AND MU.SEASON_ID=".$this->mseason_id."
            AND MT.USER_ID=MU.USER_ID
            AND MT.SELLING_DATE IS NULL             
	    AND MT.SEASON_ID=".$this->mseason_id."
            AND MP.PUBLISH='N'    
           ORDER BY SELLING_PRICE DESC, POSITION_ID1 DESC, BUYING_PRICE DESC, LAST_NAME DESC";
//echo $sql;
   $db->query($sql);
   $c = 0;
   $pre = '';
   $_SESSION['_user']['MANAGER'][$this->mseason_id]['TEAM_PRICE'] = 0;
   $captain = false; 
   $team_players = array();
   $team = array();
   while ($row = $db->nextRow()) {
//print_r($row);
     if ($row['SELLING_PRICE2'] == 0)
       $_SESSION['_user']['MANAGER'][$this->mseason_id]['TEAM_PRICE'] += $row['SELLING_PRICE'];
     else $_SESSION['_user']['MANAGER'][$this->mseason_id]['TEAM_PRICE'] += $row['SELLING_PRICE2'];

     $player = $row;
     $player['KOEFF'] = round($row['KOEFF'], 2);
     $player['SEASON_ID'] = $this->mseason_id;
     $player['COVERED'] = 0;

     if ($auth->hasSupporter()) {  
       $team['TURN_POINT_H'] = 1;
       $team['TIMES_SUPPORT_H'] = 1;
       $player['WILL_PLAY'] = $row['TIMES'];
       $player['TURNING_POINT'] = $row['TURNING_POINT'];
//       $player['TURNING_POINT'] = round(($row['CURRENT_VALUE_MONEY']*($row['PLAYED']+2)- ($row['START_VALUE'] + $row['TP']+1) * 1000)/1000, 2);
     } else {
       unset($player['TURNING_POINT']);
     }

     if (isset($row['SELLING_PRICE'])) {
       $player['SELLING_PRICE'] = $row['SELLING_PRICE'];
     } else {
       $player['SELLING_PRICE'] = 7000;
     }

     $player['PREV_PRICE'] = $player['SELLING_PRICE'];
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
  
//     if ($row['PLAYED'] > 0) {
        $player['PLAYER_SEASON_STATS']['USER_ID'] = $row['USER_ID'];
        $player['PLAYER_SEASON_STATS']['SUBSEASONS'] = $manager->seasonlist;
        $player['PLAYER_SEASON_STATS']['PLAYED'] = $row['PLAYED'];
  //   } 

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

     if ($row['INTOURN']=='0') 
       $player['INT'] = 1;
     else $this->active_team_size++;
     if ($manager->disabled_trade_wrongday == true) {
       $player['WRONG_DAY'][0]=$row;
     }
     else {
       $player['GOOD_DAY']=1;
     }
     $this->team_players_list .= $pre.$row['PLAYER_ID'];
     $pre = ',';
     if (!isset($this->posit[$row['POSITION_ID1']]))
       $this->posit[$row['POSITION_ID1']] = 0;

     if ($row['POSITION_ID2'] != '' && $row['POSITION_ID2'] != 0) {
       if (!isset($this->posit[$row['POSITION_ID2']]))
         $this->posit[$row['POSITION_ID2']] = 0;

       $this->posit[$row['POSITION_ID1']] += 0.5;
       $this->posit[$row['POSITION_ID2']] += 0.5;
     }
     else
     {
       $this->posit[$row['POSITION_ID1']]++;
     } 

     if (!isset($this->teams[$row['TEAM_ID']]))
       $this->teams[$row['TEAM_ID']] = 1;
     else $this->teams[$row['TEAM_ID']]++;

     if (!empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
       $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
     else $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];

     if (!$manager->disabled_trade_wrongday && 
	!$manager->noAvailableSubstituteSlots($row['POSITION_ID1']) &&
        !$manager->noAvailableSubsTeams($row['TEAM_ID'])) 
       $player['CAN_SUBSTITUTE']=1;

     if ($manager->captaincy) {
       $team['CAPTAINCY'] = 1;
       $player['CAPTAINCY']['ENTRY_ID'] = $row['ENTRY_ID'];
       if (!empty($row['CAPTAIN'])) {
          $player['CAPTAINCY']['CAPTAIN'] = 1;
          $captain = true;  
       } else if ($manager->disabled_trade_wrongday == true) {
          $player['CAPTAINCY']['WRONG_DAY'] = 1;
       } else {
          $player['CAPTAINCY']['GOOD_DAY']['SEASON_ID'] = $this->mseason_id;
       }
     }
     $c++;    
     $this->team_size = $c;
     $team_players[] = $player;
   }
   $this->team_main_players = $team_players;
   if ($manager->allow_substitutes == 1) {
     $summary['SUBSTITUTES_Q'] = $this->calculateSubsituteQuality();
   }

   $smarty->assign('team_players', $this->team_main_players);
   if ($manager->allow_substitutes == 1) {
     $smarty->assign('team_substitute_players', $this->team_substitute_players);
   }
   if ($this->team_size > 0)
     $smarty->assign('team', $team);
   $this->updateJokers();

   if (isset($position_types[$manager->sport_id])) {
     foreach ($position_types[$manager->sport_id] as $key => $val) {
       if ($key > 0) {
         $summary['POSITIONS'][$key]['NAME'] = $val;
         if ($this->posit[$key] > 0) 
           $summary['POSITIONS'][$key]['VALUE'] = $this->posit[$key];
         else $summary['POSITIONS'][$key]['VALUE'] = 0;
       }
     }
   }
   $summary['LEFT_JOKERS'] = $this->left_jokers;
   $summary['USED_JOKERS'] = $this->user_jokers;
   $summary['TEAM_SIZE'] = $this->team_size;
   $summary['MAX_TEAM_SIZE'] = $manager->max_players;
   $summary['TEAM_LIMIT'] = $manager->team_limit;

   if ($manager->allow_substitutes == 1) {
     $summary['MAX_SUBSTITUTES'] = count($position_types[$manager->sport_id]) - 1;
     $summary['USED_SUBSTITUTES'] = count($this->substitutes);
     $smarty->assign('allow_substitutes', 1);
     foreach ($position_types[$manager->sport_id] as $key => $val) {
       if ($key > 0) {
         $summary['SUBSTITUTES'][$key]['NAME'] = $val;
         if ($this->substitutes[$key] > 0) 
           $summary['SUBSTITUTES'][$key]['VALUE'] = $this->substitutes[$key];
         else $summary['SUBSTITUTES'][$key]['VALUE'] = 0;
       }
     }
   }

   if ($manager->captaincy) {
     $summary['CAPTAINCY'] = 1;
     if ($captain) {
       $summary['CAPTAIN_SET'] = 1;
     } 
   }

   $completeness = round(($this->active_team_size + $captain + count($this->substitutes)) * 100 / ($manager->max_players + $manager->captaincy + $manager->allow_substitutes * (count($position_types[$manager->sport_id]) - 1)), 2) - round( $_SESSION['_user']['MANAGER'][$this->mseason_id]['MONEY']/2000, 2);
   if ($completeness < 0)
     $completeness = 0;
   if ($completeness > 100)
     $completeness = 100;

   $summary['COMPLETENESS'] = $completeness;
   $_SESSION['_user']['MANAGER'][$this->mseason_id]['ACT_TEAM_SIZE'] = $this->active_team_size;
   $_SESSION['_user']['MANAGER'][$this->mseason_id]['CAPTAIN'] = $captain;

   unset($sdata);
   $sdata['COMPLETENESS'] = $summary['COMPLETENESS'];
   $sdata['LAST_REVIEWED'] = "NOW()";
   $db->update('manager_users', $sdata, 'USER_ID='.$auth->getUserId().' AND SEASON_ID='.$this->mseason_id);

   $summary['LAST_REVIEWED'] = $_SESSION['_user']['MANAGER'][$this->mseason_id]['LAST_REVIEWED'];
   $smarty->assign('summary', $summary);
  }

  function calculateSubsituteQuality() {
     $covered = 0;
     foreach ($this->team_substitute_players as &$sub) {
       foreach ($this->team_main_players as &$main) {
          if ($sub['POSITION_ID1'] == $main['POSITION_ID1'] &&
		$sub['PREV_PRICE'] < $main ['PREV_PRICE']) {
	    $covered++;
	    $sub['COVERED']++;
	    $main['COVERED']++;
          }
       }
     }
     if ($this->team_size == 0)
       return 0;
     return round($covered*100/$this->team_size, 2);
  }

  function getTeamSubstitutes($tour, $last_tour) {
    global $db;
    global $auth;    
    global $manager;
    global $position_types;
    global $smarty;

    unset($this->substitutes);
    $data='';
        // get team list
    $supporter_times = array('','','');
    if ($auth->hasSupporter()) {  
      $supporter_times[0] = ", MTGD.TIMES";
      $supporter_times[1] = "LEFT JOIN manager_tour_games_team MTGD ON MTGD.TEAM_ID=MM.TEAM_ID";
      $supporter_times[2] = ", 0 AS TIMES";
    }

    $sql = "SELECT DISTINCT MT.ENTRY_ID, MU.MONEY, MT.PLAYER_ID, B.LAST_NAME, B.FIRST_NAME, MM.TEAM_ID,
                MM.USER_ID, MM.POSITION_ID1, MM.POSITION_ID2, IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME2,
                SUBSTRING( MT.BUYING_DATE, 1, 16 ) BUYING_DATE, MPS.TOTAL_POINTS as TOTAL_POINTS_PREV2,
                MPS1.TOTAL_POINTS TOTAL1, MPS.TOTAL_POINTS + MPS.TOTAL_POINTS_PREV AS TOTAL_POINTS, MT.BUYING_PRICE, 
                MPS.CURRENT_VALUE_MONEY AS SELLING_PRICE, MPS1.CURRENT_VALUE_MONEY AS SELLING_PRICE2,
                '1' AS INTOURN, MM.PLAYED, MPS.TOTAL_POINTS_PREV, MPS.KOEFF, MM.INJURY, MM.START_VALUE, MM.TOTAL_POINTS TP, MM.CURRENT_VALUE_MONEY,
		MC.ENTRY_ID AS CAPTAIN ".$supporter_times[0].", MPRT.TIMES AS REPORTS, MM.PLAYER_STATE
		FROM  manager_users MU, manager_teams_substitutes MT
		LEFT JOIN manager_market MM ON MM.USER_ID = MT.PLAYER_ID AND MM.SEASON_ID =".$this->mseason_id."
		 ".$supporter_times[1]."
                  LEFT JOIN manager_player_reports_temp MPRT ON MPRT.PLAYER_ID=MM.USER_ID
		LEFT JOIN teams T ON MM.TEAM_ID=T.TEAM_ID 
		LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN busers B ON B.USER_ID = MT.PLAYER_ID
		LEFT JOIN manager_player_stats MPS ON MT.PLAYER_ID = MPS.PLAYER_ID AND MPS.SEASON_ID =".$this->mseason_id." AND MPS.TOUR_ID=".($tour-1)."
                LEFT JOIN manager_player_stats MPS1 ON MT.PLAYER_ID = MPS1.PLAYER_ID AND MPS1.SEASON_ID =".$this->mseason_id." AND MPS1.TOUR_ID=".($tour)."
		LEFT JOIN manager_players MP ON MT.PLAYER_ID = MP.PLAYER_ID AND MP.SEASON_ID =".$this->mseason_id."
		LEFT JOIN manager_captain MC ON MT.ENTRY_ID=MC.ENTRY_ID AND MC.END_DATE IS NULL 
          WHERE 
            MU.USER_ID=".$auth->getUserId()."
	    AND MU.SEASON_ID=".$this->mseason_id."
            AND MT.USER_ID=MU.USER_ID
            AND MT.SELLING_DATE IS NULL             
	    AND MT.SEASON_ID=".$this->mseason_id."
            AND MP.PUBLISH='Y'    
          UNION

          SELECT DISTINCT MT.ENTRY_ID, MU.MONEY, MT.PLAYER_ID, B.LAST_NAME, B.FIRST_NAME, MM.TEAM_ID,
                MM.USER_ID, MM.POSITION_ID1, MM.POSITION_ID2, IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME2,
                SUBSTRING( MT.BUYING_DATE, 1, 16 ) BUYING_DATE, MPS.TOTAL_POINTS as TOTAL_POINTS_PREV2,
                MPS1.TOTAL_POINTS TOTAL1, MPS.TOTAL_POINTS + MPS.TOTAL_POINTS_PREV AS TOTAL_POINTS, MT.BUYING_PRICE, 
                MPS.CURRENT_VALUE_MONEY AS SELLING_PRICE, MPS1.CURRENT_VALUE_MONEY AS SELLING_PRICE2,
                '0' AS INTOURN, MM.PLAYED, MPS.TOTAL_POINTS_PREV, MPS.KOEFF, MM.INJURY, MM.START_VALUE, MM.TOTAL_POINTS as TP, MM.CURRENT_VALUE_MONEY,
		MC.ENTRY_ID AS CAPTAIN ".$supporter_times[2].", -1 AS REPORTS, MM.PLAYER_STATE
		FROM  manager_users MU, manager_teams_substitutes MT
		LEFT JOIN manager_market MM ON MM.USER_ID = MT.PLAYER_ID AND MM.SEASON_ID =".$this->mseason_id."
		LEFT JOIN teams T ON MM.TEAM_ID=T.TEAM_ID 
		LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN busers B ON B.USER_ID = MT.PLAYER_ID
		LEFT JOIN manager_player_stats MPS ON MT.PLAYER_ID = MPS.PLAYER_ID AND MPS.SEASON_ID =".$this->mseason_id." AND MPS.TOUR_ID=".($tour-1)."
                LEFT JOIN manager_player_stats MPS1 ON MT.PLAYER_ID = MPS1.PLAYER_ID AND MPS1.SEASON_ID =".$this->mseason_id." AND MPS1.TOUR_ID=".($tour)."
		LEFT JOIN manager_players MP ON MT.PLAYER_ID = MP.PLAYER_ID AND MP.SEASON_ID =".$this->mseason_id."
		LEFT JOIN manager_captain MC ON MT.ENTRY_ID=MC.ENTRY_ID AND MC.END_DATE IS NULL 
          WHERE 
            MU.USER_ID=".$auth->getUserId()."
	    AND MU.SEASON_ID=".$this->mseason_id."
            AND MT.USER_ID=MU.USER_ID
            AND MT.SELLING_DATE IS NULL             
	    AND MT.SEASON_ID=".$this->mseason_id."
            AND MP.PUBLISH='N'    
           ORDER BY SELLING_PRICE DESC, POSITION_ID1 DESC, BUYING_PRICE DESC, LAST_NAME DESC";
//echo $sql;
   $db->query($sql);

   $c = 0;
   $pre = '';
   $team_players = array();
   $substitutes = array();
   while ($row = $db->nextRow()) {
     $player = $row;
     $player['KOEFF'] = round($row['KOEFF'], 2);
     $player['SEASON_ID'] = $this->mseason_id;
     $player['COVERED'] = 0;

     if (isset($row['SELLING_PRICE'])) {
       $player['SELLING_PRICE'] = $row['SELLING_PRICE'];
     } else {
       $player['SELLING_PRICE'] = 7000;
     }

     if ($auth->hasSupporter()) {  
       $substitutes['TURN_POINT_H'] = 1;
       $substitutes['TIMES_SUPPORT_H'] = 1;
       $player['WILL_PLAY'] = $row['TIMES'];
       $player['TURNING_POINT'] = round(($row['CURRENT_VALUE_MONEY']*($row['PLAYED']+2)- ($row['START_VALUE'] + $row['TP']+1) * 1000)/1000, 2);
     }

     $player['PREV_PRICE'] = $player['SELLING_PRICE'];
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

     if (!isset($this->substeams[$row['TEAM_ID']]))
       $this->substeams[$row['TEAM_ID']] = 1;
     else $this->substeams[$row['TEAM_ID']]++;
     
     if ($row['REPORTS'] == '')
       $player['PLAYER_REPORTS']['REPORTS'] = 0;
     else if ($row['REPORTS'] > 0)
       $player['PLAYER_REPORTS']['REPORTS'] = $row['REPORTS'];

     if ($row['REPORTS'] != -1) {
       $player['PLAYER_REPORTS']['USER_ID'] = $row['USER_ID'];
       $player['PLAYER_REPORTS']['SEASON_ID'] = $this->mseason_id;
     }

     $player['PLAYER_STATE_DIV'] = $manager->getPlayerStateSmarty($row['USER_ID'], $this->mseason_id, $row['PLAYER_STATE']);

     if ($row['INTOURN']=='0') 
       $player['INT'] = 1;
     else $this->active_team_size++;
     if ($manager->disabled_trade_wrongday == true) {
       $player['WRONG_DAY'][0]=$row;
     }
     else {
       $player['GOOD_DAY']=1;
     }
     $this->team_substitutes_list .= $pre.$row['PLAYER_ID'];
     $pre = ',';
     if (!isset($this->posit[$row['POSITION_ID1']]))
       $this->posit[$row['POSITION_ID1']] = 0;

     $this->substitutes[$row['POSITION_ID1']]++;

     if (!empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
       $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
     else $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];

     $c++;    
     $this->substitutes_size = $c;
     $team_players[] = $player;
   }

   $this->team_substitute_players = $team_players;
   if ($this->substitutes_size > 0)
     $smarty->assign('substitutes', $substitutes);

  }

  function updateJokers() {
     global $position_limits;
     global $jokers;
     global $manager;

     if (isset($position_limits[$manager->sport_id])) {
       $keys = array_keys($position_limits[$manager->sport_id]);
       $this->user_jokers = 0;
       foreach ($keys as $key) {
         $this->user_jokers += $this->posit[$key] - $position_limits[$manager->sport_id][$key] > 0 ? $this->posit[$key] - $position_limits[$manager->sport_id][$key] : 0;
       }
       $this->left_jokers = $jokers[$manager->sport_id] - $this->user_jokers;
     }
  }

  function getTeamSize() {
     global $db;
     global $auth;

     $sql = "SELECT 
            COUNT(MT.PLAYER_ID) PLAYERS_NO
          FROM 
            manager_teams MT, busers B, manager_users MU
            LEFT JOIN users U ON U.USER_ID=MU.USER_ID AND MU.SEASON_ID=".$this->mseason_id."
          WHERE 
            U.USER_ID=".$auth->getUserId()."
            AND MT.USER_ID=U.USER_ID
            AND MT.SELLING_DATE IS NULL 
            AND B.USER_ID=MT.PLAYER_ID
	    AND MT.SEASON_ID=".$this->mseason_id;
     $db->query($sql);

     $row = $db->nextRow();
     $this->team_size = $row['PLAYERS_NO'];
     return $row['PLAYERS_NO'];
  }

  function buyPlayer() {
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

     if ($manager->noAvailableSlots($row['POSITION_ID1'], $row['POSITION_ID2'])) 
       return -3;

     if ($row['CURRENT_VALUE_MONEY'] > -1) {     
       if ($manager->manager_trade_allow  && $_SESSION['_user']['MANAGER'][$this->mseason_id]['TRANSACTIONS'] > 0) {
         $players_count = $this->getTeamSize();
         if ($players_count < $manager->max_players
             && !$manager->noAvailableTeams($row['TEAM_ID']) ) {
           // check that user is not in a team already
           $db->query("start transaction");
           $sql = "SELECT USER_ID
                     FROM manager_teams 
                   WHERE 
                     USER_ID=".$auth->getUserId()."
                      AND SELLING_DATE IS NULL 
                      AND PLAYER_ID=".$_POST['player']."
     		      AND SEASON_ID=".$this->mseason_id."
                   ORDER BY BUYING_DATE DESC";
           $db->query($sql);     
           if ($row = $db->nextRow()) {
           }
           else {
             // put into team
            if ($price <= $_SESSION['_user']['MANAGER'][$this->mseason_id]['MONEY']) {
              unset($sdata);
             $sdata['USER_ID']=$auth->getUserId();
	     $sdata['PLAYER_ID']=$_POST['player'];
	     $sdata['BUYING_DATE']="NOW()";
             $sdata['BUYING_PRICE']=$price;
             $sdata['SELLING_PRICE']=$price;
	     $sdata['SEASON_ID']=$this->mseason_id;
             $sdata['TYPE']=1;

             $db->insert('manager_teams', $sdata);

	     $sql="INSERT INTO manager_market_stats (season_id,player_id,teams,shares) VALUES (".$this->mseason_id.",".$_POST['player'].",1, 0)
		  ON DUPLICATE KEY UPDATE teams=teams+1";
             $db->query($sql);

             $manager_user_log = new ManagerUserLog();
	     $manager_user_log->logEvent ($auth->getUserId(), 2, $price, $this->mseason_id, $_POST['player']);

             // update money
             $db->select('manager_users', '*', 'USER_ID='.$auth->getUserId().' AND SEASON_ID='.$this->mseason_id);
             $adata['MONEY'] = $_SESSION['_user']['MANAGER'][$this->mseason_id]['MONEY'] - $price;
             $_SESSION['_user']['MANAGER'][$this->mseason_id]['MONEY'] = $_SESSION['_user']['MANAGER'][$this->mseason_id]['MONEY'] - $price;
             if ($manager->last_tour > 1) {
               $adata['TRANSACTIONS'] = $_SESSION['_user']['MANAGER'][$this->mseason_id]['TRANSACTIONS'] - 1;
               $_SESSION['_user']['MANAGER'][$this->mseason_id]['TRANSACTIONS'] = $_SESSION['_user']['MANAGER'][$this->mseason_id]['TRANSACTIONS'] - 1;
             }
             if ($row = $db->nextRow()) {
               $db->update('manager_users', $adata, 'USER_ID='.$auth->getUserId().' AND SEASON_ID='.$this->mseason_id);
             }
             $db->query("commit");
	     return 1;
           }
           else { 
             return -1;
           }
         }
       }
       else {
         return -2;
         } 
       } 
      }
    }
  }

  function sellPlayer() {
    global $db;
    global $_POST;
    global $manager;
    global $auth;

    $db->query("start transaction");
    if (isset($_POST['player'])) {     
      $sql = "SELECT *
            FROM manager_market MM 
            WHERE MM.SEASON_ID=".$this->mseason_id." 
                  AND USER_ID =".$_POST['player'];
     $db->query($sql);
     $row = $db->nextRow();
     $price = $row['CURRENT_VALUE_MONEY'];
     $selling_price = $row['CURRENT_VALUE_MONEY'];

     if ($row['CURRENT_VALUE_MONEY'] > -1) {     
       if (isset($_POST['sell']) && $manager->manager_trade_allow) {
          // check that user is still in a team
          $sql = "SELECT ENTRY_ID, USER_ID, BUYING_DATE
              FROM manager_teams 
              WHERE 
                USER_ID=".$auth->getUserId()."
                AND SELLING_DATE IS NULL 
                AND PLAYER_ID=".$_POST['player']."
		AND SEASON_ID=".$this->mseason_id."
              ORDER BY BUYING_DATE DESC
	       FOR UPDATE";
          $db->query($sql);     
          if ($row = $db->nextRow()) {
            // get selling price
            unset($sdata);
       	    $sdata['SELLING_DATE']="NOW()";
            $sdata['SELLING_PRICE']=$selling_price; //$row['SELLING_PRICE'];
            $db->update('manager_teams', $sdata, "USER_ID=".$auth->getUserId()." AND PLAYER_ID=".$_POST['player']." AND BUYING_DATE='".$row['BUYING_DATE']."'");         

            unset($sdata);
       	    $sdata['TEAMS']="TEAMS-1";
            $db->update("manager_market_stats", $sdata, "SEASON_ID=".$this->mseason_id." AND PLAYER_ID=".$_POST['player']);

            $manager_user_log = new ManagerUserLog();
     	    $manager_user_log->logEvent ($auth->getUserId(), 3, $selling_price, $this->mseason_id, $_POST['player']);
            $adata['MONEY'] = $_SESSION['_user']['MANAGER'][$this->mseason_id]['MONEY'] + $selling_price; 
            $_SESSION['_user']['MANAGER'][$this->mseason_id]['MONEY'] = $_SESSION['_user']['MANAGER'][$this->mseason_id]['MONEY'] + $selling_price; 
            $db->update('manager_users', $adata, "USER_ID=".$auth->getUserId().' AND SEASON_ID='.$this->mseason_id);        
            unset($sdata);
            $sdata['END_DATE'] = "NOW()";
            $db->update('manager_captain', $sdata, "ENTRY_ID=".$row['ENTRY_ID']." AND END_DATE IS NULL");
            return true; 
          }
       }
     }
   }
   $db->query("commit");
   return false;
  }

  function substitutePlayer() {
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
      if ($manager->noAvailableSubstituteSlots($row['POSITION_ID1'])) 
        return -3;

      if ($manager->noAvailableSubsTeams($row['TEAM_ID']))
        return -2;

      if ($manager->manager_trade_allow) {
           // check that user is not in a team already
            $sql = "SELECT USER_ID
                     FROM manager_teams_substitutes
                   WHERE 
                     USER_ID=".$auth->getUserId()."
                      AND SELLING_DATE IS NULL 
                      AND PLAYER_ID=".$_POST['player']."
     		      AND SEASON_ID=".$this->mseason_id."
                   ORDER BY BUYING_DATE DESC";
           $db->query($sql);     
           if ($row = $db->nextRow()) {
           }
           else {
             // put into team
             unset($sdata);
             $sdata['USER_ID']=$auth->getUserId();
	     $sdata['PLAYER_ID']=$_POST['player'];
	     $sdata['BUYING_DATE']="NOW()";
             $sdata['BUYING_PRICE']=$price;
             $sdata['SELLING_PRICE']=$price;
	     $sdata['SEASON_ID']=$this->mseason_id;
             $sdata['TYPE']=1;

             $db->insert('manager_teams_substitutes', $sdata);

             $manager_user_log = new ManagerUserLog();
	     $manager_user_log->logEvent ($auth->getUserId(), 2, $price, $this->mseason_id, $_POST['player']);

	     return 1;
           }
        }
     }
  }

  function unsubstitutePlayer() {
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

       if (isset($_POST['unset_substitute']) && $manager->manager_trade_allow) {
          // check that user is still in a team
          $sql = "SELECT ENTRY_ID, USER_ID, BUYING_DATE
              FROM manager_teams_substitutes 
              WHERE 
                USER_ID=".$auth->getUserId()."
                AND SELLING_DATE IS NULL 
                AND PLAYER_ID=".$_POST['player']."
		AND SEASON_ID=".$this->mseason_id."
              ORDER BY BUYING_DATE DESC";
          $db->query($sql);     
          if ($row = $db->nextRow()) {
            // get selling price
            unset($sdata);
       	    $sdata['SELLING_DATE']="NOW()";
            $sdata['SELLING_PRICE']=$selling_price; //$row['SELLING_PRICE'];
            $db->update('manager_teams_substitutes', $sdata, "USER_ID=".$auth->getUserId()." AND PLAYER_ID=".$_POST['player']." AND BUYING_DATE='".$row['BUYING_DATE']."'");         

            $manager_user_log = new ManagerUserLog();
     	    $manager_user_log->logEvent ($auth->getUserId(), 3, $selling_price, $this->mseason_id, $_POST['player']);
          }
       }
     }
  }


  function getLeagues($where = "", $prefix="") {
     global $db;
     global $auth;

     $mleagues = array();
     $permissions = new ForumPermission();
     $can_chat = $permissions->canChat();
     $sql="SELECT ML.LEAGUE_ID, ML.TOPIC_ID, ML.TITLE, ML.ENTRY_FEE, MLM.STATUS, ML.SEASON_ID, 'n' as ALL_LEAGUES, 
		U.USER_NAME, T.POSTS, ML.JOINED as USERS, TT.MARK_TIME, 
		TT.MARK_TIME < T.LAST_POSTED AS TRACKER, UNIX_TIMESTAMP( TT.MARK_TIME ) AS TSTMP, T.LAST_POSTER_ID, ML.RATING, U.LEAGUE_OWNER_RATING,
		ML.PARTICIPANTS, ML.RECRUITMENT_ACTIVE, ML.ACCEPT_NEWBIES, ML.REAL_PRIZES, ML.TYPE, 
		C.CCTLD, CD.COUNTRY_NAME, ML.INVITE_TYPE
             FROM ".$prefix."manager_leagues_members MLM, manager_seasons M, ".$prefix."manager_leagues ML
                  LEFT JOIN ".$prefix."manager_leagues_members MLM1 ON ML.LEAGUE_ID=MLM1.LEAGUE_ID AND (MLM1.STATUS=1)
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
		".$where."
	     GROUP BY MLM.LEAGUE_ID ORDER BY MLM.STATUS ASC, ML.TITLE ASC";
    $db->query($sql);    
//echo $sql; 
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
        $league['TRACK']['TOPIC_ID'] = $row['TOPIC_ID'];
        $league['TRACK']['LEAGUE_ID'] = $row['LEAGUE_ID'];
        if (!empty($row['TSTMP']))
          $league['TRACK']['TSTMP'] = $row['TSTMP'];
        else $league['TRACK']['TSTMP'] = -1;
      }
      if (empty($row['POSTS']))
        $league['POSTS'] = 0;

      $mleagues[] = $league;
    }

    $this->leagues = count($mleagues);
    return $mleagues;
  }

  function getLeaguesInvites($prefix="") {
     global $db;
     global $auth;
     global $_SESSION;

     $league_invites = array();
   // get invitations
     $sql="SELECT ML.LEAGUE_ID, ML.TITLE, ML.ENTRY_FEE
             FROM ".$prefix."manager_leagues ML, ".$prefix."manager_leagues_members MLM
             WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
               AND ML.SEASON_ID=".$this->mseason_id." 
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

  function setCaptain($entry_id) {
    global $db;
    global $manager;
    global $auth;    

    $db->query("start transaction");
    // get old captain
    $data = '';
    $sql = "SELECT MC.ENTRY_ID from manager_teams MT
		left join manager_captain MC ON MC.ENTRY_ID=MT.ENTRY_ID
		WHERE MT.SELLING_DATE IS NULL
		AND MT.SEASON_ID=".$manager->mseason_id."
		AND MT.ENTRY_ID=".$entry_id."
		AND MT.USER_ID=".$auth->getUserId();

    $db->query($sql);
    $captain_id = -1;
    if ($row = $db->nextRow()) {
      $captain_id = $row['ENTRY_ID'];

      $sql = "SELECT MC.ENTRY_ID from manager_teams MT, manager_captain MC
		WHERE MT.SELLING_DATE IS NULL
		AND MT.SEASON_ID=".$manager->mseason_id."
		AND MC.ENTRY_ID=MT.ENTRY_ID
		AND MC.END_DATE IS NULL
		AND MT.USER_ID=".$auth->getUserId();
      $db->query($sql);
      // unset old captain
      if ($row = $db->nextRow()) {
	$data['OLD_CAPTAIN'] = $row['ENTRY_ID'];
        unset($sdata);
        $sdata['END_DATE'] = "NOW()";
        $db->update('manager_captain', $sdata, "ENTRY_ID=".$row['ENTRY_ID']." AND END_DATE IS NULL");
      }
  
      // set new captain
      unset($sdata);
      $sdata['ENTRY_ID'] = $entry_id;
      $sdata['START_DATE'] = "NOW()";
      $db->insert('manager_captain', $sdata);
      $captain_id = $entry_id;
    }
    $db->query("commit");    
    $data['NEW_CAPTAIN'] = $captain_id;
    return $data;
  }

  function canStartBattle($current_tour) {
    global $db;
    global $manager;
    global $auth;    

    $sql = "SELECT MU.USER_ID, MB.BATTLE_ID 
                FROM manager_users MU
                     left join manager_battles MB
				ON MU.user_id=MB.user_id
					AND MU.season_id=MB.season_id
		  		        AND MB.tour_id=".$current_tour."
		WHERE MU.season_id=".$manager->mseason_id."
		      AND MU.USER_ID=".$auth->getUserId();
//echo $sql;
    $db->query($sql);
    if ($row = $db->nextRow()) {
      if ($row['BATTLE_ID'] != '')
        return false;
      else return true;
    }
    return false;
  }

  function canChallenge($market_open_date, $current_tour) {
    global $db;
    global $manager;
    global $auth;    

    $sql = "SELECT * 
                FROM manager_users MU, manager_users_tours MUT
		WHERE MU.season_id=".$manager->mseason_id."
		      AND MU.user_id=MUT.user_id
		      AND MUT.season_id=".$manager->mseason_id."
		      AND MUT.user_id=".$auth->getUserId()."
		      AND MUT.tour_id=".($current_tour -1);

    $db->query($sql);
    if ($row = $db->nextRow()) {
      $sql = "SELECT * FROM manager_users_tours MUT WHERE 
		      MUT.season_id=".$manager->mseason_id."
		      AND MUT.user_id=".$auth->getUserId()."
		      AND MUT.tour_id=".($current_tour -1);
      $db->query($sql);
      if ($row = $db->nextRow()) {   
        $_SESSION['_user']['MANAGER'][$manager->mseason_id]['LAST_TOUR_POINTS'] = $row['POINTS'];
        return true;
      }
    }
    return false; 
  }

  function getChallenges() {
     global $db;
     global $auth;
     global $_SESSION;
     global $manager;

     $sql="SELECT U.USER_NAME, MC.STAKE, MC.TYPE
             FROM manager_challenges MC, users U
             WHERE MC.SEASON_ID=".$this->mseason_id." 
               AND MC.USER2_ID=".$auth->getUserId()."
	       AND MC.USER_ID=U.USER_ID	
               AND MC.STATUS=2
           UNION
	     SELECT U.USER_NAME, MC.STAKE, MC.TYPE
             FROM manager_challenges MC, users U
             WHERE MC.SEASON_ID=".$this->mseason_id." 
               AND MC.USER_ID=".$auth->getUserId()."
	       AND MC.USER2_ID=U.USER_ID	
               AND MC.STATUS=2
            ORDER BY USER_NAME";
    $db->query($sql);    

    $c = 0;
    $challenges = array();
    while ($row = $db->nextRow()) {
      $challenge = $row;
      if ($row['TYPE'] == 2)
        $challenge['CREDITS'] = 1;
      $c++;
      $challenges[] = $challenge;
    }
    $data['CHALLENGES']['ROWS'] = $c;
    return $challenges;
  }

  function getChallengesInvites() {
     global $db;
     global $auth;
     global $_SESSION;
     global $manager;

     $challenges_invites = array();
     if ($_SESSION['_user']['MANAGER'][$this->mseason_id]['IGNORE_CHALLENGES'] == -1) {
      // get invitations
       $sql="SELECT U.USER_NAME, U.USER_ID, MC.CHALLENGE_ID, MUT.POINTS, MC.STAKE, MC.TYPE
             FROM users U, manager_challenges MC
		  left join manager_users_tours MUT on MUT.season_id=MC.season_id
							AND MUT.USER_ID=MC.USER_ID
							AND MUT.TOUR_ID=".$manager->last_tour."
             WHERE MC.SEASON_ID=".$this->mseason_id." 
               AND MC.USER2_ID=".$auth->getUserId()."
	       AND MC.USER_ID=U.USER_ID	
               AND MC.STATUS=1";
      $db->query($sql);    
      $c = 0;
      while ($row = $db->nextRow()) {
        $challenges_invite = $row;
        if ($row['TYPE'] == 1) {
          $challenges_invite['BUDGET'] = 1;
          $challenges_invite['BUTTONS']['CHALLENGE_ID'] = $row['CHALLENGE_ID'];
        } else if ($row['TYPE'] == 2) {
          $challenges_invite['CREDITS'] = 1;
          if ($_SESSION["_user"]['CREDIT'] >= $row['STAKE']) {
            $challenges_invite['ENOUGH_CREDITS']['CHALLENGE_ID'] = $row['CHALLENGE_ID'];
            $challenges_invite['BUTTONS']['CHALLENGE_ID'] = $row['CHALLENGE_ID'];
          } else {
            $challenges_invite['NOT_ENOUGH_CREDITS']['CHALLENGE_ID'] = $row['CHALLENGE_ID'];
          }
        }

        $c++;
        $challenges_invites[] = $challenges_invite;
      }
      $db->free(); 
    }

    return $challenges_invites;
  }

  function getCandidates($current_tour, $market_open_date, $place, $type) {
    global $db;
    global $auth;
    global $manager;

      $where_stakes = '';
      $field_stakes = '';
      if ($type == 1) {
        $sql = "CREATE TEMPORARY TABLE manager_challenges_candidates_stakes (
    		user_id int NOT NULL, 
		stakes int NOT NULL)";
        $db->query($sql);

        $sql = "insert into manager_challenges_candidates_stakes 
		select user_id, sum(stakes) from
		(SELECT sum(MC.stake) stakes, MC.user_id 
			from manager_challenges MC
			where MC.season_id =".$manager->mseason_id."
				AND MC.tour_id =".$current_tour."
				AND MC.type =1
				AND MC.status =2
			group by user_id
		union
		SELECT sum(MC.stake), MC.user2_id
			from manager_challenges MC
			where MC.season_id =".$manager->mseason_id."
			AND MC.tour_id =".$current_tour."
			AND MC.type =1
			AND MC.status =2
			group by user2_id) st
		group by st.user_id";
         $db->query($sql);

         $where_stakes = " left join manager_challenges_candidates_stakes MCCS on 
			  MCCS.user_id=MU.USER_ID ";
	 $field_stakes = ", MCCS.STAKES ";
      }

      $where_credit = "";
      if ($type == 2)
        $where_credit = " AND U.CREDIT > 1";

      $where_standings = " AND MUT.PLACE BETWEEN ". ($place - 50) ." AND ". ($place + 50);
  
      $sql = "SELECT U.USER_NAME, U.IP, U.REG_IP, MS.PLACE, MS.POINTS, MC.STATUS, MC2.STATUS as STATUS2, 
			MU.SEASON_ID, MU.USER_ID, MC.STAKE, U.CREDIT, if (U.CREDIT > 5, 5, FLOOR(U.CREDIT)) as MAX_CREDIT, MC.TYPE ".$field_stakes."
              FROM users U, manager_users MU
        		".$where_stakes."
			, manager_standings MS, manager_users_tours MUT			 
			left join manager_challenges MC on MC.user_id=".$auth->getUserId()."
				 and MC.user2_id=MUT.user_id and MC.season_id=".$manager->mseason_id."
				 and MC.tour_id=".$current_tour."
				 and MC.type=".$type."
			left join manager_challenges MC2 on MC2.user2_id=".$auth->getUserId()."
				 and MC2.user_id=MUT.user_id and MC2.season_id=".$manager->mseason_id."
				 and MC2.tour_id=".$current_tour."
		WHERE MU.season_id=".$manager->mseason_id."
		      AND MU.user_id<>".$auth->getUserId()."
		      AND MS.mseason_id=MU.season_id
		      AND MU.user_id=MS.user_id			
		      AND MU.ignore_challenges=-1
		      AND (MU.COMPLETENESS > 0 OR MC2.STATUS=2 OR MC.STATUS=2)
		      AND MUT.season_id=MU.season_id
		      AND MUT.user_id=MU.user_id		      	
		      AND U.user_id=MU.user_id 
		      ".$where_credit."
		      AND MUT.tour_id=".($current_tour -1).$where_standings."
		ORDER BY PLACE, POINTS DESC";
//echo $sql;
      $db->query($sql);
      $candidates = array();
      while ($row = $db->nextRow()) {
        if ($row['IP'] != $auth->getLastIp()
             && $row['IP'] != $auth->getRegIp()
             && $row['REG_IP'] != $auth->getLastIp()
             && $row['REG_IP'] != $auth->getRegIp()) {

          if (isset($row['STAKES']) && $row['STAKES'] >= $this->default_money/100) 
            continue;
          if ($row['STATUS2'] == '') {
  	    if ($row['STATUS'] != 2) {
              $candidate = $row;
              if ($row['STATUS'] == 1) {
                $candidate['CHALLENGE_THROWN'] = 1;
              }
              else if ($row['STATUS'] == 3)
                $candidate['CHALLENGE_REJECTED'] = 1;
              else             
    	        $candidate['CHALLENGE'] = 1;
            }
	    $candidates[] = $candidate;
          } 
        }
      }
      return $candidates;
  }



  function getTournaments($open = false) {
     global $db;
     global $auth;

     $where_open = "";
     if ($open)
       $where_open = " AND ML.STATUS=1";

     $mtournaments = array();
     $permissions = new ForumPermission();
     $can_chat = $permissions->canChat();
     $sql="SELECT ML.MT_ID, ML.TOPIC_ID, ML.TITLE, ML.ENTRY_FEE, ML.SEASON_ID, 'n' as ALL_TOURNAMENTS, 
		U.USER_NAME, T.POSTS, ML.JOINED USERS, TT.MARK_TIME, 
		TT.MARK_TIME < T.LAST_POSTED AS TRACKER, UNIX_TIMESTAMP( TT.MARK_TIME ) AS TSTMP, T.LAST_POSTER_ID, 
		ML.PARTICIPANTS, ML.STATUS as TOURNAMENT_STATUS, ML.TOURNAMENT_TYPE,
		C.CCTLD, CD.COUNTRY_NAME, ML.INVITE_TYPE, ML.PRIZE_FUND, ML.REAL_PRIZES, ML.DURATION
             FROM manager_seasons M, manager_tournament_members MTB, manager_tournament ML
                  LEFT JOIN users U on ML.USER_ID=U.USER_ID
                  LEFT JOIN topic T on ML.TOPIC_ID=T.TOPIC_ID
		  left join topic_track TT ON T.TOPIC_ID=TT.TOPIC_ID AND TT.USER_ID=".$auth->getUserId()."
                  LEFT JOIN countries C ON ML.COUNTRY = C.ID
		  LEFT JOIN countries_details CD ON C.ID=CD.ID aND CD.LANG_ID=".$_SESSION['lang_id']."
             WHERE ML.SEASON_ID=".$this->mseason_id." 
                AND M.SEASON_ID=".$this->mseason_id." 
		AND MTB.MT_ID = ML.MT_ID
		AND MTB.USER_ID=".$auth->getUserId()."
		AND MTB.STATUS in (1,2)
		".$where_open."
	     ORDER BY ML.CREATED_DATE, ML.TITLE ASC";
    $db->query($sql);  
//echo $sql;  
    while ($row = $db->nextRow()) {
      unset($tournament);
      if ($row['PARTICIPANTS'] > 0)
	$row['PROGRESS']['PERCENTS'] = $row['USERS'] *100/ $row['PARTICIPANTS'];	

      if (!empty($row['CCTLD'])) {
        $row['COUNTRY_DB']['CCTLD'] = $row['CCTLD'];
        $row['COUNTRY_DB']['COUNTRY_NAME'] = $row['COUNTRY_NAME'];
      }

      $tournament = $row;
      $tournament['TOURNAMENT'] = 1;
/*      if ($row['STATUS'] == 2) {

        if ($can_chat) 
          $tournament['CHAT']['CHAT_CHANNEL'] = $row['TITLE']."_".$row['MT_ID']."_".$row['SEASON_ID'];
      }
      else if ($row['STATUS'] == 1) {
        $tournament['OWN_TOURNAMENT'] = $row;
        $tournament['OWN_TOURNAMENT']['TITLE'] = $row['TITLE'];
        if ($can_chat) 
          $tournament['CHAT']['CHAT_CHANNEL'] = $row['TITLE']."_".$row['MT_ID']."_".$row['SEASON_ID'];
      }*/

      if ($row['REAL_PRIZES'] == 'Y') {
        $tournament['PRIZES'] = 1;
      }

      if ((empty($row['MARK_TIME']) || !isset($row['TRACKER']) || (isset($row['TRACKER']) && $row['TRACKER'] != 0 && $row['TRACKER'] != '')) && $row['LAST_POSTER_ID'] != $auth->getUserId() && !empty($row['LAST_POSTER_ID'])) {
        $tournament['TRACK']['TOPIC_ID'] = $row['TOPIC_ID'];
        $tournament['TRACK']['MT_ID'] = $row['MT_ID'];
        if (!empty($row['TSTMP']))
          $tournament['TRACK']['TSTMP'] = $row['TSTMP'];
        else $tournament['TRACK']['TSTMP'] = -1;
      }
      if (empty($row['POSTS']))
        $tournament['POSTS'] = 0;

      $mtournaments[] = $tournament;
    }
    $this->tournaments = count($mtournaments);
    return $mtournaments;
  }

  function getTournamentsInvites() {
     global $db;
     global $auth;
     global $_SESSION;

     $tournament_invites = array();
   // get invitations
     $sql="SELECT ML.MT_ID, ML.TITLE, ML.ENTRY_FEE
             FROM manager_tournament ML, manager_tournament_members MLM
             WHERE ML.MT_ID=MLM.MT_ID
               AND ML.SEASON_ID=".$this->mseason_id." 
               AND MLM.USER_ID=".$auth->getUserId()."
               AND MLM.STATUS=3";
 
    $db->query($sql);    
    while ($row = $db->nextRow()) {
      $tournament_invite = $row;
      if ($row['ENTRY_FEE'] > 0 ) {
        $tournament_invite['ENTRY'] = $row;
        if ($_SESSION['_user']['CREDIT'] >= $row['ENTRY_FEE']) {
           $tournament_invite['ENOUGH_CREDITS'] = $row;
           $tournament_invite['BUTTONS'] = $row;
        } else {
           $tournament_invite['NOT_ENOUGH_CREDITS'] = $row;
        }  
      }
      else {
        $tournament_invite['BUTTONS'] = $row;
      }
      $tournament_invites[] = $tournament_invite;
    }
    $db->free(); 

    return $tournament_invites;
  }

  function getTournamentFixtures($perpage = 5) {
    global $db;
    global $auth;

    // get invitations
    $sql = "SELECT MTT.COMPLETED, MTT.DRAWN, MT.TITLE, MTR.TOUR, MTR.PAIR, MTR.SCORE as SCORE1, MTR2.SCORE as SCORE2, U1.USER_NAME as USER_NAME1, U2.USER_NAME as USER_NAME2
			FROM manager_tournament MT,
				manager_tournament_tours MTT, 
				manager_tournament_results MTR
                          left join users U1 on U1.user_id=MTR.USER_ID,
			     manager_tournament_results MTR2
                          left join users U2 on U2.user_id=MTR2.USER_ID
			WHERE  MTR.MT_ID=MTR2.MT_ID
			       AND MT.MT_ID = MTR.MT_ID
			       AND MTR.TOUR=MTR2.TOUR
			       AND MTT.NUMBER=MTR.TOUR
			       AND MTT.MT_ID = MT.MT_ID
			       AND MTR.HOME =0
			       AND MTR2.HOME =1
			       AND MTR.PAIR=MTR2.PAIR
			       AND MTR.ROUND=MTR2.ROUND
			       AND (MTR.USER_ID = ".$auth->getUserId()." OR MTR2.USER_ID = ".$auth->getUserId().")
			LIMIT 0,".$perpage;

    $db->query($sql);   
    $pairs = '';
    while ($row = $db->nextRow()) {
      unset($pair);
      $pair = $row;
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

      if ($row['DRAWN'] == 1 && $row['COMPLETED'] == 0)
	$pair['NOT_FINAL'] = 1;

      $pairs[] = $pair;
    }

    return $pairs;
  }

}

?>