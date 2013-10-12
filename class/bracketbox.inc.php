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

class BracketBox extends Box{
  var $season_id;
  
  function getBracketBox () {
    global $tpl;
    global $db;
  
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    $tpl->setTemplateFile('tpl/bar_bracket.tpl.html');

    $sql="SELECT SEASON_ID, TSEASON_TITLE
             FROM bracket_seasons MSS
             WHERE START_DATE < NOW( ) AND 
                   END_DATE > DATE_ADD( NOW( ) , INTERVAL -15 DAY ) 
                 AND PUBLISH = 'Y'  
        ORDER BY END_DATE DESC";

    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
       $season_id = $row['SEASON_ID'];
       $data['ARRANGER'][$season_id]['BRACKET_INFO'][0]['TSEASON_TITLE'] = $row['TSEASON_TITLE'];
       $data['ARRANGER'][$season_id]['BRACKET_INFO'][0]['SEASON_ID'] = $row['SEASON_ID'];
       $season_ar[$c] = $season_id;
       $c++;
    }
    $db->free();
  
    if ($c == 0)
      return '';

    for ($i = 0; $i < $c; $i++) {
       $season_id = $season_ar[$i];
       if (!empty($season_id)) {
         $sql="SELECT U.USER_NAME, U.USER_ID, MU.MONEY, MS.WEALTH AS KOEFF, MSS.SEASON_TITLE, MSS.SEASON_ID 
            FROM users U, bracket_users MU, bracket_standings MS, bracket_seasons MSS
           WHERE MS.USER_ID = U.USER_ID 
             AND MU.USER_ID = MS.USER_ID
             AND MSS.SEASON_ID = MS.MSEASON_ID
             AND MSS.SEASON_ID =  ".$season_id."
  	   AND MU.SEASON_ID =  ".$season_id."
  	   AND MS.MSEASON_ID =  ".$season_id."
           ORDER BY MS.POINTS DESC, U.USER_NAME LIMIT 3";

          $db->query($sql);
          $c = 0;
          while ($row = $db->nextRow()) {
            $data['ARRANGER'][$season_id]['BRACKET_STAND'][0]['USERS'][$c] = $row;
            $data['ARRANGER'][$season_id]['BRACKET_STAND'][0]['USERS'][$c]['NUMBER'] = $c+1;
            $data['ARRANGER'][$season_id]['BRACKET_STAND'][0]['SEASON_ID'] = $row['SEASON_ID'];
    
            $c++;
          } 
          $db->free();
       }
    
    }
    $tpl->addData($data);
    return $tpl->parse();
  }
  
  function getBracketFilterBox ($season_id) {
    global $smarty;
    global $bracket;
    
    // content
    $season = inputBracketSeasons('tseason_id', $season_id);

    $smarty->assign("season_id", $season);
    $smarty->assign("bracket", $bracket);
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch("smarty_tpl/bar_bracket_filter.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_bracket_filter.smarty".($stop-$start);
    return $output;

  }

  function getBracketSeasonBox ($widget = false, $sport_id='', $season_id='', $index=false, $dashboard = false) {
    global $_SESSION;
    global $db;
    global $auth;
    global $smarty;

    $template_file = "";
    $cached = false;
    // content
    if ($widget) {
      // turn cache on
      $template_file = "smarty_tpl/bar_bracket_seasons_external.smarty";
      $smarty->setCaching(3600);
      if ($smarty->isCached($template_file, 'bracket_widget'))
        $cached= true;
    }
    else if ($dashboard) {
      $template_file = "smarty_tpl/bar_bracket_seasons_dashboard.smarty";
    } 
    else {
      $template_file = "smarty_tpl/bar_bracket_seasons.smarty";
    }

    $bracket_seasons = "";
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
        $sql="SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE, MS.PLACE, MSS.PIC_LOCATION, NOW() > END_DATE as EXPIRED
             FROM bracket_seasons MSS
		left JOIN bracket_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                  left join bracket_standings MS on MSS.SEASON_ID=MS.MSEASON_ID AND MS.USER_ID=".$auth->getUserId()."
             WHERE MSS.START_DATE < NOW( ) 
	   AND MSS.PUBLISH='Y'
	   ".$where_season_expired."
	   ".$where_season."
	   ".$where_sport_id."
          ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";
      }
      else {
          $sql="SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE, MSS.PIC_LOCATION, NOW() > END_DATE as EXPIRED
             FROM bracket_seasons MSS
		left JOIN bracket_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
             WHERE MSS.START_DATE < NOW( ) 
	   AND MSS.PUBLISH='Y'
	   ".$where_season_expired."
	   ".$where_season."
	   ".$where_sport_id."
          ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";
      }
      $db->query($sql);
      $c=0;
      $places = '';
      $ongoing = 0;
      while ($row = $db->nextRow()) {
        $season_id = $row['SEASON_ID'];
        $bracket_season['SEASON_ID'] = $row['SEASON_ID'];
        $bracket_season['EXPIRED'] = $row['EXPIRED'];
        if ($row['EXPIRED'] == 0)
          $ongoing++;
        $bracket_season['BRACKET_INFO']['SEASON_ID'] = $row['SEASON_ID'];
        $bracket_season['BRACKET_INFO']['SEASON_TITLE'] = $row['TSEASON_TITLE'];
        $season_ar[$c] = $season_id;
        if (!empty($row['PIC_LOCATION']))
          $bracket_season['PIC_LOCATION'] = $row['PIC_LOCATION'];
        if (isset($row['PLACE']) && !empty($row['PLACE']))
          $places[$season_id] = $row['PLACE'];
        else $places[$season_id] = 2;
        $bracket_seasons[] = $bracket_season;
        $c++;
      }
      if ($c == 0)
        return "";
  
      $mlog = new BracketLog();
      foreach ($bracket_seasons as &$bracket_season) {
         $season_id = $bracket_season['BRACKET_INFO']['SEASON_ID'];
         $place = $places[$season_id];
	 if (!$index) {
          if (!empty($season_id)) {
           $sql="SELECT U.USER_NAME, U.USER_ID, MS.POINTS AS KOEFF, MSS.SEASON_ID, MS.PLACE 
              FROM users U, bracket_users MU, bracket_standings MS, bracket_seasons MSS
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
          $bracket_season['BRACKET_STAND']['USERS'] = array();
          while ($row = $db->nextRow()) {
            $user = $row;
            if (isset($row['PLACE']) && $row['PLACE']-$prev_place > 1) {
              $user['GAP'] = 1;
            }
	    if (isset($row['SEASON_ID']))
              $bracket_season['BRACKET_STAND']['SEASON_ID'] = $row['SEASON_ID'];
            $cc++;
	    if (isset($row['PLACE']))
              $prev_place = $row['PLACE'];
  	    $bracket_season['BRACKET_STAND']['USERS'][] = $user;
          } 
          if (count($bracket_season['BRACKET_STAND']['USERS']) == 0)
            unset($bracket_season['BRACKET_STAND']);
          $db->free();
         }
  
        }
        $this->getBracketOpening($season_id, $bracket_season);
        $log_entry = $mlog->getBracketLogLastItem($season_id);
        if ($log_entry != '')
          $bracket_season['BRACKET_LOG']['LOG'] = $log_entry;
      }
    }
//$db->showquery=false;
    $smarty->assign("bracket_seasons", $bracket_seasons);
    $smarty->assign("ongoing", $ongoing);
    $start = getmicrotime();
    if ($widget) {
      $output = $smarty->fetch($template_file, 'bracket_widget');    
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


  function getBracketOpening($season_id, &$bracket_season) {
    global $db;
    global $auth;
    global $bracket;

    $season_over = false;

    $data = "";
    $db->select("bracket_seasons", "*, END_DATE < NOW() ENDED", "SEASON_ID=".$season_id);
    if ($row = $db->nextRow()) {
      $season_over= $row['ENDED'];
    }

//    if (!$season_over) {
      $sql = "SELECT NUMBER, START_DATE,
                  DATE_ADD(START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS TOUR_START_DATE
             FROM bracket_tours 
             WHERE NOW() <= START_DATE
                   AND SEASON_ID=".$season_id."
            ORDER BY NUMBER ASC";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        // there are tours ahead
        $next_tour_date = $row['START_DATE'];   
        $next_tour_date_utc = $row['TOUR_START_DATE'];   
        $utc = $auth->getUserTimezoneName();
     
        $current_tour = empty($row['NUMBER']) ? "0" : $row['NUMBER'];
        if (isset($bracket))
          $bracket->last_tour = $current_tour-1;
      } 

    
      $sql = "SELECT count( LEAGUE_id ) LEAGUES
		FROM bracket_leagues 
		WHERE season_id =".$season_id;
      $db->query($sql);
      if ($row = $db->nextRow()) {
          $bracket_season['LEAGUES']['LEAGUES'] = $row['LEAGUES'];
      } 

      if ($auth->userOn()) {
         $sql = "SELECT count( RL.LEAGUE_ID ) LEAGUES
		FROM bracket_leagues RL, bracket_leagues_members RML
		WHERE RL.LEAGUE_ID=RML.LEAGUE_ID
			AND RML.USER_ID=".$auth->getUserId()."
			AND RML.STATUS IN (1, 2)
			AND RL.season_id =".$season_id;
         $db->query($sql);
         if ($row = $db->nextRow()) {
           $bracket_season['LEAGUES']['MY_LEAGUES'] = $row['LEAGUES'];
         }

         $sql = "SELECT count( MLM.league_id ) LEAGUES
		FROM bracket_leagues_members MLM, bracket_leagues ML
		WHERE MLM.STATUS =3 
			AND ML.season_id =".$season_id." 
			AND MLM.LEAGUE_ID=ML.LEAGUE_ID
			AND MLM.USER_ID=".$auth->getUserID();
         $db->query($sql);
         if ($row = $db->nextRow()) {
	   if (isset($row['LEAGUES']) && $row['LEAGUES'] > 0) {
     	     $bracket_season['LEAGUES_INVITE']['LEAGUES'] = $row['LEAGUES'];
    	     $bracket_season['LEAGUES_INVITE']['SEASON_ID'] = $season_id;
           }
         } 
       }
//    }

    if ($season_over) {
      $bracket_season['MARKET']['SEASON_OVER'] = 1;   
    } else if (isset($next_tour_date)) {
      $bracket_season['MARKET']['MARKET_OPEN']['START_DATE'] = $next_tour_date_utc;   
      $bracket_season['MARKET']['MARKET_OPEN']['UTC'] = $utc;   
    } 
  }

  function getBracketSummaryBox ($auth, $dashboard=false) {
    global $smarty;
    global $db;
    global $_SESSION;
    global $bracket;
    global $bracket_user;
    
    // content

    if ($auth->userOn()) {
      $bracket_summary = $_SESSION['_user']['ARRANGER'][$bracket_user->tseason_id];
      $bracket_summary['CREDIT'] = $_SESSION['_user']['CREDIT'];
      $bracket_summary['IGNORE_LEAGUES'] = $_SESSION['_user']['ARRANGER'][$bracket_user->tseason_id]['IGNORE_LEAGUES'];
      $bracket_summary['USE_DRAGDROP'] = $_SESSION['_user']['ARRANGER'][$bracket_user->tseason_id]['USE_DRAGDROP'];
      $bracket_summary['ALLOW_VIEW'] = $_SESSION['_user']['ARRANGER'][$bracket_user->tseason_id]['ALLOW_VIEW'];
      $bracket_summary['PRIZE_FUND'] = $bracket->prize_fund;
      $bracket_summary['TEAM_STATUS'] = $this->getBracketSummaryTeamStatusBox();

      $sql="SELECT MS.SEASON_ID, TSEASON_TITLE, MST.PLACE, MST.POINTS
             FROM bracket_seasons MS
		   LEFT JOIN bracket_seasons_details MSD ON MS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
		   LEFT JOIN bracket_standings MST ON MS.SEASON_ID = MST.MSEASON_ID AND MST.USER_ID=".$auth->getUserId()."
             WHERE  MS.SEASON_ID = ".$bracket_user->tseason_id."
                 AND PUBLISH = 'Y'  
        ORDER BY END_DATE DESC";
      $db->query($sql);
      $row = $db->nextRow();

      $bracket_summary['SEASON_TITLE'] = $row['TSEASON_TITLE'];
      $bracket_summary['PLACE'] = $row['PLACE'];
      if (!empty($row['POINTS']))
        $bracket_summary['POINTS'] = $row['POINTS'];

      $bracket_summary['LEAGUE'] = $this->getBracketLeagueBox ($bracket_user);
    }

    $smarty->assign("bracket", $bracket_summary);
    if ($dashboard)
      $template = "bar_bracket_dashboard";
    else $template = "bar_bracket";
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/'.$template.'.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/'.$template.'.smarty'.($stop-$start);
    return $output;

  } 

  function getBracketSummaryTeamStatusBox () {
    global $smarty;
    global $_SESSION;
    global $_GET;
    global $bracket_user;

    if ($_SESSION['_user']['ARRANGER'][$bracket_user->tseason_id]['IGNORE_LEAGUES'] == -1)
      $ignore_leagues['IGNORE_LEAGUES'] = $bracket_user->tseason_id;
    else $ignore_leagues['UNIGNORE_LEAGUES'] = $bracket_user->tseason_id;
    $smarty->assign("ignore_leagues", $ignore_leagues);

    if ($_SESSION['_user']['ARRANGER'][$bracket_user->tseason_id]['USE_DRAGDROP'] == 1)
      $use_dragdrop['USE_DRAGDROP'] = $bracket_user->tseason_id;
    else $use_dragdrop['NOTUSE_DRAGDROP'] = $bracket_user->tseason_id;
    $smarty->assign("use_dragdrop", $use_dragdrop);

    if ($_SESSION['_user']['ARRANGER'][$bracket_user->tseason_id]['ALLOW_VIEW'] == -1)
      $allow_view['REVEAL'] = $bracket_user->tseason_id;
    else $allow_view['HIDE'] = $bracket_user->tseason_id;
    $smarty->assign("allow_view", $allow_view);

    $smarty->assign("season_id", $bracket_user->tseason_id);

    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/bar_bracket_account_status.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_bracket_account_status.smarty'.($stop-$start);
    return $content;
  }


  function getBracketLeagueBox ($bracket_user) {
    global $smarty;
    global $_SESSION;
    global $_GET;

    $leagues = $bracket_user->getLeagues();
    $leagues_invites = $bracket_user->getLeaguesInvites();
    $smarty->assign("leagues", $leagues);
    $smarty->assign("league_invites", $leagues_invites);
    $start = getmicrotime();
    $smarty->caching = false;
    $output = $smarty->fetch('smarty_tpl/bar_bracket_leagues.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_bracket_leagues.smarty'.($stop-$start)."<br>";
    return $output;
  }

  function getBracketStandingsBox($season_id, $place=2) {
     global $db;
     global $auth;
     global $smarty;

      $place = 2;
      if ($auth->userOn()) {
        $sql="SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE as SEASON_TITLE, MS.PLACE, MSS.PIC_LOCATION
             FROM bracket_seasons MSS
		left JOIN bracket_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                  left join bracket_standings MS on MSS.SEASON_ID=MS.MSEASON_ID AND MS.USER_ID=".$auth->getUserId()."
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

      $sql="SELECT U.USER_NAME, U.USER_ID, MS.POINTS AS KOEFF, MSS.SEASON_ID, MS.PLACE 
      		 FROM users U, bracket_users MU, bracket_standings MS, bracket_seasons MSS
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
    $output = $smarty->fetch("smarty_tpl/bar_bracket_standings.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_bracket_standings.smarty".($stop-$start);
    return $output;
  }

  function getBracketSeasonDashboardBox ($season_id) {
    global $_SESSION;
    global $db;
    global $auth;
    global $smarty;
    global $langs;

    $manager_seasons = "";
    $where_season = " AND MSS.SEASON_ID=".$season_id ;


     $sql="SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE as SEASON_TITLE, MSS.PIC_LOCATION
      	  FROM bracket_seasons MSS
		left JOIN bracket_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
        WHERE MSS.START_DATE < NOW( ) 
		  AND MSS.PUBLISH='Y'
		  ".$where_season."
        ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";

    $db->query($sql);
    $c=0;
    if ($row = $db->nextRow()) {
      $season_id = $row['SEASON_ID'];
      $bracket_season['SEASON_ID'] = $row['SEASON_ID'];
      $bracket_season['BRACKET_INFO']['SEASON_ID'] = $row['SEASON_ID'];
      $bracket_season['BRACKET_INFO']['SEASON_TITLE'] = $row['SEASON_TITLE'];
      $season_ar[$c] = $season_id;
	$bracket_season['PIC_LOCATION'] = '';
      if (!empty($row['PIC_LOCATION']))
        $bracket_season['PIC_LOCATION'] = $row['PIC_LOCATION'];
      $c++;
    }
    if ($c == 0)
      return "";
   
    $mlog = new BracketLog();

    $this->getBracketOpening($season_id, $bracket_season);
    $bracket_logbox = new LogBox($langs, $_SESSION["_lang"]);
    $bracket_season['BRACKET_LOG'] = $bracket_logbox->getBracketLogBox($season_id, 1, 3);

    if ($auth->userOn()) {
      $bracket_season['BRACKET_USER_LOG'] = $bracket_logbox->getBracketUserLogBox($auth->getUserId(), $season_id, 1, 3, $langs['LANG_PERSONAL_LOG_U']);
    }


//$db->showquery=false;
    $smarty->assign("bracket_season", $bracket_season);
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch("smarty_tpl/bar_bracket_season_dashboard.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_bracket_season_dashboard.smarty".($stop-$start);
    return $output;
  }


  function getToursSchedule() {
    global $bracket;
    global $smarty;

    $current_tour = $bracket->getNextRaceId();
    $tours = $bracket->getToursSchedule($current_tour);

    $smarty->assign("tours", $tours);
    $smarty->assign("current_tour", $current_tour);

    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/bracket_tours.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bracket_tours.smarty'.($stop-$start);

    return $content;   
  }

  function getTourSchedule() {
    global $bracket;
    global $smarty;

    $current_tour = $bracket->getNextTour();
    $tour = $bracket->getTourSchedule($current_tour);

    $smarty->assign("tour", $tour);
    $smarty->assign("current_tour", $current_tour);

    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/bar_bracket_tour.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_bracket_tour.smarty'.($stop-$start);

    return $content;   
  }


  function getBracketParticipationBox () {
    global $smarty;
    global $_SESSION;    
    global $bracket;
    global $bracket_user;
    global $auth;

    if ($auth->userOn() && isset($bracket_user->inited) && $bracket_user->inited) {
      $smarty->assign("participating", 1);
    } 
//    else return "";
    $start = getmicrotime();
    $smarty->caching = false;
    $output = $smarty->fetch('smarty_tpl/bar_bracket_participation.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_bracket_participation.smarty'.($stop-$start)."<br>";
    return $output;
  }

}

?>