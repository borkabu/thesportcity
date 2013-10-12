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

class LogBox extends Box{

// generate paging info for the template
  function getLogBox ($user_id, $log_type, $page=1,$perpage=PAGE_SIZE, $log_name='') {
    global $smarty;
    global $db;
    
    // content
    $log = $this->getLog($user_id, $log_type, $page, $perpage, 3, true);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);
    if (!empty($log_name))
      $smarty->assign("log_name", $log_name);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;

  } 

  function getLog($user_id, $log_type, $page, $perpage) {
    global $user_log_events_descr_success;
    global $auth;
    global $db;
 
    $data='';
    if (isset($log_type) && $log_type > 0)
      $where = "UL.USER_ID=".$user_id." AND UL.LOG_TYPE=".$log_type;
    else $where = "UL.USER_ID=".$user_id;
//$db->showquery =  true;
    $sql_count = "SELECT COUNT(UL.ENTRY_ID) ROWS
                   FROM users_log UL
                   WHERE ".$where; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM users_log UL
          WHERE ".$where."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;
    $db->query($sql);
    $c = 0;
    $log = "";
    while ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $user_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['DELTA'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%t", $row['VALUE'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log[] = $log_item;
       $c++;
    }

    $log['_ROWS'] = $count;
      // no records?
   //print_r($data);
   return $log;
  }

  function getManagerLogBox ($season_id, $page=1,$perpage=PAGE_SIZE, $log_name='') {
    global $smarty;
    global $db;
    
    // content
//    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
//    $tpl->setTemplateFile('tpl/bar_log.tpl.html');
    $manager_log = new ManagerLog(); 
    $log = $manager_log->getManagerLog($season_id, $page, $perpage, 3, true);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);

    if (!empty($log_name))
      $smarty->assign("log_name", $log_name);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getSoloManagerLogBox ($season_id, $page=1,$perpage=PAGE_SIZE, $log_name='') {
    global $smarty;
    global $db;
    
    // content
//    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
//    $tpl->setTemplateFile('tpl/bar_log.tpl.html');
    $manager_log = new SoloManagerLog(); 
    $log = $manager_log->getManagerLog($season_id, $page, $perpage, 3, true);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);

    if (!empty($log_name))
      $smarty->assign("log_name", $log_name);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getManagerTournamentLogBox ($season_id, $page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    
    // content
    $manager_tournament_log = new ManagerTournamentLog(); 
    $log = $manager_tournament_log->getManagerTournamentLog($season_id, $page, $perpage, 3, true);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getCreditLogBox ($user_id, $page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    
    // content
    $credit_log = new CreditsLog(); 
    $log = $credit_log->getCreditLog($user_id, $page, $perpage);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getManagerUserLogBox ($user_id, $season_id, $page=1,$perpage=PAGE_SIZE, $log_name='') {
    global $smarty;
    global $db;
    
    // content
    $manager_log = new ManagerUserLog(); 
    $log = $manager_log->getManagerUserLog($user_id, $season_id, $page, $perpage);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);
    if (!empty($log_name))
      $smarty->assign("log_name", $log_name);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getSoloManagerUserLogBox ($user_id, $season_id, $page=1,$perpage=PAGE_SIZE, $log_name='') {
    global $smarty;
    global $db;
    
    // content
    $manager_log = new SoloManagerUserLog(); 
    $log = $manager_log->getManagerUserLog($user_id, $season_id, $page, $perpage);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);
    if (!empty($log_name))
      $smarty->assign("log_name", $log_name);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getManagerUserLogBoxCommon($user_id, $season_id, $page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    
    // content
    $manager_log = new ManagerUserLog(); 
    $log = $manager_log->getManagerUserLog($user_id, $season_id, $page, $perpage);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_manager_user_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getWagerLogBox ($season_id, $page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    
    // content
    $wager_log = new WagerLog(); 
    $log = $wager_log->getWagerLog($season_id, $page, $perpage, 3, true);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getWagerUserLogBox ($user_id, $season_id, $page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    
    // content
    $wager_log = new WagerUserLog(); 
    $log = $wager_log->getWagerUserLog($user_id, $season_id, $page, $perpage);
    $this->rows = $log['_ROWS'];
    unset($log['_ROWS']);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getBracketLogBox ($season_id, $page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    
    // content
//    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
//    $tpl->setTemplateFile('tpl/bar_log.tpl.html');
    $bracket_log = new BracketLog(); 
    $log = $bracket_log->getBracketLog($season_id, $page, $perpage, 3, true);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getBracketUserLogBox ($user_id, $season_id, $page=1,$perpage=PAGE_SIZE, $log_name='') {
    global $smarty;
    global $db;
    
    // content
    $bracket_log = new BracketUserLog(); 
    $log = $bracket_log->getBracketUserLog($user_id, $season_id, $page, $perpage);
    $this->rows = $log['_ROWS'];
    unset($log['_ROWS']);
    $smarty->assign("log", $log);
    if (!empty($log_name))
      $smarty->assign("log_name", $log_name);

    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getRvsManagerLogBox ($league_id, $page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    
    // content
//    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
//    $tpl->setTemplateFile('tpl/bar_log.tpl.html');
    $rvs_manager_log = new RvsManagerLog(); 
    $log = $rvs_manager_log->getRvsManagerLog($league_id, $page, $perpage, 3, true);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getRvsManagerUserLogBox ($user_id, $league_id, $page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    
    // content
    $manager_log = new RvsManagerUserLog(); 
    $log = $manager_log->getRvsManagerUserLog($user_id, $league_id, $page, $perpage);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

  function getClanLogBox ($clan_id, $page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    
    // content
    $clan_log = new ClanLog(); 
    $log = $clan_log->getClanLog($clan_id, $page, $perpage);
    $this->rows = $log['_ROWS'];	
    unset($log['_ROWS']);
    $smarty->assign("log", $log);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_log.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_log.smarty'.($stop-$start);
    return $output;
  } 

}

?>