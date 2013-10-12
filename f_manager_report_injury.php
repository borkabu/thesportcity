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

// --- build content data -----------------------------------------------------

//$db->showquery=true;
if ($auth->userOn()) {
 if (isset($_POST['report_injury'])) {
  $s_fields = array('link', 'valid_till');
  $d_fields = '';
  $c_fields = '';
  $i_fields = array('player_id', 'season_id', 'status');
 
  $r_fields=array('link', 'valid_till', 'status');
  if(!requiredFieldsOk($r_fields, $_POST)){
	$error['MSG']=$langs['LANG_ERROR_MAND_U'];
  };

// check that user do not submit same state

  if(!isset($error)){
    $udata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);

    $udata['DATE_REPORTED'] = "NOW()";
    $udata['USER_ID'] = $auth->getUserId();
    if ($_POST['status'] == 1)
      $udata['season_id'] = 0;
    $db->insert('manager_player_reports', $udata);  
    unset($udata);
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getMessageBox('MESSAGE_ADD_CONTENT_SUCCESS');
    header('Location: '.$_SERVER['REQUEST_URI']);
  }
 } 
}

$opt['class'] = 'input';
$opt['options'] = $player_state;

if (isset($_GET['player_id']))
  $report_form['PLAYER_ID'] = $_GET['player_id'];
if (isset($_GET['season_id']))
  $report_form['SEASON_ID'] = $_GET['season_id'];

// get current reports
$manager = new Manager($_GET['season_id']);
$sql="SELECT MM.FIRST_NAME, MM.LAST_NAME, MM.TEAM_NAME2, MM.PLAYER_STATE
	from manager_market MM
	WHERE user_id=".$_GET['player_id']."
		AND season_id=".$_GET['season_id'];

$db->query($sql);
$c = 0;
if ($row = $db->nextRow()) {
  $report_form['FIRST_NAME'] = $row['FIRST_NAME'];
  $report_form['LAST_NAME'] = $row['LAST_NAME'];
  $report_form['TEAM_NAME'] = $row['TEAM_NAME2'];
  $manager = new Manager($_GET['season_id']);
  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
//echo $player_state;
  $report_form['PLAYER_STATE_DIV'] = $managerbox->getPlayerStateDiv($_GET['player_id'], $_GET['season_id'], $row['PLAYER_STATE'], false, true);
  if ($row['PLAYER_STATE'] & 1)
    unset($opt['options'][1]);

  if ($row['PLAYER_STATE'] & 2)
    unset($opt['options'][2]);
  else 
    unset($opt['options'][-2]);

  if ($row['PLAYER_STATE'] & 4)
    unset($opt['options'][4]);

  if (!($row['PLAYER_STATE'] & 4) && !($row['PLAYER_STATE'] & 1))
    unset($opt['options'][-1]);

}
$report_form['SEASON_TITLE'] = $manager->title;

$managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
$reports = $managerbox->getPlayerReports($_GET['player_id'], $_GET['season_id'], $opt);

if ($auth->userOn()) {
  if (count($opt['options']) > 0) {
    $report_form['ENABLED']['PLAYER_STATE'] = $frm->getInput(FORM_INPUT_SELECT, 'status', $player_state, $opt, $player_state);
    $report_form['ENABLED_SUBMIT'] = 1;
    $report_form['DATEPICKER'] = "valid_till";
  } else {
    $error['MSG']=$langs['LANG_ERROR_MANAGER_REPORT_LIMIT_U'];
  }
}
else $error['MSG']=$langs['LANG_ERROR_CONTENT_LOGIN_U'];

  if (isset($error)) 
    $smarty->assign("error", $error);

  $smarty->assign("report_form", $report_form);
  $smarty->assign("reports", $reports);

  $start = getmicrotime();
  $content = $smarty->fetch('smarty_tpl/f_manager_report_injury.smarty');
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_report_injury.smarty'.($stop-$start);

include('inc/top_jui.inc.php');
// content
echo $content;

// ----------------------------------------------------------------------------
// include common footer
//include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');
?>