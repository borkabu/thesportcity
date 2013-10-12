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
include('class/newsletter.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 6);

//$db->showquery=true;
  $wager = new Wager();
  $wager_filter_box = $wagerbox->getWagerFilterBox($wager->getSeason());

  if ($auth->userOn() && isset($_POST['create_account'])) {
    unset($sdata);
    $sdata['USER_ID'] = $auth->getUserId();
//    $sdata['MONEY'] = 0; //$wager->default_money;
    $sdata['SEASON_ID'] = $wager->tseason_id;
    $db->insert('wager_users', $sdata);
    $wager_user_log = new WagerUserLog();
    $wager_user_log->logEvent ($auth->getUserId(), 1, 0, 0, $wager->tseason_id);

    // subscribe to newsletter
    if (isset($_POST['newsletter']) && $_POST['newsletter'] == 'Y') {
      $newsletter = new Newsletter();
      $newsletter->subscribe($wager->newsletter_id, $auth->getUserId());
    }
    header('Location: wager_control.php');
  }

  $has_account = false;
  if ($auth->userOn()) {
  // initialize user team
    $db->select("wager_users", "*", "USER_ID=".$auth->getUserId()." AND SEASON_ID=".$wager->tseason_id);
    if (!$row = $db->nextRow()) {
      if (!$wager->season_over)
        $create_account_offer = 1;
    }
    else $has_account = true;
  }

  if ($wager->season_over) {
    $wager_status['MSG'] = $langs['LANG_SEASON_OVER_U'];
  } 

  if (!$auth->userOn()) {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_WAGER_LOGIN');
    $sched = $wagerbox->getWagerGamesBox($wager->tseason_id, 1, 20);
  } 
  else {
    $wager_user = new WagerUser($wager->tseason_id);
   
    if ($has_account) {
      $processed_bets = '';
      if (isset($_POST['multiple_bets'])) {
        $processed_bets = $wager_user->processMultipleBets(); 
      } 
//      if ($_SESSION['_user']['WAGER'][$wager->tseason_id]['MAX_STAKE'] == 30)
  //      $logged['SUMMARY'] = 1;
      if (isset($_GET['past']) && $_GET['past'] == 'y') {
        $logged = $wager_user->getGames(0);
        $gamemenu['PAST_GAMES'] = 1;
      }
      else if (isset($_GET['present']) && $_GET['present'] == 'y') {
        $logged = $wager_user->getGames(1);
        $gamemenu['PRESENT_GAMES'] = 1;
      }
      else {
        $logged = $wager_user->getGames(2, 1, 10, $processed_bets);
        $gamemenu['FUTURE_GAMES'] = 1;
//        if (!$wager->season_over)  
//          $logged['FUTURE_SUBMIT_ALL'] = 1;
      }
    }
  }

  if (isset($error))  
    $smarty->assign("error", $error);
  if (isset($logged))  
    $smarty->assign("logged", $logged);
  if (isset($sched))  
    $smarty->assign("sched", $sched);
  $smarty->assign("credits", $auth->getCredits());


  if (isset($wager_status))  
    $smarty->assign("wager_status", $wager_status);
  $smarty->assign("wager_filter_box", $wager_filter_box);
  if (isset($gamemenu))  
    $smarty->assign("gamemenu", $gamemenu);
  if (isset($create_account_offer))
    $smarty->assign("create_account_offer", $create_account_offer);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/wager_control.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/wager_control.smarty'.($stop-$start);

  define("WAGER", 1);
  include('inc/top.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_wager.inc.php');

// close connections
include('class/db_close.inc.php');
?>