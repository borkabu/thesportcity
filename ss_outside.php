<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
index.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - first page of a portal

TABLES USED: 
  - BASKET.NEWS
  - BASKET.EVENTS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
//return '';
//ob_start();
//header('location: online.php?game_id=11332');
//exit;
// includes
include('class/conf.inc.php');
include('class/func.inc.php');
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

//$aa=getmicrotime();
// user session
include('class/ss_const.inc.php');
include('include.php');
include('class/user_session.inc.php');

// http header
include('class/headers.inc.php');
include('class/inputs.inc.php');
// extras
include('class/ss_conf.inc.php');
include('ss_include.php');
// --- build content data -----------------------------------------------------
//else 
 $utils->setLocation(SS_OUTSIDE);

  $content = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]));
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('tpl/ss_outside.tpl.html');
$tpl->addData($data);
$content .= $tpl->parse();
// include common header

include('inc/top_ss.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_ss.inc.php');

// close connections
include('class/db_close.inc.php');
?>