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

class AnnouncementBox extends NewsBox{   

  function AnnouncementBox($langs, $lang) {
    parent::NewsBox($langs, $lang);
  }

  function getAnnouncementBox ($cat='',$page=1,$perpage=PAGE_SIZE, $last_date = '') {
    global $tpl;
    global $db;
    global $_SESSION;
    global $smarty;
    global $conf_home_dir;
    // content
    $news = $this->getNewsShortData($cat, $page, $perpage, 3, $last_date);
    $more['GENRE'] = 3;
    $smarty->assign("more", $more);
//    $this->rows = $this->data['NEWS'][0]['_ROWS'];	
//print_r($this->data['NEWS']);
    return $smarty->fetch($conf_home_dir."smarty_tpl/bar_announcement.smarty");    
  } 

  function getAnnouncements ($cat='',$page=1,$perpage=PAGE_SIZE) {
    global $tpl;
    global $db;
    global $_SESSION;
    global $smarty;
    
    // content
    $news = $this->getNewsData($cat, $page, $perpage, 3, false);
//    $this->rows = $this->data['NEWS'][0]['_ROWS'];	
//print_r($this->data['NEWS']);
    return $smarty->fetch("smarty_tpl/bar_announcement.smarty");    
  } 


}   
?>