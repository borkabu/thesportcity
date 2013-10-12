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

class Log {
 
  function Log() {

  }

  function logEvent ($user_id, $delta, $event_type, $value, $log_type){
    global $db;

    $trust = new Trust();
    $comment_trust = $trust->getCommentTrustForUser($user_id);
    unset($sdata);
    $sdata['USER_ID'] = $user_id;
    $sdata['EVENT_TYPE'] = $event_type;
    $sdata['EVENT_DATE'] = 'NOW()';
    $sdata['VALUE'] = $value;
    $sdata['DELTA'] = $delta;
    $sdata['LOG_TYPE'] = $log_type;
    $db->insert('users_log', $sdata);
  }

}

?>