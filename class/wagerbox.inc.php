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

class WagerBox extends Box{
  var $season_id;
  
  function getWagerBox () {
    global $tpl;
    global $db;
  
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    $tpl->setTemplateFile('tpl/bar_wager.tpl.html');

    $sql="SELECT SEASON_ID, TSEASON_TITLE
             FROM wager_seasons MSS
             WHERE START_DATE < NOW( ) AND 
                   END_DATE > DATE_ADD( NOW( ) , INTERVAL -15 DAY ) 
                 AND PUBLISH = 'Y'  
        ORDER BY END_DATE DESC";

    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
       $season_id = $row['SEASON_ID'];
       $data['WAGER'][$season_id]['WAGER_INFO'][0]['TSEASON_TITLE'] = $row['TSEASON_TITLE'];
       $data['WAGER'][$season_id]['WAGER_INFO'][0]['SEASON_ID'] = $row['SEASON_ID'];
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
            FROM users U, wager_users MU, wager_standings MS, wager_seasons MSS
           WHERE MS.USER_ID = U.USER_ID 
             AND MU.USER_ID = MS.USER_ID
             AND MSS.SEASON_ID = MS.SEASON_ID
             AND MSS.SEASON_ID =  ".$season_id."
  	   AND MU.SEASON_ID =  ".$season_id."
  	   AND MS.SEASON_ID =  ".$season_id."
           ORDER BY MS.WEALTH DESC, U.USER_NAME LIMIT 3";

          $db->query($sql);
          $c = 0;
          while ($row = $db->nextRow()) {
            $data['WAGER'][$season_id]['WAGER_STAND'][0]['USERS'][$c] = $row;
            $data['WAGER'][$season_id]['WAGER_STAND'][0]['USERS'][$c]['NUMBER'] = $c+1;
            $data['WAGER'][$season_id]['WAGER_STAND'][0]['SEASON_ID'] = $row['SEASON_ID'];
    
            $c++;
          } 
          $db->free();
       }
    
    }
    $tpl->addData($data);
    return $tpl->parse();
  }
  
  function getWagerFilterBox ($season_id) {
    global $smarty;
    global $wager;
    
    // content
    $season = inputWagerSeasons('season_id', $season_id);

    $smarty->assign("tseason_id", $season);
    $smarty->assign("wager", $wager);
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch("smarty_tpl/bar_wager_filter.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_wager_filter.smarty".($stop-$start);
    return $output;
  }

  function getWagerSeasonBox ($widget = false, $season_id='', $index = false, $dashboard=false) {
    global $smarty;
    global $_SESSION;
    global $db;
    global $auth;

    $where_season= '';
    $where_season_expired = '';
    if (!empty($season_id)) {
      $where_season = " AND MSS.SEASON_ID=".$season_id ;
    }
    else if (!$dashboard)
      $where_season_expired = " AND DATE_ADD(NOW(), INTERVAL -14 DAY) < END_DATE ";
    
    if ($auth->userOn()) {
      $sql="SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE, MS.PLACE, END_DATE < NOW() ENDED
           FROM wager_seasons MSS
		left JOIN wager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                left join wager_standings MS on MSS.SEASON_ID=MS.SEASON_ID AND MS.USER_ID=".$auth->getUserId()."
           WHERE MSS.START_DATE < NOW( ) 
                 AND PUBLISH = 'Y'  
	   ".$where_season_expired."
	   ".$where_season."
        ORDER BY MSS.END_DATE DESC";
    }
    else {
        $sql="SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE, END_DATE < NOW() ENDED
           FROM wager_seasons MSS
		left JOIN wager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
           WHERE MSS.START_DATE < NOW( ) 
                 AND PUBLISH = 'Y'  
	   ".$where_season_expired."
	   ".$where_season."
        ORDER BY MSS.END_DATE DESC";
    }
//echo $sql;
    $db->query($sql);
    $c=0;
    $places = '';
    $ongoing = 0;
    $wager_seasons = "";
    while ($row = $db->nextRow()) {
      $season_id = $row['SEASON_ID'];
      $wager_season['ENDED'] = $row['ENDED'];
      if ($row['ENDED'] == 0)
	$ongoing++;
      $wager_season['SEASON_ID'] = $row['SEASON_ID'];
      $wager_season['WAGER_INFO']['SEASON_ID'] = $row['SEASON_ID'];
      $wager_season['WAGER_INFO']['TSEASON_TITLE'] = $row['TSEASON_TITLE'];
      $wager_season['WAGER_INFO']['ENDED'] = $row['ENDED'];
      if (isset($row['PLACE']))
        $places[$season_id] = $row['PLACE'];
      else $places[$season_id] = 2;
      $wager_seasons[] = $wager_season;
      $c++;
    }

    if ($c == 0)
      return '';
//$db->showquery=true;
    $mlog = new WagerLog();
    $mulog = new WagerUserLog();
    foreach ($wager_seasons as &$wager_season) {
      $season_id = $wager_season['WAGER_INFO']['SEASON_ID'];
      $season_over = $wager_season['WAGER_INFO']['ENDED'];
      if ($season_over)
        $wager_season['SEASON_OVER'] = 1;   

      if (!empty($places[$season_id]))
        $place = $places[$season_id]; 
      else $place = 2;
      if (!$index) {
        if (!empty($season_id)) {
          $sql="SELECT U.USER_NAME, U.USER_ID, MU.MONEY, MS.WEALTH AS KOEFF, MSS.SEASON_ID, MS.PLACE 
            FROM users U, wager_users MU, wager_standings MS, wager_seasons MSS
           WHERE MS.USER_ID = U.USER_ID 
             AND MU.USER_ID = MS.USER_ID
             AND MSS.SEASON_ID = MS.SEASON_ID
             AND MSS.SEASON_ID =  ".$season_id."
  	   AND MU.SEASON_ID =  ".$season_id."
  	   AND MS.SEASON_ID =  ".$season_id."
           AND MS.PLACE IN (1,".($place-1).",".$place.",".($place+1).")
           ORDER BY MS.WEALTH DESC, MS.PLACE ASC, U.USER_NAME";

          $db->query($sql);
          $prev_place = '';
          $users = "";
          while ($row = $db->nextRow()) {
            $user = $row;
            if (strlen($user['USER_NAME']) > 13)
              $user['USER_NAME'] = substr($user['USER_NAME'], 0,13)."...";

            if ($row['PLACE']-$prev_place > 1) {
              $user['GAP'] = 1;
            }
            $wager_season['WAGER_STAND']['SEASON_ID'] = $row['SEASON_ID'];
            $prev_place = $row['PLACE'];
  	    $wager_season['WAGER_STAND']['USERS'][] = $user;
          } 
          $db->free();
        }
       }
        $log_entry = $mlog->getWagerLogLastItem($season_id);
        if ($log_entry != '') {
           $wager_season['WAGER_LOG']['LOG'] = $log_entry;
	   $wager_season['WAGER_LOG']['SEASON_ID'] = $season_id;
        }
	if (!$season_over) {
          $log_entry = $mulog->getWagerUserLogLastItem($auth->getUserId(), $season_id);
          if ($log_entry != '')
             $wager_season['WAGER_USER_LOG']['LOG'] = $log_entry;
        }

        $sql = "SELECT count( LEAGUE_id ) LEAGUES
		FROM wager_leagues 
  		WHERE season_id =".$season_id;
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $wager_season['LEAGUES']['LEAGUES'] = $row['LEAGUES'];
        }

        $sql = "SELECT count( CHALLENGE_id ) CHALLENGES
		FROM wager_challenges 
  		WHERE season_id =".$season_id;
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $wager_season['CHALLENGES']['CHALLENGES'] = $row['CHALLENGES'];
        }

        $sql = "SELECT count( WC.CHALLENGE_id ) CHALLENGES
		FROM wager_challenges WC, games G
  		WHERE WC.user2_id is null 
		      AND G.GAME_ID=WC.GAME_ID
		      AND G.START_DATE > NOW()
			AND WC.season_id =".$season_id;
//echo $sql;
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $wager_season['CHALLENGES']['OPEN_CHALLENGES'] = $row['CHALLENGES'];
        }

        if ($auth->userOn()) {
          $sql = "SELECT count( MLM.league_id ) LEAGUES
		FROM wager_leagues_members MLM, wager_leagues ML
		WHERE MLM.STATUS =3 
			AND ML.season_id =".$season_id." 
			AND MLM.LEAGUE_ID=ML.LEAGUE_ID
			AND MLM.USER_ID=".$auth->getUserID();
          $db->query($sql);
          if ($row = $db->nextRow()) {
	    if (isset($row['LEAGUES']) && $row['LEAGUES'] > 0) {
     	      $wager_season['LEAGUES_INVITE']['LEAGUES'] = $row['LEAGUES'];
    	      $wager_season['LEAGUES_INVITE']['SEASON_ID'] = $season_id;
            }
          } 

          $sql = "SELECT count( RL.LEAGUE_ID ) LEAGUES
                	FROM wager_leagues RL, wager_leagues_members RML
        	WHERE RL.LEAGUE_ID=RML.LEAGUE_ID
	  		AND RML.USER_ID=".$auth->getUserId()."
			AND RML.STATUS IN (1, 2)
			AND RL.season_id =".$season_id;
          $db->query($sql);
          if ($row = $db->nextRow()) {
            $wager_season['LEAGUES']['MY_LEAGUES'] = $row['LEAGUES'];
          }

          $sql = "SELECT count( WL.CHALLENGE_ID ) CHALLENGES
                	FROM wager_challenges WL
        	WHERE (WL.USER_ID=".$auth->getUserId()."
                      OR WL.USER2_ID=".$auth->getUserId().")
			AND WL.season_id =".$season_id;
          $db->query($sql);
          if ($row = $db->nextRow()) {
            $wager_season['CHALLENGES']['MY_CHALLENGES'] = $row['CHALLENGES'];
          }

        }
     }

    // content
    $template_file = "";
    if ($widget) 
      $template_file = 'smarty_tpl/bar_wager_seasons_external.smarty';
    else if ($dashboard)
           $template_file = 'smarty_tpl/bar_wager_seasons_dashboard.smarty';
    else $template_file = 'smarty_tpl/bar_wager_seasons.smarty';
//print_r($wager_seasons);
    $smarty->assign("wager_seasons", $wager_seasons);
    $smarty->assign("ongoing", $ongoing);
    $start = getmicrotime();
    $output = $smarty->fetch($template_file);    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/'.$template_file.'.smarty'.($stop-$start);
    return $output;
  }

  function getWagerSummaryBox ($auth, $dashboard=false) {
    global $smarty;
    global $db;
    global $_SESSION;
    global $wager;
    global $wager_user;
    
    // content

    if ($auth->userOn() && isset($_SESSION['_user']['WAGER'])) {
      $wager_summary = $_SESSION['_user']['WAGER'][$wager_user->tseason_id];
      $wager_summary['CREDIT'] = $_SESSION['_user']['CREDIT'];
      $wager_summary['IGNORE_LEAGUES'] = $_SESSION['_user']['WAGER'][$wager_user->tseason_id]['IGNORE_LEAGUES'];
      $wager_summary['STAKE_SLIDER'] = $_SESSION['_user']['WAGER'][$wager_user->tseason_id]['STAKE_SLIDER'];
      $wager_summary['TEAM_STATUS']['TEAM_STATUS'] = $this->getWagerSummaryTeamStatusBox();
      $wager_summary['GET_MONEY'] = $this->getWagerSummaryConvertMoneyBox();

      $sql="SELECT MS.SEASON_ID, TSEASON_TITLE, MST.PLACE, MST.WEALTH
             FROM wager_seasons MS
		   LEFT JOIN wager_seasons_details MSD ON MS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
		   LEFT JOIN wager_standings MST ON MS.SEASON_ID = MST.SEASON_ID AND MST.USER_ID=".$auth->getUserId()."
             WHERE  MS.SEASON_ID = ".$wager_user->tseason_id."
                 AND PUBLISH = 'Y'  
        ORDER BY END_DATE DESC";
      $db->query($sql);
      $row = $db->nextRow();

      $wager_summary['SEASON_TITLE'] = $row['TSEASON_TITLE'];
      $wager_summary['PLACE'] = $row['PLACE'];
      if (!empty($row['WEALTH']))
        $wager_summary['WEALTH'] = $row['WEALTH'];

      $wager_summary['LEAGUE'] = $this->getWagerLeagueBox ($wager_user);
    }
    $wager_summary['CHALLENGE'] = $this->getWagerChallengeSummaryBox ($wager_user);
    $wager_summary['STAKES'] = $wager->getAllStakes();

    if ($dashboard) 
      $template_file = 'smarty_tpl/bar_wager_dashboard.smarty';
    else $template_file = 'smarty_tpl/bar_wager.smarty';
    if ($auth->userOn())    
      $smarty->assign("user_on", 1);
    $smarty->assign("wager_summary", $wager_summary);
    $start = getmicrotime();
    $output = $smarty->fetch($template_file);    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/'.$template_file.'.smarty'.($stop-$start);
    return $output;

  } 

  function getWagerSummaryTeamStatusBox () {
    global $smarty;
    global $_SESSION;
    global $_GET;
    global $wager_user;

    if ($_SESSION['_user']['WAGER'][$wager_user->tseason_id]['IGNORE_LEAGUES'] == -1)
      $ignore_leagues['IGNORE_LEAGUES'] = $wager_user->tseason_id;
    else $ignore_leagues['UNIGNORE_LEAGUES'] = $wager_user->tseason_id;

    if ($_SESSION['_user']['WAGER'][$wager_user->tseason_id]['STAKE_SLIDER'] == 1)
      $stake_slider['STAKE_SLIDER'] = $wager_user->tseason_id;
    else $stake_slider['STAKE_TEXT'] = $wager_user->tseason_id;

    $smarty->assign("ignore_leagues", $ignore_leagues);
    $smarty->assign("stake_slider", $stake_slider);
    $smarty->assign("season_id", $wager_user->tseason_id);

    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/bar_wager_account_status.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_wager_account_status.smarty'.($stop-$start);
    return $content;
  }

  function getWagerSummaryConvertMoneyBox () {
    global $smarty;
    global $_SESSION;
    global $_GET;
    global $wager;
    global $wager_user;

    if ($wager->season_over)
      return '';
    if ($_SESSION['_user']['WAGER'][$wager_user->tseason_id]['WEALTH'] < 100) {
      $smarty->assign("few_money", 1);         
        if ($_SESSION['_user']['CREDIT'] < 1) {
        $smarty->assign("not_enough_credits", 1);
      }
      else {
        $get_money['SEASON_ID'] = $wager_user->tseason_id;
        $smarty->assign("get_money", $get_money);
      }
    }

    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/bar_wager_convert_money.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_wager_convert_money.smarty'.($stop-$start);
    return $content;
  }

  function getWagerLeagueBox ($wager_user) {
    global $smarty;
    global $_SESSION;
    global $_GET;
    global $wager_user;

    $leagues = $wager_user->getLeagues();
    $leagues_invites = $wager_user->getLeaguesInvites();
    $smarty->assign("leagues", $leagues);
    $smarty->assign("league_invites", $leagues_invites);

    $smarty->assign("leagues", $leagues);
    $output = $smarty->fetch("smarty_tpl/bar_wager_leagues.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_wager_leagues.smarty".($stop-$start);
    return $output;

  }

  function getWagerStandingsBox($season_id, $place=2) {
     global $db;
     global $auth;
     global $smarty;

      $place = 2;
      if ($auth->userOn()) {
        $sql="SELECT MSS.SEASON_ID, MSD.SEASON_TITLE, MS.PLACE, MSS.PIC_LOCATION
             FROM wager_seasons MSS
		left JOIN wager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
                  left join wager_standings MS on MSS.SEASON_ID=MS.SEASON_ID AND MS.USER_ID=".$auth->getUserId()."
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

      $sql="SELECT U.USER_NAME, U.USER_ID, MU.BALANCE, MSS.SEASON_ID, MS.PLACE 
      		 FROM users U, wager_users MU, wager_standings MS, wager_seasons MSS
	       WHERE MS.USER_ID = U.USER_ID 
        	 AND MU.USER_ID = MS.USER_ID
	         AND MSS.SEASON_ID = MS.SEASON_ID
        	 AND MSS.SEASON_ID =  ".$season_id."
	         AND MU.SEASON_ID =  ".$season_id."
	         AND MS.SEASON_ID =  ".$season_id."
	         AND (MS.PLACE = 1
                  OR MS.PLACE BETWEEN ".($place-5)." AND ".($place+5).")
	       ORDER BY MS.PLACE ASC, MU.BALANCE DESC, U.USER_NAME";
//echo $sql;
      $db->query($sql);
      $cc = 0;
      $prev_place = '';
      $wager_standings['USERS'] = array();
      while ($row = $db->nextRow()) {
        $user = $row;
        if (strlen($user['USER_NAME']) > 13)
          $user['USER_NAME'] = substr($user['USER_NAME'], 0,13)."...";

        if (isset($row['PLACE']) && $row['PLACE']-$prev_place > 1) {
          $user['GAP'] = 1;
        }
        if (isset($row['PLACE']))
          $prev_place = $row['PLACE'];
        $wager_standings['USERS'][] = $user;
      } 
      if (count($wager_standings['USERS']) == 0)
        return '';

    $smarty->assign("season_id", $season_id);
    $smarty->assign("wager_standings", $wager_standings);
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch("smarty_tpl/bar_wager_standings.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_wager_standings.smarty".($stop-$start);
    return $output;

  }

  function getWagerSeasonDashboardBox ($season_id) {
    global $_SESSION;
    global $db;
    global $auth;
    global $smarty;
    global $langs;

    $wager_seasons = "";
    $where_season = " AND MSS.SEASON_ID=".$season_id ;


    $sql="SELECT MSS.SEASON_ID, MSD.TSEASON_TITLE as SEASON_TITLE
      	  FROM wager_seasons MSS
		left JOIN wager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
        WHERE MSS.START_DATE < NOW( ) 
		  AND MSS.PUBLISH='Y'
		  ".$where_season."
        ORDER BY MSS.START_DATE ASC, MSS.END_DATE DESC";
//echo $sql;
    $db->query($sql);
    $c=0;
    if ($row = $db->nextRow()) {
      $season_id = $row['SEASON_ID'];
      $wager_season['SEASON_ID'] = $row['SEASON_ID'];
      $wager_season['WAGER_INFO']['SEASON_ID'] = $row['SEASON_ID'];
      $wager_season['WAGER_INFO']['SEASON_TITLE'] = $row['SEASON_TITLE'];
      $season_ar[$c] = $season_id;
      $c++;
    }
    if ($c == 0)
      return "";
   
    $mlog = new WagerLog();

 //   $this->getBracketOpening($season_id, &$bracket_season);
    $wager_logbox = new LogBox($langs, $_SESSION["_lang"]);
    $wager_season['WAGER_LOG'] = $wager_logbox->getWagerLogBox($season_id, 1, 3);

    if ($auth->userOn()) {
      $wager_season['WAGER_USER_LOG'] = $wager_logbox->getWagerUserLogBox($auth->getUserId(), $season_id, 1, 3, $langs['LANG_PERSONAL_LOG_U']);
    }

    $sql = "SELECT count( LEAGUE_id ) LEAGUES
		FROM wager_leagues 
		WHERE season_id =".$season_id;
    $db->query($sql);
    if ($row = $db->nextRow()) {
        $wager_season['LEAGUES']['LEAGUES'] = $row['LEAGUES'];
    } 

    if ($auth->userOn()) {
       $sql = "SELECT count( RL.LEAGUE_ID ) LEAGUES
		FROM wager_leagues RL, wager_leagues_members RML
		WHERE RL.LEAGUE_ID=RML.LEAGUE_ID
			AND RML.USER_ID=".$auth->getUserId()."
			AND RML.STATUS IN (1, 2)
			AND RL.season_id =".$season_id;
       $db->query($sql);
       if ($row = $db->nextRow()) {
         $wager_season['LEAGUES']['MY_LEAGUES'] = $row['LEAGUES'];
       }

       $sql = "SELECT count( MLM.league_id ) LEAGUES
		FROM wager_leagues_members MLM, wager_leagues ML
		WHERE MLM.STATUS =3 
			AND ML.season_id =".$season_id." 
			AND MLM.LEAGUE_ID=ML.LEAGUE_ID
			AND MLM.USER_ID=".$auth->getUserID();
       $db->query($sql);
       if ($row = $db->nextRow()) {
	   if (isset($row['LEAGUES']) && $row['LEAGUES'] > 0) {
    	     $wager_season['LEAGUES_INVITE']['LEAGUES'] = $row['LEAGUES'];
    	     $wager_season['LEAGUES_INVITE']['SEASON_ID'] = $season_id;
           }
       } 
    }

//$db->showquery=false;
    $smarty->assign("wager_season", $wager_season);
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch("smarty_tpl/bar_wager_season_dashboard.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_wager_season_dashboard.smarty".($stop-$start);
    return $output;
  }

  function getWagerGamesBox ($season_id, $page=1, $perpage=10) {
    global $_SESSION;
    global $db;
    global $auth;
    global $smarty;
    global $langs;
    global $wager;

    $games = $wager->getGames(2, $page, $perpage);
    $smarty->assign("games", $games);

    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/bar_wager_games.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_wager_games.smarty'.($stop-$start);
    return $content; 
  }

  function getWagerChallengesBox ($type, $page=1, $perpage=10) {
    global $_SESSION;
    global $db;
    global $auth;
    global $smarty;
    global $langs;
    global $wager;

    $games = $wager->getGamesChallenges($page, $perpage, $type);
    if ($type == 2)
      $title = $langs['LANG_ACCEPTED_CHALLENGES_U'];
    else       
      $title = $langs['LANG_WAGER_CHALLENGES_THROWN_U'];

    $smarty->assign("title", $title);
    $smarty->assign("games", $games);

    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/bar_wager_challenges.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_wager_challenges.smarty'.($stop-$start);
    return $content; 
  }
            
  function getWagerChallengeSummaryBox () {
    global $smarty;
    global $_SESSION;
    global $_GET;
    global $wager;

    $challenges = $wager->getChallengeSummary();
    $smarty->assign("challenges", $challenges);

    $output = $smarty->fetch("smarty_tpl/bar_wager_challenges_summary.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_wager_challenges_summary.smarty".($stop-$start);
    return $output;

  }

  function getIndexEntriesBox() {
    global $smarty;
    
 
    $wager = new Wager(); 
    $game = $wager->getGames(2, 1, 1);
    $challenge = $wager->getGamesChallenges(1, 1, 3);

    $smarty->assign("last_game", $game);
    $smarty->assign("last_challenge", $challenge);
//    $smarty->assign("last_challenge", $challenge);
    $output = $smarty->fetch("smarty_tpl/bar_wager_index_entries.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_wager_index_entries.smarty".($stop-$start);
    return $output;

  }
}

?>