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
include('class/bracket.inc.php');
include('class/bracket_user.inc.php');
include('class/newsletter.inc.php');
// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

// include common header
  $content = '';
  $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 7);

  $bracket = new Bracket();
  $bracketbox = new BracketBox($langs, $_SESSION["_lang"]);
  $bracket_filter_box = $bracketbox->getBracketFilterBox($bracket->tseason_id);

  if ($auth->userOn() && isset($_POST['create_account'])) {
    $sdata['USER_ID'] = $auth->getUserId();
    $sdata['SEASON_ID'] = $bracket->tseason_id;
    $db->insert('bracket_users', $sdata);
    $bracket_user_log = new BracketUserLog();
    $bracket_user_log->logEvent ($auth->getUserId(), 1, $bracket->tseason_id);

//print_r($sdata);
//echo $db->dbNativeErrorText();
    // subscribe to newsletter
    if (isset($_POST['newsletter']) && $_POST['newsletter'] == 'Y') {
      $newsletter = new Newsletter();
      $newsletter->subscribe($bracket->newsletter_id, $auth->getUserId());
    }
    header('Location: bracket_control.php');
  }

  $has_account = false;
  if ($auth->userOn()) {
  // initialize user team
    $db->select("bracket_users", "*", "USER_ID=".$auth->getUserId()." AND SEASON_ID=".$bracket->tseason_id);
    if (!$row = $db->nextRow()) {
      if (!$bracket->season_over)
        $create_account_offer = 1;
    }
    else $has_account = true;
  }

  if (isset($_POST['race_id']))
    $_SESSION['current_race'] = $_POST['race_id'];
  else if (isset($_GET['race_id']))
    $_SESSION['current_race'] = $_GET['race_id'];

  if ($auth->userOn() && $has_account && isset($_POST['random_arrangement'])) {
    $bracket_user = new BracketUser($bracket->tseason_id);
    $process_result = $bracket_user->processRandomArrangement();
    header('Location: bracket_control.php');
  }
  if ($auth->userOn() && $has_account && isset($_POST['copy_results'])) {
    $bracket_user = new BracketUser($bracket->tseason_id);
    $process_result = $bracket_user->copyLastRaceResults();
    header('Location: bracket_control.php');
  }
  if ($auth->userOn() && $has_account && isset($_POST['copy_arrangement'])) {
    $bracket_user = new BracketUser($bracket->tseason_id);
    $process_result = $bracket_user->copyLastRaceArrangement();
    header('Location: bracket_control.php');
  }

  
  if ($bracket->season_over) {
    $arranger_status['MSG'] = $langs['LANG_SEASON_OVER_U'];
  } 

  if (!$auth->userOn()) {
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_ARRANGER_LOGIN');
  } else {
    
//$db->showquery=true;
   if ($has_account) {
      $bracket_user = new BracketUser($bracket->tseason_id);
      if (isset($_POST['save_arrangement'])) {
        $process_result = $bracket_user->processArrangement();
        if ($process_result == 1) {
         $logged['FUTURE_RACES']['ARRANGEMENT_SUBMITED']=1;
        } else if ($process_result == -1) {
        } else if ($process_result == -2) {       
        } else if ($process_result == -3) {       
         $logged['FUTURE_RACES']['NOT_ENOUGH_PILOTS']=1;
        }

      }

      if ($bracket->racesLeft() > 0) {
        $logged['FUTURE_RACES']['SEASON_ID'] = $bracket->tseason_id;
        $logged['FUTURE_RACES']['ARRANGER_RACES_FILTER_BOX'] = inputBracketRaces("race_id", $bracket->tseason_id, 1, isset($_SESSION['current_race']) ? $_SESSION['current_race'] : '');
        $next_race = -1;
        if (isset($_POST['race_id'])) {
          $next_race = $_POST['race_id'];
        } else {
          $next_race = $bracket->getNextRaceID();
	}
        $logged['FUTURE_RACES']['RACE'][0] = $bracket_user->getRace($next_race);
        if ($_SESSION['_user']['ARRANGER'][$bracket->tseason_id]['USE_DRAGDROP'] == 1)
          $logged['USE_DRAGDROP'] = 1;
      } 

      if ($bracket->racesPast() > 0) {
        $logged['PAST_RACES']['SEASON_ID'] = $bracket->tseason_id;
        $logged['PAST_RACES']['ARRANGER_RACES_FILTER_BOX'] = inputBracketRaces("prev_race_id", $bracket->tseason_id, 0);
        $prev_race = -1;
        if (isset($_POST['prev_race_id'])) {
          $prev_race = $_POST['prev_race_id'];
        } else {
          $prev_race = $bracket->getPrevRaceID();
	}
        $logged['PAST_RACES']['RACE'][0] = $bracket_user->getRaceResult($prev_race);

      } 
    }
  }
  $smarty->assign("bracket_filter_box", $bracket_filter_box);
  if (isset($create_account_offer))
    $smarty->assign("create_account_offer", $create_account_offer);
  if (isset($arranger_status))
    $smarty->assign("arranger_status", $arranger_status);
  if (isset($logged))
    $smarty->assign("logged", $logged);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/bracket_control.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/bracket_control.smarty'.($stop-$start);

  define("ARRANGER", 1);
  include('inc/top.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot_arranger.inc.php');

// close connections
include('class/db_close.inc.php');
?>