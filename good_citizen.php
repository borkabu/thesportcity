<?php
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
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
$content = '';

 $content .= $pagebox->getPage(17);

 if (!$auth->userOn()) {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_GC_LOGIN');
 } 
 else {

    if (!$auth->hasSupporter() && isset($_POST['get_gc'])
		&& isset($_POST['duration']) && $_POST['duration'] > 0
		&& $_SESSION["_user"]['CREDIT'] >= $_POST['duration']) {
      unset($sdata);
      $sdata['USER_ID'] = $auth->getUserId();
      $sdata['START_DATE'] = "NOW()";
      if ($_POST['duration'] == 0.3)
        $sdata['END_DATE'] = "DATE_ADD(NOW(), INTERVAL 1 DAY)";
      else if ($_POST['duration'] == 2)
        $sdata['END_DATE'] = "DATE_ADD(NOW(), INTERVAL 1 WEEK)";
      else if ($_POST['duration'] == 9)
        $sdata['END_DATE'] = "DATE_ADD(NOW(), INTERVAL 1 MONTH)";
      else if ($_POST['duration'] == 99)
        $sdata['END_DATE'] = "DATE_ADD(NOW(), INTERVAL 1 YEAR)";

      $db->insert('user_supporter',$sdata);
      $credits = new Credits();
      $credits->updateCredits ($auth->getUserId(), -1 * $_POST['duration']);
      $credit_log = new CreditsLog();
      $credit_log->logEvent ($auth->getUserId(), 9, $_POST['duration']);

      $auth->getSupporterInfo();
    }    
    $content .= $supporterbox->getSupporterBox(false);       
 }
  
  include('inc/top.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot.inc.php');

// close connections
include('class/db_close.inc.php');
?>