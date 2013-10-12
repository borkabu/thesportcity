<?php
/*
===============================================================================
cat_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit individual category
  - create new category

TABLES USED: 
  - BASKET.CATS

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

if (empty($_SESSION["_admin"][MENU_SPORT_CITY]) || strcmp($_SESSION["_admin"][MENU_SPORT_CITY], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_SPORT_CITY], 'RO') == 0)
  $ro = TRUE;

//$db->showquery=true;
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save'])&&!$ro){
  // required fields
  $s_fields = array('item_type_name');
  $d_fields = '';
  $c_fields = '';
  $i_fields = array('lang_id');
  $r_fields = array('item_type_name');

  $s_fields_d = '';
  $d_fields_d = '';
  $c_fields_d = '';
  $i_fields_d = array('effect_type');
  
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
    if(!empty($_GET["item_type_id"]) && !empty($_POST["lang_id"])){
      // UPDATE
      $db->update('ss_item_types', $tdata, "ITEM_TYPE_ID=".$_GET["item_type_id"]);
      $sdata['item_type_id'] = $_GET["item_type_id"];
      $db->replace('ss_item_types_details', $sdata);
    }
    else {
      // INSERT
      $tdata['PUBLISH'] = "'N'";
      $db->insert('ss_item_types', $tdata);
      $sdata['item_type_id'] = $db->id();
      $db->insert('ss_item_types_details',$sdata);
      $db->free();
    }
    
    // redirect to list page
    $db->close();
    header('Location: ss_item_type.php');
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['item_type_id'])) {
  // edit
  $fields='ITEM_TYPE_ID, ITEM_TYPE_NAME';
  $db->select('ss_item_types_details',$fields,"ITEM_TYPE_ID=".$_GET['item_type_id']." AND LANG_ID=".$_SESSION['lang_id']);

  if (!$row = $db->nextRow()) {
    // ERROR! No such item. redirect to list
	$PRESET_VARS['publish']='Y';
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

$data['EFFECT_TYPE']=inputEffectTypes('effect_type');

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/ss_item_type_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>