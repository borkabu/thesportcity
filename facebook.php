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

// user session
include('class/ss_const.inc.php');

include('include.php');
// http header
include('class/headers.inc.php');
include('class/user_session.inc.php');
// page requirements
include('class/inputs.inc.php');


// --- build content data -----------------------------------------------------
//else 
//print_r( $data['VIDEO']);

// include common header
include('inc/top_facebook.inc.php');
//$db->showquery=true;
// content
//echo $content;

// include common footer
include('inc/bot_index.inc.php');

include('class/db_close.inc.php');
?>