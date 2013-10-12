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

class ManagerBox extends Box{
  var $season_id;
  
  function getManagerFilterBox ($mseason_id) {
    global $manager;
    global $smarty;
    
    // content
    $season = inputManagerSeasons('mseason_id', $mseason_id);

    $smarty->assign("mseason_id", $season);
    $smarty->assign("manager", $manager);
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch("smarty_tpl/bar_manager_filter.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_manager_filter.smarty".($stop-$start);
    return $output;
  
  }

  function getSoloManagerFilterBox ($mseason_id) {
    global $manager;
    global $smarty;
    
    // content
    $season = inputSoloManagerSeasons('mseason_id', $mseason_id);

    $smarty->assign("mseason_id", $season);
    $smarty->assign("manager", $manager);
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch("smarty_tpl/bar_manager_filter.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_manager_filter.smarty".($stop-$start);
    return $output;
  
  }

  function getRvsManagerFilterBox ($mseason_id) {
    global $smarty;
    global $manager;
    
    // content
    $season = inputRvsManagerSeasons('mseason_id', $mseason_id); 

    $smarty->assign("mseason_id", $season);
    $smarty->assign("manager", $manager);
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch("smarty_tpl/bar_manager_filter.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_manager_filter.smarty".($stop-$start);
    return $output;
  }

  function getManagerSeasonBox ($widget = false, $sport_id='', $season_id='', $index = false, $dashboard = false) {
    global $_SESSION;
    global $db;
    global $auth;
    global $smarty;
    global $clients;
    global $host;

    $sports =  "";
    if (isset($_SESSION['external_user']))
      $sports = " AND MSS.SPORT_ID IN (".$clients[$_SESSION['external_user']['SOURCE']]['sports'].")";

    $template_file = "";
    $cached = false;
    // content
    if ($widget) {
      // turn cache on
      $template_file = "smarty_tpl/bar_manager_seasons_external.smarty";      
      $smarty->assign("host", $host);
      $smarty->setCaching(Smarty::CACHING_LIFETIME_SAVED);
      $smarty->setCacheLifetime(3600);

      if ($smarty->isCached($template_file, 'manager_widget'.$auth->getUserId()."_lang_id".$_SESSION['lang_id'].$sports))
        $cached= true;
    }
    elseif ($dashboard) 
      $template_file = "smarty_tpl/bar_manager_seasons_dashboard.smarty";
    else 
      $template_file = "smarty_tpl/bar_manager_seasons.smarty";

    $manager_seasons = "";
    if (!$cached) {
      $season_ar = '';
  
      $where_season= '';
      $where_sport_id= '';
      $where_season_expired = '';
      if (!empty($season_id)) {
        $where_season = " AND MSS.SEASON_ID=".$season_id ;
      }
      else if (!$dashboard)
        $where_season_expired = " AND DATE_ADD(NOW(), INTERVAL -7 DAY) < END_DATE ";
      if (!empty($sport_id)) {
        $where_sport_id = " AND MSS.SPORT_ID=".$sport_id ;
        $where_season_expired = " AND DATE_ADD(NOW(), INTERVAL -7 DAY) < END_DATE ";
      }      

//$db->showquery=true;
      if ($auth->userOn()) {
        $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MS.PLACE, MSS.PIC_LOCATION, NOW() > END_DATE as EXPIRED, MSS.ALLOW_SOLO
             FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                  left join manager_standings MS on MSS.SEASON_ID=MS.MSEASON_ID AND MS.USER_ID=".$auth->getUserId()."
             WHERE MSS.START_DATE < NOW( ) 
	   AND MSS.PUBLISH='Y'
	   ".$where_season_expired."
	   ".$where_season."
	   ".$where_sport_id.$sports."
          ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";
      }
      else {
          $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MSS.PIC_LOCATION, NOW() > END_DATE as EXPIRED, MSS.ALLOW_SOLO
             FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
             WHERE MSS.START_DATE < NOW( ) 
	   AND MSS.PUBLISH='Y'
	   ".$where_season_expired."
	   ".$where_season."
	   ".$where_sport_id.$sports."
          ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";
      }
      $db->query($sql);
      $c=0;
      $places = '';
      while ($row = $db->nextRow()) {
        $season_id = $row['SEASON_ID'];
        $manager_season['SEASON_ID'] = $row['SEASON_ID'];
        $manager_season['EXPIRED'] = $row['EXPIRED'];
        $manager_season['MANAGER_INFO']['SEASON_ID'] = $row['SEASON_ID'];
        $manager_season['MANAGER_INFO']['SEASON_TITLE'] = $row['SEASON_TITLE'];
        if ($row['ALLOW_SOLO'] == 'Y')
          $manager_season['MANAGER_INFO']['ALLOW_SOLO'] = true;
        else $manager_season['MANAGER_INFO']['ALLOW_SOLO'] = false;
        $season_ar[$c] = $season_id;
	$manager_season['PIC_LOCATION'] = '';
        if (!empty($row['PIC_LOCATION']))
          $manager_season['PIC_LOCATION'] = $row['PIC_LOCATION'];
        if (isset($row['PLACE']) && !empty($row['PLACE']))
          $places[$season_id] = $row['PLACE'];
        else $places[$season_id] = 2;
        $manager_seasons[] = $manager_season;
        $c++;
      }
      if ($c == 0)
        return "";
   
      $mlog = new ManagerLog();
      foreach ($manager_seasons as &$manager_season) {
        $season_id = $manager_season['MANAGER_INFO']['SEASON_ID'];
        $place = $places[$season_id];
        if (!$index) {
          if (!empty($season_id)) {
            $sql="SELECT U.USER_NAME, U.USER_ID, MU.MONEY, MS.POINTS AS KOEFF, MSS.SEASON_ID, MS.PLACE 
              FROM users U, manager_users MU, manager_standings MS, manager_seasons MSS
             WHERE MS.USER_ID = U.USER_ID 
               AND MU.USER_ID = MS.USER_ID
               AND MSS.SEASON_ID = MS.MSEASON_ID
               AND MSS.SEASON_ID =  ".$season_id."
       	       AND MU.SEASON_ID =  ".$season_id."
    	       AND MS.MSEASON_ID =  ".$season_id."
               AND MS.PLACE IN (1,".($place-1).",".$place.",".($place+1).")
             ORDER BY MS.POINTS DESC, MS.PLACE ASC, U.USER_NAME";
  
            $db->query($sql);
            $cc = 0;
            $prev_place = '';
            $manager_season['MANAGER_STAND']['USERS'] = array();
            while ($row = $db->nextRow()) {
              $user = $row;
              if (strlen($user['USER_NAME']) > 13)
                $user['USER_NAME'] = substr($user['USER_NAME'], 0,13)."...";

              if (isset($row['PLACE']) && $row['PLACE']-$prev_place > 1) {
                $user['GAP'] = 1;
              }
	      if (isset($row['SEASON_ID']))
                $manager_season['MANAGER_STAND']['SEASON_ID'] = $row['SEASON_ID'];
  	      if (isset($row['PLACE']))
                $prev_place = $row['PLACE'];
  	      $manager_season['MANAGER_STAND']['USERS'][] = $user;
            } 
            if (count($manager_season['MANAGER_STAND']['USERS']) == 0)
              unset($manager_season['MANAGER_STAND']);
            $db->free();
          }
         }
         $log_entry = $mlog->getManagerLogLastItem($season_id);
         if ($log_entry != '')
           $manager_season['MANAGER_LOG']['LOG'] = $log_entry;
	 $this->getMarketOpening($season_id, $manager_season);
      }
    }
//$db->showquery=false;
    $smarty->assign("manager_seasons", $manager_seasons);
    $start = getmicrotime();
    if ($widget) {
      $output = $smarty->fetch($template_file, 'manager_widget'.$auth->getUserId()."_lang_id".$_SESSION['lang_id'].$sports);    
      $smarty->caching= false;
    }
    else {
      $smarty->caching= false;
      $output = $smarty->fetch($template_file);    
    }
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo $template_file.($stop-$start);
    return $output;
  }

  function getManagerSeasonDashboardBox ($season_id, $mode = '', $show_logs=true) {
    global $_SESSION;
    global $db;
    global $auth;
    global $smarty;
    global $langs;

    $manager_seasons = "";
      $where_season = " AND MSS.SEASON_ID=".$season_id ;


       $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MSS.PIC_LOCATION
        	  FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
          WHERE MSS.START_DATE < NOW( ) 
		  AND MSS.PUBLISH='Y'
		  ".$where_season."
          ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";

      $db->query($sql);
      $c=0;
      if ($row = $db->nextRow()) {
        $season_id = $row['SEASON_ID'];
        $manager_season['SEASON_ID'] = $row['SEASON_ID'];
        $manager_season['MANAGER_INFO']['SEASON_ID'] = $row['SEASON_ID'];
        $manager_season['MANAGER_INFO']['SEASON_TITLE'] = $row['SEASON_TITLE'];
        $season_ar[$c] = $season_id;
	$manager_season['PIC_LOCATION'] = '';
        if (!empty($row['PIC_LOCATION']))
          $manager_season['PIC_LOCATION'] = $row['PIC_LOCATION'];
        $c++;
      }
      if ($c == 0)
        return "";

      if ($show_logs) {   
        $mlog = new ManagerLog();

        $manager_logbox = new LogBox($langs, $_SESSION["_lang"]);
        if ($mode == 'solo') {
          $manager_season['MANAGER_LOG'] = $manager_logbox->getSoloManagerLogBox($season_id, 1, 3);
          if ($auth->userOn()) {
            $manager_season['MANAGER_USER_LOG'] = $manager_logbox->getSoloManagerUserLogBox($auth->getUserId(), $season_id, 1, 3, $langs['LANG_PERSONAL_LOG_U']);
          }

        } else {
          $manager_season['MANAGER_LOG'] = $manager_logbox->getManagerLogBox($season_id, 1, 3);
          if ($auth->userOn()) {
            $manager_season['MANAGER_USER_LOG'] = $manager_logbox->getManagerUserLogBox($auth->getUserId(), $season_id, 1, 3, $langs['LANG_PERSONAL_LOG_U']);
          }
        }
      }
      $this->getMarketOpening($season_id, $manager_season);

//$db->showquery=false;
    $smarty->assign("manager_season", $manager_season);
    $start = getmicrotime();
    $smarty->caching= false;
    if ($mode == 'rvs')
      $template = "bar_rvs_manager_season_dashboard";
    else if ($mode == 'solo')
      $template = "bar_solo_manager_season_dashboard";
    else $template = "bar_manager_season_dashboard";
    $output = $smarty->fetch("smarty_tpl/".$template.".smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/".$template.".smarty".($stop-$start);
    return $output;
  }

  function getManagerStandingsBox($season_id, $place=2) {
     global $db;
     global $auth;
     global $smarty;

      $place = 2;
      if ($auth->userOn()) {
        $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MS.PLACE, MSS.PIC_LOCATION
             FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                  left join manager_standings MS on MSS.SEASON_ID=MS.MSEASON_ID AND MS.USER_ID=".$auth->getUserId()."
             WHERE MSS.START_DATE < NOW( ) 
           AND MSS.SEASON_ID =  ".$season_id."
	   AND MSS.PUBLISH='Y'
          ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          if (isset($row['PLACE']) && !empty($row['PLACE']))
            $place = $row['PLACE'];
          else $place = 2;
        }
      }

      $sql="SELECT U.USER_NAME, U.USER_ID, MU.MONEY, MS.POINTS AS KOEFF, MSS.SEASON_ID, MS.PLACE 
      		 FROM users U, manager_users MU, manager_standings MS, manager_seasons MSS
	       WHERE MS.USER_ID = U.USER_ID 
        	 AND MU.USER_ID = MS.USER_ID
	         AND MSS.SEASON_ID = MS.MSEASON_ID
        	 AND MSS.SEASON_ID =  ".$season_id."
	         AND MU.SEASON_ID =  ".$season_id."
	         AND MS.MSEASON_ID =  ".$season_id."
	         AND (MS.PLACE = 1
                  OR MS.PLACE BETWEEN ".($place-5)." AND ".($place+5).")
	       ORDER BY MS.POINTS DESC, MS.PLACE ASC, U.USER_NAME";
      $db->query($sql);
      $cc = 0;
      $prev_place = '';
      $manager_standings['USERS'] = array();
      while ($row = $db->nextRow()) {
        $user = $row;
        if (strlen($user['USER_NAME']) > 13)
          $user['USER_NAME'] = substr($user['USER_NAME'], 0,13)."...";
        if (isset($row['PLACE']) && $row['PLACE']-$prev_place > 1) {
          $user['GAP'] = 1;
        }
        if (isset($row['PLACE']))
          $prev_place = $row['PLACE'];
        $manager_standings['USERS'][] = $user;
      } 
      if (count($manager_standings['USERS']) == 0)
        return '';

    $smarty->assign("season_id", $season_id);
    $smarty->assign("manager_standings", $manager_standings);
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch("smarty_tpl/bar_manager_standings.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_manager_standings.smarty".($stop-$start);
    return $output;

  }

  function getManagerStandingsClansBox($season_id, $place=2) {
     global $db;
     global $auth;
     global $smarty;

      $place = 2;
      if ($auth->userOn()) {
        $clan_id = $auth->isClanMember();
        $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MS.PLACE, MSS.PIC_LOCATION
             FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                  left join clan_teams CT on MSS.SEASON_ID=CT.MSEASON_ID AND CT.CLAN_ID=".$clan_id."
                  left join manager_clan_teams_standings MS on MSS.SEASON_ID=MS.MSEASON_ID AND MS.TEAM_ID=CT.TEAM_ID
             WHERE MSS.START_DATE < NOW( ) 
           AND MSS.SEASON_ID =  ".$season_id."
	   AND MSS.PUBLISH='Y'
          ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          if (isset($row['PLACE']) && !empty($row['PLACE']))
            $place = $row['PLACE'];
          else $place = 2;
        }
      }

      $sql="SELECT U.CLAN_NAME, U.USER_ID, MS.POINTS AS KOEFF, MSS.SEASON_ID, MS.PLACE 
      		 FROM clans U, clan_teams MU, manager_clan_teams_standings MS, manager_seasons MSS
	       WHERE U.CLAN_ID = MU.CLAN_ID 
        	 AND MU.TEAM_ID = MS.TEAM_ID
	         AND MSS.SEASON_ID = MS.MSEASON_ID
        	 AND MSS.SEASON_ID =  ".$season_id."
	         AND MU.SEASON_ID =  ".$season_id."
	         AND MS.MSEASON_ID =  ".$season_id."
	         AND (MS.PLACE = 1
                  OR MS.PLACE BETWEEN ".($place-5)." AND ".($place+5).")
	       ORDER BY MS.POINTS DESC, MS.PLACE ASC, U.CLAN_NAME";
//echo $sql;
      $db->query($sql);
      $cc = 0;
      $prev_place = '';
      $manager_standings['USERS'] = array();
      while ($row = $db->nextRow()) {
        $user = $row;
        if (isset($row['PLACE']) && $row['PLACE']-$prev_place > 1) {
          $user['GAP'] = 1;
        }
        if (isset($row['PLACE']))
          $prev_place = $row['PLACE'];
        $manager_standings['USERS'][] = $user;
      } 
      if (count($manager_standings['USERS']) == 0)
        return '';

    $smarty->assign("season_id", $season_id);
    $smarty->assign("manager_standings", $manager_standings);
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch("smarty_tpl/bar_manager_standings_clans.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_manager_standings_clans.smarty".($stop-$start);
    return $output;

  }
        
  function getMarketOpening($season_id, &$manager_season, $rvs = false) {
    global $db;
    global $auth;
    global $manager;

    $season_over = false;
    $allow_rvs_leagues= false;
    $allow_solo= false;

    $data = "";
    $db->select("manager_seasons", "*, END_DATE < NOW() ENDED", "SEASON_ID=".$season_id);
    if ($row = $db->nextRow()) {
      $season_over= $row['ENDED'];
      $allow_rvs_leagues = $row['ALLOW_RVS_LEAGUES'] == 'Y' ? 1 : 0;
      $allow_solo = $row['ALLOW_SOLO'] == 'Y' ? 1 : 0;
    }

    $db->select("manager_statistics", "*", "SEASON_ID=".$season_id);

    if (!$row = $db->nextRow()) {
      $manager_trade_allow = FALSE;
    }
    else {
      if ($row['MARKET'] == 'Y') {
        $manager_trade_allow = TRUE;
      } else {
        $manager_trade_allow = FALSE;
       }
    } 

    if (true) {
      $sql = "SELECT DATE_ADD(END_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS END_DATE, NUMBER
             FROM manager_tours 
             WHERE NOW() >= START_DATE 
                   AND NOW() <= END_DATE
                   AND SEASON_ID=".$season_id;
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $disabled_trade = true;
        $disabled_trade_wrongday = true;
        $manager_trade_allow = FALSE;
        $current_tour_end_date =  $row['END_DATE'];
        $current_tour =  empty($row['NUMBER']) ? "0" : $row['NUMBER'];
        $utc = $auth->getUserTimezoneName();
      }
      else {
        $sql = "SELECT NUMBER, START_DATE,
                    DATE_ADD(START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE
               FROM manager_tours 
               WHERE NOW() <= END_DATE
                     AND SEASON_ID=".$season_id."
              ORDER BY NUMBER ASC";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          // there are tours ahead
          $next_tour_date = $row['START_DATE'];   
          $next_tour_date_utc = $row['TOUR_START_DATE'];   
          $utc = $auth->getUserTimezoneName();
     
          $current_tour = empty($row['NUMBER']) ? "0" : $row['NUMBER'];
          if (isset($manager))
  	    $manager->last_tour = $current_tour-1;
        } 
      }
      if (isset($current_tour) && $current_tour >= 2) {
        // get challenges
        if ($auth->userOn() && !isset($_SESSION['_user']['MANAGER'][$season_id]['IGNORE_CHALLENGES'])) { 
  	  $sql = "SELECT IGNORE_CHALLENGES
		FROM `manager_users`
		WHERE USER_ID = ".$auth->getUserId()." 
			AND SEASON_ID=".$season_id;
          $db->query($sql);
          if ($row = $db->nextRow()) {
            $_SESSION['_user']['MANAGER'][$season_id]['IGNORE_CHALLENGES'] = $row['IGNORE_CHALLENGES'];
          }
        }

        $invitations_sql = "";
        if (!$rvs) {
          if ($auth->userOn() && !$rvs) {
            $invitations_sql = " UNION SELECT count( challenge_id ) CHALLENGES, 1 as STATUS
		FROM `manager_challenges`
		WHERE STATUS =1 AND season_id =".$season_id." AND USER2_ID=".$auth->getUserID()."
		UNION SELECT count( challenge_id ) CHALLENGES, 5 as STATUS
		FROM `manager_challenges`
		WHERE STATUS =2 AND season_id =".$season_id." AND (USER2_ID=".$auth->getUserID()." OR USER_ID=".$auth->getUserID().")";
          }

  	  $sql = "SELECT count( challenge_id ) CHALLENGES, 2 as STATUS 
		FROM `manager_challenges`
		WHERE STATUS =2 AND season_id =".$season_id . $invitations_sql;
          $db->query($sql);
//echo "<!--".$_SESSION['_user']['MANAGER'][$season_id]['IGNORE_CHALLENGES']."-->";
          while ($row = $db->nextRow()) {
            if ($row['STATUS'] == 2)
      	      $manager_season['CHALLENGES']['CHALLENGES'] = $row['CHALLENGES'];
            else if ($row['STATUS'] == 5)
      	      $manager_season['CHALLENGES']['MY_CHALLENGES'] = $row['CHALLENGES'];
            else if ($row['STATUS'] == 1 && $row['CHALLENGES'] > 0 && $_SESSION['_user']['MANAGER'][$season_id]['IGNORE_CHALLENGES'] == -1) {
      	      $manager_season['CHALLENGES_INVITE']['CHALLENGES'] = $row['CHALLENGES'];
              $manager_season['CHALLENGES_INVITE']['SEASON_ID'] = $season_id;
            }
          }

          $mybattles_sql = '';
          if ($auth->userOn() ) {
                $mybattles_sql = " UNION
		SELECT count( MB.BATTLE_id ) BATTLES, 5 as STATUS 
		FROM manager_battles MB, manager_battles_members MBM
		WHERE MBM.BATTLE_ID=MB.BATTLE_ID 
			AND MBM.USER_ID=".$auth->getUserID()."
			AND MB.STATUS=2 AND MB.season_id =".$season_id."
                UNION
		SELECT count( MB.BATTLE_id ) BATTLES, 6 as STATUS 
		FROM manager_battles MB, manager_battles_members MBM
		WHERE MBM.BATTLE_ID=MB.BATTLE_ID 
			AND MBM.USER_ID=".$auth->getUserID()."
			AND MB.STATUS=0 AND MB.season_id =".$season_id;
          }
  	  $sql = "SELECT count( BATTLE_id ) BATTLES, 2 as STATUS 
		FROM manager_battles
		WHERE STATUS=2 AND season_id =".$season_id."
                UNION
		SELECT count( BATTLE_id ) BATTLES, 1 as STATUS 
		FROM manager_battles
		WHERE STATUS=0 AND season_id =".$season_id.$mybattles_sql;
          $db->query($sql);
          if (!$manager_trade_allow)
            $manager_season['BATTLES']['BATTLES'] = 0;
          else $manager_season['OPEN_BATTLES']['OPEN_BATTLES'] = 0;
          while ($row = $db->nextRow()) {
            if ($row['STATUS'] == 2 && (!$manager_trade_allow || $row['BATTLES'] > 0))
      	    $manager_season['BATTLES']['BATTLES'] = $row['BATTLES'];
            else if ($row['STATUS'] == 5 && (!$manager_trade_allow || $row['BATTLES'] > 0))
      	    $manager_season['BATTLES']['MY_BATTLES'] = $row['BATTLES'];
            else if ($row['STATUS'] == 1 && $manager_trade_allow)
      	    $manager_season['OPEN_BATTLES']['OPEN_BATTLES'] = $row['BATTLES'];
            else if ($row['STATUS'] == 6 && $manager_trade_allow)
      	    $manager_season['OPEN_BATTLES']['MY_OPEN_BATTLES'] = $row['BATTLES'];
  
          }
        } 
      }

      if ($allow_rvs_leagues == 1) {
        $manager_season['RVS_LEAGUES_ALLOWED'] = 1;
	  $sql = "SELECT count( LEAGUE_id ) LEAGUES
		FROM rvs_manager_leagues 
		WHERE STATUS in (0, 1, 2) AND season_id =".$season_id;
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $manager_season['RVS_LEAGUES']['LEAGUES'] = $row['LEAGUES'];
        }

        if ($auth->userOn()) {
	  $sql = "SELECT count( RL.LEAGUE_ID ) LEAGUES
		FROM rvs_manager_leagues RL, rvs_manager_leagues_members RML
		WHERE RL.LEAGUE_ID=RML.LEAGUE_ID
			AND RML.USER_ID=".$auth->getUserId()."
			AND RML.STATUS IN (1, 2)
			AND RL.STATUS in (0, 1, 2) AND RL.season_id =".$season_id;
          $db->query($sql);
          if ($row = $db->nextRow()) {
	          $manager_season['RVS_LEAGUES']['MY_LEAGUES'] = $row['LEAGUES'];
          }
        }
      }

      if ($allow_solo == 1) {
        $manager_season['SOLO_LEAGUES_ALLOWED'] = 1;
        $sql = "SELECT count( LEAGUE_id ) LEAGUES
		FROM solo_manager_leagues 
		WHERE season_id =".$season_id;
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $manager_season['SOLO_LEAGUES']['LEAGUES'] = $row['LEAGUES'];
        }

        if ($auth->userOn()) {
          $sql = "SELECT count( MLM.league_id ) LEAGUES
		FROM solo_manager_leagues_members MLM, solo_manager_leagues ML
		WHERE MLM.STATUS =3 
			AND ML.season_id =".$season_id." 
			AND MLM.LEAGUE_ID=ML.LEAGUE_ID
			AND MLM.USER_ID=".$auth->getUserID();
          $db->query($sql);
          if ($row = $db->nextRow()) {
	    if (isset($row['LEAGUES']) && $row['LEAGUES'] > 0) {
     	      $manager_season['SOLO_LEAGUES_INVITE']['LEAGUES'] = $row['LEAGUES'];
    	      $manager_season['SOLO_LEAGUES_INVITE']['SEASON_ID'] = $season_id;
            }
          } 
        }

        $sql = "SELECT count( RL.LEAGUE_ID ) LEAGUES
	FROM solo_manager_leagues RL, solo_manager_leagues_members RML
	WHERE RL.LEAGUE_ID=RML.LEAGUE_ID
		AND RML.USER_ID=".$auth->getUserId()."
		AND RML.STATUS IN (1, 2)
		AND RL.season_id =".$season_id;
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $manager_season['SOLO_LEAGUES']['MY_LEAGUES'] = $row['LEAGUES'];
        }

      }

      if (!$rvs) {
        $sql = "SELECT count( MT_ID ) TOURNAMENTS
		FROM manager_tournament 
		WHERE STATUS in (0, 1, 2) AND season_id =".$season_id;
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $manager_season['TOURNAMENTS']['TOURNAMENTS'] = $row['TOURNAMENTS'];
        }
      }
      
      $sql = "SELECT count( LEAGUE_id ) LEAGUES
		FROM manager_leagues 
		WHERE season_id =".$season_id;
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $manager_season['LEAGUES']['LEAGUES'] = $row['LEAGUES'];
      }

      if ($auth->userOn()) {
          $sql = "SELECT count( MLM.league_id ) LEAGUES
		FROM manager_leagues_members MLM, manager_leagues ML
		WHERE MLM.STATUS =3 
			AND ML.season_id =".$season_id." 
			AND MLM.LEAGUE_ID=ML.LEAGUE_ID
			AND MLM.USER_ID=".$auth->getUserID();
          $db->query($sql);
          if ($row = $db->nextRow()) {
	    if (isset($row['LEAGUES']) && $row['LEAGUES'] > 0) {
     	      $manager_season['LEAGUES_INVITE']['LEAGUES'] = $row['LEAGUES'];
    	      $manager_season['LEAGUES_INVITE']['SEASON_ID'] = $season_id;
            }
          } 

          $sql = "SELECT count( MLM.league_id ) LEAGUES
		FROM rvs_manager_leagues_members MLM, rvs_manager_leagues ML
		WHERE MLM.STATUS =3 
			AND ML.season_id =".$season_id." 
			AND MLM.LEAGUE_ID=ML.LEAGUE_ID
			AND MLM.USER_ID=".$auth->getUserID();
          $db->query($sql);
          if ($row = $db->nextRow()) {
	    if (isset($row['LEAGUES']) && $row['LEAGUES'] > 0) {
     	      $manager_season['RVS_LEAGUES_INVITE']['LEAGUES'] = $row['LEAGUES'];
    	      $manager_season['RVS_LEAGUES_INVITE']['SEASON_ID'] = $season_id;
            }
          } 

          $sql = "SELECT count( MLM.MT_ID ) TOURNAMENTS
		FROM manager_tournament_members MLM, manager_tournament ML
		WHERE MLM.STATUS =3 
			AND ML.season_id =".$season_id." 
			AND MLM.MT_ID=ML.MT_ID
			AND MLM.USER_ID=".$auth->getUserID();
          $db->query($sql);
          if ($row = $db->nextRow()) {
	    if (isset($row['TOURNAMENTS']) && $row['TOURNAMENTS'] > 0) {
     	      $manager_season['TOURNAMENTS_INVITE']['TOURNAMENTS'] = $row['TOURNAMENTS'];
    	      $manager_season['TOURNAMENTS_INVITE']['SEASON_ID'] = $season_id;
            }
          } 

	  $sql = "SELECT count( RL.MT_ID ) TOURNAMENTS
		FROM manager_tournament RL, manager_tournament_members RML
		WHERE RL.MT_ID=RML.MT_ID
			AND RML.USER_ID=".$auth->getUserId()."
			AND RML.STATUS IN (1, 2)
			AND RL.STATUS in (0, 1, 2) AND RL.season_id =".$season_id;
          $db->query($sql);
          if ($row = $db->nextRow()) {
	          $manager_season['TOURNAMENTS']['MY_TOURNAMENTS'] = $row['TOURNAMENTS'];
          }
      }
    
 
      if ($auth->userOn()) {
        $sql = "SELECT count( RL.LEAGUE_ID ) LEAGUES
	FROM manager_leagues RL, manager_leagues_members RML
	WHERE RL.LEAGUE_ID=RML.LEAGUE_ID
		AND RML.USER_ID=".$auth->getUserId()."
		AND RML.STATUS IN (1, 2)
		AND RL.season_id =".$season_id;
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $manager_season['LEAGUES']['MY_LEAGUES'] = $row['LEAGUES'];
        }
      }
    }       

    if ($season_over) {
      $manager_season['MARKET']['SEASON_OVER'] = 1;   
    } else if (isset($next_tour_date) && $manager_trade_allow) {
      $manager_season['MARKET']['MARKET_OPEN']['START_DATE'] = $next_tour_date_utc;   
      $manager_season['MARKET']['MARKET_OPEN']['UTC'] = $utc;   
     } else if (isset($current_tour_end_date)) {
       $manager_season['MARKET']['NOMARKET']['START_DATE'] = $current_tour_end_date;   
       $manager_season['MARKET']['NOMARKET']['UTC'] = $utc;   
     }
     else if (!$manager_trade_allow)
       $manager_season['MARKET']['NOMARKET_DELAY'] = 1;   

   }

  function getManagerSummaryBox ($auth, $dashboard = false) {
    global $tpl;
    global $db;
    global $_SESSION;
    global $manager;
    global $manager_user;
    global $smarty;
    
    // content
    if ($auth->userOn()) {
      $manager_summary = $_SESSION['_user']['MANAGER'][$manager_user->mseason_id];
      $manager_summary['CREDIT'] = $_SESSION['_user']['CREDIT'];
      $manager_summary['ALLOW_VIEW'] = $_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['ALLOW_VIEW'];
      $manager_summary['IGNORE_LEAGUES'] = $_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['IGNORE_LEAGUES'];
      $manager_summary['IGNORE_CHALLENGES'] = $_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['IGNORE_CHALLENGES'];
      $manager_summary['REMINDER'] = $_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['REMINDER'];
      $manager_summary['TEAM_PRICE'] = empty($_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['TEAM_PRICE']) ? 0 : $_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['TEAM_PRICE'];
      $manager_summary['WEALTH'] = $manager_summary['TEAM_PRICE'] + $_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['MONEY'];
      $manager_summary['PRIZE_FUND'] = $manager->prize_fund;
      $manager_summary['TEAM_STATUS'] = $this->getManagerSummaryTeamStatusBox();
      $manager_summary['GET_TRANSACTIONS'] = $this->getManagerSummaryConvertTransactionsBox();

      $where_external="";
      $field_external="";
      if (isset($_SESSION['external_user'])) {
        $where_external = " LEFT JOIN manager_standings_external MSE ON MSE.USER_ID=".$auth->getUserId()." and MSE.MSEASON_ID=".$manager_user->mseason_id." AND MSE.SOURCE='".$_SESSION['external_user']['SOURCE']."'";
        $field_external = ", MSE.PLACE as EXTERNAL_PLACE";
      }
      $sql="SELECT MS.SEASON_ID, SEASON_TITLE, PIC_LOCATION, MST.PLACE, MST.POINTS ".$field_external."
             FROM manager_seasons MS
		   LEFT JOIN manager_seasons_details MSD ON MS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
		   ".$where_external."
		   LEFT JOIN manager_standings MST ON MS.SEASON_ID = MST.MSEASON_ID AND MST.USER_ID=".$auth->getUserId()."
             WHERE  MS.SEASON_ID = ".$manager_user->mseason_id."
                 AND PUBLISH = 'Y'  
        ORDER BY END_DATE DESC";
      $db->query($sql);
      $row = $db->nextRow();

      $manager_summary['SEASON_TITLE'] = $row['SEASON_TITLE'];
      $manager_summary['PLACE'] = $row['PLACE'];
      if (isset($row['EXTERNAL_PLACE']))
        $manager_summary['EXTERNAL_PLACE'] = $row['EXTERNAL_PLACE'];
      $manager_summary['POINTS'] = $row['POINTS'];
      $manager_summary['LEAGUE'] = $this->getManagerLeagueBox ($manager_user);
      $manager_summary['CHALLENGE'] = $this->getManagerChallengeBox ($manager_user);
      $manager_summary['TOURNAMENT'] = $this->getManagerTournamentBox ($manager_user);
    }

    $smarty->assign("manager", $manager_summary);
    if ($dashboard)
      $template = "bar_manager_dashboard";
    else $template = "bar_manager";
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/'.$template.'.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/'.$template.'.smarty'.($stop-$start);
    return $output;
  } 

  function getManagerSmallSummaryBox ($auth) {
    global $tpl;
    global $db;
    global $_SESSION;
    global $manager_user_small;
    
    // content
    if ($auth->userOn()) {
      $this->data['MANAGER'][0] = $_SESSION['_user']['MANAGER'][$manager_user_small->mseason_id];
      $this->data['MANAGER'][0]['CREDIT'] = $_SESSION['_user']['CREDIT'];
      if (isset($this->data['MANAGER'][0]['TEAM_PRICE']) && isset($this->data['MANAGER'][0]['MONEY']))
        $this->data['MANAGER'][0]['WEALTH'] = $this->data['MANAGER'][0]['TEAM_PRICE'] + $this->data['MANAGER'][0]['MONEY'];

      $sql="SELECT MS.SEASON_ID, SEASON_TITLE, PIC_LOCATION, MST.PLACE, MST.POINTS
             FROM manager_seasons MS
		   LEFT JOIN manager_seasons_details MSD ON MS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
		   LEFT JOIN manager_standings MST ON MS.SEASON_ID = MST.MSEASON_ID AND MST.USER_ID=".$auth->getUserId()."
             WHERE  MS.SEASON_ID = ".$manager_user_small->mseason_id."
                 AND PUBLISH = 'Y'  
        ORDER BY START_DATE ASC";
      $db->query($sql);
      $row = $db->nextRow();

      $this->data['MANAGER'][0]['SEASON_TITLE'] = $row['SEASON_TITLE'];
      $this->data['MANAGER'][0]['PLACE'] = $row['PLACE'];
      $this->data['MANAGER'][0]['POINTS'] = $row['POINTS'];
      $this->data['MANAGER'][0]['LEAGUE'] = $this->getManagerLeagueBox ($manager_user_small);
    }

    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    $tpl->setTemplateFile('tpl/bar_manager_small.tpl.html');
    $tpl->addData($this->data);
    return $tpl->parse();
  } 

  function getManagerSummaryTeamStatusBox () {
    global $tpl;
    global $_SESSION;
    global $_GET;
    global $manager_user;
    global $smarty;

    if ($_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['ALLOW_VIEW'] == -1)
      $team_squad['REVEAL'] = $manager_user->mseason_id;
    else $team_squad['HIDE'] = $manager_user->mseason_id;
    $smarty->assign("team_squad", $team_squad);

    if ($_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['IGNORE_LEAGUES'] == -1)
      $ignore_leagues['IGNORE_LEAGUES'] = $manager_user->mseason_id;
    else $ignore_leagues['UNIGNORE_LEAGUES'] = $manager_user->mseason_id;
    $smarty->assign("ignore_leagues", $ignore_leagues);

    if ($_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['IGNORE_CHALLENGES'] == -1)
      $ignore_challenges['IGNORE_CHALLENGES'] = $manager_user->mseason_id;
    else $ignore_challenges['UNIGNORE_CHALLENGES'] = $manager_user->mseason_id;
    $smarty->assign("ignore_challenges", $ignore_challenges);

    if ($_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['REMINDER'] == -1)
      $reminder['REMINDER_ON'] = $manager_user->mseason_id;
    else $reminder['REMINDER_OFF'] = $manager_user->mseason_id;
    $smarty->assign("reminder", $reminder);

    $smarty->assign("season_id", $manager_user->mseason_id);

    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_manager_team_status.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_team_status.smarty'.($stop-$start);

    return $output;
  }

  function getSoloManagerSummaryTeamStatusBox () {
    global $tpl;
    global $_SESSION;
    global $_GET;
    global $manager_user;
    global $smarty;

    if ($_SESSION['_user']['SOLO_MANAGER'][$manager_user->mseason_id]['ALLOW_VIEW'] == -1)
      $team_squad['REVEAL'] = $manager_user->mseason_id;
    else $team_squad['HIDE'] = $manager_user->mseason_id;
    $smarty->assign("team_squad_solo", $team_squad);

    if ($_SESSION['_user']['SOLO_MANAGER'][$manager_user->mseason_id]['IGNORE_LEAGUES'] == -1)
      $ignore_leagues['IGNORE_LEAGUES'] = $manager_user->mseason_id;
    else $ignore_leagues['UNIGNORE_LEAGUES'] = $manager_user->mseason_id;
    $smarty->assign("ignore_leagues_solo", $ignore_leagues);

    $smarty->assign("season_id", $manager_user->mseason_id);

    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_solo_manager_team_status.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_solo_manager_team_status.smarty'.($stop-$start);
    return $output;
  }

  function getManagerSummaryConvertTransactionsBox () {
    global $tpl;
    global $_SESSION;
    global $_GET;
    global $manager_user;

    if ($_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['TRANSACTIONS'] == 0) {
      $this->data['GET_TRANSACTIONS'][0]['NO_TRANSACTIONS'][0]['X'] = 1;
      if ($_SESSION['_user']['CREDIT'] < 1) {
        $this->data['GET_TRANSACTIONS'][0]['NO_CREDITS'][0]['X'] = 1;
      }
      else 
        $this->data['GET_TRANSACTIONS'][0]['GET_TRANSACTIONS'][0]['SEASON_ID'] = $manager_user->mseason_id;
    }

    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    $tpl->setTemplateFile('tpl/bar_manager_convert_transactions.tpl.html');

    $tpl->addData($this->data);
    return $tpl->parse();
  }

  function getManagerLeagueBox ($manager_user) {
    global $smarty;
    global $_SESSION;
    global $_GET;

    $leagues = $manager_user->getLeagues();
    $leagues_invites = $manager_user->getLeaguesInvites();
    $smarty->assign("leagues", $leagues);
    $smarty->assign("league_invites", $leagues_invites);
    $start = getmicrotime();
    $smarty->caching = false;
    $output = $smarty->fetch('smarty_tpl/bar_manager_leagues.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_leagues.smarty'.($stop-$start)."<br>";
    return $output;
  }

  function getManagerChallengeBox ($manager_user) {
    global $smarty;
    global $_SESSION;
    global $_GET;

    $challenges = $manager_user->getChallenges();
    $challenges_invites = $manager_user->getChallengesInvites();

    $smarty->assign("challenges", $challenges);
    $smarty->assign("challenges_invites", $challenges_invites);

    $smarty->caching = false;
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_manager_challenges.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_challenges.smarty'.($stop-$start)."<br>";
    return $output;
  }

  function getManagerTournamentBox ($manager_user) {
    global $smarty;
    global $_SESSION;
    global $_GET;

    $tournaments = $manager_user->getTournaments();
    $tournaments_invites = $manager_user->getTournamentsInvites();
    $smarty->assign("trnms", $tournaments);
    $smarty->assign("tournaments_invites", $tournaments_invites);
    $start = getmicrotime();
    $smarty->caching = false;
    $output = $smarty->fetch('smarty_tpl/bar_manager_tournaments.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_tournaments.smarty'.($stop-$start)."<br>";
    return $output;
  }

  function getPlayerReports($player_id, $season_id, &$opt = '') {
    global $smarty;
    global $_SESSION;
    global $manager;

    $reports = $manager->getPlayerReports($player_id, $season_id, $opt);
    $smarty->assign("reports", $reports);

    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_manager_player_reports.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_player_reports.smarty'.($stop-$start);

    return $output;

  }

  function getPlayerStateDiv($player_id, $season_id, $player_state, $admin = false, $show_grey = false) {
    global $conf_home_dir;

    $tpl = new template;
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    if ($admin) {
      $tpl->setTemplateFile($conf_home_dir.'tpl/bar_manager_player_state_admin.tpl.html');
    } else { 
      $tpl->setTemplateFile($conf_home_dir.'tpl/bar_manager_player_state.tpl.html');
    }

    unset($this->data['INJURY_STATE']);
    unset($this->data['SUSPENSION_STATE']);
    unset($this->data['QUESTIONABLE_STATE']);
//    $data= '';
    if (($player_state & 1) == 1) {
      if ($admin) {
        $this->data['INJURY_STATE'][0]['ON'][0]['ADMIN'][0]['PLAYER_ID'] = $player_id;
        $this->data['INJURY_STATE'][0]['ON'][0]['ADMIN'][0]['SEASON_ID'] = $season_id;
      } else {
        $this->data['INJURY_STATE'][0]['ON'][0]['X'] = 1;
      }
    }
    else {
      if ($admin) {
	$this->data['INJURY_STATE'][0]['OFF'][0]['ADMIN'][0]['PLAYER_ID'] = $player_id;
        $this->data['INJURY_STATE'][0]['OFF'][0]['ADMIN'][0]['SEASON_ID'] = $season_id;
      } else if ($show_grey) {
        $this->data['INJURY_STATE'][0]['OFF'][0]['X'] = 1;        
      } else {
        $this->data['INJURY_STATE'][0]['DEFAULT'][0]['X'] = 1;
      }
    }

    if (($player_state & 2) == 2) {
      if ($admin) {
        $this->data['SUSPENSION_STATE'][0]['ON'][0]['ADMIN'][0]['PLAYER_ID'] = $player_id;
        $this->data['SUSPENSION_STATE'][0]['ON'][0]['ADMIN'][0]['SEASON_ID'] = $season_id;
      } else {
        $this->data['SUSPENSION_STATE'][0]['ON'][0]['X'] = 1;
      }
    }
    else {
      if ($admin) {
	$this->data['SUSPENSION_STATE'][0]['OFF'][0]['ADMIN'][0]['PLAYER_ID'] = $player_id;
        $this->data['SUSPENSION_STATE'][0]['OFF'][0]['ADMIN'][0]['SEASON_ID'] = $season_id;
      } else if ($show_grey) {
        $this->data['SUSPENSION_STATE'][0]['OFF'][0]['X'] = 1;        
      } else {
        $this->data['SUSPENSION_STATE'][0]['DEFAULT'][0]['X'] = 1;
      }
    }

    if (($player_state & 4) == 4) {
      if ($admin) {
        $this->data['QUESTIONABLE_STATE'][0]['ON'][0]['ADMIN'][0]['PLAYER_ID'] = $player_id;
        $this->data['QUESTIONABLE_STATE'][0]['ON'][0]['ADMIN'][0]['SEASON_ID'] = $season_id;
      } else {
        $this->data['QUESTIONABLE_STATE'][0]['ON'][0]['X'] = 1;
      }
    }
    else {
      if ($admin) {
	$this->data['QUESTIONABLE_STATE'][0]['OFF'][0]['ADMIN'][0]['PLAYER_ID'] = $player_id;
        $this->data['QUESTIONABLE_STATE'][0]['OFF'][0]['ADMIN'][0]['SEASON_ID'] = $season_id;
      } else if ($show_grey) {
        $this->data['QUESTIONABLE_STATE'][0]['OFF'][0]['X'] = 1;        
      } else {
        $this->data['QUESTIONABLE_STATE'][0]['DEFAULT'][0]['X'] = 1;
      }
    }
    $this->data['PLAYER_ID'] = $player_id;
    $this->data['SEASON_ID'] = $season_id;
    $tpl->addData($this->data);
    return $tpl->parse();
  }

  function getTeam($tour, $last_tour) {
    global $manager_user;
    global $manager;
    global $smarty;
    
    // content
    $manager_user->getTeam($tour, $last_tour);
  
//    $start = getmicrotime();
    if ($manager->sport_id != 4)
      $output = $smarty->fetch('smarty_tpl/f_manager_team.smarty');
    else
      $output = $smarty->fetch('smarty_tpl/f_manager_team_ind.smarty');
//    $stop = getmicrotime();
//    echo 'smarty_tpl/f_manager_team.smarty'.($stop-$start);
    return $output;
  }

  function getMarket($where = '', $where_int ='', $order = '') {
    global $manager;
    global $smarty;
    
    // content
    $manager->getMarket($where, $where_int, $order);
  
    $start = getmicrotime();
    if ($manager->sport_id != 4)
      $output = $smarty->fetch('smarty_tpl/f_manager_market.smarty');
    else
      $output = $smarty->fetch('smarty_tpl/f_manager_market_ind.smarty');
    $stop = getmicrotime();

//    echo 'smarty_tpl/f_manager_market.smarty'.($stop-$start);
    return $output;
  }

  function getPortfolio() {
    global $manager_user;
    global $smarty;
    
    // content
    $portfolio = $manager_user->getPortfolio();
  
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/f_manager_portfolio.smarty');
    $stop = getmicrotime();
    if ($_GET['debugphp'])
      echo 'smarty_tpl/f_manager_portfolio.smarty'.($stop-$start)."br";
    return $output;
  }

  function getToursSchedule() {
    global $manager;
    global $smarty;

    $current_tour = $manager->getCurrentTour();
    $tours = $manager->getToursSchedule($current_tour);

    $smarty->assign("tours", $tours);
    $smarty->assign("current_tour", $current_tour);

    $start = getmicrotime();
    if ($manager->sport_id != 4 && $manager->sport_id != 3)
      $content = $smarty->fetch('smarty_tpl/f_manager_tours.smarty');    
    else $content = $smarty->fetch('smarty_tpl/f_manager_tours_ind.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/f_manager_tours.smarty'.($stop-$start);

    return $content;   
  }

  function getTourSchedule() {
    global $manager;
    global $smarty;

    $current_tour = $manager->getCurrentTour();
    $tour = $manager->getTourSchedule($current_tour);

    $smarty->assign("tour", $tour);
    $smarty->assign("current_tour", $current_tour);

    $start = getmicrotime();
    if ($manager->sport_id != 4 && $manager->sport_id != 3)
      $content = $smarty->fetch('smarty_tpl/bar_manager_tour.smarty');    
    else $content = $smarty->fetch('smarty_tpl/bar_manager_tour_ind.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_tour.smarty'.($stop-$start);

    return $content;
    
  }

  function getMarketStatsBox() {
    global $manager;
    global $smarty;

    if ($manager->manager_trade_allow) {
      $can_view = false;
    }

    if (!$manager->manager_trade_allow) {
      $players = $manager->getMarketStats($manager->mseason_id, 1, 5);
      $smarty->assign("market", $players);
    } else {
      $smarty->assign("noaccess", 1);
    }
    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/bar_manager_market_stats.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_market_stats.smarty'.($stop-$start);

    return $content;

  }

  function getRvsManagerSummaryBox ($auth) {
    global $tpl;
    global $db;
    global $_SESSION;
    global $manager;
    global $rvs_manager_user;
    global $smarty;
    global $_GET;

    // content
    if ($auth->userOn()) {
      $manager_summary = isset($_SESSION['_user']['RVS_MANAGER']) ? $_SESSION['_user']['RVS_MANAGER'][$rvs_manager_user->league_id] : '';
      $manager_summary['CREDIT'] = $_SESSION['_user']['CREDIT'];

      if (isset($_GET['league_id'])) {
        $sql="SELECT RML.PRIZE_FUND, RML.TITLE, MS.SEASON_ID, SEASON_TITLE, PIC_LOCATION, MST.PLACE, MST.POINTS, RML.LEAGUE_ID
             FROM rvs_manager_leagues RML, manager_seasons MS
		   LEFT JOIN manager_seasons_details MSD ON MS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
		   LEFT JOIN rvs_manager_standings MST ON MST.LEAGUE_ID = ".$rvs_manager_user->league_id." AND MST.USER_ID=".$auth->getUserId()."
             WHERE  MS.SEASON_ID = RML.SEASON_ID
		    AND RML.LEAGUE_ID = ".$rvs_manager_user->league_id."
                 AND PUBLISH = 'Y'  
        ORDER BY MS.END_DATE DESC";

        $db->query($sql);
        $row = $db->nextRow();
        $manager_league['PRIZE_FUND'] = $row['PRIZE_FUND'];
        $manager_league['PLACE'] = $row['PLACE'];
        $manager_league['POINTS'] = $row['POINTS'];
        $manager_league['TITLE'] = $row['TITLE'];
        $manager_league['LEAGUE_ID'] = $row['LEAGUE_ID'];

        $mlog = new RvsManagerLog();
        $log_entry = $mlog->getRvsManagerLogLastItem($row['LEAGUE_ID']);
        if ($log_entry != '')
          $manager_league['LOG'] = $log_entry;
        
        $mlog = new RvsManagerUserLog();
        $log_entry = $mlog->getRvsManagerUserLogLastItem($auth->getUserId(), $row['LEAGUE_ID']);
        if ($log_entry != '')
          $manager_league['USER_LOG'] = $log_entry;


      }
      $sql = "SELECT count( RL.LEAGUE_ID ) LEAGUES
	FROM rvs_manager_leagues RL, rvs_manager_leagues_members RML
	WHERE RL.LEAGUE_ID=RML.LEAGUE_ID
		AND RML.USER_ID=".$auth->getUserId()."
		AND RML.STATUS IN (1, 2)
		AND RL.STATUS in (0, 1, 2) AND RL.season_id =".$manager->mseason_id;
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $manager_summary['RVS_LEAGUES']['MY_LEAGUES'] = $row['LEAGUES'];
      }

      $manager_summary['LEAGUE'] = $this->getRvsManagerLeagueBox ($rvs_manager_user);
    }

    $sql = "SELECT count( LEAGUE_id ) LEAGUES
		FROM rvs_manager_leagues 
		WHERE STATUS in (0, 1, 2) AND season_id =".$manager->mseason_id;
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $manager_summary['RVS_LEAGUES']['LEAGUES'] = $row['LEAGUES'];
    }

    $manager_summary['SEASON_TITLE'] = $manager->title;
    $manager_summary['SEASON_ID'] = $manager->mseason_id;
    if (isset($manager_summary)) {
      $smarty->assign("manager_summary", $manager_summary);
    }
    if (isset($manager_league))
      $smarty->assign("manager_league", $manager_league);

    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_rvs_manager.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_rvs_manager.smarty'.($stop-$start);
    return $output;
  } 

  function getRvsManagerLeagueBox ($rvs_manager_user) {
    global $smarty;
    global $_SESSION;
    global $_GET;

    $leagues = $rvs_manager_user->getRvsLeagues();
    $leagues_invites = $rvs_manager_user->getRvsLeaguesInvites();
    $smarty->assign("leagues", $leagues);
    $smarty->assign("league_invites", $leagues_invites);
    $start = getmicrotime();
    $smarty->caching = false;
    $output = $smarty->fetch('smarty_tpl/bar_rvs_manager_leagues.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_rvs_manager_leagues.smarty'.($stop-$start)."<br>";
    return $output;
  }

  function getRvsTeam($tour, $last_tour) {
    global $rvs_manager_user;
    global $manager;
    global $smarty;
    
    // content
    $rvs_manager_user->getTeam($tour, $last_tour);
  
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/rvs_manager_team.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/rvs_manager_team.smarty'.($stop-$start);
    return $output;
  }

  function getRvsModerateTransfers() {
    global $rvs_manager_user;
    global $manager;
    global $smarty;
    
    // content
    $rvs_manager_user->getModerateTransfers();
  
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/rvs_manager_moderate_transfers.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/rvs_manager_moderate_transfers.smarty'.($stop-$start);

    return $output;
  }

  function getRvsManagerSeasonBox ($widget = false, $sport_id='', $season_id='', $index = false, $dashboard = false) {
    global $_SESSION;
    global $db;
    global $auth;
    global $smarty;

    $template_file = "";
    $cached = false;
    // content
    if ($widget) {
      // turn cache on
      $template_file = "smarty_tpl/bar_rvs_manager_seasons_external.smarty";
      $smarty->setCaching(3600);
      if ($smarty->isCached($template_file, 'rvs_manager_widget'))
        $cached= true;
    }
    elseif ($dashboard) 
      $template_file = "smarty_tpl/bar_rvs_manager_seasons_dashboard.smarty";
    else 
      $template_file = "smarty_tpl/bar_rvs_manager_seasons.smarty";

    $manager_seasons = "";
    if (!$cached) {
      $season_ar = '';
  
      $where_season= '';
      $where_sport_id= '';
      $where_season_expired = '';
      if (!empty($season_id)) {
        $where_season = " AND MSS.SEASON_ID=".$season_id ;
      }
      else if (!$dashboard)
        $where_season_expired = " AND DATE_ADD(NOW(), INTERVAL -14 DAY) < END_DATE ";
      if (!empty($sport_id)) {
        $where_sport_id = " AND MSS.SPORT_ID=".$sport_id ;
        $where_season_expired = " AND DATE_ADD(NOW(), INTERVAL -14 DAY) < END_DATE ";
      }      

//$db->showquery=true;
      if ($auth->userOn()) {
        $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MS.PLACE, MSS.PIC_LOCATION, NOW() > END_DATE as EXPIRED
             FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                  left join manager_standings MS on MSS.SEASON_ID=MS.MSEASON_ID AND MS.USER_ID=".$auth->getUserId()."
             WHERE MSS.START_DATE < NOW( ) 
	   AND MSS.PUBLISH='Y'
		AND MSS.ALLOW_RVS_LEAGUES = 'Y'
	   ".$where_season_expired."
	   ".$where_season."
	   ".$where_sport_id."
          ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";
      }
      else {
          $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MSS.PIC_LOCATION, NOW() > END_DATE as EXPIRED
             FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
             WHERE MSS.START_DATE < NOW( ) 
	   AND MSS.PUBLISH='Y'
		AND MSS.ALLOW_RVS_LEAGUES = 'Y'
	   ".$where_season_expired."
	   ".$where_season."
	   ".$where_sport_id."
          ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";
      }

      $db->query($sql);
      $c=0;
      $places = '';
      while ($row = $db->nextRow()) {
        $season_id = $row['SEASON_ID'];
        $manager_season['EXPIRED'] = $row['EXPIRED'];
        $manager_season['SEASON_ID'] = $row['SEASON_ID'];
        $manager_season['MANAGER_INFO']['SEASON_ID'] = $row['SEASON_ID'];
        $manager_season['MANAGER_INFO']['SEASON_TITLE'] = $row['SEASON_TITLE'];
        $season_ar[$c] = $season_id;
	$manager_season['PIC_LOCATION'] = '';
        if (!empty($row['PIC_LOCATION']))
          $manager_season['PIC_LOCATION'] = $row['PIC_LOCATION'];
        if (isset($row['PLACE']) && !empty($row['PLACE']))
          $places[$season_id] = $row['PLACE'];
        else $places[$season_id] = 2;
        $manager_seasons[] = $manager_season;
        $c++;
      }
      if ($c == 0)
        return "";
   
      $mlog = new ManagerLog();
      foreach ($manager_seasons as &$manager_season) {
        $season_id = $manager_season['MANAGER_INFO']['SEASON_ID'];
        $place = $places[$season_id];
   //      $log_entry = $mlog->getManagerLogLastItem($season_id);
   //      if ($log_entry != '')
   //        $manager_season['MANAGER_LOG']['LOG'] = $log_entry;
	 $this->getMarketOpening($season_id, $manager_season);
      }
    }
//$db->showquery=false;
    $smarty->assign("manager_seasons", $manager_seasons);
    $start = getmicrotime();
    if ($widget) {
      $output = $smarty->fetch($template_file, 'rvs_manager_widget');    
      $smarty->caching= false;
    }
    else {
      $smarty->caching= false;
      $output = $smarty->fetch($template_file);    
    }
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo $template_file.($stop-$start);
    return $output;
  }


  function getSoloManagerSeasonBox ($widget = false, $sport_id='', $season_id='', $index = false, $dashboard = false) {
    global $_SESSION;
    global $db;
    global $auth;
    global $smarty;

    $template_file = "";
    $cached = false;
    // content
    if ($widget) {
      // turn cache on
      $template_file = "smarty_tpl/bar_solo_manager_seasons_external.smarty";
      $smarty->setCaching(3600);
      if ($smarty->isCached($template_file, 'solo_manager_widget'))
        $cached= true;
    }
    elseif ($dashboard) 
      $template_file = "smarty_tpl/bar_solo_manager_seasons_dashboard.smarty";
    else 
      $template_file = "smarty_tpl/bar_solo_manager_seasons.smarty";

    $manager_seasons = "";
    if (!$cached) {
      $season_ar = '';
  
      $where_season= '';
      $where_sport_id= '';
      $where_season_expired = '';
      if (!empty($season_id)) {
        $where_season = " AND MSS.SEASON_ID=".$season_id ;
      }
      else if (!$dashboard)
        $where_season_expired = " AND DATE_ADD(NOW(), INTERVAL -14 DAY) < END_DATE ";
      if (!empty($sport_id)) {
        $where_sport_id = " AND MSS.SPORT_ID=".$sport_id ;
        $where_season_expired = " AND DATE_ADD(NOW(), INTERVAL -14 DAY) < END_DATE ";
      }      

//$db->showquery=true;
      if ($auth->userOn()) {
        $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MS.PLACE, MSS.PIC_LOCATION, NOW() > END_DATE as EXPIRED
             FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                  left join solo_manager_standings MS on MSS.SEASON_ID=MS.SEASON_ID AND MS.USER_ID=".$auth->getUserId()."
             WHERE MSS.START_DATE < NOW( ) 
	   AND MSS.PUBLISH='Y'
		AND MSS.ALLOW_SOLO = 'Y'
	   ".$where_season_expired."
	   ".$where_season."
	   ".$where_sport_id."
          ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";
      }
      else {
          $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MSS.PIC_LOCATION, NOW() > END_DATE as EXPIRED
             FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
             WHERE MSS.START_DATE < NOW( ) 
	   AND MSS.PUBLISH='Y'
		AND MSS.ALLOW_SOLO = 'Y'
	   ".$where_season_expired."
	   ".$where_season."
	   ".$where_sport_id."
          ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";
      }
//echo $sql;
      $db->query($sql);
      $c=0;
      $places = '';
      while ($row = $db->nextRow()) {
        $season_id = $row['SEASON_ID'];
        $manager_season['EXPIRED'] = $row['EXPIRED'];
        $manager_season['SEASON_ID'] = $row['SEASON_ID'];
        $manager_season['MANAGER_INFO']['SEASON_ID'] = $row['SEASON_ID'];
        $manager_season['MANAGER_INFO']['SEASON_TITLE'] = $row['SEASON_TITLE'];
        $season_ar[$c] = $season_id;
	$manager_season['PIC_LOCATION'] = '';
        if (!empty($row['PIC_LOCATION']))
          $manager_season['PIC_LOCATION'] = $row['PIC_LOCATION'];
        if (isset($row['PLACE']) && !empty($row['PLACE']))
          $places[$season_id] = $row['PLACE'];
        else $places[$season_id] = 2;
        $manager_seasons[] = $manager_season;
        $c++;
      }
      if ($c == 0)
        return "";
   
      $mlog = new ManagerLog();
      foreach ($manager_seasons as &$manager_season) {
        $season_id = $manager_season['MANAGER_INFO']['SEASON_ID'];
        $place = $places[$season_id];
   //      $log_entry = $mlog->getManagerLogLastItem($season_id);
   //      if ($log_entry != '')
   //        $manager_season['MANAGER_LOG']['LOG'] = $log_entry;
	 $this->getMarketOpening($season_id, $manager_season);
      }
    }
//$db->showquery=false;
    $smarty->assign("manager_seasons", $manager_seasons);
    $start = getmicrotime();
    if ($widget) {
      $output = $smarty->fetch($template_file, 'solo_manager_widget');    
      $smarty->caching= false;
    }
    else {
      $smarty->caching= false;
      $output = $smarty->fetch($template_file);    
    }
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo $template_file.($stop-$start);
    return $output;
  }

  function getManagerTournamentsDashboardBox () {
    global $_SESSION;
    global $db;
    global $auth;
    global $smarty;

    $template_file = "";
    $cached = false;
    // content

    $manager_seasons = "";
    if (!$cached) {
      $season_ar = '';
  
      $where_season= '';
      $where_sport_id= '';
      $where_season_expired = '';
//$db->showquery=true;
      if ($auth->userOn()) {
        $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MSS.PIC_LOCATION, MT.MT_ID, MT.TITLE, MT.STATUS, MTM.USER_ID,
                MAX(MTU.TOUR) TOUR_REACHED, MAX(MTU2.TOUR) CURRENT_TOUR, MTU3.WINNER, MT.JOINED, MT.TOURNAMENT_TYPE
        	FROM manager_seasons MSS
			left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id'].",
	               manager_tournament MT
                        left join manager_tournament_members MTM on MT.MT_ID=MTM.MT_ID and MTM.USER_ID=".$auth->getUserId()." and MTM.STATUS in (1,2)
                        left join manager_tournament_users MTU on MT.MT_ID=MTU.MT_ID and MTU.USER_ID=".$auth->getUserId()." 
                        left join manager_tournament_users MTU2 on MT.MT_ID=MTU2.MT_ID 
                        left join manager_tournament_users MTU3 on MT.MT_ID=MTU3.MT_ID and MTU3.USER_ID=".$auth->getUserId()."  and MTU3.winner=1
             WHERE MSS.START_DATE < NOW( ) 
		   AND MSS.PUBLISH='Y'
        	   AND DATE_ADD(NOW(), INTERVAL -14 DAY) <  MSS.END_DATE
                   and MT.SEASON_ID=MSS.SEASON_ID
           GROUP BY MSS.SEASON_ID, MSD.SEASON_TITLE, MSS.PIC_LOCATION, MT.MT_ID, MT.TITLE, MT.STATUS, MTM.USER_ID
          ORDER BY MT.STATUS DESC";
      }
      else {
        $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MSS.PIC_LOCATION, MT.MT_ID, MT.TITLE, 
		MT.STATUS, MAX(MTU2.TOUR) CURRENT_TOUR, MT.JOINED, MT.TOURNAMENT_TYPE
        	FROM manager_seasons MSS
			left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id'].",
	               manager_tournament MT
                        left join manager_tournament_users MTU2 on MT.MT_ID=MTU2.MT_ID 
             WHERE MSS.START_DATE < NOW( ) 
		   AND MSS.PUBLISH='Y'
        	   AND DATE_ADD(NOW(), INTERVAL -14 DAY) <  MSS.END_DATE
                   and MT.SEASON_ID=MSS.SEASON_ID
           GROUP BY MSS.SEASON_ID, MSD.SEASON_TITLE, MSS.PIC_LOCATION, MT.MT_ID, MT.TITLE, MT.STATUS
          ORDER BY MT.STATUS DESC";
      }
//echo $sql;
      $db->query($sql);
      while ($row = $db->nextRow()) {
        $season_id = $row['SEASON_ID'];
        $manager_season = $row;
        $manager_season['MANAGER_INFO']['SEASON_ID'] = $row['SEASON_ID'];
        $manager_season['MANAGER_INFO']['SEASON_TITLE'] = $row['SEASON_TITLE'];
	$manager_season['PIC_LOCATION'] = '';
        if (!empty($row['PIC_LOCATION']))
          $manager_season['PIC_LOCATION'] = $row['PIC_LOCATION'];
        $manager_seasons[] = $manager_season;
      }
    }
//$db->showquery=false;
//print_r($manager_seasons);
    $smarty->assign("manager_seasons", $manager_seasons);
    $smarty->assign("user_on", $auth->userOn());
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch('smarty_tpl/bar_manager_tournaments_dashboard.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_tournaments_dashboard.smarty'.($stop-$start);
    return $output;
  }


  function getManagerParticipationBox () {
    global $smarty;
    global $_SESSION;    
    global $manager;
    global $manager_user;
    global $auth;

    if ($auth->userOn() && isset($manager_user->inited) && $manager_user->inited) {
      $smarty->assign("participating", 1);
    } 
//    else return "";
    $start = getmicrotime();
    $smarty->caching = false;
    $output = $smarty->fetch('smarty_tpl/bar_manager_participation.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_participation.smarty'.($stop-$start)."<br>";
    return $output;
  }

  function getManagerPlayerMarkBox ($mode) {
    global $tpl;
    global $_SESSION;
    global $_GET;
    global $smarty;

    $smarty->assign("season_id", $_GET['season_id']);
    $smarty->assign("player_id", $_GET['player_id']);
    if ($mode =='mark')
      $smarty->assign("marked", 1);
    else $smarty->assign("unmarked", 1);

    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_manager_mark_player.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_mark_player.smarty'.($stop-$start);

    return $output;
  }

  function getSoloTours() {
    global $manager;
    global $smarty;
    
    // content
    $days = $manager->getSoloTours();
    $smarty->assign("days", $days);  
      
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/solo_manager_control_tours.smarty');
    $stop = getmicrotime();

//    echo 'smarty_tpl/solo_manager_control_tours.smarty'.($stop-$start);
    return $output;
  }


  function getSoloToursSchedule() {
    global $manager;
    global $smarty;

    $today = date('Y-m-d');;
    $tours = $manager->getSoloToursSchedule($today);

    $smarty->assign("tours", $tours);
    $smarty->assign("today", $today);

    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/solo_manager_tours.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/solo_manager_tours.smarty'.($stop-$start);

    return $content;
    
  }


  function getSoloTourSchedule() {
    global $manager;
    global $smarty;

    $current_tour = $manager->getCurrentTour();
    $tours = $manager->getSoloTourSchedule($current_tour);

    $smarty->assign("tours", $tours);

    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/bar_solo_manager_tour.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_solo_manager_tour.smarty'.($stop-$start);

    return $content;
    
  }

  function getSoloManagerStandingsBox($season_id, $place=2) {
     global $db;
     global $auth;
     global $smarty;
//$db->showquery = true;
      $place = 2;
      if ($auth->userOn()) {
        $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MS.PLACE, MSS.PIC_LOCATION
             FROM manager_seasons MSS
		left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                  left join solo_manager_standings MS on MSS.SEASON_ID=MS.SEASON_ID AND MS.USER_ID=".$auth->getUserId()."
             WHERE MSS.START_DATE < NOW( ) 
           AND MSS.SEASON_ID =  ".$season_id."
	   AND MSS.PUBLISH='Y'
	   AND MSS.ALLOW_SOLO='Y'
          ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          if (isset($row['PLACE']) && !empty($row['PLACE']))
            $place = $row['PLACE'];
          else $place = 2;
        }
      }

      $sql="SELECT U.USER_NAME, U.USER_ID, MS.POINTS AS KOEFF, MSS.SEASON_ID, MS.PLACE 
      		 FROM users U, solo_manager_users MU, solo_manager_standings MS, manager_seasons MSS
	       WHERE MS.USER_ID = U.USER_ID 
        	 AND MU.USER_ID = MS.USER_ID
	         AND MSS.SEASON_ID = MS.SEASON_ID
        	 AND MSS.SEASON_ID =  ".$season_id."
	         AND MSS.ALLOW_SOLO='Y'
	         AND MU.SEASON_ID =  ".$season_id."
	         AND MS.SEASON_ID =  ".$season_id."
	         AND (MS.PLACE = 1
                  OR MS.PLACE BETWEEN ".($place-5)." AND ".($place+5).")
	       ORDER BY MS.POINTS DESC, MS.PLACE ASC, U.USER_NAME";
      $db->query($sql);
      $cc = 0;
      $prev_place = '';
      $manager_standings['USERS'] = array();
      while ($row = $db->nextRow()) {
        $user = $row;
        if (strlen($user['USER_NAME']) > 13)
          $user['USER_NAME'] = substr($user['USER_NAME'], 0,13)."...";
        if (isset($row['PLACE']) && $row['PLACE']-$prev_place > 1) {
          $user['GAP'] = 1;
        }
        if (isset($row['PLACE']))
          $prev_place = $row['PLACE'];
        $manager_standings['USERS'][] = $user;
      } 
      if (count($manager_standings['USERS']) == 0)
        return '';

    $smarty->assign("season_id", $season_id);
    $smarty->assign("manager_standings", $manager_standings);
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch("smarty_tpl/bar_manager_standings.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_manager_standings.smarty".($stop-$start);
    return $output;

  }

  function getSoloManagerParticipationBox () {
    global $smarty;
    global $_SESSION;    
    global $manager;
    global $manager_user;
    global $auth;

    if ($auth->userOn() && isset($manager_user->solo_inited) && $manager_user->solo_inited) {
      $smarty->assign("participating", 1);
    } 
//    else return "";
    $start = getmicrotime();
    $smarty->caching = false;
    $output = $smarty->fetch('smarty_tpl/bar_solo_manager_participation.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_solo_manager_participation.smarty'.($stop-$start)."<br>";
//echo $output;
    return $output;

  }

  function getSoloManagerSummaryBox ($auth, $dashboard = false) {
    global $tpl;
    global $db;
    global $_SESSION;
    global $manager;
    global $manager_user;
    global $smarty;
    
    // content
    if ($auth->userOn()) {
      $manager_summary = $_SESSION['_user']['SOLO_MANAGER'][$manager_user->mseason_id];
      $manager_summary['CREDIT'] = $_SESSION['_user']['CREDIT'];
      $manager_summary['IGNORE_LEAGUES'] = $_SESSION['_user']['SOLO_MANAGER'][$manager_user->mseason_id]['IGNORE_LEAGUES'];
      $manager_summary['TEAM_STATUS'] = $this->getSoloManagerSummaryTeamStatusBox();

      $where_external="";
      $field_external="";
      if (isset($_SESSION['external_user'])) {
        $where_external = " LEFT JOIN solo_manager_standings_external MSE ON MSE.USER_ID=".$auth->getUserId()." and MSE.MSEASON_ID=".$manager_user->mseason_id." AND MSE.SOURCE='".$_SESSION['external_user']['SOURCE']."'";
        $field_external = ", MSE.PLACE as EXTERNAL_PLACE";
      }
      $sql="SELECT MS.SEASON_ID, SEASON_TITLE, PIC_LOCATION, MST.PLACE, MST.POINTS ".$field_external."
             FROM manager_seasons MS
		   LEFT JOIN manager_seasons_details MSD ON MS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
		   ".$where_external."
		   LEFT JOIN solo_manager_standings MST ON MS.SEASON_ID = MST.SEASON_ID AND MST.USER_ID=".$auth->getUserId()."
             WHERE  MS.SEASON_ID = ".$manager_user->mseason_id."
                 AND PUBLISH = 'Y'  
                 AND ALLOW_SOLO = 'Y'  
        ORDER BY END_DATE DESC";
      $db->query($sql);
      $row = $db->nextRow();

      $manager_summary['SEASON_TITLE'] = $row['SEASON_TITLE'];
      $manager_summary['PLACE'] = $row['PLACE'];
      if (isset($row['EXTERNAL_PLACE']))
        $manager_summary['EXTERNAL_PLACE'] = $row['EXTERNAL_PLACE'];
      $manager_summary['POINTS'] = $row['POINTS'];
      $manager_summary['LEAGUE'] = $this->getSoloManagerLeagueBox ($manager_user);
    }

    $smarty->assign("solo_manager", $manager_summary);
    if ($dashboard)
      $template = "bar_solo_manager_dashboard";
    else $template = "bar_solo_manager";
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/'.$template.'.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/'.$template.'.smarty'.($stop-$start);
    return $output;
  } 

  function getSoloManagerLeagueBox ($manager_user) {
    global $smarty;
    global $_SESSION;
    global $_GET;

    $leagues = $manager_user->getLeagues("", "solo_");
    $leagues_invites = $manager_user->getLeaguesInvites("solo_");
    $smarty->assign("leagues", $leagues);
    $smarty->assign("league_invites", $leagues_invites);
    $start = getmicrotime();
    $smarty->caching = false;
    $output = $smarty->fetch('smarty_tpl/bar_solo_manager_leagues.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_solo_manager_leagues.smarty'.($stop-$start)."<br>";
    return $output;
  }

}

?>