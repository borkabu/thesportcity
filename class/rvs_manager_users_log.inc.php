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

class RvsManagerUserLog {
 
  function RvsManagerUserLog() {

  }

  function logEvent ($user_id, $event_type, $season_id, $league_id, $player_id='', $user2_id='', $player2_id='', $player_str='', $player_str2=''){
    global $db;

    unset($sdata);
    $sdata['USER_ID'] = $user_id;
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';
    $sdata['LEAGUE_ID'] = $league_id;
    if (!empty($player_id))
      $sdata['PLAYER_ID'] = $player_id;
    if (!empty($player_str))
      $sdata['PLAYER_STRING'] = "'".$player_str."'";
    if (!empty($user2_id))
      $sdata['USER2_ID'] = $user2_id;
    if (!empty($player2_id))
      $sdata['PLAYER2_ID'] = $player2_id;
    if (!empty($player_str2))
      $sdata['PLAYER_STRING2'] = "'".$player_str2."'";


    $sdata['SEASON_ID'] = $season_id;
    $db->insert('rvs_manager_users_log', $sdata);
  }


 function getRvsManagerUserLog($user_id, $league_id, $page, $perpage) {
    global $rvs_manager_users_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
    $where_season = "";
//$db->showquery =  true;
    if ($league_id != "")
      $where_season = " AND UL.LEAGUE_ID=".$league_id;
    $sql_count = "SELECT COUNT(UL.ENTRY_ID) ROWS
                   FROM rvs_manager_users_log UL
                   WHERE USER_ID=".$user_id.$where_season; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, B.LAST_NAME, B.FIRST_NAME, B1.LAST_NAME as LAST_NAME2, B1.FIRST_NAME as FIRST_NAME2, 
		MSD.TITLE, U1.USER_NAME,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM rvs_manager_users_log UL
                left join busers B on B.USER_ID=UL.PLAYER_ID and UL.USER_ID > 0
                left join busers B1 on B1.USER_ID=UL.PLAYER2_ID and UL.USER_ID > 0
                left join rvs_manager_leagues MSD on MSD.LEAGUE_ID=".$league_id."
                left join users U1 on U1.USER_ID=UL.USER_ID and UL.USER_ID > 0
          WHERE UL.USER_ID=".$user_id.$where_season."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;
    $db->query($sql);
//echo $sql;
    $c = 0;
    $log = "";
    while ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $rvs_manager_users_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%p", $row['FIRST_NAME']." ".$row['LAST_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%r", $row['FIRST_NAME2']." ".$row['LAST_NAME2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%l", $row['TITLE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%s", $row['PLAYER_STRING'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%z", $row['PLAYER_STRING2'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['LEAGUE_ID'] = $league_id;
       $log[] = $log_item;
       $c++;
    }

    $log['_ROWS'] = $count;

   //print_r($data);
   return $log;
  }


 function getRvsManagerUserLogLastItem($user_id, $league_id) {
    global $rvs_manager_users_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
    $where_season = "";
//$db->showquery =  true;
    if ($league_id != "")
      $where_season = " AND UL.LEAGUE_ID=".$league_id;
    $sql_count = "SELECT MAX(UL.ENTRY_ID) LAST_ID
                   FROM rvs_manager_users_log UL
                   WHERE USER_ID=".$user_id.$where_season; 
    $db->query($sql_count);
    $last_id = 0;
    if ($row = $db->nextRow()) {
      $last_id = $row['LAST_ID'];
    }

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, B.LAST_NAME, B.FIRST_NAME, B1.LAST_NAME as LAST_NAME2, B1.FIRST_NAME as FIRST_NAME2, 
		MSD.TITLE, U1.USER_NAME,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM rvs_manager_users_log UL
                left join busers B on B.USER_ID=UL.PLAYER_ID and UL.USER_ID > 0
                left join busers B1 on B1.USER_ID=UL.PLAYER2_ID and UL.USER_ID > 0
                left join rvs_manager_leagues MSD on MSD.LEAGUE_ID=".$league_id."
                left join users U1 on U1.USER_ID=UL.USER2_ID and UL.USER2_ID > 0
          WHERE ENTRY_ID=".$last_id." AND UL.USER_ID=".$user_id.$where_season;
    $db->query($sql);
//echo $sql;
    $log = "";
    if ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $rvs_manager_users_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%p", $row['FIRST_NAME']." ".$row['LAST_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%r", $row['FIRST_NAME2']." ".$row['LAST_NAME2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%l", $row['TITLE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%s", $row['PLAYER_STRING'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%z", $row['PLAYER_STRING2'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['LEAGUE_ID'] = $league_id;
       $log[] = $log_item;
    }

   //print_r($data);
   return $log;
  }
}

?>