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
  $s_fields = '';
  $d_fields = '';
  $c_fields = '';
  $i_fields = array('cat_id', 'group_id');
  $r_fields = array('cat_id');

  $s_fields_d = array('forum_name');
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
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields,  $_POST);
    $tdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d,  $_POST);
    
    // proceed to database updates
    if (!empty($_GET["forum_id"]) && !empty($_POST["lang_id"])){
      // UPDATE
      $db->update('forum', $sdata, "FORUM_ID=".$_GET["forum_id"]);
      $tdata['forum_id'] = $_GET["forum_id"];
      $db->select('forum_details', "*", "FORUM_ID=".$_GET["forum_id"]." AND LANG_ID=".$_POST['lang_id']);
      if ($row = $db->nextRow())
	  $db->update('forum_details', $tdata, "FORUM_ID=".$_GET["forum_id"]." AND LANG_ID=".$_POST['lang_id']);
      else $db->insert('forum_details', $tdata);
    }
    else {
      // INSERT
	$sdata['PUBLISH'] = "'N'";
	$db->insert('forum', $sdata);
	$tdata['forum_id'] = $db->id();
	$db->insert('forum_details',$tdata);
	$db->free();
    }
    
    // redirect to list page
    header('Location: forum.php');
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['forum_id'])) {
  // edit
  $sql = "SELECT F.FORUM_ID, FD.FORUM_NAME, F.CAT_ID, F.GROUP_ID, F.PUBLISH, C.CAT_ID
		FROM forum_cats C, forum F LEFT JOIN forum_details FD ON FD.FORUM_ID=F.FORUM_ID AND FD.LANG_ID=".$_SESSION['lang_id']."			    
		WHERE F.CAT_ID=C.CAT_ID AND F.FORUM_ID=".$_GET['forum_id'] ;
  $db->query($sql);

//echo $sql;
  if (!$row = $db->nextRow()) {
    // ERROR! No such item. redirect to list
	$PRESET_VARS['publish']='Y';
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
      $data[$key] = $val;
    }
  }
  $db->free();
}
else {
  // adding record
  $PRESET_VARS['publish'] = 'N';
}

$data['CAT_ID']=inputForumCats('cat_id');
$data['GROUP_ID']=inputForumGroups('group_id');

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/forum_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>