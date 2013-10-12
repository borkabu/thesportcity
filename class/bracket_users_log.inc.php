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

class BracketUserLog {
 
  function BracketUserLog() {

  }

  function logEvent ($user_id, $event_type, $season_id, $tour_id = ''){
    global $db;

    unset($sdata);
    $sdata['USER_ID'] = $user_id;
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';
    if (!empty($tour_id))
      $sdata['TOUR_ID'] = $tour_id;

    $sdata['SEASON_ID'] = $season_id;
    $db->insert('bracket_users_log', $sdata);
  }


 function getBracketUserLog($user_id, $season_id, $page, $perpage=1) {
    global $bracket_users_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT COUNT(UL.ENTRY_ID) ROWS
                   FROM bracket_users_log UL
                   WHERE USER_ID=".$user_id." AND SEASON_ID=".$season_id; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, MSD.TSEASON_TITLE, U1.USER_NAME, GR.TITLE,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM bracket_users_log UL
                left join bracket_seasons_details MSD on MSD.SEASON_ID=UL.SEASON_ID and MSD.LANG_ID=".$_SESSION['lang_id']."
                left join users U1 on U1.USER_ID=UL.USER_ID and UL.USER_ID > 0
                left join games_races GR on GR.GAME_ID=UL.TOUR_ID
          WHERE UL.USER_ID=".$user_id." AND UL.SEASON_ID=".$season_id."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;
//echo $sql;
    $db->query($sql);
    $log = "";
    $c = 0;
    while ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $bracket_users_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%t", $row['TITLE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%s", $row['TSEASON_TITLE'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['SEASON_ID'] = $season_id;
       $log[] = $log_item; 
       $c++;
    }

    $log['_ROWS'] = $count;
      // no records?
   //print_r($data);
   return $log;
  }


 function getBracketUserLogLastItem($user_id, $season_id) {
    global $bracket_users_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT MAX(UL.ENTRY_ID) LAST_ID
                   FROM bracket_users_log UL
                   WHERE USER_ID=".$user_id." AND SEASON_ID=".$season_id; 
    $db->query($sql_count);
    $last_id = 0;
    while ($row = $db->nextRow()) {
      $last_id = $row['LAST_ID'];
     }


    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, MSD.TSEASON_TITLE, U1.USER_NAME,          
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM bracket_users_log UL
                left join bracket_seasons_details MSD on MSD.SEASON_ID=UL.SEASON_ID and MSD.LANG_ID=".$_SESSION['lang_id']."
                left join users U1 on U1.USER_ID=UL.USER_ID and UL.USER_ID > 0
          WHERE UL.ENTRY_ID=".$last_id." AND UL.USER_ID=".$user_id." AND UL.SEASON_ID=".$season_id;
    $db->query($sql);
    $log = "";
    $c = 0;
    if ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $bracket_users_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%s", $row['TSEASON_TITLE'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['SEASON_ID'] = $season_id;
       $log[] = $log_item;
       $c++;
    }

   //print_r($data);
   return $log;
  }
}

?>