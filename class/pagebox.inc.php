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

class PageBox extends Box{

  function PageBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getPage($page_id='', $page_title='') {
    global $db;
    global $smarty;
    global $_SESSION;
    global $html_page;

    if (isset($page_id) && is_numeric($page_id)) {

          $sql = "SELECT P.PAGE_ID, PD.TITLE, PD.DESCRIPTION 
                  FROM 
                    pages_details PD, pages P
                  WHERE
                    P.PAGE_ID='".$page_id."' 
                    AND P.PUBLISH='Y'
		    AND P.PAGE_ID=PD.PAGE_ID AND PD.LANG_ID=".$_SESSION['lang_id'];

          $db->query($sql);
          if ($row = $db->nextRow()) {
            $smarty->assign('title', $row['TITLE']);
            $smarty->assign('description', $row['DESCRIPTION']);

	    $html_page->page_title = $row['TITLE'];     
          }
    }

    return $smarty->fetch('smarty_tpl/page.smarty');

  }
}   
?>