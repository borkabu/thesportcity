<?php
/*
===============================================================================
ppl_edit.php
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
  - BASKET.TOURNAMENTS
  - BASKET.ORGANIZATIONS
  - BASKET.KEYWORDS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] interface with LDAP server
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

if (empty($_SESSION["_admin"][MENU_SHOP]) || strcmp($_SESSION["_admin"][MENU_SHOP], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_SHOP], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
//$db->showquery=true;
$error = FALSE;
  if (!isset($_GET['attribute_id'])) 
   {
    header('Location: '.$_POST['referer']);
    exit;
   }
if(isset($_POST['form_save'])&&!$ro){
  // required fields
  $s_fields = array('item_name');
  $d_fields = '';
  $c_fields = '';
  $i_fields = array('lang_id');
  $r_fields = array('item_name');

  $s_fields_d = '';
  $d_fields_d = '';
  $c_fields_d = '';
  $i_fields_d = array('order_no');
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
    $tdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d,  $_POST);

    $tdata['attribute_id']      = $_GET['attribute_id'];    
    // proceed to database updates
    if(!empty($_GET["value_id"]) && !empty($_POST["lang_id"])){
      // UPDATE
      $db->update('shop_attributes_values', $tdata, "VALUE_ID=".$_GET["value_id"]);
      $sdata['value_id'] = $_GET["value_id"];
      $db->replace('shop_attributes_values_details', $sdata);
    }
    else {
      // INSERT
      $tdata['PUBLISH'] = "'N'";
      $db->insert('shop_attributes_values', $tdata);
      $sdata['value_id'] = $db->id();
      $db->insert('shop_attributes_values_details',$sdata);
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
if (isset($_GET['value_id'])) {
  // edit
  $sql = "SELECT C.VALUE_ID, CD.ITEM_NAME, C.PUBLISH, C.ORDER_NO
        FROM shop_attributes_values C
              left join shop_attributes_values_details CD 
		on  C.VALUE_ID = CD.VALUE_ID AND LANG_ID=".$_SESSION['lang_id']."
        WHERE C.VALUE_ID=".$_GET['value_id'];
  $db->query($sql);
echo $sql;

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

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/shop_attributes_values_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>