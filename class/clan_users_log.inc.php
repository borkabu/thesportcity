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

class ClanUserLog {
 
  function ClanUserLog() {

  }

  function logEvent ($user_id, $event_type, $clan_id){
    global $db;

    unset($sdata);
    $sdata['USER_ID'] = $user_id;
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';

    $sdata['CLAN_ID'] = $clan_id;
    $db->insert('clan_users_log', $sdata);
  }


 function getClanUserLog($user_id, $clan_id, $page, $perpage=1) {
    global $clan_users_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT COUNT(UL.ENTRY_ID) ROWS
                   FROM clan_users_log UL
                   WHERE USER_ID=".$user_id." AND CLAN_ID=".$clan_id; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, U1.USER_NAME, GR.TITLE,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM clan_users_log UL
                left join users U1 on U1.USER_ID=UL.USER_ID and UL.USER_ID > 0
          WHERE UL.USER_ID=".$user_id." AND UL.CLAN_ID=".$clan_id."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;
//echo $sql;
    $db->query($sql);
    $log = "";
    $c = 0;
    while ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $clan_users_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%c", $row['CLAN_NAME'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['CLAN_ID'] = $clan_id;
       $log[] = $log_item; 
       $c++;
    }

    $log['_ROWS'] = $count;
      // no records?
   //print_r($data);
   return $log;
  }


 function getClanUserLogLastItem($user_id, $clan_id) {
    global $clan_users_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT MAX(UL.ENTRY_ID) LAST_ID
                   FROM clan_users_log UL
                   WHERE USER_ID=".$user_id." AND CLAN_ID=".$clan_id; 
    $db->query($sql_count);
    $last_id = 0;
    while ($row = $db->nextRow()) {
      $last_id = $row['LAST_ID'];
     }


    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, U1.USER_NAME,          
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM clan_users_log UL
                left join users U1 on U1.USER_ID=UL.USER_ID and UL.USER_ID > 0
          WHERE UL.ENTRY_ID=".$last_id." AND UL.USER_ID=".$user_id." AND UL.CLAN_ID=".$clan_id;
    $db->query($sql);
    $log = "";
    $c = 0;
    if ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $clan_users_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%c", $row['CLAN_NAME'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['CLAN_ID'] = $clan_id;
       $log[] = $log_item;
       $c++;
    }

   //print_r($data);
   return $log;
  }
}

?>