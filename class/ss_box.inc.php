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

class SSBox extends Box{

  function getSSBox ($auth) {
    global $tpl;
    global $db;
    global $_SESSION;
    
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    $tpl->setTemplateFile('tpl/bar_ss.tpl.html');

    if ($auth->userOn()) {
      $db->select("ss_users", "*, UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(LAST_VISIT) AS TIMECHECK", "USER_ID=".$auth->getUserID());
      if ($row = $db->nextRow()) {
        $_SESSION["_user"]['SS'][0] = $row;
      }  
      $this->data['SS'] = $_SESSION['_user']['SS'];
      $this->data['SS'][0]['STAMINA'] = round($this->data['SS'][0]['STAMINA'], 4);
    }
    $tpl->addData($this->data);
    return $tpl->parse();
  } 
}

?>