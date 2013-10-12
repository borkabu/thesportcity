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

class WagerLog {
 
  function WagerLog() {

  }

  function logEvent ($event_type, $value, $season_id, $wager_id=''){
    global $db;

    unset($sdata);
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';
    $sdata['VALUE'] = $value;
    if (!empty($wager_id))
      $sdata['WAGER_ID'] = $wager_id;
    $sdata['SEASON_ID'] = $season_id;
    $db->insert('wager_log', $sdata);
  }


 function getWagerLog($season_id, $page, $perpage) {
    global $wager_log_events_descr_success;
    global $db;
    global $auth;
    global $_SESSION;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT COUNT(UL.ENTRY_ID) ROWS
                   FROM wager_log UL
                   WHERE SEASON_ID=".$season_id; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, MSD.TSEASON_TITLE, 
                 T1.TEAM_NAME TEAM_NAME1, T2.TEAM_NAME TEAM_NAME2,
		 T1.TEAM_TYPE TEAM_TYPE1, CD1.COUNTRY_NAME COUNTRY_NAME1,
		 T2.TEAM_TYPE TEAM_TYPE2, CD2.COUNTRY_NAME COUNTRY_NAME2,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM wager_log UL
                left join wager_seasons_details MSD on MSD.SEASON_ID=UL.SEASON_ID and MSD.LANG_ID=".$_SESSION['lang_id']."
                left join wager_games WG on WG.WAGER_ID=UL.WAGER_ID and UL.WAGER_ID > 0
                left join games G on G.GAME_ID=WG.GAME_ID
                left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
                left join countries_details CD1 on CD1.ID=T1.COUNTRY and CD1.lang_id=".$_SESSION['lang_id']."
                left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
                left join countries_details CD2 on CD2.ID=T2.COUNTRY and CD2.lang_id=".$_SESSION['lang_id']."

          WHERE UL.SEASON_ID=".$season_id."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;
    $db->query($sql);
    $log = "";
    $c = 0;
    while ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $wager_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       if ($row['TEAM_TYPE1'] == 1)	
         $log_item['LOG_ENTRY'] = str_replace("%t1", $row['TEAM_NAME1'], $log_item['LOG_ENTRY']);
       else if ($row['TEAM_TYPE1'] == 2)	
         $log_item['LOG_ENTRY'] = str_replace("%t1", $row['COUNTRY_NAME1'], $log_item['LOG_ENTRY']);
       if ($row['TEAM_TYPE2'] == 1)	
         $log_item['LOG_ENTRY'] = str_replace("%t2", $row['TEAM_NAME2'], $log_item['LOG_ENTRY']);
       else if ($row['TEAM_TYPE2'] == 2)	
         $log_item['LOG_ENTRY'] = str_replace("%t2", $row['COUNTRY_NAME2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v", $row['VALUE'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['TSEASON_ID'] = $season_id;
       $log[] = $log_item;
       $c++;
    }

    $log['_ROWS'] = $count;

   //print_r($data);
   return $log;
  }

 function getWagerLogLastItem($season_id) {
    global $wager_log_events_descr_success;
    global $db;
    global $auth;
    global $_SESSION;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT MAX(UL.ENTRY_ID) LAST_ID
                   FROM wager_log UL
                   WHERE SEASON_ID=".$season_id; 
    $db->query($sql_count);
    $last_id = 0;
    while ($row = $db->nextRow()) {
      $last_id = $row['LAST_ID'];
     }

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, MSD.TSEASON_TITLE, 
                 T1.TEAM_NAME TEAM_NAME1, T2.TEAM_NAME TEAM_NAME2,
		 T1.TEAM_TYPE TEAM_TYPE1, CD1.COUNTRY_NAME COUNTRY_NAME1,
		 T2.TEAM_TYPE TEAM_TYPE2, CD2.COUNTRY_NAME COUNTRY_NAME2,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM wager_log UL
                left join wager_seasons_details MSD on MSD.SEASON_ID=UL.SEASON_ID and MSD.LANG_ID=".$_SESSION['lang_id']."
                left join wager_games WG on WG.WAGER_ID=UL.WAGER_ID and UL.WAGER_ID > 0
                left join games G on G.GAME_ID=WG.GAME_ID
                left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
                left join countries_details CD1 on CD1.ID=T1.COUNTRY and CD1.lang_id=".$_SESSION['lang_id']."
                left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
                left join countries_details CD2 on CD2.ID=T2.COUNTRY and CD2.lang_id=".$_SESSION['lang_id']."

          WHERE UL.ENTRY_ID=".$last_id." AND UL.SEASON_ID=".$season_id;
    $db->query($sql);
    $c = 0;
    if ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $wager_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       if ($row['TEAM_TYPE1'] == 1)	
         $log_item['LOG_ENTRY'] = str_replace("%t1", $row['TEAM_NAME1'], $log_item['LOG_ENTRY']);
       else if ($row['TEAM_TYPE1'] == 2)	
         $log_item['LOG_ENTRY'] = str_replace("%t1", $row['COUNTRY_NAME1'], $log_item['LOG_ENTRY']);
       if ($row['TEAM_TYPE2'] == 1)	
         $log_item['LOG_ENTRY'] = str_replace("%t2", $row['TEAM_NAME2'], $log_item['LOG_ENTRY']);
       else if ($row['TEAM_TYPE2'] == 2)	
         $log_item['LOG_ENTRY'] = str_replace("%t2", $row['COUNTRY_NAME2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v", $row['VALUE'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['TSEASON_ID'] = $season_id;
       $log[] = $log_item;
       $c++;
    }

   //print_r($data);
   return $log;
  }
}

?>