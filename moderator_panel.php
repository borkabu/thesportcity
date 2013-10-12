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
include('class/moderatorbox.inc.php');

// --- build content data -----------------------------------------------------
//else 
$content = '';
if ($auth->userOn()) {
  $trust = new Trust();
  $comment_trust = $trust->getCommentTrustLevel($_SESSION["_user"]);
  $content_trust = $trust->getContentTrustLevel($_SESSION["_user"]);
  if ($comment_trust >= 5 || $content_trust >= 5) {
    $content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 17);

    $moderator_queue = new ModeratorBox($langs, $_SESSION["_lang"]);
    // get comment queue
    if ($comment_trust >= 5) {
      $smarty->assign("comment_queue", $moderator_queue->getCommentQueue());
    }

    // get content queue
    if ($content_trust >= 5) {
      $smarty->assign("content_queue", $moderator_queue->getContentQueue());
    }

    $start = getmicrotime();
    $content .= $smarty->fetch('smarty_tpl/moderator_panel.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/moderator_panel.smarty'.($stop-$start);    
  } else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_NOT_ENOUGH_TRUST_TO_MODERATE');
  }
}
else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_NOT_LOGGED_IN');
}

  // include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>