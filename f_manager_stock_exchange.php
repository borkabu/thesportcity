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
 $content = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);

 $manager = new Manager();
 $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);

 $manager_user = '';
 $players_count = 0;

 $manager_filter_box = $managerbox->getManagerFilterBox($manager->mseason_id);
//print_r($_POST);

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
      'CURRENT_VALUE_MONEY' => 'LANG_CURRENT_PRICE_U',
      'TOTAL_POINTS' => 'LANG_POINTS_U',
      'PLAYED' => 'LANG_PLAYED_U'
    )
  );

  $opt_pos = array(
    'class' => 'input',
    'options' => $position_types[$manager->sport_id]
  );

  $filtering['WHERE_POS'] = $frm->getInput(FORM_INPUT_SELECT, 'where_pos', isset($_GET['where_pos']) ? $_GET['where_pos'] : '', $opt_pos, isset($_GET['where_pos']) ? $_GET['where_pos'] : '');
  $filtering['WHERE_TEAM'] = inputManagerTeams($manager->mseason_id, 'where_team', isset($_GET['where_team']) ? $_GET['where_team'] : '', 80, true);
  $filtering['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
  $filtering['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

  $param['where'] = '';
  if (!empty($_GET['query'])) {
    $param['where'] = " AND UPPER(LAST_NAME) like UPPER('%".$_GET['query']."%') ";
    $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
  }

  $param['where_pos'] = '';
  if (!empty($_GET['where_pos'])) {
    $param['where'] .= " AND (POSITION_ID1=".$_GET['where_pos']." OR POSITION_ID2=".$_GET['where_pos'].") ";
    $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
  }

  $param['where_team'] = '';
  if (!empty($_GET['where_team'])) {
    $param['where'] .= " AND MM.TEAM_ID=".$_GET['where_team'];
    $filtering['FILTERED']['SEASON_ID'] = $manager->mseason_id;
  }

  $current_tour = $manager->getLastTour();

  $stock_status = array();
  if ($manager->season_over) {
    $stock_status['SEASON_OVER'] = 1;      
  } else if ($current_tour >= 1) {
    $stock_status['STOCK'] = 1;   
  } else if ($current_tour == 0) {
    $stock_status['NOSTOCK'] = 1;   
  }

  if (!$auth->userOn()) {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_LOGIN');
  } else if (!$manager->allow_stock) {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_STOCK_OFF');
  } else {
    $manager_user = new ManagerUser($manager->mseason_id);

    $data['LOGGED'][0]['TOUR_ID'] = $manager->getCurrentTour();
   
    if (!$manager->manager_trade_allow)
     $manager->closeMarket();

    $manager->getLastTour();
    $manager->getNextTour();
    $manager->countTourGamesPerTeam($manager->next_tour);

    // buy user
    if (isset($_POST['buy'])) {
      $manager_user->getPortfolio();
      $outcome = $manager_user->buyPlayerStock();
      $error = array();
      if ($outcome == -1) {
        $error['MSG'] = $langs['LANG_NOT_ENOUGH_MONEY_U'];
      } else if ($outcome == -2) {
        $error['MSG'] = $langs['LANG_ERROR_MANAGER_STOCK_LIMIT'];
      } else if ($outcome == -3) {
        $error['MSG'] = $langs['LANG_ERROR_MANAGER_WRONG_QUANTITY'];
      }

    } else if (isset($_POST['sell'])) {
       $outcome = $manager_user->sellPlayerStock();
       if ($outcome == -3) {
         $error['MSG'] = $langs['LANG_ERROR_MANAGER_WRONG_QUANTITY'];
       }
    }
    
    $portfolio = $managerbox->getPortfolio();
 }

 if ($manager->allow_stock) {
/// market
  if (!empty($_GET['order']))
    $param['order'] = $_GET['order'];
  else $param['order'] = "MMS.TEAMS/10 + MMS.SHARES/1000 DESC, START_VALUE DESC, MM.TEAM_NAME2, LAST_NAME, FIRST_NAME ";

  $stock['STOCK'] = $managerbox->getStockExchange($param['where'], $param['where_int'], $param['order']); // $data['PLAYERS'];

  $filtering['ORDER'] = $param['order'];

  // paging
  $stock['PAGING'] = $pagingbox->getPagingBox($manager->getMarketSize(), isset($_GET['page']) ? $_GET['page'] : 0);

  $db->query("commit");
  // add data


  $smarty->clearAllAssign();
  if ($manager->allow_stock)
    $smarty->assign("allow_stock", 1);
  $smarty->assign("stock", $stock);
  $smarty->assign("filtering", $filtering);
  $smarty->assign("stock_status", $stock_status);
  if (isset($portfolio))
    $smarty->assign("portfolio", $portfolio);
 }

  if (isset($error))  
    $smarty->assign("error", $error);
  $smarty->assign("manager_filter_box", $manager_filter_box);
 
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_stock_exchange.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_stock_exchange.smarty'.($stop-$start);
 
// ----------------------------------------------------------------------------

// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager.inc.php');

// close connections
include('class/db_close.inc.php');

?>
