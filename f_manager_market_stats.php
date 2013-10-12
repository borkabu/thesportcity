<?php
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

// --- build content data -----------------------------------------------------

 $content = '';
 $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);

 $manager = new Manager();
 $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
 $manager_user = '';
 $players_count = 0;

 $manager_user = new ManagerUser($manager->mseason_id);

 $manager_filter_box = $managerbox->getManagerFilterBox($manager->mseason_id);
//$db->showquery=true;
  $filtering['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_POST['query']) ? $_POST['query'] : '', array('class' => 'input'));
  if (isset($_POST['query'])) {
    $filtering['FILTERED'] = 1;
  }

  $players = $manager->getMarketStats(isset($_POST['query']) ? $_POST['query'] : "", isset($_GET['page']) ? $_GET['page'] : 1, isset($_GET['page_size']) ? $_GET['page_size'] : PAGE_SIZE); 
     
  if (isset($players['COUNT']) && $players['COUNT'] > 0) {
    $market['PLAYERS'] = $players['PLAYERS'];
    $market['PAGING'] = $pagingbox->getPagingBox($players['COUNT'], isset($_GET['page']) ? $_GET['page'] : 0);  
  }
  $market['CHANGED'] = $players['CHANGED'];

  $sql = "SELECT DISTINCT MUT.TOUR_ID
		 FROM manager_users_tours MUT
		WHERE MUT.SEASON_ID=".$manager->mseason_id." 
         ORDER BY MUT.TOUR_ID";

  $db->query($sql);   
  $c = 0;
  $tours = array();
  while ($row = $db->nextRow()) {
    unset($tour);
    $state = 'NORMAL'; 
    if (isset($_GET['tour_id']) && $row['TOUR_ID'] == $_GET['tour_id'])
      $state = 'SELECTED'; 
    $tour[$state] = $row;
    $tour[$state]['NUMBER'] = $row['TOUR_ID'];
    $tour[$state]['MSEASON_ID'] = $manager->mseason_id;
    $tours[] = $tour;
  }
  if (isset($_GET['tour_id'])) {
    $all['NORMAL']['MSEASON_ID'] = $manager->mseason_id;;
  } else {
    $all['SELECTED'] = 1;
  }

  if ($manager->manager_trade_allow && !isset($_GET['tour_id'])) {
    $smarty->assign("noaccess", 1);
  }
  $smarty->assign("market", $market);
  $smarty->assign("manager_filter_box", $manager_filter_box);
  $smarty->assign("tours", $tours);
  $smarty->assign("all", $all);
  $smarty->assign("filtering", $filtering);
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_market_stats.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_market_stats.smarty'.($stop-$start);


// ----------------------------------------------------------------------------
  define("FANTASY_MANAGER", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager.inc.php');

// close connections
include('class/db_close.inc.php');

?>