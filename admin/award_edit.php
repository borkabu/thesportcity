<?php
/*
===============================================================================
org_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit organization records
  - edit keywords
  - create new organization record

TABLES USED: 
  - BASKET.ORGANIZATION
  - BASKET.KEYWORDS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] additional buttons
===============================================================================
*/
ob_start();
// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/inputs.inc.php');
include('../class/adm_menu.php');
include('../class/update.inc.php');
include('../lib/tournament_types.inc.php');

// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/language.class.php');
include('../class/form.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$lng = new language;
$frm = new form;

// security layer
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_admin[MENU_USERS]) || strcmp($_admin[MENU_USERS], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

if (strcmp($_admin[MENU_USERS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if (isset($form_save) && !$ro) {
  // required fields
  $s_fields = array('title', 'descr', 'pic_location');
  $i_fields = '';
  $d_fields = '';
  $c_fields = '';
  $r_fields = array('title', 'descr', 'pic_location');
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $HTTP_POST_VARS)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG'] = 'Neu¦pildyti visi bvtini laukeliai!';
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, 
                           $i_fields, 
                           $d_fields, 
                           $c_fields, 
                           $HTTP_POST_VARS);
    
    // proceed to database updates
    if (!empty($award_id)) {
      // UPDATE
      $db->update('awards', $sdata, "AWARD_ID=$award_id");
    }
    else {
      // INSERT
      $db->insert('awards', $sdata);
    }
    
    
    // redirect to news page
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
if (isset($award_id)) {
  // edit
  $db->select('awards', '*', "AWARD_ID=$award_id");
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    $db->close();
    header('Location: award.php');
    exit;
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
    }
  }
  $db->free();

  // user awards
    
}
else {
  // adding record
}

ob_end_flush();

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm_award_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
