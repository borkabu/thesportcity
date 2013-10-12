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

class SoloManagerUserLog {
 
  function SoloManagerUserLog() {

  }

  function logEvent ($user_id, $event_type, $value, $season_id, $player_id='', $user2_id='', $value2='', $str_value=''){
    global $db;

    unset($sdata);
    $sdata['USER_ID'] = $user_id;
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';
    $sdata['VALUE'] = $value;
    if (!empty($player_id))
      $sdata['PLAYER_ID'] = $player_id;
    if (!empty($user2_id))
      $sdata['USER2_ID'] = $user2_id;
    if (!empty($value2))
      $sdata['VALUE2'] = $value2;
    if (!empty($str_value))
      $sdata['STR_VALUE'] = "'".$str_value."'";

    $sdata['SEASON_ID'] = $season_id;
    $db->insert('solo_manager_users_log', $sdata);
  }


 function getManagerUserLog($user_id, $season_id, $page, $perpage) {
    global $solo_manager_users_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
    $where_season = "";
//$db->showquery =  true;
    if ($season_id != "")
      $where_season = " AND UL.SEASON_ID=".$season_id;
    $sql_count = "SELECT COUNT(UL.ENTRY_ID) ROWS
                   FROM solo_manager_users_log UL
                   WHERE USER_ID=".$user_id.$where_season; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, B.LAST_NAME, B.FIRST_NAME, MSD.SEASON_TITLE, U1.USER_NAME,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM solo_manager_users_log UL
                left join busers B on B.USER_ID=UL.PLAYER_ID and UL.USER_ID > 0
                left join manager_seasons_details MSD on MSD.SEASON_ID=UL.SEASON_ID and MSD.LANG_ID=".$_SESSION['lang_id']."
                left join users U1 on U1.USER_ID=UL.USER2_ID and UL.USER2_ID > 0
          WHERE UL.USER_ID=".$user_id.$where_season."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;
    $db->query($sql);
//echo $sql;
    $c = 0;
    $log = "";
    while ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $solo_manager_users_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%p", $row['FIRST_NAME']." ".$row['LAST_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%s", $row['SEASON_TITLE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v", $row['VALUE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%f", $row['VALUE2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%g", $row['STR_VALUE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['MSEASON_ID'] = $season_id;
       $log_item['SEASON_TITLE'] = $row['SEASON_TITLE'];
       $log[] = $log_item;
       $c++;
    }

    $log['_ROWS'] = $count;

   //print_r($data);
   return $log;
  }

}

?>