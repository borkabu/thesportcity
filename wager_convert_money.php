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
include('class/wager.inc.php');
include('class/wager_user.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
  $wager_user = new WagerUser($_GET['season_id']);

  $data='';
//$db->showquery=true;
  if ($_SESSION['_user']['CREDIT'] > 1 && $_SESSION['_user']['WAGER'][$_GET['season_id']]['WEALTH'] < 100) { 
    $credits = new Credits();
    $credits->updateCredits ($auth->getUserId(), -1);
    $credit_log = new CreditsLog();
    $credit_log->logEvent ($auth->getUserId(), 9, 1);
    unset($trdata);
    $trdata['MONEY'] = $_SESSION['_user']['WAGER'][$_GET['season_id']]['MONEY'] + 100;
    $trdata['REFILLED'] = "REFILLED + 1";
    $_SESSION['_user']['WAGER'][$_GET['season_id']]['MONEY'] = $_SESSION['_user']['WAGER'][$_GET['season_id']]['MONEY'] + 100;
    $_SESSION['_user']['WAGER'][$_GET['season_id']]['WEALTH'] = $_SESSION['_user']['WAGER'][$_GET['season_id']]['WEALTH'] + 100;
    $db->update('wager_users', $trdata, 'USER_ID='.$auth->getUserId().' AND SEASON_ID='.$_GET['season_id']);
    unset($trdata);
    $content = "wager_money@@@".$_SESSION['_user']['WAGER'][$_GET['season_id']]['MONEY'];
    $content .= "###wager_wealth@@@".$_SESSION['_user']['WAGER'][$_GET['season_id']]['WEALTH'];
    $content .= "###credits@@@".$_SESSION['_user']['CREDIT'];
    $wager_user_log = new WagerUserLog();
    $wager_user_log->logEvent ($auth->getUserId(), 5, 100, 1, $_GET['season_id']);

  }
  else {
    $content = "wager_money@@@".$_SESSION['_user']['WAGER'][$_GET['season_id']]['MONEY'];
  }

  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/bar_wager_convert_money.tpl.html');

  $tpl->addData($data);

  $content .= "###get_money@@@";
  $content .= $tpl->parse();

  echo $content;
// close connections
include('class/db_close.inc.php');
?>