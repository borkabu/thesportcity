<?php
/*
===============================================================================
team_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit team records
  - edit keywords
  - create new team record

TABLES USED: 
  - BASKET.TEAMS
  - BASKET.TEAM_TOURNAMENTS
  - BASKET.TOURNAMENTS
  - BASKET.KEYWORDS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] additional buttons
===============================================================================
*/

ob_start();
// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/inputs.inc.php');
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

// --- BEGIN UPDATES ----------------------------------------------------------
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_BASKET], 'FA') == 0) {
  // delete membership
  $db->delete('team_names', 'ID='.$_GET['del']);
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('team_names', array('PUBLISH' => "'Y'"),'ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('team_names', array('PUBLISH' => "'N'"),'ID='.$_GET['deactivate']);
}

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
$db->showquery=true;
if(isset($_POST['form_save']) && !$ro){
  // required fields
  $s_fields = array('team_name', 'team_name2', 'city', 'country',  'original_name', 'pic_location');
  $i_fields = array('founded_year', 'founded_month', 'founded_day', 'disband_year', 'sport_id', 'team_type');
  $d_fields = array();
  $c_fields = array('publish', 'gender');
  $r_fields = array('team_name');
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, 
                           $i_fields, 
                           $d_fields, 
                           $c_fields, 
                           $_POST);
    
    // proceed to database updates
    if (!empty($_GET['team_id'])) {
      // UPDATE
      $db->update('teams', $sdata, "TEAM_ID=".$_GET['team_id']);
    }
    else {
      // INSERT
      $db->insert('teams', $sdata);
    }     
    
    header('Location: '.$_POST['referer']);
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------


// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);

// new or edit?
if (isset($_GET['team_id'])) {
  // edit
  $db->select('teams', '*', "TEAM_ID=".$_GET['team_id']);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header('Location: team.php');
    exit;
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
    }
  }
  $db->free();
  
  $data['TEAM_ID']=$_GET['team_id'];
}
else {
  // adding record
  $PRESET_VARS['publish'] = 'Y';
}

// get common inputs
$founded = getVal('founded_year').'-'
           .getVal('founded_month').'-'
           .getVal('founded_day');

$disband = getVal('disband_year').'-08-01';

$ddata = $frm->dateInput($disband, 'disband', 1891, '', array('class' => 'input'), 
                         array('_year', '_month', '_day'), TRUE);

$fdata = $frm->dateInput($founded, 'founded', 1890, '', array('class' => 'input'), 
                         array('_year', '_month', '_day'), TRUE);

$data['DYEAR'] = $ddata['disband_year'];
$data['FYEAR'] = $fdata['founded_year'];
$data['FMONTH'] = $fdata['founded_month'];
$data['FDAY'] = $fdata['founded_day'];
$data['SPORT_ID'] = inputManagerSportTypes('sport_id', $PRESET_VARS['sport_id']);
$data['COUNTRY'] = inputCountries('country', $PRESET_VARS['country']);
$data['TEAM_TYPE'] = inputTeamTypes('team_type', $PRESET_VARS['team_type']);

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/team_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>