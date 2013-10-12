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

class BracketLog {
 
  function BracketLog() {

  }

  function logEvent ($event_type, $season_id){
    global $db;

    unset($sdata);
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';
    $sdata['SEASON_ID'] = $season_id;
    $db->insert('bracket_log', $sdata);
  }


 function getBracketLog($season_id, $page, $perpage) {
    global $bracket_log_events_descr_success;
    global $db;
    global $auth;
    global $_SESSION;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT COUNT(UL.ENTRY_ID) ROWS
                   FROM bracket_log UL
                   WHERE SEASON_ID=".$season_id; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, 
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM bracket_log UL
          WHERE SEASON_ID=".$season_id."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;
    $db->query($sql);
    $c = 0;
    $log = "";
    while ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $bracket_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['MSEASON_ID'] = $season_id;
       $log[] = $log_item;
       $c++;
    }

    $log['_ROWS'] = $count;

   //print_r($data);
   return $log;
  }

 function getBracketLogLastItem($season_id) {
    global $bracket_log_events_descr_success;
    global $db;
    global $auth;
    global $_SESSION;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT MAX(UL.ENTRY_ID) LAST_ID
                   FROM bracket_log UL
                   WHERE SEASON_ID=".$season_id; 
    $db->query($sql_count);
    $last_id = 0;
    if ($row = $db->nextRow()) {
      $last_id = $row['LAST_ID'];
    }

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM bracket_log UL
          WHERE ENTRY_ID=".$last_id." AND SEASON_ID=".$season_id;
    $db->query($sql);
    $c = 0;
    $log = "";
    if ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $bracket_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['MSEASON_ID'] = $season_id;
       $log[] = $log_item;
       $c++;
    }

   //print_r($data);
   return $log;
  }

}

?>