<?php
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
include('class/user_session.inc.php');
// page requirements
include('class/inputs.inc.php');

// http header
include('class/headers.inc.php');

$content = '';

if ($_GET['message'] == 'paypal_success') {
  $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
  $errorbox1 = $errorbox->getMessageBox('MESSAGE_PAYPAL_SUCCESS');
}
// ----------------------------------------------------------------------------

// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

// close connections
include('class/db_close.inc.php');
?>