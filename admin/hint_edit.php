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
  $s_fields = '';
  $i_fields = array('hint_type', 'hint_level');
  $d_fields = '';
  $c_fields = array('publish');
  $r_fields = array('hint_type', 'hint_level');
  
  $s_fields_d = array('hint_title', 'descr');
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
    if (!empty($_GET['hint_id'])) {
      // UPDATE
      $db->update('hints', $sdata, "hint_ID=".$_GET['hint_id']);
      $tdata['hint_id'] = $_GET["hint_id"];
      $db->select('hints_details', "*", "hint_ID=".$_GET["hint_id"]." AND LANG_ID=".$_POST['lang_id']);
      if ($row = $db->nextRow())
        $db->update('hints_details', $tdata, "hint_ID=".$_GET["hint_id"]." AND LANG_ID=".$_POST['lang_id']);
      else $db->insert('hints_details', $tdata);
    }
    else {
      // INSERT
      $db->insert('hints', $sdata);
      $tdata['hint_id'] = $db->id();
      $db->insert('hints_details',$tdata);
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
if (isset($_GET['hint_id'])) {
  // edit
  $sql = "SELECT C.HINT_ID, C.HINT_TYPE, C.HINT_LEVEL, CD.HINT_TITLE, CD.DESCR, C.PUBLISH
		FROM hints C LEFT JOIN hints_details CD ON CD.HINT_ID=C.HINT_ID AND CD.LANG_ID=".$_SESSION['lang_id']."			    
		WHERE C.HINT_ID=".$_GET['hint_id'] ;

  $db->query($sql);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header('Location: hint.php');
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
  $PRESET_VARS['publish'] = 'Y';
}

//$data['HINT_TYPE']=;
//$data['HINT_LEVEL']=;

$opt['class']= "input";
$opt['options'] = $hint_types;

$data['HINT_TYPE'] = $frm->getInput(FORM_INPUT_SELECT, 'hint_type', isset($PRESET_VARS['hint_type']) ? $PRESET_VARS['hint_type'] : 0, $opt, isset($PRESET_VARS['hint_type']) ? $PRESET_VARS['hint_type'] : 0);   

$opt['options'] = array(-1,0,1,2,3,4,5,6);

$data['HINT_LEVEL'] = $frm->getInput(FORM_INPUT_SELECT, 'hint_level', isset($PRESET_VARS['hint_level']) ? $PRESET_VARS['hint_level'] : 0, $opt, isset($PRESET_VARS['hint_level']) ? $PRESET_VARS['hint_level'] : 0);   

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/hint_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>