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
include('../class/ss_const.inc.php');
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
  $s_fields = array('attr_name', 'descr');
  $d_fields = '';
  $c_fields = '';
  $i_fields = array('lang_id');
  $r_fields = array('attr_name');

  $s_fields_d = '';
  $d_fields_d = '';
  $c_fields_d = '';
  $i_fields_d = array('sport_id', 'price', 'levels', 'value', 'prop_affected');
  $r_fields_d = array('sport_id', 'price', 'levels', 'value', 'prop_affected');
  
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
    if(!empty($_GET["attr_id"]) && !empty($_POST["lang_id"])){
      // UPDATE
      $db->update('ss_skills', $tdata, "ATTR_ID=".$_GET["attr_id"]);
      $sdata['attr_id'] = $_GET["attr_id"];
      $db->replace('ss_skills_details', $sdata);
    }
    else {
      // INSERT
      $tdata['PUBLISH'] = "'N'";
      $db->insert('ss_skills', $tdata);
      $sdata['attr_id'] = $db->id();
      $db->insert('ss_skills_details',$sdata);
      $db->free();
    }
    
    // redirect to list page
    header('Location: ss_skill.php');
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['attr_id'])) {
  // edit
  $sql = "SELECT * FROM ss_skills SI left join ss_skills_details SID 
				ON SID.ATTR_ID= SI.ATTR_ID 
				AND SID.LANG_ID=".$_SESSION['lang_id']."
		WHERE SI.ATTR_ID=".$_GET['attr_id'];
//  $fields='ITEM_ID, ITEM_NAME';
//  $db->select('ss_skills_details',$fields,"ATTR_ID=".$_GET['attr_id']." AND LANG_ID=".$_SESSION['lang_id']);
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
  if (empty($row['LEVEL'])) {
    $PRESET_VARS['level'] = "0";
  }


  }
  $db->free();
}
else {
  // adding record
  $PRESET_VARS['publish'] = 'Y';
}

//$data['EQUIP_POINT']=inputEquipPoints('equip_point');
$data['SPORT_ID']=inputSportTypes('sport_id', isset($row['SPORT_ID']) ? $row['SPORT_ID'] : '');
$data['PROP_AFFECTED']=inputProperties('prop_affected');


// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/ss_skill_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>