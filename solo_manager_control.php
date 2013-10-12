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
 $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 8);

 $manager = new Manager();
 $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
 $manager_user = '';
 $players_count = 0;

 $manager_filter_box = $managerbox->getSoloManagerFilterBox($manager->mseason_id);
//print_r($_POST);
  if ($auth->userOn() && isset($_POST['create_team']) && !empty($_POST['team_name'])) {
    $s_fields = array('team_name');
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
    $sdata['USER_ID'] = $auth->getUserId();
    $sdata['SEASON_ID'] = $manager->mseason_id;
    $sdata['DATE_CREATED'] = "NOW()";
    if (isset($_SESSION['external_user']))
      $sdata['SOURCE'] = "'".$_SESSION['external_user']['SOURCE']."'";
    $db->insert('solo_manager_users', $sdata);
    $manager_user_log = new SoloManagerUserLog();
    $manager_user_log->logEvent ($auth->getUserId(), 1, 0, $manager->mseason_id);

//print_r($sdata);
//echo $db->dbNativeErrorText();
    if (isset($_POST['reminder']) && $_POST['reminder'] == 'Y') {
        unset($sdata);
        $sdata['SEASON_ID'] = $manager->mseason_id;
	$sdata['USER_ID'] = $auth->getUserId();
	$sdata['TYPE']=3;
        $actkey = gen_rand_string(0, 10);
        $sdata['UNSUBSCRIBE_KEY'] = "'".$actkey."'";
	$db->insert('reminder_subscribe', $sdata);
    }
    header('Location: solo_manager_control.php');
  }

$has_team = false;
if ($auth->userOn()) {
// initialize user team
 $db->select("solo_manager_users", "*", "USER_ID=".$auth->getUserId()." AND SEASON_ID=".$manager->mseason_id);
 if ((!$row = $db->nextRow()) && !$manager->season_over) {
   $create_team_offer = 1;
 }
 else $has_team = true;

}

  $smarty->clearAllAssign();

  if ($auth->userOn()) {
    if (isset($_POST['appoint'])) {
      $manager->appointPlayer();
    }
    $smarty->assign("user_on", 1);
    $manager_user = new ManagerUser($manager->mseason_id);
    $data['LOGGED']['TOUR_ID'] = $manager->getCurrentTour();
    if ($has_team)
      $solo['TOURS'] = $managerbox->getSoloTours(); 
  }
  else if (!$auth->userOn()) {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_MANAGER_LOGIN');
  } 

  $db->query("commit");

  $smarty->assign("solo", $solo);

  $smarty->assign("sport_id", $manager->sport_id);
  $smarty->assign("filtering", $filtering);
  if (isset($error))  
    $smarty->assign("error", $error);
  $smarty->assign("manager_filter_box", $manager_filter_box);
  if (isset($create_team_offer))
    $smarty->assign("create_team_offer", $create_team_offer);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/solo_manager_control.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/solo_manager_control.smarty'.($stop-$start);

// ----------------------------------------------------------------------------

define(SOLO_MANAGER, 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager.inc.php');

// close connections
include('class/db_close.inc.php');

?>