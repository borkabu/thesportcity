<?php
/*
===============================================================================
help.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows help item
  = shows help topic index

TABLES USED: 
  - BASKET.HELP

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
include('class/update.inc.php');

// classes
include('class/db.class.php');
include('class/template.class.php');
include('class/language.class.php');
include('class/form.class.php');

// connections
include('class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/manager.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/f_manager_tournament_init.tpl.html');

// && $auth->getCredits() >= 3
  if ($auth->userOn()) {
    $data['CAN_CREATE'][0]['X'] = 1;
  } 

/*else {
    $data['ERROR'][0]['MSG'] = $langs['LANG_ERROR_NOT_ENOUGH_CREDITS_U'];
  }*/
  $tpl->addData($data);
  $content .= $tpl->parse();
  echo $content;
// close connections
include('class/db_close.inc.php');
?>