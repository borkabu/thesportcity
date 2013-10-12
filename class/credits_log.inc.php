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

class CreditsLog {
 
  function CreditsLog() {

  }

  function logEvent ($user_id, $event_type, $value, $user_id2=''){
    global $db;

    unset($sdata);
    $sdata['USER_ID'] = $user_id;
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';
    $sdata['CREDITS'] = $value;
    if (!empty($user_id2))
      $sdata['USER_ID2'] = $user_id2;
    $db->insert('credits', $sdata);
  }


 function getCreditLog($user_id, $page, $perpage) {
    global $credits_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT COUNT(UL.ID) ROWS
                   FROM credits UL
                   WHERE USER_ID=".$user_id; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, U.USER_NAME,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM credits UL
                left join users U on U.USER_ID=UL.USER_ID2 and UL.USER_ID2 > 0
          WHERE UL.USER_ID=".$user_id."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;
    $db->query($sql);
    $c = 0;
    $log = "";
    while ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $credits_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%c", $row['CREDITS'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log[] = $log_item;
       $c++;
    }

    $log['_ROWS'] = $count;
      // no records?

   //print_r($data);
   return $log;
  }

 function getCreditLogLastItem($user_id) {
    global $credits_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT MAX(UL.ID) LAST_ID
                   FROM credits UL
                   WHERE USER_ID=".$user_id; 
    $db->query($sql_count);
    $last_id = 0;
    if ($row = $db->nextRow()) {
      $last_id = $row['LAST_ID'];
    }

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, U.USER_NAME,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM credits UL
                left join users U on U.USER_ID=UL.USER_ID2 and UL.USER_ID2 > 0
          WHERE ID=".$last_id." AND UL.USER_ID=".$user_id;
    $db->query($sql);
    $c = 0;
    $log = array();
    while ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $credits_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%c", $row['CREDITS'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log[] = $log_item;
       $c++;
    }

   //print_r($data);
   return $log;
  }

}

?>