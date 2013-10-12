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

if (!$auth->userOn()) {
  header('Location: index.php');
}

$content = '';
$updated = false;

// --- reminder ---------------------------------------------------------------
  $submenu = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 16);
if (isset($_POST["change"])) {
  $error = FALSE;
  if (!empty($_POST["password"]) && !empty($_POST["password2"]) && !empty($_POST["password3"])) {
    if ($_POST["password2"] != $_POST["password3"]) {
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_PASSWORDS_NOT_MATCH');
      $error = TRUE;
    }
    else {
      $db->select('users', 'USER_ID, USER_NAME, EMAIL, PASSWORD', "USER_ID=".$auth->getUserId());
      if (!$row = $db->nextRow()) {
        // no such user found
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_REMIND_NO_USER');
      } else {
        if ($row['PASSWORD'] != md5($_POST["password"])) {
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getErrorBox('ERROR_BAD_PASSWORD');
        }
        else if ($_POST["password"] == $_POST["password2"]) {
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getErrorBox('ERROR_SAME_PASSWORD');
        }
        else {
          // everything is fine, proceed in changing
          $sdata['password'] = "'".md5($_POST["password2"])."'";
          $db->update('users', $sdata, "USER_ID=".$row['USER_ID']);
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CHANGE_PASSWORD_SUCCESS');
          $updated = true;     
        }
      }
    }
  }
  else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_CHANGE_PASSWORD_MAND');
    $error = TRUE;
  }
} 


if (!$updated) {
  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/change_password.tpl.html');
  $tpl->addData($data);
  $content = $tpl->parse();
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