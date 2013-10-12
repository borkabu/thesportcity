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

if (empty($_SESSION["_admin"][MENU_SHOP]) || strcmp($_SESSION["_admin"][MENU_SHOP], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_SHOP], 'RO') == 0)
  $ro = TRUE;

//$db->showquery=true;
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
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
  $i_fields_d = '';
  
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
    if(!empty($_GET["attribute_id"]) && !empty($_POST["lang_id"])){
      // UPDATE
      $db->update('shop_attributes', $tdata, "ATTRIBUTE_ID=".$_GET["attribute_id"]);
      $sdata['attribute_id'] = $_GET["attribute_id"];
      $db->replace('shop_attributes_details', $sdata);
    }
    else {
      // INSERT
      $tdata['PUBLISH'] = "'N'";
      $db->insert('shop_attributes', $tdata);
      $sdata['attribute_id'] = $db->id();
      $db->insert('shop_attributes_details',$sdata);
      $db->free();
    }
    
    // redirect to list page
    $db->close();
    header('Location: shop_attributes.php');
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['attribute_id'])) {
  // edit
  $data ['ATTRIBUTE_ID'] = $_GET['attribute_id'];
  $fields='ATTRIBUTE_ID, ITEM_NAME';
  $db->select('shop_attributes_details',$fields,"ATTRIBUTE_ID=".$_GET['attribute_id']." AND LANG_ID=".$_SESSION['lang_id']);

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
  $db->showquery=true;

  $sql = "SELECT * FROM languages ORDER BY ID";
  $db->query($sql);
  while ($row = $db->nextRow()) {
    $languages[$row['ID']] = $row;
  }


  $sql = "SELECT C.ATTRIBUTE_ID, C.VALUE_ID, CD.ITEM_NAME, C.PUBLISH, 
	       GROUP_CONCAT(CD2.LANG_ID) as LANGUAGES
        FROM shop_attributes_values  C 
		left JOIN shop_attributes_values_details CD ON C.VALUE_ID = CD.VALUE_ID AND CD.LANG_ID=".$_SESSION['lang_id']."
		left join shop_attributes_values_details CD2 ON CD2.VALUE_ID=C.VALUE_ID
        WHERE C.ATTRIBUTE_ID=".$_GET['attribute_id']."
	GROUP BY C.VALUE_ID
        ORDER BY C.ORDER_NO";
  echo $sql; 

  // get source list
  $db->query($sql);
  $db->setPage($page, $perpage);
  $rows = $db->rows();

  $c = 0;
  while ($row = $db->nextRow()) {
    $data['VALUES'][$c] = $row;

    $used_langs = explode(",", $row['LANGUAGES']);
    foreach ($languages as $language) {
      if (in_array($language['ID'], $used_langs)) {
        $data['VALUES'][$c]['LANGS'][$language['ID']]['USED'][0] = $language;
        $data['VALUES'][$c]['LANGS'][$language['ID']]['USED'][0]['VALUE_ID'] = $row['VALUE_ID'];
        $data['VALUES'][$c]['LANGS'][$language['ID']]['USED'][0]['ATTRIBUTE_ID'] = $row['ATTRIBUTE_ID'];
      }
      else {
        $data['VALUES'][$c]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
        $data['VALUES'][$c]['LANGS'][$language['ID']]['NOTUSED'][0]['VALUE_ID'] = $row['VALUE_ID'];
        $data['VALUES'][$c]['LANGS'][$language['ID']]['NOTUSED'][0]['ATTRIBUTE_ID'] = $row['ATTRIBUTE_ID'];
      }
    }
  
    if (strcmp($_SESSION["_admin"][MENU_SHOP], 'FA') == 0)
       $data['VALUES'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['VALUE_ID']);
    
    if ($c & 2 > 0)
      $data['VALUES'][$c]['ODD'][0]['X'] = 1;
    else
      $data['VALUES'][$c]['EVEN'][0]['X'] = 1;
    
    if ($row['PUBLISH'] == 'Y')
      $data['VALUES'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['VALUE_ID']);
    else
      $data['VALUES'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['VALUE_ID']);
    
    $c++;
  }
  $db->free();

  if ($rows == 0) {
    $data['NORECORDS'][0]['X'] = 1;
  }

}
else {
  // adding record
  $PRESET_VARS['publish'] = 'Y';
}

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/shop_attributes_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>