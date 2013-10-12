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

    $sql = "SELECT ML.*, DRAFT_START_DATE < NOW() as DRAFT_STARTED, NOW() as NOW
		from rvs_manager_leagues ML
		WHERE ML.league_ID =".$_GET['league_id']; 
    $db->query($sql); 
    if ($row = $db->nextRow()) {
      echo $row['DRAFT_START_DATE']."<br>";
      echo $row['NOW']."<br>";
      echo $row['DRAFT_STARTED'];
    }
// include common header

// close connections
include('class/db_close.inc.php');
?>