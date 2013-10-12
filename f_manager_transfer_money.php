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

//  $data='';
//$db->showquery=true;

    $sql = "SELECT if (date_add(MU.LAST_TRANSFER_DATE, INTERVAL 24 HOUR) < NOW() OR MU.LAST_TRANSFER_DATE is NULL, 1, 0) CAN_TRANSFER
             FROM users U LEFT JOIN manager_users MU ON U.USER_ID=MU.USER_ID and MU.SEASON_ID=".$manager_user->mseason_id."
			LEFT JOIN manager_standings MS ON U.USER_ID=MS.USER_ID and MS.MSEASON_ID=".$manager_user->mseason_id."
             WHERE U.USER_ID=".$auth->getUserId()." AND SEASON_ID=".$manager_user->mseason_id;
    $db->query($sql); 
    $row = $db->nextRow();

  if ($_GET['money'] > 0 && $_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['PORTFOLIO_PRICE'] + $_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['MONEY_STOCK'] - 1000 >= floor($_GET['money']) && $_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['MONEY_STOCK'] >= floor($_GET['money'])) { 
    $trdata['MONEY'] = $_SESSION['_user']['MANAGER'][$_GET['season_id']]['MONEY'] + floor($_GET['money']);
    $trdata['MONEY_STOCK'] = $_SESSION['_user']['MANAGER'][$_GET['season_id']]['MONEY_STOCK'] - floor($_GET['money']);
    $trdata['LAST_TRANSFER_DATE'] = "NOW()";
    $_SESSION['_user']['MANAGER'][$_GET['season_id']]['MONEY_STOCK'] = $_SESSION['_user']['MANAGER'][$_GET['season_id']]['MONEY_STOCK'] - floor($_GET['money']);
    $db->update('manager_users', $trdata, 'USER_ID='.$auth->getUserId().' AND SEASON_ID='.$_GET['season_id']);

    $manager_user_log = new ManagerUserLog();
    $manager_user_log->logEvent ($auth->getUserId(), 8, $_GET['money'], $_GET['season_id']);

    unset($trdata);
    $content = "money_stock@@@".$_SESSION['_user']['MANAGER'][$_GET['season_id']]['MONEY_STOCK'];
    $content .= "###money@@@".($_SESSION['_user']['MANAGER'][$_GET['season_id']]['MONEY'] +floor($_GET['money']));
    $content .= "###wealth@@@".($_SESSION['_user']['MANAGER'][$_GET['season_id']]['MONEY'] + (isset($_SESSION['_user']['MANAGER'][$_GET['season_id']]['TEAM_PRICE']) ? $_SESSION['_user']['MANAGER'][$_GET['season_id']]['TEAM_PRICE'] : 0) + floor($_GET['money']));
    $content .= "###transfer_money@@@".$langs['LANG_TRANSFER_SUCCESS_U'];
    $_SESSION['_user']['MANAGER'][$_GET['season_id']]['MONEY'] = $_SESSION['_user']['MANAGER'][$_GET['season_id']]['MONEY'] + floor($_GET['money']);
  }
  else

  {
    $content = "money_stock@@@".$_SESSION['_user']['MANAGER'][$_GET['season_id']]['MONEY_STOCK'];
    $content .= "###transfer_money@@@".$langs['LANG_TRANSFER_FAILURE_U'];
  }

//   $data['TRANSFER_MONEY'][0]['MSG'] = $langs['LANG_TRANSFER_STOCK_MONEY_NOT_ALLOW_U']; 
  if ($row['CAN_TRANSFER'] == 0) {
      $data['TRANSFER_MONEY'][0]['MSG'] = $langs['LANG_TRANSFER_STOCK_MONEY_NOT_ALLOW_U']; 
  } else if (floor($_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['MONEY_STOCK']) > 1000) {
    $data['TRANSFER_MONEY'][0]['ALLOW_TRANSFER'][0]['SEASON_ID'] = $manager_user->mseason_id;
    $data['TRANSFER_MONEY'][0]['MSG'] = str_replace("%m", floor($_SESSION['_user']['MANAGER'][$manager_user->mseason_id]['MONEY_STOCK'] - 1000), $langs['LANG_TRANSFER_STOCK_MONEY_INSTR_U']); 
  }

  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/bar_manager_transfer_money.tpl.html');

  $tpl->addData($data);
//print_r($data);
  $content .= $tpl->parse();

  echo $content;
// close connections
include('class/db_close.inc.php');
?>