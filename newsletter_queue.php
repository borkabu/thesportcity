<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
set_time_limit(0);
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
include('class/newsletter.inc.php');
include('class/manager.inc.php');
include('class/wager.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

$db->showquery = true;
// include common header
$newsletter = new Newsletter($_GET['id']);

// generate new entries
$newsletter->submitNewsletterToQueue();

// process queue -> generate email queue entries
$newsletter->prepareEmailQueue();
$newsletter->generateEmailQueue();
// close connections
flush();
include('class/db_close.inc.php');
?>