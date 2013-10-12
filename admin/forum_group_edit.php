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

if (empty($_SESSION["_admin"][MENU_FORUM]) || strcmp($_SESSION["_admin"][MENU_FORUM], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_FORUM], 'RO') == 0)
  $ro = TRUE;

include('../class/prepare.inc.php');
//$db->showquery=true;
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save'])&&!$ro){
  // required fields
  $s_fields = array('group_name');
  $d_fields = '';
  $c_fields = '';
  $i_fields = array('lang_id');
  $r_fields = array('group_name');

  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }

  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
    $tdata['pic_location'] = "'".$_POST['pic_location']."'";    
    $tdata['type'] = $_POST['type'];    
    // proceed to database updates
    if(!empty($_GET["group_id"]) && !empty($_POST["lang_id"])){
      // UPDATE
      $db->update('forum_groups', $tdata, "GROUP_ID=".$_GET["group_id"]);
      $sdata['group_id'] = $_GET["group_id"];
      $db->replace('forum_groups_details', $sdata);
    }
    else {
      // INSERT
	$tdata['GROUP_MEMBERS'] = 0;
	$db->insert('forum_groups', $tdata);
	$sdata['group_id'] = $db->id();
	$db->insert('forum_groups_details',$sdata);
    }
    
    // redirect to list page
    header('Location: forum_group.php');
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['group_id'])) {
  // edit
  $sql = "SELECT FG.GROUP_ID, GROUP_NAME, PIC_LOCATION, TYPE
		FROM forum_groups_details FGD, forum_groups FG
		WHERE FGD.GROUP_ID=FG.GROUP_ID
		  AND FG.GROUP_ID=".$_GET['group_id']." 
		  AND FGD.LANG_ID=".$_SESSION['lang_id'];
  $db->query($sql);

  if (!$row = $db->nextRow()) {
    // ERROR! No such item. redirect to list
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
  'options' => $group_types
);

$data['TYPE'] = $frm->getInput(FORM_INPUT_SELECT, 'type', $PRESET_VARS['type'], $opt, $PRESET_VARS['type']);

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/forum_group_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>