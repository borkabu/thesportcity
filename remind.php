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

if ($auth->userOn()) {
  header('Location: index.php');
}

$content = '';
$reminded = false;
// --- reminder ---------------------------------------------------------------
if (isset($_POST["remind"])) {
  $error = FALSE;
  if (!empty($_POST["user_name"]) && !empty($_POST["email"])) {
    // check by email address
    $where = "ACTIVE='Y' AND UPPER(EMAIL) LIKE UPPER('".$_POST["email"]."') AND UPPER(USER_NAME) LIKE UPPER('".$_POST["user_name"]."')";
  }
  else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_REMIND_ENTER');
    $error = TRUE;
  }
  
  if (!$error) {
    $db->select('users', 'USER_ID, USER_NAME, EMAIL', $where);
    if (!$row = $db->nextRow()) {
      // no such user found
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_REMIND_NO_USER');
//      $data['ERROR_NO_USER'][0]['X'] = 1; = 'LANG_ERROR_NO_USER_FOUND_U;
    }
    else {

      $password = gen_rand_string(0, 8);
      $sdata['new_password'] = "'".md5($password)."'";
      $actkey = gen_rand_string(0, 10);
      $sdata['ACTKEY'] = "'".$actkey."'";
      $db->update('users', $sdata, "USER_ID=".$row['USER_ID']);

      $to = $row['EMAIL'];
      $subject = $langs['LANG_EMAIL_PASSWORD_REMINDER_SUBJECT'];   

      $email = new Email($langs, $_SESSION['_lang']);
      $sdata['USER_NAME'] = $row['USER_NAME'];
      $sdata['PASSWORD'] = $password;
      $sdata['URL'] = $conf_site_url.'user_activation.php?mode=p_reminder&user_id='.$row['USER_ID'].'&actkey='.$actkey;
      $email->getEmailFromTemplate ('email_remind_password', $sdata) ;
      if ($email->send($to, $subject)) {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getMessageBox('MESSAGE_PASSWORD_REMINDER_SUCCESS');
	$reminded = true;
      }
      else {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_EMAIL');
      }
    }
  }
}

if (!$reminded) {
  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/remind.tpl.html');
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