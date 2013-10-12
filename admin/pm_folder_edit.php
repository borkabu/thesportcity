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
include('../lib/pm_folder_types.inc.php');
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_SESSION["_admin"][MENU_FORUM]) || strcmp($_SESSION["_admin"][MENU_FORUM], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_FORUM], 'RO') == 0)
  $ro = TRUE;

//$db->showquery=true;
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save'])&&!$ro){
  // required fields
  $s_fields = '';
  $d_fields = '';
  $c_fields = '';
  $i_fields = array('folder_type');

  $s_fields_d = array('title');
  $d_fields_d = '';
  $c_fields_d = '';
  $i_fields_d = array('lang_id');

  $r_fields = array('title');
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
    $tdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d, $_POST);
    
    // proceed to database updates
    if(!empty($_GET["folder_id"]) && !empty($_POST["lang_id"])){
      // UPDATE
      $tdata['folder_id'] = $_GET["folder_id"];
      $db->replace('pm_folder_details', $tdata);
    }
    else {
      // INSERT
	$db->insert('pm_folder', $sdata);
	$tdata['folder_id'] = $db->id();
	$db->insert('pm_folder_details',$tdata);
	$db->free();
    }
    
    // redirect to list page
    header('Location: pm_folder.php');
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['folder_id'])) {
  // edit
  $sql = "SELECT F.FOLDER_ID, FD.TITLE, F.FOLDER_TYPE
		FROM pm_folder F LEFT JOIN pm_folder_details FD ON FD.FOLDER_ID=F.FOLDER_ID AND FD.LANG_ID=".$_SESSION['lang_id']."			    
		WHERE F.FOLDER_ID=".$_GET['folder_id'] ;
  $db->query($sql);

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
}

$opt = array(
  'class' => 'input',
  'options' => $folder_types
);

$data['FOLDER_TYPE'] = $frm->getInput(FORM_INPUT_SELECT, 'folder_type', $PRESET_VARS['folder_type'], $opt, $PRESET_VARS['folder_type']);


// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/pm_folder_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>