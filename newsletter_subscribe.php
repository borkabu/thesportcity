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
include('class/newsletter.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
  $newsletter = new Newsletter();
  if (isset($_GET['mode']) && $_GET['mode'] == 'user') {
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);
    $tpl->setTemplateFile('tpl/bar_newsletters_settings.tpl.html');

    if (isset($_GET['action']) && $_GET['action'] == 'subscribe' && $auth->userOn()) {
      $newsletter->subscribe($_GET['newsletter_id'], $auth->getUserId());
      $data['UNSUBSCRIBE'][0]['NEWSLETTER_ID'] = $_GET['newsletter_id'];
    }
  
    if (isset($_GET['action']) && $_GET['action'] == 'unsubscribe' && $auth->userOn()) {
      if ($_GET['action'] == 'unsubscribe' && $auth->userOn()) {
        $newsletter->unsubscribe($_GET['newsletter_id'], $auth->getUserId());
        $data['SUBSCRIBE'][0]['NEWSLETTER_ID'] = $_GET['newsletter_id'];
      }
    } 
    $tpl->addData($data);

    $content .= $tpl->parse();
    echo $content;
  } else {
     if (isset($_GET['action']) && $_GET['action'] == 'unsubscribe') {
       $db->select('newsletter_subscribers', "*", "USER_ID=".$_GET['user_id']." AND ID=".$_GET['newsletter_id']." AND UNSUBSCRIBE_KEY='".$_GET['actkey']."'");
       if ($row = $db->nextRow()) {
         $newsletter->unsubscribe($_GET['newsletter_id'], $_GET['user_id']);
         $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
         $errorbox1 = $errorbox->getMessageBox('MESSAGE_NEWSLETTER_UNSUBSCRIBE_SUCCESS');

       } else {
         $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
         $errorbox1 = $errorbox->getErrorBox('ERROR_NEWSLETTER_UNSUBSCRIBE');
       }
     } else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_NEWSLETTER_UNSUBSCRIBE');
     }
     include('inc/top.inc.php');
     include('inc/bot.inc.php');
  }


// close connections
include('class/db_close.inc.php');
?>