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

if (empty($_admin[MENU_ADMINS]) || strcmp($_admin[MENU_ADMINS], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

if (strcmp($_admin[MENU_ADMINS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if (!isset($admin_group_id)) 
 {
   header('Location: '.$HTTP_POST_VARS['referer']);
   exit;
 }

if (isset($form_save) && !$ro) {
  // required fields
  $s_fields = '';
  $i_fields = array('user_id');
  $d_fields = '';
  $c_fields = '';
  $r_fields = array('user_id');
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $HTTP_POST_VARS)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG'] = 'Neu�pildyti visi b�tini laukeliai!';
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, 
                           $i_fields, 
                           $d_fields, 
                           $c_fields, 
                           $HTTP_POST_VARS);
 
     $sdata['ADMIN_GROUP_ID'] = $admin_group_id;
     if (!isset($id)) {
        $db->insert('admin_group_members', $sdata);
      }
     else
      {
        $db->update('admin_group_members', $sdata, " ID=".$id);       
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
if (isset($id)) {
  $db->select('admin_group_members', '*', "ID=".$id);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    $db->close();
//    header('Location: admin_group.php');
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
  // adding records
  $PRESET_VARS['publish'] = 'Y';
}

$data['USER_ID'] = inputAdmins('user_id');
ob_end_flush();
// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm_admin_group_members_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
