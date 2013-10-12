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

class ManagerTournamentBox extends Box{
  var $season_id;
  
  function getManagerTournamentSummaryBox () {
    global $smarty;
    global $manager_user;
    global $_SESSION;
    global $db;
    global $auth;
    global $_GET;
    // content

    if (isset($_GET['mt_id'])) {
      $tournaments = array();
      $utc = $auth->getUserTimezoneName();
      $sql="SELECT MSS.MT_ID, MSS.TITLE, 
           MSS.JOINED, MSS.PARTICIPANTS > MSS.JOINED AS OPEN, MSU.USER_ID, U.USER_NAME
         FROM manager_tournament MSS
		left join manager_tournament_users MSU on MSU.MT_ID=MSS.MT_ID AND MSU.WINNER=1
		left join users U on U.USER_ID=MSU.USER_ID
         WHERE MSS.PUBLISH='Y'
                AND MSS.MT_ID=".$_GET['mt_id']." 
      ORDER BY MSS.START_DATE ASC";
//echo $sql;  
      $db->query($sql);
      $c=0;
      $mts = '';
      while ($row = $db->nextRow()) {
        $mt_id = $row['MT_ID'];
        $mts[$c] = $mt_id;
        $tournament = $row;
        if ($row['OPEN'] == 1) {
          $tournament['REGISTRATION_OPEN'] = 1;
        }
        else if (!empty($row['USER_ID']))
          $tournament['TOURNAMENT_OVER']['USER_NAME'] = $row['USER_NAME'];
        else
          $tournament['REGISTRATION_CLOSED'] = 1;
        $c++;
      }
  
      $mlog = new ManagerTournamentLog();
      $log_entry = $mlog->getManagerTournamentLogLastItem($tournament['MT_ID'], 1, 1);
      if ($log_entry != '')
        $tournament['MANAGER_TOURNAMENT_LOG']['LOG'] = $log_entry;
      $smarty->assign("tournament", $tournament);
    }
    $tournaments_summary = $this->getManagerTournamentBox ();
    $smarty->assign("tournaments_summary", $tournaments_summary);

    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_manager_tournament.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_tournament.smarty'.($stop-$start);
    $smarty->caching= false;
    return $output;

  }
  
  function getManagerTournamentFilterBox ($mt_id) {
    global $smarty;

    // content
    $mt = inputManagerTournamentSeasons('mt_id', $mt_id);

    $smarty->assign("mt", $mt);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_manager_tournament_filter.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_tournament_filter.smarty'.($stop-$start);
  
    return $output;
  }

  function getManagerTournamentBox () {
    global $smarty;
    global $_SESSION;
    global $manager_user;

    if (isset($manager_user)) {
      $trnms = $manager_user->getTournaments();
      $tournaments_invites = $manager_user->getTournamentsInvites();
      $smarty->assign("trnms", $trnms);
      $smarty->assign("tournament_invites", $tournaments_invites);

      $start = getmicrotime();
      $smarty->caching = false;
      $output = $smarty->fetch('smarty_tpl/bar_manager_tournaments.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/bar_manager_tournaments.smarty'.($stop-$start)."<br>";
      return $output;
    } else return "";
  }

  function getManagerActiveTournamentsBox () {
    global $smarty;
    global $_SESSION;
    global $manager;

    $trnms = $manager->getTournaments(false, true);
    $smarty->assign("trnms", $trnms);

    $start = getmicrotime();
    $smarty->caching = false;
    $output = $smarty->fetch('smarty_tpl/bar_manager_active_tournaments.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_active_tournaments.smarty'.($stop-$start)."<br>";
    return $output;
  }

  function getManagerTournamentsFixturesBox ($perpage = 5) {
    global $smarty;
    global $_SESSION;
    global $manager_user;

    if (isset($manager_user)) {
      $fixtures = $manager_user->getTournamentFixtures($perpage);
      $smarty->assign("pairs", $fixtures);

      $start = getmicrotime();
      $smarty->caching = false;
      $output = $smarty->fetch('smarty_tpl/bar_manager_tournaments_fixtures.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/bar_manager_tournaments_fixtures.smarty'.($stop-$start)."<br>";
      return $output;
    } else return "";
  }

}

?>