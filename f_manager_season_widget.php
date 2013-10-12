<?php

// Specify domains from which requests are allowed
header('Access-Control-Allow-Origin: *');

// Specify which request methods are allowed
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Additional headers which may be sent along with the CORS request
// The X-Requested-With header allows jQuery requests to go through
header('Access-Control-Allow-Headers: X-Requested-With');

// Set the age to 1 day to improve speed/caching.
header('Access-Control-Max-Age: 86400');

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

//$db->showquery=true;
  $manager = new Manager();

  $host=$_GET['host'];
  $content .= $managerbox->getManagerSeasonBox(true, isset($_GET['sport_id']) ? $_GET['sport_id'] : '', isset($_GET['season_id']) ? $_GET['season_id'] : '');

  include('inc/top_very_small_widget.inc.php');
  echo $content;
  include('inc/bot_very_small_widget.inc.php');
// content

// include common footer

// close connections
include('class/db_close.inc.php');
?>