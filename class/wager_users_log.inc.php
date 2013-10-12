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

class WagerUserLog {
 
  function WagerUserLog() {

  }

  function logEvent ($user_id, $event_type, $value1,$value2, $season_id, $wager_id = '', $user2_id=''){
    global $db;

    unset($sdata);
    if (!empty($user_id))
      $sdata['USER_ID'] = $user_id;
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';
    $sdata['VALUE1'] = $value1;
    $sdata['VALUE2'] = $value2;

    if (!empty($wager_id))
      $sdata['WAGER_ID'] = $wager_id;
    if (!empty($user2_id))
      $sdata['USER2_ID'] = $user2_id;

    $sdata['SEASON_ID'] = $season_id;
    $db->insert('wager_users_log', $sdata);
  }


 function getWagerUserLog($user_id, $season_id, $page, $perpage=1) {
    global $wager_users_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT COUNT(UL.ENTRY_ID) ROWS
                   FROM wager_users_log UL
                   WHERE USER_ID=".$user_id." AND SEASON_ID=".$season_id; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, MSD.TSEASON_TITLE, U1.USER_NAME, U2.USER_NAME as USER_NAME2, 
                 T1.TEAM_NAME TEAM_NAME1, T2.TEAM_NAME TEAM_NAME2,
		 T1.TEAM_TYPE TEAM_TYPE1, CD1.COUNTRY_NAME COUNTRY_NAME1,
		 T2.TEAM_TYPE TEAM_TYPE2, CD2.COUNTRY_NAME COUNTRY_NAME2,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM wager_users_log UL
                left join wager_seasons_details MSD on MSD.SEASON_ID=UL.SEASON_ID and MSD.LANG_ID=".$_SESSION['lang_id']."
                left join users U1 on U1.USER_ID=UL.USER_ID and UL.USER_ID > 0
                left join users U2 on U2.USER_ID=UL.USER2_ID and UL.USER2_ID > 0
                left join wager_games WG on WG.WAGER_ID=UL.WAGER_ID and UL.WAGER_ID > 0
                left join games G on G.GAME_ID=WG.GAME_ID
                left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
                left join countries_details CD1 on CD1.ID=T1.COUNTRY and CD1.lang_id=".$_SESSION['lang_id']."
                left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
                left join countries_details CD2 on CD2.ID=T2.COUNTRY and CD2.lang_id=".$_SESSION['lang_id']."
          WHERE UL.USER_ID=".$user_id." AND UL.SEASON_ID=".$season_id."
          ORDER BY UL.EVENT_DATE DESC ".
          $limitclause;
    $db->query($sql);
    $log = "";
    $c = 0;
    while ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $wager_users_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%s", $row['TSEASON_TITLE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v1", $row['VALUE1'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v2", $row['VALUE2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME2'], $log_item['LOG_ENTRY']);
       if ($row['TEAM_TYPE1'] == 1)	
         $log_item['LOG_ENTRY'] = str_replace("%t1", $row['TEAM_NAME1'], $log_item['LOG_ENTRY']);
       else if ($row['TEAM_TYPE1'] == 2)	
         $log_item['LOG_ENTRY'] = str_replace("%t1", $row['COUNTRY_NAME1'], $log_item['LOG_ENTRY']);
       if ($row['TEAM_TYPE2'] == 1)	
         $log_item['LOG_ENTRY'] = str_replace("%t2", $row['TEAM_NAME2'], $log_item['LOG_ENTRY']);
       else if ($row['TEAM_TYPE2'] == 2)	
         $log_item['LOG_ENTRY'] = str_replace("%t2", $row['COUNTRY_NAME2'], $log_item['LOG_ENTRY']);

       $log_item['LOG_ENTRY'] = str_replace("%t1", $row['TEAM_NAME1'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%t2", $row['TEAM_NAME2'], $log_item['LOG_ENTRY']);
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


 function getWagerUserLogLastItem($user_id, $season_id) {
    global $wager_users_log_events_descr_success;
    global $db;
    global $auth;
 
    $data='';
//$db->showquery =  true;
    $sql_count = "SELECT MAX(UL.ENTRY_ID) LAST_ID
                   FROM wager_users_log UL
                   WHERE USER_ID=".$user_id." AND SEASON_ID=".$season_id; 
    $db->query($sql_count);
    $last_id = 0;
    while ($row = $db->nextRow()) {
      $last_id = $row['LAST_ID'];
     }


    $utc = $auth->getUserTimezoneName();
    $sql="SELECT DISTINCT UL.*, MSD.TSEASON_TITLE, U1.USER_NAME, U2.USER_NAME as USER_NAME2, 
                 T1.TEAM_NAME TEAM_NAME1, T2.TEAM_NAME TEAM_NAME2,
		 T1.TEAM_TYPE TEAM_TYPE1, CD1.COUNTRY_NAME COUNTRY_NAME1,
		 T2.TEAM_TYPE TEAM_TYPE2, CD2.COUNTRY_NAME COUNTRY_NAME2,
                 DATE_ADD(UL.EVENT_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS LOG_EVENT_DATE
           FROM wager_users_log UL
                left join wager_seasons_details MSD on MSD.SEASON_ID=UL.SEASON_ID and MSD.LANG_ID=".$_SESSION['lang_id']."
                left join users U1 on U1.USER_ID=UL.USER_ID and UL.USER_ID > 0
                left join users U2 on U2.USER_ID=UL.USER2_ID and UL.USER2_ID > 0
                left join wager_games WG on WG.WAGER_ID=UL.WAGER_ID and UL.WAGER_ID > 0
                left join games G on G.GAME_ID=WG.GAME_ID
                left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
                left join countries_details CD1 on CD1.ID=T1.COUNTRY and CD1.lang_id=".$_SESSION['lang_id']."
                left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
                left join countries_details CD2 on CD2.ID=T2.COUNTRY and CD2.lang_id=".$_SESSION['lang_id']."
          WHERE UL.ENTRY_ID=".$last_id." AND UL.USER_ID=".$user_id." AND UL.SEASON_ID=".$season_id;
    $db->query($sql);
    $log = "";
    $c = 0;
    if ($row = $db->nextRow()) {
       $log_item['EVENT_DATE'] = $row['LOG_EVENT_DATE'];
       $log_item['LOG_ENTRY'] = $wager_users_log_events_descr_success[$row['EVENT_TYPE']];
       $log_item['LOG_ENTRY'] = str_replace("%d", $row['EVENT_DATE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%s", $row['TSEASON_TITLE'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v1", $row['VALUE1'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%v2", $row['VALUE2'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%u", $row['USER_NAME2'], $log_item['LOG_ENTRY']);
       if ($row['TEAM_TYPE1'] == 1)	
         $log_item['LOG_ENTRY'] = str_replace("%t1", $row['TEAM_NAME1'], $log_item['LOG_ENTRY']);
       else if ($row['TEAM_TYPE1'] == 2)	
         $log_item['LOG_ENTRY'] = str_replace("%t1", $row['COUNTRY_NAME1'], $log_item['LOG_ENTRY']);
       if ($row['TEAM_TYPE2'] == 1)	
         $log_item['LOG_ENTRY'] = str_replace("%t2", $row['TEAM_NAME2'], $log_item['LOG_ENTRY']);
       else if ($row['TEAM_TYPE2'] == 2)	
         $log_item['LOG_ENTRY'] = str_replace("%t2", $row['COUNTRY_NAME2'], $log_item['LOG_ENTRY']);

       $log_item['LOG_ENTRY'] = str_replace("%t1", $row['TEAM_NAME1'], $log_item['LOG_ENTRY']);
       $log_item['LOG_ENTRY'] = str_replace("%t2", $row['TEAM_NAME2'], $log_item['LOG_ENTRY']);
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