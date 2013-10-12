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

class ClanLog {
 
  function ClanLog() {

  }

  function logEvent ($clan_id, $event_type, $value, $user_id = '', $team_id = ''){
    global $db;

    unset($sdata);
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';
    $sdata['VALUE'] = $value;
    $sdata['CLAN_ID'] = $clan_id;
    if (!empty($user_id))
      $sdata['USER_ID'] = $user_id;
    if (!empty($team_id))
      $sdata['TEAM_ID'] = $team_id;

    $db->insert('clan_log', $sdata);
  }

 function getClanLog($clan_id, $page, $perpage) {
    global $clan_log_events_descr_success;
    global $db;
    global $auth;
    global $_SESSION;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT COUNT(UL.ENTRY_ID) ROWS
                   FROM clan_log UL
                   WHERE CLAN_ID=".$clan_id; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, U.USER_NAME, C.CLAN_NAME, CT.TEAM_NAME, MSD.SEASON_TITLE,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM clan_log UL
                left join users U on U.USER_ID=UL.USER_ID and UL.USER_ID > 0
		left join clans C on C.CLAN_ID=UL.CLAN_ID
		left join clan_teams CT on CT.TEAM_ID=UL.TEAM_ID and CT.CLAN_ID=UL.CLAN_ID
		left join manager_seasons MS on CT.SEASON_ID=MS.SEASON_ID
                left join manager_seasons_details MSD on MSD.SEASON_ID=MS.SEASON_ID and MSD.lang_id=".$_SESSION['lang_id']."
          WHERE UL.CLAN_ID=".$clan_id."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;
//echo $sql;
    $db->query($sql);
    $log = "";
    while ($row = $db->nextRow()) {
       $log_item['ENTRY_ID'] = $row['ENTRY_ID'];
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $clan_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%c", $row['CLAN_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v", $row['VALUE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%t", $row['TEAM_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%b", $row['SEASON_TITLE'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['CLAN_ID'] = $clan_id;
       $log[] = $log_item;
    }

    $log['_ROWS'] = $count;

   //print_r($data);
   return $log;
  }

 function getClanLogLastItem($clan_id) {
    global $clan_log_events_descr_success;
    global $db;
    global $auth;
    global $_SESSION;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT MAX(UL.ENTRY_ID) LAST_ID
                   FROM clan_log UL
                   WHERE SEASON_ID=".$season_id; 
    $db->query($sql_count);
    $last_id = 0;
    if ($row = $db->nextRow()) {
      $last_id = $row['LAST_ID'];
    }

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, U.USER_NAME, C.CLAN_NAME, CT.TEAM_NAME, MSD.SEASON_TITLE,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM clan_log UL
                left join users U on U.USER_ID=UL.USER_ID and UL.USER_ID > 0
		left join clans C on C.CLAN_ID=UL.CLAN_ID
		left join clan_teams CT on CT.TEAM_ID=UL.TEAM_ID and CT.CLAN_ID=UL.CLAN_ID
		left join manager_seasons MS on CT.SEASON_ID=MS.SEASON_ID
                left join manager_seasons_details MSD on MSD.SEASON_ID=MS.SEASON_ID and MSD.lang_id=".$_SESSION['lang_id']."
          WHERE UL.ENTRY_ID=".$last_id." AND UL.CLAN_ID=".$clan_id;
    $db->query($sql);
    $log = "";
    if ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $clan_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%c", $row['CLAN_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v", $row['VALUE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%t", $row['TEAM_NAME'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%b", $row['SEASON_TITLE'], $log_item['LOG_ENTRY']);
       $log_item['UTC'] = $utc;
       $log_item['CLAN_ID'] = $clan_id;
       $log[] = $log_item;
    }

   //print_r($data);
   return $log;
  }

}

?>