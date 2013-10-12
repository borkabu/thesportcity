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

class ManagerLog {
 
  function ManagerLog() {

  }

  function logEvent ($user_id, $event_type, $value, $season_id, $team1_id='', $team2_id=''){
    global $db;

    unset($sdata);
    if (!empty($user_id))
      $sdata['USER_ID'] = $user_id;
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';
    $sdata['VALUE'] = $value;
    if (!empty($team1_id))
      $sdata['TEAM1_ID'] = $team1_id;
    if (!empty($team2_id))
      $sdata['TEAM2_ID'] = $team2_id;
    $sdata['SEASON_ID'] = $season_id;
    $db->insert('manager_log', $sdata);
  }


 function getManagerLog($season_id, $page, $perpage) {
    global $manager_log_events_descr_success;
    global $db;
    global $auth;
    global $_SESSION;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT COUNT(UL.ENTRY_ID) ROWS
                   FROM manager_log UL
                   WHERE EVENT_TYPE <> 9 AND SEASON_ID=".$season_id; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, B.LAST_NAME, B.FIRST_NAME, T.TEAM_NAME2 as TEAM1, T2.TEAM_NAME2 as TEAM2, 
		T.TEAM_TYPE, CD.COUNTRY_NAME,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM manager_log UL
                left join busers B on B.USER_ID=UL.USER_ID and UL.USER_ID > 0
                left join teams T on T.TEAM_ID=UL.TEAM1_ID and UL.TEAM1_ID > 0
                left join countries_details CD on CD.ID=T.COUNTRY and CD.lang_id=".$_SESSION['lang_id']."
                left join teams T2 on T2.TEAM_ID=UL.TEAM2_ID and UL.TEAM2_ID > 0
          WHERE EVENT_TYPE <> 9 AND SEASON_ID=".$season_id."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;

    $db->query($sql);
    $log = "";
    while ($row = $db->nextRow()) {
       $log_item['ENTRY_ID'] = $row['ENTRY_ID'];
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $manager_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%n", $row['FIRST_NAME']." ".$row['LAST_NAME'], $log_item['LOG_ENTRY']);
       if ($row['TEAM_TYPE'] == 1)	
         $log_item['LOG_ENTRY'] = str_replace("%k", $row['TEAM1'], $log_item['LOG_ENTRY']);
       else if ($row['TEAM_TYPE'] == 2)	
         $log_item['LOG_ENTRY'] = str_replace("%k", $row['COUNTRY_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%w", $row['TEAM2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v", $row['VALUE'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['MSEASON_ID'] = $season_id;
       $log[] = $log_item;
    }

    $log['_ROWS'] = $count;

   //print_r($data);
   return $log;
  }

 function getManagerLogLastItem($season_id) {
    global $manager_log_events_descr_success;
    global $db;
    global $auth;
    global $_SESSION;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT MAX(UL.ENTRY_ID) LAST_ID
                   FROM manager_log UL
                   WHERE EVENT_TYPE <> 9 AND SEASON_ID=".$season_id; 
    $db->query($sql_count);
    $last_id = 0;
    if ($row = $db->nextRow()) {
      $last_id = $row['LAST_ID'];
    }

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, B.LAST_NAME, B.FIRST_NAME, T.TEAM_NAME2 as TEAM1, T2.TEAM_NAME2 as TEAM2, 
		T.TEAM_TYPE, CD.COUNTRY_NAME,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM manager_log UL
                left join busers B on B.USER_ID=UL.USER_ID and UL.USER_ID > 0
                left join teams T on T.TEAM_ID=UL.TEAM1_ID and UL.TEAM1_ID > 0
                left join countries_details CD on CD.ID=T.COUNTRY and CD.lang_id=".$_SESSION['lang_id']."
                left join teams T2 on T2.TEAM_ID=UL.TEAM2_ID and UL.TEAM2_ID > 0
          WHERE EVENT_TYPE <> 9 AND ENTRY_ID=".$last_id." AND SEASON_ID=".$season_id;
    $db->query($sql);
    $c = 0;
    $log = "";
    if ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $manager_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%n", $row['FIRST_NAME']." ".$row['LAST_NAME'], $log_item['LOG_ENTRY']);
       if ($row['TEAM_TYPE'] == 1)	
         $log_item['LOG_ENTRY'] = str_replace("%k", $row['TEAM1'], $log_item['LOG_ENTRY']);
       else if ($row['TEAM_TYPE'] == 2)	
         $log_item['LOG_ENTRY'] = str_replace("%k", $row['COUNTRY_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%w", $row['TEAM2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v", $row['VALUE'], $log_item['LOG_ENTRY']);
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