<?php
ini_set('display_errors', 1);
error_reporting (E_ALL & ~E_NOTICE);
//return '';

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
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/manager.inc.php');
include('class/manager_user.inc.php');
include('class/newsletter.inc.php');
include('lib/manager_config.inc.php');

// --- build content data -----------------------------------------------------
//$db->showquery=true;
 $db->query("start transaction");

 $content = '';
 $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);

 $manager = new Manager();
 $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
 $manager_user = '';
 $players_count = 0;

 $manager_filter_box = $managerbox->getManagerFilterBox($manager->mseason_id);
//print_r($_POST);
  if ($auth->userOn() && isset($_POST['create_team']) && !empty($_POST['team_name'])) {
    $s_fields = array('team_name');
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
    $sdata['USER_ID'] = $auth->getUserId();
    $sdata['MONEY'] = $manager->default_money;
    $sdata['TRANSACTIONS'] = $manager->default_transactions;
    $sdata['SEASON_ID'] = $manager->mseason_id;
    $sdata['DATE_CREATED'] = "NOW()";
    if (isset($_SESSION['external_user']))
      $sdata['SOURCE'] = "'".$_SESSION['external_user']['SOURCE']."'";
    $db->insert('manager_users', $sdata);
    $manager_user_log = new ManagerUserLog();
    $manager_user_log->logEvent ($auth->getUserId(), 1, 0, $manager->mseason_id);

//print_r($sdata);
//echo $db->dbNativeErrorText();
    // subscribe to newsletter
    if (isset($_POST['newsletter']) && $_POST['newsletter'] == 'Y') {
      $newsletter = new Newsletter();
      $newsletter->subscribe($manager->newsletter_id, $auth->getUserId());
    }
    if (isset($_POST['reminder']) && $_POST['reminder'] == 'Y') {
        unset($sdata);
        $sdata['SEASON_ID'] = $manager->mseason_id;
	$sdata['USER_ID'] = $auth->getUserId();
	$sdata['TYPE']=1;
        $actkey = gen_rand_string(0, 10);
        $sdata['UNSUBSCRIBE_KEY'] = "'".$actkey."'";
	$db->insert('reminder_subscribe', $sdata);
    }
    header('Location: f_manager_control.php');
  }

$has_team = false;
if ($auth->userOn() && !$manager->season_over) {
// initialize user team
 $db->select("manager_users", "*", "USER_ID=".$auth->getUserId()." AND SEASON_ID=".$manager->mseason_id);
 if (!$row = $db->nextRow()) {
   $create_team_offer = 1;
 }
 else $has_team = true;
}

  // filtering
  $opt = array(
    'class' => 'input',
    'options' => array(
      'LAST_NAME' => 'LANG_SURNAME_U'
    )
  );
  $opt_int = array(
    'class' => 'input',
    'options' => array(
      'MM.CURRENT_VALUE_MONEY' => 'LANG_CURRENT_PRICE_U',
      'MM.TOTAL_POINTS' => 'LANG_POINTS_U',
      'MM.PLAYED' => 'LANG_PLAYED_U'
    )
  );
  if ($auth->hasSupporter()) {
    $opt_int['options']['MTGD.TIMES'] = 'LANG_WILL_PLAY_NEXT_TOUR_U';
  }

  $opt_pos = array(
    'class' => 'input',
    'options' => $position_types[$manager->sport_id],
    'multiple' => true,
    'size' => count($position_types[$manager->sport_id]),
  );

  $current_tour = $manager->getCurrentTour();
  $market_status = array();
  if ($manager->season_over) {
    $market_status['SEASON_OVER'] = 1;      
  } else if (isset($manager->next_tour_date) && $manager->manager_trade_allow) {
    $market_status['MARKET_OPEN']['START_DATE'] = $manager->next_tour_date_utc;   
    $market_status['MARKET_OPEN']['UTC'] = $manager->utc;   
  } else if (isset($manager->current_tour_end_date)) {
    $market_status['NOMARKET']['START_DATE'] = $manager->current_tour_end_date;   
    $market_status['NOMARKET']['UTC'] = $manager->utc;   
  }
  else if (!$manager->manager_trade_allow)
     $market_status['NOMARKET_DELAY'] = 1;   

    $manager->getTeamLimit();
    $manager->getNextTour();
    $manager->countTourGamesPerTeam($manager->next_tour);
    $manager->countReportsPerPlayer();
    $manager->getLastTour();

    if (!$auth->userOn()) {
      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
      $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_LOGIN');
    } 
    else {
      $manager_user = new ManagerUser($manager->mseason_id);
  
      $data['LOGGED']['TOUR_ID'] = $manager->getCurrentTour();
     
     if (!$manager->manager_trade_allow)
       $manager->closeMarket();
   
     if ($has_team) {
  
      // buy user
      if (isset($_POST['buy'])) {
        $manager_user->getTeam($current_tour, $manager->last_tour);
        $outcome = $manager_user->buyPlayer();
        if ($outcome == -1) {
          $error['MSG'] = $langs['LANG_NOT_ENOUGH_MONEY_U'];
        } else if ($outcome == -2) {
          $error['MSG'] = $langs['LANG_ERROR_MANAGER_PLAYER_LIMIT'];
        } else if ($outcome == -3) {
          $error['MSG'] = $langs['LANG_MARKET_TOO_MANY_POSITIONS_U'];
        }
      }
      else if (isset($_POST['sell'])) {
        $outcome = $manager_user->sellPlayer();
      }
      else if (isset($_POST['substitute'])) {
        $manager_user->getTeam($current_tour, $manager->last_tour);
        $outcome = $manager_user->substitutePlayer();
	if ($outcome == -3) {
          $error['MSG'] = $langs['LANG_MARKET_TOO_MANY_POSITIONS_U'];
        }
      }
      else if (isset($_POST['unset_substitute'])) {
        $outcome = $manager_user->unsubstitutePlayer();
      } 
      else if (isset($_POST['main2substitute'])) {
	$_POST['sell'] = 'y';
        if ($manager_user->sellPlayer()) {
          $manager_user->getTeam($current_tour, $manager->last_tour);
  	  //$_POST['substitute'] = 'y';
          $outcome = $manager_user->substitutePlayer();
  	  if ($outcome == -3) {
            $error['MSG'] = $langs['LANG_MARKET_TOO_MANY_POSITIONS_U'];
          }
        }
      }
       
      $team = $managerbox->getTeam($current_tour, $manager->last_tour);
    } 
  }

/// market
  if (!empty($_GET['order']))
    $param['order'] = $_GET['order'];
  else $param['order'] = "MM.CURRENT_VALUE_MONEY DESC, MM.START_VALUE DESC, MM.TEAM_NAME2, MM.LAST_NAME, MM.FIRST_NAME ";

  $filtering['ORDER'] = $param['order'];
//  $data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
  if (isset($_POST['filter']) && $_POST['filter']=y) {
    $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_int'] = $_POST['where_int'];
    $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_pos'] = $_POST['where_pos'];
    $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_team'] = $_POST['where_team'];
    $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query'] = $_POST['query'];
    $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query_less'] = $_POST['query_less'];
    $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query_more'] = $_POST['query_more'];
    $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_active'] = $_POST['where_active'];
    $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_active_last_tour'] = $_POST['where_active_last_tour'];
    $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_marked'] = $_POST['where_marked'];
  }
  else if (isset($_POST['filter']) && $_POST['filter']=n) {
   unset($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']);
  }

  if (!$auth->hasSupporter() && $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_int'] == 'MTGD.TIMES') {
    unset($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_int']);
  }

  $filtering['WHERE_INT'] = $frm->getInput(FORM_INPUT_SELECT, 'where_int', $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_int'], $opt_int, $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_int']);
  $filtering['WHERE_POS'] = $frm->getInput(FORM_INPUT_SELECT, 'where_pos', $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_pos'], $opt_pos, $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_pos']);
  $filtering['WHERE_TEAM'] = inputManagerTeams($manager->mseason_id, 'where_team', $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_team'], 80, true);
  $filtering['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query'], array('class' => 'input'));
  $filtering['QUERY_LESS'] = $frm->getInput(FORM_INPUT_TEXT, 'query_less', $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query_less'], array('class' => 'input_short', 'maxlength' => '5'));
  $filtering['QUERY_MORE'] = $frm->getInput(FORM_INPUT_TEXT, 'query_more', $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query_more'], array('class' => 'input_short', 'maxlength' => '5'));
  $filtering['WHERE_ACTIVE'] = $frm->getInput(FORM_INPUT_CHECKBOX, 'where_active', 1, array('class' => 'input'), $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_active'] == 1 ? 1 : '');
  $filtering['WHERE_MARKED'] = $frm->getInput(FORM_INPUT_CHECKBOX, 'where_marked', 1, array('class' => 'input'), $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_marked'] == 1 ? 1 : '');
  $filtering['WHERE_ACTIVE_LAST_TOUR'] = $frm->getInput(FORM_INPUT_CHECKBOX, 'where_active_last_tour', 1, array('class' => 'input'), $_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_active_last_tour'] == 1 ? 1 : '');
  $filtering['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

  $param['where'] = '';
  if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query'])) {
    $param['where'] = " AND UPPER(LAST_NAME) like UPPER('%".$_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query']."%') ";
    $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
  }

  $param['where_pos'] = '';
  if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_pos'])) {
    $positions = "";
    $pre = "";
    foreach($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_pos'] as $poskey => $posvalue) {
      if ($posvalue == 0) 
        continue;
      $positions .= $pre.$posvalue;
      $pre = ",";
    }
    if ($positions != "") {
      $param['where'] .= " AND (POSITION_ID1 IN (".$positions.") OR POSITION_ID2 IN (".$positions.")) ";
      $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
    }
  }

  $param['where_team'] = '';
  if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_team'])) {
    $param['where'] .= " AND MM.TEAM_ID=".$_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_team'];
    $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
  }

  if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_active'])) {
    $param['where'] .= " AND MM.PLAYED>0 ";
    $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
  }

  if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_active_last_tour'])) {
    $param['where'] .= " AND MPS.PLAYED>0 ";
    $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
  }

  if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_marked'])) {
    $param['where'] .= " AND MPM.USER_ID IS NOT NULL ";
    $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
  }

  $param['where_int'] = '';
  if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query_less']) ||
      !empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query_more'])) {
    $param['where_int'] = "";
    if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query_less']))
      $param['where_int'] .= " AND ".$_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_int']." >= ".$_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query_less'];
    if (!empty($_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query_more']))
      $param['where_int'] .= " AND ".$_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['where_int']." <= ".$_SESSION['_user']['MANAGER'][$manager->mseason_id]['FILTER']['query_more'];
    $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
  }

//echo $param['order'];
  $market['MARKET'] = $managerbox->getMarket($param['where'], $param['where_int'], $param['order']); 
   
  // paging
  $market['PAGING'] = $pagingbox->getPagingBox($manager->getMarketSize(), isset($_GET['page']) ? $_GET['page'] : 0);

  $db->query("commit");
  // add data

  $smarty->clearAllAssign();
  $smarty->assign("market", $market);
  if ($manager->allow_substitutes == true)
    $smarty->assign("allow_subsitutes", 1);
  if ($auth->userOn()) {
    $smarty->assign("user_on", 1);
  }

  $smarty->assign("sport_id", $manager->sport_id);
  $smarty->assign("filtering", $filtering);
  $smarty->assign("market_status", $market_status);
  if (isset($error))  
    $smarty->assign("error", $error);
  $smarty->assign("manager_filter_box", $manager_filter_box);
  if (isset($team))
    $smarty->assign("team", $team);
  if (isset($create_team_offer))
    $smarty->assign("create_team_offer", $create_team_offer);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_control.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_control.smarty'.($stop-$start);

// ----------------------------------------------------------------------------

define(FANTASY_MANAGER, 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager.inc.php');

// close connections
include('class/db_close.inc.php');

?>