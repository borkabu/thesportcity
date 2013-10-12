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
include('class/buser.inc.php');

// --- build content data -----------------------------------------------------

$buser = new Buser($_GET['user_id']);
$content = $buser->getBuserSeasonStats($_GET['seasons']);

include('inc/top_very_small.inc.php');
// content
echo $content;

// ----------------------------------------------------------------------------
// include common footer
include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');
?>