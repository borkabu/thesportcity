<?php
error_reporting(E_ALL ^ E_NOTICE);
/*
===============================================================================
remind.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - sends a password reminder to user's email

TABLES USED: 
  - BASKET.USERS

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

// user session
include('class/ss_const.inc.php');

include('include.php');
//include('class/user_session.inc.php');

if (isset($clients[$_GET['client']]) && $clients[$_GET['client']]['ip'] == $_SERVER['REMOTE_ADDR'] 
    && isset($_GET['client_ip'])
    && $_GET['client_ip'] == $_SERVER['REMOTE_ADDR'] 
    && isset($_GET['user_ip'])) {
  // valid request -> process
  $seed = gen_rand_string(0, 16);
  $sdata['SEED'] = "'".$seed."'";
  $sdata['SERVER_IP'] = "'".$_GET['client_ip']."'";
  $sdata['USER_IP'] = "'".$_GET['user_ip']."'";
  $sdata['SOURCE'] = "'".$_GET['client']."'";
  $sdata['ISSUE_DATE'] = "NOW()";
  $db->insert("authentication_seeds", $sdata);
  $id = $db->id();
  echo $id."|".$seed;
} else {
  echo "-1|Validation error";
} 


?>