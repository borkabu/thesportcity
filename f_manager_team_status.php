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

  if (isset($_GET['season_id'])) {
    $manager = new Manager($_GET['season_id']);
    $manager_user = new ManagerUser($manager->mseason_id);
    $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
    if ($_GET['flip'] == 'true' && $auth->userOn()) {
      if ($_GET['param'] == 'allow_view') {
        unset($sdata); 
        if (empty($_SESSION['_user']['MANAGER'][$_GET['season_id']]['ALLOW_VIEW']) || $_SESSION['_user']['MANAGER'][$_GET['season_id']]['ALLOW_VIEW'] == 0)
          $_SESSION['_user']['MANAGER'][$_GET['season_id']]['ALLOW_VIEW'] = 1;
        $_SESSION['_user']['MANAGER'][$_GET['season_id']]['ALLOW_VIEW'] *= -1;
        $sdata['ALLOW_VIEW'] = $_SESSION['_user']['MANAGER'][$_GET['season_id']]['ALLOW_VIEW'];
        $db->update('manager_users', $sdata, 'USER_ID='.$auth->getUserId().' AND SEASON_ID='.$_GET['season_id']);
      } 
      if ($_GET['param'] == 'ignore_leagues') {
        unset($sdata);
        if (empty($_SESSION['_user']['MANAGER'][$_GET['season_id']]['IGNORE_LEAGUES']) || $_SESSION['_user']['MANAGER'][$_GET['season_id']]['IGNORE_LEAGUES'] == 0)
          $_SESSION['_user']['MANAGER'][$_GET['season_id']]['IGNORE_LEAGUES'] = 1;
  
        $_SESSION['_user']['MANAGER'][$_GET['season_id']]['IGNORE_LEAGUES'] *= -1;
        $sdata['IGNORE_LEAGUES'] = $_SESSION['_user']['MANAGER'][$_GET['season_id']]['IGNORE_LEAGUES'];
        $db->update('manager_users', $sdata, 'USER_ID='.$auth->getUserId().' AND SEASON_ID='.$_GET['season_id']);
      } 
      if ($_GET['param'] == 'ignore_challenges') {
        unset($sdata);
        $_SESSION['_user']['MANAGER'][$_GET['season_id']]['IGNORE_CHALLENGES'] *= -1;
        $sdata['IGNORE_CHALLENGES'] = $_SESSION['_user']['MANAGER'][$_GET['season_id']]['IGNORE_CHALLENGES'];
        $db->update('manager_users', $sdata, 'USER_ID='.$auth->getUserId().' AND SEASON_ID='.$_GET['season_id']);
      } 
  
      if ($_GET['param'] == 'reminder') {
        $_SESSION['_user']['MANAGER'][$_GET['season_id']]['REMINDER'] *= -1;
        if ($_SESSION['_user']['MANAGER'][$_GET['season_id']]['REMINDER'] == -1) 
          $db->delete('reminder_subscribe', 'USER_ID='.$auth->getUserId().' AND TYPE=1 AND SEASON_ID='.$_GET['season_id']);
        else {
          unset($sdata);
          $sdata['SEASON_ID'] = $_GET['season_id'];
	$sdata['USER_ID'] = $auth->getUserId();
	$sdata['TYPE']=1;
        $actkey = gen_rand_string(0, 10);
        $sdata['UNSUBSCRIBE_KEY'] = "'".$actkey."'";
	$db->insert('reminder_subscribe', $sdata);
        }
      } 
    }
    $content .= $managerbox->getManagerSummaryTeamStatusBox();
  }

  echo $content;
// close connections
include('class/db_close.inc.php');
?>