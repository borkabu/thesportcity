<?php
/*
===============================================================================
session.inc.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - checks whether admin session is up and redirects to login screen if not
  - logs admin on (login is checked against BASKET.USERS table; 
    ADMIN field must be set to 'Y' in order to login)

TABLES USED: 
  - BASKET.USERS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
//include('prepare.inc.php');
require_once('auth.inc.php');
require_once('credits.inc.php');
require_once('credits_log.inc.php');

$auth = new Auth();

$data['LANGUAGE']=inputLanguages('lang', $_SESSION['_lang']);  

$db->query("SELECT ID AS LANG_ID FROM languages WHERE SHORt_CODE='".$_SESSION['_lang']."'");
$row = $db->nextRow();
$_SESSION['lang_id'] = $row['LANG_ID'];
//echo $_SESSION['lang_id'];
// register session

if (isset($_GET['logoff']) || isset($_POST['logout'])) {
  $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
  $errorbox1 = $errorbox->getErrorBox('ERROR_GOODBYE');
  $lang = $_SESSION["_lang"];
  $_SESSION = array();

   // If it's desired to kill the session, also delete the session cookie.
   // Note: This will destroy the session, and not just the session data!
  if (isset($_COOKIE[session_name()])) {
     setcookie(session_name(), '', time()-42000, '/');
  }  session_unset();

  session_destroy();
  session_set_cookie_params(1800); 
  session_start();
  $_SESSION["_lang"] = $lang;
//  header("Location: index.php");
//  exit;
}

//$db->showquery=true;
// login process
if (isset($_POST['l_user_name']) || isset($_POST['l_password'])) {
      $fields = 'USER_ID, FIRST_NAME, LAST_NAME, USER_NAME, PASSWORD, EMAIL, 
             PHONE, COUNTRY, MOBILE_PHONE, TOWN, ADDRESS1, ADDRESS2, POSTCODE, PUBLISH, 
             CREDIT, FROZEN_CREDITS, GENDER, SUBSTRING(BIRTH_DATE, 1, 10) BIRTH_DATE,  COOKIESTRING,
             PIC_LOCATION, IP, REG_DATE, LAST_LOGIN, ACTIVE, TRUST, COMMENT_TRUST, CONTENT_TRUST, TIMEZONE, ADMIN';
  
  if (strlen($_POST['l_user_name']) > 1) {
    // login form
    $db->select('users', $fields, "UPPER(USER_NAME) LIKE UPPER('".$_POST['l_user_name']."')
                                   AND PASSWORD=MD5('".$_POST['l_password']."') AND ADMIN='Y'");
    $logproc = TRUE;
  }

  if ($row = $db->nextRow()) {
    // login successeful
    $_SESSION["_user"] = $row;

    // update user last login date
    $db->update('users', 'LAST_LOGIN=SYSDATE()', 'USER_ID='.$row['USER_ID']);
    $sql='SELECT ITEM_CODE, ACCESS_LEVEL FROM admin_rights WHERE USER_ID='.$row['USER_ID'];
    $db->query($sql);

    while ($row = $db->nextRow())
      $_SESSION["_admin"][$row['ITEM_CODE']]= $row['ACCESS_LEVEL'];
  }
  else {
    // login incorrect. go back to login page with error
    header("Location: login.php?err=login");
    exit();
  }
}
// maybe user is alrady logged on?
elseif (!isset($_SESSION["_user"])) {
  // no - redirect to login page
    header("Location: access_denied.php");
    exit();  
}
// check whether user is an admin
elseif (isset($_SESSION["_user"]['ADMIN']) && $_SESSION["_user"]['ADMIN'] != 'Y') {
  // no - redirect to login page
  header("Location: login.php?err=admin");
  exit();
}


?>