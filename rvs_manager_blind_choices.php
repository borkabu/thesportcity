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
include('class/rvs_manager_user.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
$content = '';

  if (isset($_GET['player_id']) && isset($_GET['league_id'])) {
      $sql = "SELECT MM.CURRENT_VALUE_MONEY, MM.SEASON_ID, RML.TITLE, RML.SEASON_ID
            FROM manager_market MM, rvs_manager_leagues RML
            WHERE RML.LEAGUE_ID=".$_GET['league_id']." 
                   AND MM.SEASON_ID=RML.SEASON_ID
                  AND MM.USER_ID =".$_GET['player_id'];
     $db->query($sql);
     $row = $db->nextRow();
     $season_id = $row['SEASON_ID'];
     $manager = new Manager($season_id);
     $title = $row['TITLE'];
     $selling_price = $row['CURRENT_VALUE_MONEY'];
     $manager = new Manager($row['SEASON_ID']); 
     $current_tour = $manager->getCurrentTour();

     $where_played = "";
     if ($current_tour > 1)
       $where_played = "AND MM.PLAYED > 0" ;
     $players = array();
     // get random player from similar range.
     $sql= "SELECT MM.LAST_NAME, MM.FIRST_NAME, MM.USER_ID, MM.POSITION_ID1, MM.PLAYER_STATE,
			MM.CURRENT_VALUE_MONEY, MM.PLAYED,
		IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME2
		FROM manager_market MM
			LEFT JOIN teams T ON MM.TEAM_ID=T.TEAM_ID 
			LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
		WHERE MM.season_id=".$season_id."
		AND MM.PLAYER_STATE = 0
		AND MM.PUBLISH='Y'
		".$where_played."
		AND MM.PUBLISH='Y'
		AND MM.CURRENT_VALUE_MONEY >= ".$selling_price."
		AND MM.USER_ID NOT IN 
		(SELECT PLAYER_ID FROM rvs_manager_teams
			WHERE LEAGUE_ID=".$_GET['league_id']."
				AND SELLING_DATE IS NULL)
             ORDER BY MM.CURRENT_VALUE_MONEY ASC
	    LIMIT 5";
//echo $sql;
     $db->query($sql); 
     $c = 4;
     while ($row = $db->nextRow()) {
       $player = $row;
       if (!empty($row['POSITION_ID2']) && empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
         $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
       else $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];

       $player['PLAYER_STATE_DIV'] = $managerbox->getPlayerStateDiv($player['USER_ID'], $season_id, $row['PLAYER_STATE'], false, false);
       $players[$c] = $player;
       $c--;
     }
//print_r($players);
     ksort($players);

     $sql= "SELECT MM.LAST_NAME, MM.FIRST_NAME, MM.USER_ID, MM.POSITION_ID1, MM.PLAYER_STATE,
		MM.CURRENT_VALUE_MONEY, MM.PLAYED,
		IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME2
		FROM manager_market MM
		LEFT JOIN teams T ON MM.TEAM_ID=T.TEAM_ID 
		LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
		WHERE MM.season_id=".$season_id."
		AND MM.PLAYER_STATE = 0
		AND MM.PUBLISH='Y'
		".$where_played."
		AND MM.CURRENT_VALUE_MONEY < ".$selling_price."
		AND MM.USER_ID NOT IN 
		(SELECT PLAYER_ID FROM rvs_manager_teams
			WHERE LEAGUE_ID=".$_GET['league_id']."
				AND SELLING_DATE IS NULL)
                ORDER BY MM.CURRENT_VALUE_MONEY DESC
	LIMIT 5";
     $db->query($sql); 
     $c = 5;
     while ($row = $db->nextRow()) {
       $player = $row;
       if (!empty($row['POSITION_ID2']) && !empty($position_types[$manager->sport_id][$row['POSITION_ID2']]))
         $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']]."/".$position_types[$manager->sport_id][$row['POSITION_ID2']];
       else $player['TYPE_NAME'] = $position_types[$manager->sport_id][$row['POSITION_ID1']];

       $player['PLAYER_STATE_DIV'] = $managerbox->getPlayerStateDiv($player['USER_ID'], $season_id, $row['PLAYER_STATE'], false, false);
       $players[$c] = $player;
       $c++;
     }

     $smarty->assign("league_name", $title);
     $smarty->assign("selling_price", $selling_price);
     $smarty->assign("players", $players);
  }                                      


  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/rvs_manager_blind_choices.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/rvs_manager_blind_choices.smarty'.($stop-$start);

  include('inc/top_very_small.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');
?>