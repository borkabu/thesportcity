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
  if (!isset($user_id)) 
   {
    header('Location: '.$HTTP_POST_VARS['referer']);
    exit;
   }
if (isset($form_save) && !$ro) {
  // required fields
  $s_fields = array('comment');
  $i_fields = array('award_id', 'user_id');;
  $d_fields = array('date_awarded');
  $c_fields = '';
  $r_fields = array('award_id', 'user_id');
  
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
     if (empty($id)) {
        $db->insert('user_awards', $sdata);
      }
     else
      {
        $db->update('user_awards', $sdata, " ID=".$id);       
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
$data['USER_ID'] = $user_id;
$data['ID'] = $id;

// new or edit?
if (isset($id)) {
  // edit
  $db->select('user_awards', "*", "ID=$id");
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    $db->close();
    header('Location: user.php?user_id=$user_id ');
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

$data['AWARD_ID'] = inputAwards('award_id');

ob_end_flush();
// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm_award_user_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
