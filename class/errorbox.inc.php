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

class ErrorBox extends Box{

  function getErrorBox ($error_type) {
    global $tpl;
    
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    $tpl->setTemplateFile('tpl/bar_error.tpl.html');
    $this->data[$error_type][0]['X'] = 1;
    $tpl->addData($this->data);
    return $tpl->parse();
  } 

  function getMessageBox ($message_type) {
    global $tpl;
    
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    $tpl->setTemplateFile('tpl/bar_message.tpl.html');
    $this->data[$message_type][0]['X'] = 1;

    $tpl->addData($this->data);
    return $tpl->parse();
  } 

}

?>