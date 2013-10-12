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

class RvsManagerLog {
 
  function RvsManagerLog() {

  }

  function logEvent ($user_id = '', $event_type, $season_id, $league_id, $player_id='', $user2_id='', $player2_id='', $player_str='', $player_str2='', $value=''){
    global $db;

    unset($sdata);
    if (!empty($user_id))
      $sdata['USER_ID'] = $user_id;
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';
    $sdata['SEASON_ID'] = $season_id;
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
    if (!empty($value))
      $sdata['VALUE'] = "'".$value."'";

    $db->insert('rvs_manager_log', $sdata);
  }


 function getRvsManagerLog($league_id, $page, $perpage) {
    global $rvs_manager_log_events_descr_success;
    global $db;
    global $auth;
    global $_SESSION;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT COUNT(UL.ENTRY_ID) ROWS
                   FROM rvs_manager_log UL
                   WHERE LEAGUE_ID=".$league_id; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, U.USER_NAME, B.LAST_NAME, B.FIRST_NAME, B1.LAST_NAME as LAST_NAME2, B1.FIRST_NAME as FIRST_NAME2, 
		 U2.USER_NAME as USER_NAME2, DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM rvs_manager_log UL
                left join users U on U.USER_ID=UL.USER_ID and UL.USER_ID > 0          
                left join users U2 on U2.USER_ID=UL.USER2_ID and UL.USER2_ID > 0          
                left join busers B on B.USER_ID=UL.PLAYER_ID and UL.USER_ID > 0
                left join busers B1 on B1.USER_ID=UL.PLAYER2_ID and UL.USER_ID > 0
	  WHERE LEAGUE_ID=".$league_id."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;
//echo $sql;
    $db->query($sql);
    $c = 0;
    $log = "";
    while ($row = $db->nextRow()) {
       $log_item['ENTRY_ID'] = $row['ENTRY_ID'];
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $rvs_manager_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%u2", $row['USER_NAME2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%p", $row['FIRST_NAME']." ".$row['LAST_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%r", $row['FIRST_NAME2']." ".$row['LAST_NAME2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%s", $row['PLAYER_STRING'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%z", $row['PLAYER_STRING2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v", $row['VALUE'], $log_item['LOG_ENTRY']);

       $log_item['UTC'] = $utc;
       $log_item['LEAGUE_ID'] = $league_id;
       $log[] = $log_item;
       $c++;
    }

    $log['_ROWS'] = $count;

   //print_r($data);
   return $log;
  }

 function getRvsManagerLogLastItem($league_id) {
    global $rvs_manager_log_events_descr_success;
    global $db;
    global $auth;
    global $_SESSION;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT MAX(UL.ENTRY_ID) LAST_ID
                   FROM rvs_manager_log UL
                   WHERE LEAGUE_ID=".$league_id; 
    $db->query($sql_count);
    $last_id = 0;
    if ($row = $db->nextRow()) {
      $last_id = $row['LAST_ID'];
    }

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, U.USER_NAME, B.LAST_NAME, B.FIRST_NAME, B1.LAST_NAME as LAST_NAME2, B1.FIRST_NAME as FIRST_NAME2, 
		 U2.USER_NAME as USER_NAME2, DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM rvs_manager_log UL
                left join users U on U.USER_ID=UL.USER_ID and UL.USER_ID > 0
                left join users U2 on U2.USER_ID=UL.USER2_ID and UL.USER2_ID > 0          
                left join busers B on B.USER_ID=UL.PLAYER_ID and UL.USER_ID > 0
                left join busers B1 on B1.USER_ID=UL.PLAYER2_ID and UL.USER_ID > 0
	  WHERE ENTRY_ID=".$last_id." AND LEAGUE_ID=".$league_id;
    $db->query($sql);
//echo $sql;
    $log = "";
    if ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $rvs_manager_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u2", $row['USER_NAME2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%p", $row['FIRST_NAME']." ".$row['LAST_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%r", $row['FIRST_NAME2']." ".$row['LAST_NAME2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%s", $row['PLAYER_STRING'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%z", $row['PLAYER_STRING2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v", $row['VALUE'], $log_item['LOG_ENTRY']);

       $log_item['UTC'] = $utc;
       $log_item['LEAGUE_ID'] = $league_id;
       $log[] = $log_item;
    }

   //print_r($data);
   return $log;
  }

}

?>