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

class CountdownBox extends Box{

  function CountdownBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getCountdownBox($mseason_id='') {
    global $db;
    global $smarty;
    global $langs;

    $where_season = '';
    if ($mseason_id != '')
      $where_season = ' AND MSS.SEASON_ID='.$mseason_id;

    $sql = "SELECT UNIX_TIMESTAMP(MT.START_DATE) - UNIX_TIMESTAMP(NOW()) TIMELEFT, MSD.SEASON_TITLE 
		FROM manager_tours MT, manager_statistics MST, manager_seasons MSS
			left JOIN manager_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
		 WHERE MT.start_date > NOW( ) AND DATE_ADD( NOW( ) , INTERVAL 7 DAY ) > MT.start_date
			AND MT.SEASON_ID=MSS.SEASON_ID
			AND MST.SEASON_ID=MSS.SEASON_ID
			AND MST.MARKET='Y' ".$where_season."
		
		 ORDER BY MT.start_date ASC
		 LIMIT 1";
    $db->query($sql);
    if ($row=$db->nextRow()) {
      $smarty->assign("timeleft", $row['TIMELEFT']);
      $info = str_replace("%s", $row['SEASON_TITLE'], $langs['LANG_COUNTDOWN_INFO_U']);
      $smarty->assign("info", $info);
      $start = getmicrotime();
      $output = $smarty->fetch('smarty_tpl/bar_countdown.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/bar_countdown.smarty'.($stop-$start);
      return $output;
    }
    else return "";
  }

  function getBracketCountdownBox($season_id='') {
    global $db;
    global $smarty;
    global $langs;

    $where_season = '';
    if ($season_id != '')
      $where_season = ' AND MSS.SEASON_ID='.$season_id;

    $sql = "SELECT UNIX_TIMESTAMP(MT.START_DATE) - UNIX_TIMESTAMP(NOW()) TIMELEFT, MSD.TSEASON_TITLE as SEASON_TITLE
		FROM bracket_tours MT, bracket_seasons MSS
			left JOIN bracket_seasons_details MSD ON MSS.SEASON_ID = MSD.SEASON_ID AND MSD.LANG_ID=".$_SESSION['lang_id']."
		 WHERE MT.start_date > NOW( ) AND DATE_ADD( NOW( ) , INTERVAL 7 DAY ) > MT.start_date
			AND MT.SEASON_ID=MSS.SEASON_ID ".$where_season."	
		 ORDER BY MT.start_date ASC
		 LIMIT 1";
//echo $sql;
    $db->query($sql);
    if ($row=$db->nextRow()) {
      $smarty->assign("timeleft", $row['TIMELEFT']);
      $info = str_replace("%s", $row['SEASON_TITLE'], $langs['LANG_COUNTDOWN_INFO_U']);
      $smarty->assign("info", $info);
      $start = getmicrotime();
      $output = $smarty->fetch('smarty_tpl/bar_countdown.smarty');
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/bar_countdown.smarty'.($stop-$start);
      return $output;
    }
    else return "";
  }
}   
?>