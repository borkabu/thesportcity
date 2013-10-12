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

class ManagerTournamentLog {
 
  function ManagerTournamentLog() {

  }

  function logEvent ($user_id, $event_type, $tour, $round, $mt_id){
    global $db;

    unset($sdata);
    if (!empty($user_id))
      $sdata['USER_ID'] = $user_id;
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';
    if (!empty($tour))
      $sdata['TOUR'] = $tour;
    if (!empty($round))
      $sdata['ROUND'] = $round;
    $sdata['MT_ID'] = $mt_id;
    $db->insert('manager_tournament_log', $sdata);
  }

  function getManagerTournamentLog($mt_id, $page, $perpage) {
    global $manager_tournament_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT COUNT(UL.ENTRY_ID) ROWS
                   FROM manager_tournament_log UL
                   WHERE MT_ID=".$mt_id; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, U.USER_NAME,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM manager_tournament_log UL
                left join users U on U.USER_ID=UL.USER_ID and UL.USER_ID > 0
          WHERE MT_ID=".$mt_id."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;
    $db->query($sql);
    $c = 0;
    $log = "";
    while ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $manager_tournament_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%t", $row['TOUR'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%r", $row['ROUND'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['MT_ID'] = $mt_id;
       $log[] = $log_item;
       $c++;
    }

    $log['_ROWS'] = $count;

   //print_r($data);
   return $log;
  }


  function getManagerTournamentLogLastItem($mt_id) {
    global $manager_tournament_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT MAX(UL.ENTRY_ID) LAST_ID
                   FROM manager_tournament_log UL
                   WHERE MT_ID=".$mt_id; 
    $db->query($sql_count);
    $last_id = 0;
    while ($row = $db->nextRow()) {
      $last_id = $row['LAST_ID'];
     }

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, U.USER_NAME,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM manager_tournament_log UL
                left join users U on U.USER_ID=UL.USER_ID and UL.USER_ID > 0
          WHERE ENTRY_ID=".$last_id."  AND MT_ID=".$mt_id;
    $db->query($sql);
    $c = 0;
    $log = "";
    if ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $manager_tournament_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%t", $row['TOUR'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%r", $row['ROUND'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['MT_ID'] = $mt_id;
       $log[] = $log_item;
       $c++;
    }

   //print_r($data);
   return $log;
  }
}

?>