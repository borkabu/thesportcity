<?php
/*
===============================================================================
season_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit tournament seasons
  - create new tournament season

TABLES USED: 
  - BASKET.SEASONS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/adm_menu.php');
include('../class/update.inc.php');

// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/language.class.php');
include('../class/form.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

// security layer
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_SESSION["_admin"][MENU_BASKET]) || strcmp($_SESSION["_admin"][MENU_BASKET], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = false;
if (strcmp($_SESSION["_admin"][MENU_BASKET], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------

if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_BASKET], 'FA') == 0) {
  $db->delete('team_seasons', "ID=".$_GET['del']);
}

$error = FALSE;
if(isset($_POST['form_save']) && !$ro){
  // required fields
  $s_fields = '';
  $i_fields = array('tournament_id', 'sport_id');
  $d_fields = array('start_date', 'end_date');
  $c_fields = array('publish');
  $r_fields = array('season_title', 'tournament_id',
                    'start_date_y', 'start_date_m', 'start_date_d',
                    'end_date_y', 'end_date_m', 'end_date_d');

  $s_fields_d=array('season_title');
  $i_fields_d=array('lang_id');
  $d_fields_d='';
  $c_fields_d='';
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$_POST);
    $tdata=buildSaveData($s_fields_d,$i_fields_d,$d_fields_d,$c_fields_d,$_POST);
    
    // proceed to database updates
    if (!empty($_GET["season_id"]) && !empty($_POST["lang_id"])){
      // UPDATE
      $db->update('seasons', $sdata, "SEASON_ID=".$_GET['season_id']);
      $tdata['season_id'] = $_GET["season_id"];
      $db->replace('seasons_details', $tdata);
    }
    else {
      // INSERT
      $db->insert('seasons', $sdata);
	$tdata['season_id'] = $db->id();
	$db->insert('seasons_details',$tdata);
    }

    // redirect to news page
    header('Location: '.$_POST['referer']);
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['season_id'])) {

  $sql = "SELECT P.SEASON_ID, PD.SEASON_TITLE, P.PUBLISH, P.TOURNAMENT_ID, P.SPORT_ID,
		SUBSTRING(P.START_DATE, 1, 16) START_DATE, 
		SUBSTRING(P.END_DATE, 1, 16) END_DATE
        FROM seasons  P 
		left JOIN seasons_details PD ON P.SEASON_ID = PD.SEASON_ID AND PD.LANG_ID=".$_SESSION['lang_id']."
		left join seasons_details pd2 ON pd2.SEASON_ID=P.SEASON_ID
        WHERE P.SEASON_ID=".$_GET['season_id'];

  //echo $sql; 
  $db->query($sql);

  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header('Location: season.php');
    exit;
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
    }
    $data['SEASON_ID'] = $_GET['season_id'];
  }
  $db->free();

  // read team listings
  $teams = array();
  $sql = "SELECT TS.ID, TS.TEAM_ID, T.TEAM_NAME
          FROM team_seasons TS, seasons SS, teams T
          WHERE TS.SEASON_ID=".$_GET['season_id']."
                AND SS.SEASON_ID=TS.SEASON_ID 
		AND T.TEAM_ID=TS.TEAM_ID
          ORDER BY TEAM_NAME";
  $db->query($sql);
  $t=0;
  while ($row = $db->nextRow()) {
    $data['TEAM'][$t] = $row;
    $data['TEAM'][$t]['NO'] = $t + 1;
    $data['TEAM'][$t]['TEAM_NAME'] = $row['TEAM_NAME'];
    $data['TEAM'][$t]['SEASON_ID'] = $_GET['season_id'];
    if (strcmp($_SESSION["_admin"][MENU_BASKET], 'FA') == 0)  
      $data['TEAM'][$t]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['ID']);
    if ($t & 2 > 0) 
      $data['TEAM'][$t]['ODD'][0]['X'] = 1;

    $t++;  
  }
}
else {
  // adding record
  $PRESET_VARS['publish'] = 'Y';
}

// get common inputs
$data['TOURNAMENT'] = inputTournaments('tournament_id', '', 50);
$data['YF'] = date('Y');
$data['YT'] = $data['YF']+1;
$data['SPORT_ID'] = inputManagerSportTypes('sport_id', $PRESET_VARS['sport_id']);

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/season_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
