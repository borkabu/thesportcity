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

class NetworkBox extends Box{

  function getNetworkBox () {
    global $tpl;
    global $_SESSION;   
    global $smarty;
    global $lang;
    
    $smarty->assign("lang", $lang);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_networks.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_networks.smarty'.($stop-$start);
    return $output;
  } 
}

?>