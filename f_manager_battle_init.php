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
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/f_manager_battle_init.tpl.html');
  $opt['class'] = 'input';
  $opt['options'] = $battle_places_limit;
  $place_limit = '';
  $data['PLACE_LIMIT'] = $frm->getInput(FORM_INPUT_SELECT, 'place_limit', $place_limit, $opt, $place_limit);
  $tpl->addData($data);

  $content .= $tpl->parse();

  echo $content;
// close connections
include('class/db_close.inc.php');
?>