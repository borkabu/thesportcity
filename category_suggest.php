<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
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
include('class/category.inc.php');

// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

$content = '';
if ($auth->userOn() && !empty($_POST['suggested_cat'])) {
   // check that user already has voted
   $category = new Category();    
   if (!$category->addCategory($_POST['suggested_cat'])) 
     $sugerror = 1;
} else {
  $sugerror = 1;
}

 if (isset($sugerror)) {
   $smarty->assign("sugerror", $sugerror);
 }
 $start = getmicrotime();
 $content = $smarty->fetch('smarty_tpl/category_suggest.smarty');
 $stop = getmicrotime();
 if (isset($_GET['debugphp']))
   echo 'smarty_tpl/category_suggest.smarty'.($stop-$start);

 echo $content;

// close connections
include('class/db_close.inc.php');
?>