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

class Page {
  var $page_title;
  var $page_descr;
  
  function Page() {

  }

  function setPageTitle ($page_title){
    $this->page_title = $page_title;
  }

  function setPageDescr ($page_descr){
    $this->page_descr = $page_descr;
  }
}

?>