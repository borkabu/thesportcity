<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
login.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a login form
  - displays a login error

TABLES USED:
  - none

STATUS:
  - [STAT:FNCTNL] functional
===============================================================================
*/
//set_error_level(E_ALL);
// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');

// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/language.class.php');
include('../class/form.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

include('../include.php');
include('../class/inputs.inc.php');
//include('../class/session.inc.php');


$data['LANGUAGE']=inputLanguages('lang', $_SESSION['_lang']);  

$db->query("SELECT ID AS LANG_ID FROM languages WHERE SHORt_CODE='".$_SESSION['_lang']."'");
$row = $db->nextRow();
$_SESSION['lang_id'] = $row['LANG_ID'];

if (isset($_GET['err']) && $_GET['err'] == 'login') {
  // there was an error while logging on
  $data['ERROR'][0]['MSG'] = $langs['LANG_ERROR_LOGIN_ADMIN_U'];
}

if (isset($_GET['expired']) && !empty($HTTP_REFERER)) {
  // session expired. go back to the previous page after relogin
  $data['FORM'] = $HTTP_REFERER;
}
else {
 if (!empty($_SESSION['_user']['ADMIN_DEFAULT']))
    $data['FORM'] = $_SESSION["_user"]['ADMIN_DEFAULT'];
 else
    $data['FORM'] = 'index.php';   // otherwise go to news page after login
}

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/login.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>