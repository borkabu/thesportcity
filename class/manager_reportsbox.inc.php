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

class ManagerReportsBox extends Box{
  
  function getManagerReportsBox ($perpage, $season_id=-1) {
    global $smarty;
    global $_SESSION;
    global $db;
    global $auth;
    // content
  
    $smarty->setCaching(Smarty::CACHING_LIFETIME_SAVED);
    $smarty->setCacheLifetime(1000);
    if (!$smarty->isCached('smarty_tpl/bar_manager_reports.smarty', 'bar_manager_repots'.$season_id."_lang_id".$_SESSION['lang_id'])) {
      $manager_reports = new ManagerReports();
      $reports['REPORT'] = $manager_reports->getReportList($season_id, 1, $perpage);         
      if (count($reports['REPORT']) > 0)
        $smarty->assign("reports", $reports);
    }
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_manager_reports.smarty', 'bar_manager_repots'.$season_id."_lang_id".$_SESSION['lang_id']);
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_reports.smarty'.($stop-$start);
    $smarty->caching= false;
    return $output;

  }


}  
?>