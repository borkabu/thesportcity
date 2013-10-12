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

class Box {
  var $langs;
  var $lang;
  var $data;
  var $rows;
 
  function Box($langs, $lang) {
    $this->langs = $langs  ;
    $this->lang = $lang  ;
  }

  function getRows() {
    return $this->rows;
  }

  function getHeaderBox ($title) {
    global $smarty;
    
    // content
    $smarty->assign("game_title", $title);
    $start = getmicrotime();
    $smarty->caching= false;
    $output = $smarty->fetch("smarty_tpl/bar_header.smarty");    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo "smarty_tpl/bar_header.smarty".($stop-$start);
    return $output;
  
  }

}

?>