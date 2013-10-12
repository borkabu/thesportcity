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
  if (!isset($_GET['item_id'])) 
   {
    header('Location: '.$_POST['referer']);
    exit;
   }
if(isset($_POST['form_save'])&&!$ro){
  // required fields
  $s_fields = '';
  $d_fields = '';
  $c_fields = array('default_value');
  $i_fields = array('price_euro', 'price_credits');
  $r_fields = '';
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }

  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);

    $sdata['item_id']      = $_GET['item_id'];    
    $fields = explode("_", $_POST['attr_value']);  
    $sdata['attribute_id'] = $fields[0];
    $sdata['value_id'] = $fields[1];
    // proceed to database updates
    if(!empty($_GET["entry_id"]) && !empty($_POST["lang_id"])){
      // UPDATE
      $db->update('shop_stock_attributevalues', $sdata, "ENTRY_ID=".$_GET["entry_id"]);
    }
    else {
      // INSERT
      $db->insert('shop_stock_attributevalues', $sdata);
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
$attribute_id = 0;
$value_id = 0;

if ($_GET['entry_id']) {
  $sql = "SELECT DISTINCT SSA.ENTRY_ID, SSA.ITEM_ID, SSA.ATTRIBUTE_ID, SSA.VALUE_ID, SSA.PRICE_CREDITS, SSA.PRICE_EURO, SSA.DEFAULT_VALUE
		FROM shop_stock_attributevalues SSA
	   WHERE SSA.ENTRY_ID = ".$_GET['entry_id'];
  $db->query($sql);
echo $sql;

  // edit

  if (!$row = $db->nextRow()) {
    // ERROR! No such item. redirect to list
//	$PRESET_VARS['publish']='Y';
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
    }
  }
  $attribute_id = $row['ATTRIBUTE_ID'];
  $value_id = $row['VALUE_ID'];

  $db->free();
}
else {
  // adding record
//  $PRESET_VARS['publish'] = 'Y';
}

$data['ATTR_VALUE'] = inputShopStockAttrValue('attr_value', isset($_GET['entry_id']) ? $attribute_id."_".$value_id : '');
// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/shop_stock_attributes_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>