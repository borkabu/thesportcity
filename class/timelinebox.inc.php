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

class TimelineBox extends Box{

  function TimelineBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getTimelineBox($mode = '') {
    global $db;
    global $_SESSION;
    global $smarty;
    global $months;
    global $langs;
    global $auth;
    global $clients;

    $sports =  "";
    if (isset($_SESSION['external_user']) && isset($clients[$_SESSION['external_user']['SOURCE']]['sports']))
      $sports = " AND MSS.SPORT_ID IN (".$clients[$_SESSION['external_user']['SOURCE']]['sports'].")";

    $smarty->setCaching(Smarty::CACHING_LIFETIME_SAVED);
    $smarty->setCacheLifetime(1000);
//$db->showquery=true;
    $smarty->caching= false;
    if (true) { //!$smarty->isCached('smarty_tpl/bar_timeline.smarty', 'bar_timeline'.$mode."_lang_id".$_SESSION['lang_id'])) {

         $day_fields = "";

// 0 opened, 1 closed, 2 opening, 3 closing, 4 same day event
         for ($i = -1; $i < 13; $i++) {
            $day_fields .= ", SUM(MT.START_DATE < DATE_ADD(NOW(), INTERVAL ".$i." DAY) AND MT.END_DATE >DATE_ADD(NOW(), INTERVAL ".$i." DAY)) 'day".$i."',
                              SUM(IF(DATE(MT.START_DATE) < DATE_ADD(NOW(), INTERVAL ".$i." DAY) AND DATE_ADD(DATE(MT.START_DATE), INTERVAL 1 DAY) > DATE_ADD(NOW(), INTERVAL ".$i." DAY), MT.NUMBER, 0)) 'closing".$i."',
                              SUM(IF(DATE(MT.END_DATE) < DATE_ADD(NOW(), INTERVAL ".$i." DAY) AND DATE_ADD(DATE(MT.END_DATE), INTERVAL 1 DAY) > DATE_ADD(NOW(), INTERVAL ".$i." DAY), MT.NUMBER, 0)) 'opening".$i."'";
         }

         $sql = ""; 
         $pre = "";
         $where_rvs = "";
         $where_solo = "";
         if ($mode == 'rvs_manager')
           $where_rvs = " AND MSS.ALLOW_RVS_LEAGUES='Y'";

         if ($mode == 'solo_manager')
           $where_solo = " AND MSS.ALLOW_SOLO='Y'";
           
         if (empty($mode) || $mode == 'manager' || $mode == 'rvs_manager' || $mode == 'manager_tournament' || $mode == 'solo_manager') {
           $sql .= "SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MU.USER_ID, MSS.PIC_LOCATION, 'MANAGER' as TYPE, ALLOW_SOLO ".$day_fields."
		           FROM manager_seasons MSS
			left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                        left join manager_users MU ON MU.SEASON_ID=MSS.SEASON_ID
					AND MU.USER_ID=".($auth->userOn() ? $auth->getUserId() : -1)."
			, manager_tours MT
		  WHERE MSS.START_DATE < NOW() 
			AND MSS.END_DATE > NOW()
			AND MSS.PUBLISH='Y'
			AND MT.SEASON_ID=MSS.SEASON_ID
			".$where_rvs.$where_solo.$sports."
			AND ((MT.START_DATE > NOW() AND MT.START_DATE < DATE_ADD(NOW(), INTERVAL 30 DAY))
				OR (MT.END_DATE > NOW() AND MT.END_DATE < DATE_ADD(NOW(), INTERVAL 30 DAY)))
		GROUP BY MSS.SEASON_ID";
           $pre = " UNION ";
         }
         if (empty($mode) || $mode == 'arranger') {
	   $sql .= $pre." SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE as SEASON_TITLE, MU.USER_ID, MSS.PIC_LOCATION, 'ARRANGER' as TYPE, 'N'  ".$day_fields."
		           FROM bracket_seasons MSS
			left JOIN bracket_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                        left join bracket_users MU ON MU.SEASON_ID=MSS.SEASON_ID
					AND MU.USER_ID=".($auth->userOn() ? $auth->getUserId() : -1)."
			, bracket_tours MT
		  WHERE MSS.START_DATE < NOW() 
			AND MSS.END_DATE > NOW()
			AND MSS.PUBLISH='Y'
			AND MT.SEASON_ID=MSS.SEASON_ID
			AND ((MT.START_DATE > NOW() AND MT.START_DATE < DATE_ADD(NOW(), INTERVAL 30 DAY))
				OR (MT.END_DATE > NOW() AND MT.END_DATE < DATE_ADD(NOW(), INTERVAL 30 DAY)))
		GROUP BY MSS.SEASON_ID";
           $pre = " UNION ";
         }
         if ($sql != "") {
          $db->query($sql);     
          $events = array();
          while ($row = $db->nextRow()) {
            $event = array();
            $event['SEASON_TITLE'] = $row['SEASON_TITLE'];
            $event['ALLOW_SOLO'] = $row['ALLOW_SOLO'];
	    $event['USER_ID'] = $row['USER_ID'];
            if (!empty($row['PIC_LOCATION']))
              $event['PIC_LOCATION'] = $row['PIC_LOCATION'];
            if ($mode == 'rvs_manager')
              $event['TYPE'] = "RVS_MANAGER";
            else if ($mode == 'manager_tournament')
              $event['TYPE'] = "MANAGER_TOURNAMENT";
            else $event['TYPE'] = $row['TYPE'];
            $event['SEASON_ID'] = $row['SEASON_ID'];
            for ($i = -1; $i < 13; $i++) {
              $state = array(); 
              $state['DAY'] = $row['day'.$i];
              $state['OPENING'] = $row['opening'.$i];
              $state['CLOSING'] = $row['closing'.$i];
              if ($i == 0)
		$state['TODAY'] = 1;
	      $event['STATE'][] = $state;
            }
            $events[] = $event;   
          }
         }
         if (empty($mode) || $mode == 'wager') {
           $wager_day_fields = '';
           for ($i = -1; $i < 13; $i++) {
              $wager_day_fields .= ", SUM(G.START_DATE > DATE_ADD(NOW(), INTERVAL ".$i." DAY) AND G.START_DATE <DATE_ADD(NOW(), INTERVAL ".($i+1)." DAY) ) 'day".$i."'";
           }

	   $sql = "SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE as SEASON_TITLE, MU.USER_ID, 'WAGER' as TYPE ".$wager_day_fields."
		           FROM wager_games WG, seasons S, games G, wager_seasons MSS
			left JOIN wager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                        left join wager_users MU ON MU.SEASON_ID=MSS.SEASON_ID
					AND MU.USER_ID=".($auth->userOn() ? $auth->getUserId() : -1)."
		  WHERE MSS.START_DATE < NOW() 
			AND MSS.END_DATE > NOW()
			AND MSS.PUBLISH='Y'
			AND WG.GAME_ID=G.GAME_ID
		        AND G.SEASON_ID = S.SEASON_ID
                        AND WG.WSEASON_ID=MSS.SEASON_ID
		        and G.START_DATE < MSS.END_DATE
			AND (G.START_DATE > NOW() AND G.START_DATE < DATE_ADD(NOW(), INTERVAL 30 DAY))
		GROUP BY MSS.SEASON_ID";
           $db->query($sql);
           if (!isset($events))
	     $events = array();     
           while ($row = $db->nextRow()) {
             $event = array();
             $event['SEASON_TITLE'] = $row['SEASON_TITLE'];
             $event['TYPE'] = $row['TYPE'];
             $event['SEASON_ID'] = $row['SEASON_ID'];
	     $event['USER_ID'] = $row['USER_ID'];
             for ($i = -1; $i < 13; $i++) {
               $state = ''; 
               $state['GAMES'] = $row['day'.$i];
               if ($i == 0)
		 $state['TODAY'] = 1;
	      $event['WAGERS'][] = $state;
             }

             $events[] = $event;   
           }
         }

         $days = array(); 
         $day_names = array(); 
	 $date = date("Y-m-d");
	 $daymonths = array();
         for ($i = -1; $i < 13; $i++) {
	   $days[] = 'day'.$i;
	   $dd = strtotime($i." day", strtotime($date));
	   $day_name['NAME'] = date ( 'm d' , $dd);
	   $day_name['DAY'] = date ( 'd' , $dd);
           if (isset($daymonths[date ( 'm' , $dd)]['WIDTH'])) 
  	     $daymonths[date ( 'm' , $dd)]['WIDTH']++;
           else $daymonths[date ( 'm' , $dd)]['WIDTH'] = 1;
	   $daymonths[date ( 'm' , $dd)]['NAME'] = $months[date ( 'n' , $dd)];
           $day_names[] = $day_name;
         }

         if ($auth->userOn()) {
           $this->getNotification($events);
           $smarty->assign("useron", 1);
         }

         if (count($events) > 0) {
           $smarty->assign("days", $days);
           $smarty->assign("daymonths", $daymonths);
           $smarty->assign("day_names", $day_names);
           $smarty->assign("events", $events);
         }
    }
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_timeline.smarty', 'bar_timeline'."_lang_id".$_SESSION['lang_id']);
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_timeline.smarty'.($stop-$start);
    $smarty->caching= false;
    return $output;

  }

  function getNotification(&$events) {
    global $db;
    global $auth;

    $sql = "SELECT COUNT(MLM.LEAGUE_ID) LEAGUES, ML.SEASON_ID, 'MANAGER' as TYPE
		FROM manager_leagues_members MLM, manager_leagues ML
		WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
			AND MLM.status=3 
			AND ML.SEASON_ID IS NOT NULL
			AND MLM.user_id=".$auth->getUserId()."
                GROUP BY  ML.SEASON_ID
                HAVING COUNT(MLM.LEAGUE_ID) > 0
            UNION
	    SELECT COUNT(MLM.LEAGUE_ID) LEAGUES, ML.SEASON_ID, 'MANAGER' as TYPE
		FROM rvs_manager_leagues_members MLM, rvs_manager_leagues ML
		WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
			AND MLM.status=3 
			AND ML.SEASON_ID IS NOT NULL
			AND MLM.user_id=".$auth->getUserId()."		
                GROUP BY  ML.SEASON_ID
                HAVING COUNT(MLM.LEAGUE_ID) > 0
            UNION
	    SELECT COUNT(MLM.MT_ID) LEAGUES, ML.SEASON_ID, 'MANAGER' as TYPE
		FROM manager_tournament_members MLM, manager_tournament ML
		WHERE ML.MT_ID=MLM.MT_ID
			AND MLM.status=3 
			AND ML.SEASON_ID IS NOT NULL
			AND MLM.user_id=".$auth->getUserId()."		
                GROUP BY  ML.MT_ID
                HAVING COUNT(MLM.MT_ID) > 0
            UNION
	    SELECT COUNT(MLM.LEAGUE_ID) LEAGUES, ML.SEASON_ID, 'WAGER' as TYPE
		FROM wager_leagues_members MLM, wager_leagues ML
		WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
			AND MLM.status=3 
			AND ML.SEASON_ID IS NOT NULL
			AND MLM.user_id=".$auth->getUserId()."		
                GROUP BY  ML.SEASON_ID
                HAVING COUNT(MLM.LEAGUE_ID) > 0
            UNION
	    SELECT COUNT(MLM.LEAGUE_ID) LEAGUES, ML.SEASON_ID, 'ARRANGER' as TYPE
		FROM bracket_leagues_members MLM, bracket_leagues ML
		WHERE ML.LEAGUE_ID=MLM.LEAGUE_ID
			AND MLM.status=3 
			AND ML.SEASON_ID IS NOT NULL
			AND MLM.user_id=".$auth->getUserId()."
                GROUP BY  ML.SEASON_ID
                HAVING COUNT(MLM.LEAGUE_ID) > 0";

     $db->query($sql);
     while ($row = $db->nextRow()) {
      if (count($events) > 0)
        foreach ($events as &$event) {
          if ($event['SEASON_ID'] == $row['SEASON_ID']
		&& $event['TYPE'] == $row['TYPE'])
            $event['NOTIFICATION'] = 1;
        }

     }

    
  }


}   
?>