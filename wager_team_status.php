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
  if (isset($_GET['season_id'])) {  
    $wager = new Wager($_GET['season_id']);
    $wager_user = new WagerUser($_GET['season_id']);
    $wagerbox = new WagerBox($langs, $_SESSION["_lang"]);
    if ($_GET['flip'] == 'true' && $auth->userOn()) {
      if ($_GET['param'] == 'ignore_leagues') {
        unset($sdata);
        if (empty($_SESSION['_user']['WAGER'][$_GET['season_id']]['IGNORE_LEAGUES']) || $_SESSION['_user']['WAGER'][$_GET['season_id']]['IGNORE_LEAGUES'] == 0)
          $_SESSION['_user']['WAGER'][$_GET['season_id']]['IGNORE_LEAGUES'] = 1;
  
        $_SESSION['_user']['WAGER'][$_GET['season_id']]['IGNORE_LEAGUES'] *= -1;
        $sdata['IGNORE_LEAGUES'] = $_SESSION['_user']['WAGER'][$_GET['season_id']]['IGNORE_LEAGUES'];
        $db->update('wager_users', $sdata, 'USER_ID='.$auth->getUserId().' AND SEASON_ID='.$_GET['season_id']);
      } 
      if ($_GET['param'] == 'stake_slider') {
        unset($sdata);
        if (empty($_SESSION['_user']['WAGER'][$_GET['season_id']]['STAKE_SLIDER']) || $_SESSION['_user']['WAGER'][$_GET['season_id']]['IGNORE_LEAGUES'] == 0)
          $_SESSION['_user']['WAGER'][$_GET['season_id']]['STAKE_SLIDER'] = 1;
  
        $_SESSION['_user']['WAGER'][$_GET['season_id']]['STAKE_SLIDER'] *= -1;
        $sdata['STAKE_SLIDER'] = $_SESSION['_user']['WAGER'][$_GET['season_id']]['STAKE_SLIDER'];
        $db->update('wager_users', $sdata, 'USER_ID='.$auth->getUserId().' AND SEASON_ID='.$_GET['season_id']);
      } 

    }
    $content .= $wagerbox->getWagerSummaryTeamStatusBox();
  }

  echo $content;
// close connections
include('class/db_close.inc.php');
?>