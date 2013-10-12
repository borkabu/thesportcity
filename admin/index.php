<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);

/*
===============================================================================
index.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - redirects to news.php

TABLES USED:
  - none

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/adm_menu.php');

// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/language.class.php');
include('../class/form.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

// security layer
include('../include.php');
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');


//flush();

// check perform update

if (!empty($_SESSION['_user']['ADMIN_DEFAULT']))
   {
    header('Location: '.$_SESSION['_user']['ADMIN_DEFAULT']);
   }
 else
  { 
    header('Location: dashboard.php');
  } 

?>