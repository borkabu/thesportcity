<?php
/*
===============================================================================
league_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit tournament records
  - edit keywords
  - create new tournament record

TABLES USED: 
  - BASKET.TOURNAMENTS
  - BASKET.KEYWORDS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] additional buttons
===============================================================================
*/
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

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_BASKET], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save'])&&!$ro){
  // required fields

  $s_fields = array('pic_location');;
  $d_fields = '';
  $c_fields = array('publish');
  $i_fields = '';

  $s_fields_d = array('tname');
  $d_fields_d = '';
  $c_fields_d = '';
  $i_fields_d = array('lang_id');
  $r_fields_d = array('tname');

  // check for required fields
  if (!requiredFieldsOk($r_fields_d, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields,  $_POST);
    $tdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d,  $_POST);
    
    // proceed to database updates
    if (!empty($_GET['tournament_id']) && !empty($_POST["lang_id"])) {
      // UPDATE
      $db->update('tournaments', $sdata, "TOURNAMENT_ID=".$_GET['tournament_id']);
      $tdata['tournament_id'] = $_GET["tournament_id"];
      $db->replace('tournaments_details', $tdata);
    }
    else {
      // INSERT
      $db->insert('tournaments', $sdata);
      $tdata['tournament_id'] = $db->id();
      $db->insert('tournaments_details',$tdata);
      $db->free();
    }   
   
    // redirect to news page
    header('Location: '.$_POST['referer']);
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------


// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['tournament_id'])) {
  // edit
  $sql = "SELECT F.TOURNAMENT_ID, FD.TNAME, F.PUBLISH, F.PIC_LOCATION
		FROM tournaments F LEFT JOIN tournaments_details FD ON FD.TOURNAMENT_ID=F.TOURNAMENT_ID AND FD.LANG_ID=".$_SESSION['lang_id']."			    
		WHERE F.TOURNAMENT_ID=".$_GET['tournament_id'] ;
  $db->query($sql);

  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header('Location: league.php');
    exit;
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
    }
  }
  $db->free();
}
else {
  // adding record
  $PRESET_VARS['publish'] = 'Y';
}


// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/league_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>