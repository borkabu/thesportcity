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

if (isset($del) && !$ro && strcmp($_admin[MENU_ADMINS], 'FA') == 0) {
  // delete membership
  $db->delete('admin_group_members', 'ID='.$del);
}
// activate
/*if (isset($activate) && !$ro) {
  $db->update('members', array('APPROVED' => "'Y'"),'ID='.$activate);
}
// deactivate
if (isset($deactivate) && !$ro) {
  $db->update('members', array('APPROVED' => "'N'"),'ID='.$deactivate);
} */

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if (isset($form_save) && !$ro) {
  // required fields
  $s_fields = array('group_name');
  $i_fields = '';
  $d_fields = '';
  $c_fields = '';
  $r_fields = array('group_name');
  
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
    if (!empty($admin_group_id)) {
      // UPDATE
      $db->update('admin_groups', $sdata, "ADMIN_GROUP_ID=$admin_group_id");
    }
    else {
      // INSERT
      $db->insert('admin_groups', $sdata);
      $db->select('admin_groups', 'MAX(ADMIN_GROUP_ID) ADMIN_GROUP_ID');
      $row = $db->nextRow();
      $db->free();
      $admin_group_id = $row['ADMIN_GROUP_ID'];      
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
if (isset($admin_group_id)) {
  // edit
  $data['ADMIN_GROUP_ID'] = $admin_group_id;
  $db->select('admin_groups', '*', "ADMIN_GROUP_ID=$admin_group_id");
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    $db->close();
    header('Location: admin_group.php');
    exit;
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
    }
     $sql='SELECT U.USER_ID, U.USER_NAME, ADM.ID, ADM.ADMIN_GROUP_ID
             FROM users U, admin_group_members ADM
             WHERE ADM.USER_ID=U.USER_ID
                AND ADM.ADMIN_GROUP_ID='.$admin_group_id.'
            ORDER BY U.USER_NAME ASC';
     $db->query($sql);

     $t=0;
     while ($row = $db->nextRow()) {
       $data['ADMIN_NAMES'][$t] = $row;
      if (strcmp($_admin[MENU_ADMINS], 'FA') == 0)  
        $data['ADMIN_NAMES'][$t]['DEL'][0]['DEL_URL'] = $PHP_SELF.url('del', $row['ID']);
      if ($t & 2 > 0) 
        $data['ADMIN_NAMES'][$t]['ODD'][0]['X'] = 1;
      $t++;    
    }


  }
  $db->free(); 
}
else {
  // adding records
}

ob_end_flush();
// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm_admin_group_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
