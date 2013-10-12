<?php
/*
===============================================================================
source_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit individual shortcut
  - create new shortcut

TABLES USED: 
  - BASKET.SHORTCUTS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
ob_start();
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
include('../lib/banner_positions.inc.php');
// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$lng = new language;
$frm = new form;

// security layer
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_admin[MENU_PARAMETERS]) || strcmp($_admin[MENU_PARAMETERS], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

if (strcmp($_admin[MENU_PARAMETERS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if (isset($form_save) && !$ro) {
  // required fields
  $s_fields = array('title', 'filename', 'format', 'link');
  $i_fields = array('order_no', 'width', 'height', 'position', 'percent', 'subsite');
  $d_fields = '';
  $c_fields = array('publish', 'foreign_ips');
  $r_fields = array('title', 'filename', 'format');
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $HTTP_POST_VARS)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG'] = 'Neuþpildyti visi bûtini laukeliai!';
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, 
                           $i_fields, 
                           $d_fields, 
                           $c_fields, 
                           $HTTP_POST_VARS);
    
    // proceed to database updates
    if (!empty($banner_id)) {
      // UPDATE
      $db->update('banners', $sdata, "BANNER_ID=$banner_id");
    }
    else {
      // INSERT
      $db->insert('banners', $sdata);
//echo $db->dbNativeErrorText();
//exit;
    }
    
    // redirect to list page
    $db->close();
    header('Location: '.$HTTP_POST_VARS['referer']);
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------


// build data
$data['MENU'] = getMenu(scriptName($PHP_SELF));
$data['REFERER'] = getReferer($HTTP_POST_VARS);

// new or edit?
if (isset($banner_id)) {
  // edit
  $db->select('banners', '*', "BANNER_ID=$banner_id");
  if (!$row = $db->nextRow()) {
    // ERROR! No such item. redirect to list
    $db->close();
    header('Location: banner.php');
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
  // adding
  $db->select('banners', 'MAX(ORDER_NO) MAX_ORDER_NO');
  $row = $db->nextRow();
  $db->free();
  $PRESET_VARS['order_no'] = $row['MAX_ORDER_NO'] + 1;
  $PRESET_VARS['publish'] = 'Y';
  $PRESET_VARS['foreign_ips'] = 'N';
}

$data['POSITION']=inputBannerPositions('position');
$data['SUBSITE'] = inputSubsites('subsite');

ob_end_flush();
// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm_banner_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
