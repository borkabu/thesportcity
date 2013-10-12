<?php
/*
===============================================================================
help.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows help item
  = shows help topic index

TABLES USED: 
  - BASKET.HELP

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
include('class/update.inc.php');

// classes
include('class/db.class.php');
include('class/template.class.php');
include('class/language.class.php');
include('class/form.class.php');

// connections
include('class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/manager.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
$content = '';

  if ($auth->userOn() && isset($_GET['day']) && isset($_GET['season_id'])) {
    $manager = new Manager($_GET['season_id']);
    $manager->countReportsPerPlayer();
    // check if chosen player is not played yet
      $sql = "SELECT DISTINCT DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d') GAME_DAY, SMP.PLAYER_ID,
			MM.FIRST_NAME, MM.LAST_NAME, G1.START_DATE, G1.START_DATE > NOW() as LOCKED, 
			DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d') > NOW() as FUTURE
	      FROM  seasons S, games G, manager_tours MT
                    left join solo_manager_players SMP ON SMP.SEASON_ID=MT.season_id
							AND SMP.GAME_DAY=GAME_DAY
							AND SMP.USER_ID=".$auth->getUserId()."
		    left join manager_market MM ON MM.user_id=SMP.PLAYER_ID 
							AND MM.season_id=".$manager->mseason_id."
                    left join games G1 ON (G1.TEAM_ID1 = MM.TEAM_ID OR G1.TEAM_ID2 = MM.TEAM_ID) 
					 AND DATE_FORMAT(DATE_ADD(G1.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d') = GAME_DAY
	      WHERE MT.season_id=".$manager->mseason_id."
		      and S.season_id in (".$manager->seasonlist.") 
		      and G.season_id=S.SEASON_ID
		      and G.PUBLISH='Y'
	              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
		      and game_day='".$_GET['day']."'
              ORDER BY GAME_DAY";
//echo $sql;
    if ($row = $db->nextRow()) {
      print_r($row);
    }

    if (empty($_GET['page']))
      $page = 1;
    else $page = $_GET['page'];
    if (empty($perpage))
      $perpage = $page_size;
    else $perpage = $_GET['page_size'];
    
    $sql = "SELECT count(MM.USER_ID) CNT FROM manager_market MM, seasons S, games G, manager_tours MT
		WHERE MM.USER_ID not in (SELECT PLAYER_ID from solo_manager_players 
						where SEASON_ID=".$manager->mseason_id."
							AND USER_ID=".$auth->getUserId().")
		      AND MM.season_id=".$manager->mseason_id."
           	      and S.season_id in (".$manager->seasonlist.") 
		      and G.season_id=S.SEASON_ID
		      and G.PUBLISH='Y'
	              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
		      and DATE_FORMAT(DATE_ADD(G.START_DATE, INTERVAL -1 HOUR), '%Y-%m-%d')='".$_GET['day']."'";
    $db->query($sql);
    $row = $db->nextRow();
    $count = $row['CNT'];

    // show available players;
    $sql = "SELECT MM.*, MPRT.TIMES as REPORTS FROM manager_market MM
                        LEFT JOIN manager_player_reports_temp MPRT ON MPRT.PLAYER_ID=MM.USER_ID
			, seasons S, games G, manager_tours MT
		WHERE MM.USER_ID not in (SELECT PLAYER_ID from solo_manager_players 
						where SEASON_ID=".$manager->mseason_id."
							AND USER_ID=".$auth->getUserId().")
		      AND MM.season_id=".$manager->mseason_id."
           	      and S.season_id in (".$manager->seasonlist.") 
		      and G.season_id=S.SEASON_ID
		      and G.PUBLISH='Y'
		      AND (G.TEAM_ID1 = MM.TEAM_ID OR G.TEAM_ID2 = MM.TEAM_ID)
	              and MT.START_DATE < G.start_DATE and MT.END_DATE > G.START_DATE
			AND DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) > NOW()
			AND MT.SEASON_ID=MM.season_id
		      and DATE_FORMAT(G.START_DATE, '%Y-%m-%d')='".$_GET['day']."'
		ORDER BY MM.TOTAL_POINTS DESC";
//echo $sql;
    $db->query($sql);
    $players = array();
    while ($row = $db->nextRow()) {
      $player = $row;
      if (!empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
        $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
      else $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];

      $player['PLAYER_STATE_DIV'] = $manager->getPlayerStateSmarty($row['USER_ID'], $manager->mseason_id, $row['PLAYER_STATE']);
      if ($row['REPORTS'] == '')
        $player['REPORTS'] = 0;

      $players[] = $player;
    }
    $paging = $pagingbox->getPagingBox($count, isset($_GET['page']) ? $_GET['page'] : 0);

    $tour = $manager->getSoloSingleTourSchedule($_GET['day']);

    $smarty->assign("tour", $tour);
    $smarty->assign('players', $players);
    $smarty->assign('paging', $paging);
    $smarty->assign('game_day', $_GET['day']);
    $smarty->assign('subseasons', $manager->seasonlist);

  }                                      

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/solo_manager_select_player.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/solo_manager_select_player.smarty'.($stop-$start);

  include('inc/top_very_small.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');
?>