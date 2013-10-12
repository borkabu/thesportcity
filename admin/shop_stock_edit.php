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

if (empty($_SESSION["_admin"][MENU_SHOP]) || strcmp($_SESSION["_admin"][MENU_SHOP], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_SPORT_CITY], 'RO') == 0)
  $ro = TRUE;

if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_SHOP], 'FA') == 0) {
  $db->delete('shop_stock_attributevalues', "ENTRY_ID=".$_GET['del']);
}

//$db->showquery=true;
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save'])&&!$ro){
  // required fields
  $s_fields = array('item_name', 'descr');
  $d_fields = '';
  $c_fields = '';
  $i_fields = array('lang_id');
  $r_fields = array('item_name');

  $s_fields_d = array('pic_location');
  $d_fields_d = '';
  $c_fields_d = array('publish');
  $i_fields_d = array('price_euro', 'price_credits');
  $r_fields_d = array('price_euro', 'price_credits');
  
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
    if(!empty($_GET["item_id"]) && !empty($_POST["lang_id"])){
      // UPDATE
      $db->update('shop_stock', $tdata, "ITEM_ID=".$_GET["item_id"]);
      $sdata['item_id'] = $_GET["item_id"];
      $db->replace('shop_stock_details', $sdata);
    }
    else {
      // INSERT
      $tdata['publish'] = "'N'";
      $db->insert('shop_stock', $tdata);
      $sdata['item_id'] = $db->id();
      $db->insert('shop_stock_details',$sdata);
      $db->free();
    }
    
    // redirect to list page
    header('Location: shop_stock.php');
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['item_id'])) {
  // edit
  $data['ITEM_ID'] = $_GET['item_id'];
  $sql = "SELECT * FROM shop_stock SI left join shop_stock_details SID 
				on SID.ITEM_ID= SI.ITEM_ID 
				AND SID.LANG_ID=".$_SESSION['lang_id']."
		WHERE  SI.ITEM_ID=".$_GET['item_id'];
//  $fields='ITEM_ID, ITEM_NAME';
//  $db->select('ss_items_details',$fields,"ITEM_ID=".$_GET['item_id']." AND LANG_ID=".$_SESSION['lang_id']);
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

    $sql = "SELECT DISTINCT SSA.ENTRY_ID, SSA.ITEM_ID, SSA.ATTRIBUTE_ID, SSA.VALUE_ID, SAD.ITEM_NAME AS ATTR_NAME, SAVD.ITEM_NAME as VALUE, SSA.PRICE_CREDITS, SSA.PRICE_EURO
		FROM shop_stock_attributevalues SSA
			left JOIN shop_attributes_details SAD ON SSA.attribute_ID = SAD.attribute_ID  AND SAD.LANG_ID=".$_SESSION['lang_id']."
			left JOIN shop_attributes_values_details SAVD ON SSA.VALUE_ID = SAVD.VALUE_ID AND SAVD.LANG_ID=".$_SESSION['lang_id']."
	   WHERE SSA.ITEM_ID = ".$_GET['item_id']."
	   ORDER BY SAD.ITEM_NAME, SAVD.ITEM_NAME";
echo $sql;
  $db->query($sql);
  $t=0;
  while ($row = $db->nextRow()) {
    $data['VALUES'][$t] = $row;
    $data['VALUES'][$t]['NUMBER'] = $t + 1;
    if (strcmp($_SESSION["_admin"][MENU_SHOP], 'FA') == 0)  
      $data['VALUES'][$t]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['ENTRY_ID']);
    if ($t & 2 > 0) 
      $data['VALUES'][$t]['ODD'][0]['X'] = 1;

    $t++;  
  }

}
else {
  // adding record
  $PRESET_VARS['publish'] = 'Y';
}

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/shop_stock_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>