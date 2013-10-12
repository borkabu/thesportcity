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

class GameBox extends Box{

  function getGameBox ($game_name, $descr) {
    global $tpl;
    global $_SESSION;   
    global $smarty;
    global $lang;
    
    $smarty->assign("game_name", $game_name);
    $smarty->assign("descr", $descr);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_game.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_game.smarty'.($stop-$start);
    return $output;
  } 

  function getTimeReports($game_id) {
    global $smarty;
    global $_SESSION;

    $game = new Game($game_id);
    $reports = $game->getTimeReports();
    $smarty->assign("reports", $reports);

    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_game_time_reports.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_game_time_reports.smarty'.($stop-$start);

    return $output;
  }

}

?>