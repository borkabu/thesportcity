<?php
/*
===============================================================================
user_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit people records
  - edit membership with teams
  - edit membership with tournaments
  - edit membership with organizations
  - edit keywords
  - create new person record

TABLES USED: 
  - BASKET.USERS
  - BASKET.MEMBERS
  - BASKET.TEAMS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] interface with LDAP server
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

include('../class/prepare.inc.php');

if (empty($_SESSION["_admin"][MENU_PARAMETERS]) || strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save'])&&!$ro){
  // required fields
  $s_fields = array('latin_name', 'original', 'short_code', 'cctld');
  $i_fields = '';
  $d_fields = '';
  $c_fields = '';
  $r_fields = array('latin_name', 'original');
  
  $s_fields_d = array('country_name');
  $d_fields_d = '';
  $c_fields_d = '';
  $i_fields_d = array('lang_id');

  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
    $tdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d,  $_POST);
    
    // proceed to database updates
    if (!empty($_GET['id'])) {
      // UPDATE
      $db->update('countries', $sdata, "ID=".$_GET['id']);
      $tdata['id'] = $_GET["id"];
      $db->select('countries_details', "*", "ID=".$_GET["id"]." AND LANG_ID=".$_POST['lang_id']);
      if ($row = $db->nextRow())
	  $db->update('countries_details', $tdata, "ID=".$_GET["id"]." AND LANG_ID=".$_POST['lang_id']);
      else $db->insert('countries_details', $tdata);
    }
    else {
      // INSERT
      $db->insert('countries', $sdata);
      $tdata['id'] = $db->id();
      $db->insert('countries_details',$tdata);
    }
    
    // redirect to list page
    header('Location: '.$_POST['referer']);
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------


// build data
$data['MENU'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['id'])) {
  // edit
  $sql = "SELECT C.ID, C.LATIN_NAME, C.ORIGINAL, C.SHORT_CODE, C.CCTLD, CD.COUNTRY_NAME
		FROM countries C LEFT JOIN countries_details CD ON CD.ID=C.ID AND CD.LANG_ID=".$_SESSION['lang_id']."			    
		WHERE C.ID=".$_GET['id'] ;

  $db->query($sql);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header('Location: country.php');
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
  // adding records
}

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/country_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>