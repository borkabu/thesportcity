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

class PagingBox extends Box{

// generate paging info for the template 
  function getPagingBox ($rows, $pg = 0, $pp = 0, $page_var = 'page') {
    global $page;
    global $perpage;
    global $page_size;
    global $smarty;
    global $tpl;
    
    // settings
    if ($pg > 0)
      $page = $pg;
    if (empty($page))
      $page = 1;
    if ($pp > 0)
      $perpage = $pp;
    if (empty($perpage))
      $perpage = $page_size;
    
    // generate data
    $paging = $rows;
    $pages = array();
    $page_tmp = 0;
    for ($c = 0; $c < $rows; $c += $perpage) {
      $page_tmp++;
      $page_item = array();
      $page_item['PAGENUM'] = $page_tmp;
      $page_item['URL'] = url($page_var, $page_tmp);
      if ($page_tmp == $page) {
        $page_item['SELECTED']['PAGENUM'] = $page_tmp;
      }
      else {
        $page_item['NORMAL']['PAGENUM'] = $page_tmp;
        $page_item['NORMAL']['URL'] = url($page_var, $page_tmp);
      }
      $pages[] = $page_item;
    }
       
    $smarty->assign('paging', $paging);
    $smarty->assign('pages', $pages);
    return $smarty->fetch('smarty_tpl/pagingbox.smarty');    
  }
}

?>