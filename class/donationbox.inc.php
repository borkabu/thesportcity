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

class DonationBox extends Box{

  function getDonationBox () {
    global $_SESSION;
    global $smarty;
    
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_donation.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_donation.smarty'.($stop-$start);
    return $output;

  } 
}

?>