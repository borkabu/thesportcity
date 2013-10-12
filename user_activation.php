<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
index.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - first page of a portal

TABLES USED: 
  - BASKET.NEWS
  - BASKET.EVENTS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
// includes
include('class/conf.inc.php');
include('class/func.inc.php');
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
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/newsletter.inc.php');
include('class/moderatorbox.inc.php');

// --- build content data -----------------------------------------------------
//else 
$content = '';
//$db->showquery = true;
if (isset($_GET['mode']) && $_GET['mode'] == 'activate') {
  if (!empty($_GET['k']) && isset($_GET['u']) && is_numeric($_GET['u'])) {
    $sql = "SELECT * FROM users WHERE user_id=".$_GET['u']." AND actkey='".$_GET['k']."'";
    $db->query($sql);
    if ($row = $db->nextRow()) {
      unset($sdata);
      $sdata['ACTIVE'] = "'Y'";
      $sdata['ACTKEY'] = "''";
      $sdata['EMAIL_VERIFIED'] = "'Y'";
      $db->update('users', $sdata, 'USER_ID='.$_GET['u']);

      $db->select('newsletter', 'ID', 'TYPE=0');
      $queues = '';
      $c = 0;
      while ($row = $db->nextRow()) {
        $queues[$c] = $row;
        $c++;
      }

      $newsletter = new Newsletter();
      if (is_array($queues)) {
        foreach ($queues as $queue) {
          $newsletter->subscribe($queue['ID'], $_GET['u']);
        }
      } 

      $trust = new Trust();	
      $trust->change(1, $_GET['u']);
      $trust->changeCommentTrust(1, $_GET['u']);
//      $trust->changeContentTrust(1, $_GET['u']);

      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getMessageBox('MESSAGE_ACTIVATION_SUCCESS');

      $log->logEvent($_GET['u'], 0, 5, 0, 1);
     // activation good
    }
    else {
      $sql = "SELECT * FROM users WHERE user_id=".$_GET['u']." AND active='Y'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_ACTIVATION_ALREADY');
      }
      else {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_ACTIVATION');
      }

    }
  }
//    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
//    $errorbox1 = $errorbox->getErrorBox('ERROR_REMIND_ENTER');
}
if (isset($_GET['mode']) && $_GET['mode'] == 'p_reminder') {
  if (!empty($_GET['actkey']) && isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $sql = "SELECT * FROM users WHERE user_id=".$_GET['user_id']." AND actkey='".$_GET['actkey']."'";
    $db->query($sql);
    if ($row = $db->nextRow()) {
      unset($sdata);
      $sdata['PASSWORD'] = "NEW_PASSWORD";
      $sdata['NEW_PASSWORD'] = "''";
      $sdata['ACTKEY'] = "''";
      $db->update('users', $sdata, 'USER_ID='.$_GET['user_id']);

      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getMessageBox('MESSAGE_PASSWORD_ACTIVATION_SUCCESS');

    }
    else {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 .= $errorbox->getErrorBox('ERROR_PASSWORD_ACTIVATION');
    }
  }
}

if (isset($_GET['mode']) && isset($_GET['actkey'])) {
  $moderator_queue = new ModeratorBox($langs, $_SESSION["_lang"]);
  if (isset($_GET['post_id']))
    $errorbox1 .= $moderator_queue->moderateComment($_GET['mode'], $_GET['post_id'], $_GET['actkey']);
  if (isset($_GET['news_id']))
    $errorbox1 .= $moderator_queue->moderateContent($_GET['mode'], $_GET['news_id'], $_GET['actkey']);
  if (isset($_GET['video_id']))
    $errorbox1 .= $moderator_queue->moderateContent($_GET['mode'], $_GET['video_id'], $_GET['actkey']);

}

// include common header
include('inc/top.inc.php');

echo  $content;
// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>