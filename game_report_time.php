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
include('class/game.inc.php');
include('class/gamebox.inc.php');
$gamebox = new GameBox($langs, $_SESSION['_lang']);

// --- build content data -----------------------------------------------------

//$db->showquery=true;
if ($auth->userOn()) {
 if (isset($_POST['report_time'])) {
  $s_fields = array('link');
  $d_fields = '';
  $c_fields = '';
  $i_fields = array('game_id');
 
  $r_fields=array('link');
  if(!requiredFieldsOk($r_fields, $_POST)){
	$error['MSG']=$langs['LANG_ERROR_MAND_U'];
  };

// check that user do not submit same state

  if(!isset($error)){
    $udata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);

    $udata['DATE_REPORTED'] = "NOW()";
    $udata['USER_ID'] = $auth->getUserId();
    $udata['REPORTED_START_DATE'] = "DATE_ADD('".$_POST['game_start_date']."', INTERVAL -".$auth->getUserTimezone()." HOUR)";
    $db->insert('games_reports', $udata);  
    unset($udata);
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getMessageBox('MESSAGE_ADD_CONTENT_SUCCESS');
    header('Location: '.$_SERVER['REQUEST_URI']);
  }
 } 
}

if (isset($_GET['game_id']))
  $report_form['GAME_ID'] = $_GET['game_id'];

// get current reports
$sql="SELECT SD.SEASON_TITLE, DATE_ADD(G.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE) AS START_DATE, 
             UNIX_TIMESTAMP(DATE_ADD(G.START_DATE, INTERVAL " .($auth->getUserTimezone()*60). " MINUTE)) TMSTP,
 	     IF(T1.TEAM_TYPE = 1, T1.TEAM_NAME2, CD1.COUNTRY_NAME) TEAM_NAME1, IF(T2.TEAM_TYPE = 1, T2.TEAM_NAME2, CD2.COUNTRY_NAME) TEAM_NAME2
	from seasons S
	      left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id']."
              , games G
		LEFT JOIN teams T1 ON G.TEAM_ID1 = T1.TEAM_ID
			LEFT JOIN countries_details CD1 ON CD1.ID=T1.COUNTRY AND T1.TEAM_TYPE=2 AND CD1.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN teams T2 ON G.TEAM_ID2 = T2.TEAM_ID
			LEFT JOIN countries_details CD2 ON CD2.ID=T2.COUNTRY AND T2.TEAM_TYPE=2 AND CD2.LANG_ID=".$_SESSION['lang_id']."

	WHERE G.SEASON_ID=S.SEASON_ID
		AND G.game_id=".$_GET['game_id'];
//echo $sql;
$db->query($sql);
$c = 0;
if ($row = $db->nextRow()) {
  $report_form['SEASON_TITLE'] = $row['SEASON_TITLE'];
  $report_form['TEAM_NAME1'] = $row['TEAM_NAME1'];
  $report_form['TEAM_NAME2'] = $row['TEAM_NAME2'];
  $report_form['START_DATE'] = $row['START_DATE'];
  $report_form['UTC'] = $auth->getUserTimezoneName();

  $currentTime = $row['TMSTP']; //Change date into time
//echo $currentTime;
//echo $row['START_DATE'];
//  $timeAfterOneHour = $currentTime+60*60*$auth->getUserTimezone();
  $report_form['DATE']['YEAR'] = date("Y", $currentTime);
  $report_form['DATE']['MONTH'] = date("m", $currentTime);
  $report_form['DATE']['DAY'] = date("d", $currentTime);
  $report_form['DATE']['HOUR'] = date("H", $currentTime);
  $report_form['DATE']['MINUTE'] = date("i", $currentTime);

//echo $player_state;
}

$reports = $gamebox->getTimeReports($_GET['game_id']);

if ($auth->userOn()) {
  $report_form['ENABLED'] = 1;
  $report_form['ENABLED_SUBMIT'] = 1;
}
else $error['MSG']=$langs['LANG_ERROR_CONTENT_LOGIN_U'];

  if (isset($error)) 
    $smarty->assign("error", $error);

  $smarty->assign("report_form", $report_form);
  $smarty->assign("reports", $reports);

  $start = getmicrotime();
  $content = $smarty->fetch('smarty_tpl/game_report_time.smarty');
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/game_report_time.smarty'.($stop-$start);

include('inc/top_jui.inc.php');
// content
echo $content;

// ----------------------------------------------------------------------------
// include common footer
//include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');
?>