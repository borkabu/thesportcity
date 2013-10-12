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
  $manager = new Manager($_GET['season_id']);
  $manager->getCurrentTour();

  if ($manager->manager_trade_allow) {
    $manager_user = new ManagerUser($manager->mseason_id);

    $result = $manager_user->setCaptain($_GET['entry_id']);
   
    if ($result['NEW_CAPTAIN'] < 0) {
      return '';
    }
    else {
      $new_captain['ENTRY_ID'] = $result['NEW_CAPTAIN'];
      if (isset($result['OLD_CAPTAIN'])) {
         $old_captain['ENTRY_ID'] = $result['OLD_CAPTAIN'];
         $old_captain['SEASON_ID'] = $manager->mseason_id;
      }
      $_SESSION['_user']['MANAGER'][$manager->mseason_id]['CAPTAIN'] = 1;
      $team_quality = round(($_SESSION['_user']['MANAGER'][$manager->mseason_id]['ACT_TEAM_SIZE'] + $_SESSION['_user']['MANAGER'][$manager->mseason_id]['CAPTAIN']) * 100 / ($manager->max_players + 1), 2) - round( $_SESSION['_user']['MANAGER'][$manager->mseason_id]['MONEY']/2000, 2);
  
      $smarty->assign("team_quality", $team_quality);
      $smarty->assign("new_captain", $new_captain);
      $smarty->assign("old_captain", $old_captain);
      $start = getmicrotime();
      $content .= $smarty->fetch('smarty_tpl/bar_manager_set_captain.smarty');    
      $stop = getmicrotime();
      if (isset($_GET['debugphp']))
        echo 'smarty_tpl/bar_manager_set_captain.smarty'.($stop-$start);
   
      echo $content;
    }
  }
  else {
    $manager->closeMarket();
    return '';
  }
// close connections
include('class/db_close.inc.php');
?>