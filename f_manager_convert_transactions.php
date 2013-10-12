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
include('class/manager.inc.php');
include('class/manager_user.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
  $manager_user = new ManagerUser($_GET['season_id']);

  $data='';
//$db->showquery=true;
  if ($_GET['credits'] >= 1 && $_SESSION['_user']['CREDIT'] >= $_GET['credits']) { 
    $credits = new Credits();
    $credits->updateCredits ($auth->getUserId(), $_GET['credits'] * -1);
    $credit_log = new CreditsLog();
    $credit_log->logEvent ($auth->getUserId(), 6, $_GET['credits']);
    unset($trdata);
    $trdata['TRANSACTIONS'] = $_SESSION['_user']['MANAGER'][$_GET['season_id']]['TRANSACTIONS'] + 3*$_GET['credits'];
    $trdata['REFUNDABLE'] = 3*$_GET['credits'];
    $_SESSION['_user']['MANAGER'][$_GET['season_id']]['TRANSACTIONS'] = $_SESSION['_user']['MANAGER'][$_GET['season_id']]['TRANSACTIONS'] + 3*$_GET['credits'];
    $db->update('manager_users', $trdata, 'USER_ID='.$auth->getUserId().' AND SEASON_ID='.$_GET['season_id']);
    unset($trdata);
    $content = "transactions@@@".$_SESSION['_user']['MANAGER'][$_GET['season_id']]['TRANSACTIONS'];
    $content .= "###credits@@@".$_SESSION['_user']['CREDIT'];
  }
  else {
    $content = "transactions@@@".$_SESSION['_user']['MANAGER'][$_GET['season_id']]['TRANSACTIONS'];
  }

  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/bar_manager_convert_transations.tpl.html');

  $tpl->addData($data);

  $content .= "###get_transactions@@@";
  $content .= $tpl->parse();

  echo $content;
// close connections
include('class/db_close.inc.php');
?>