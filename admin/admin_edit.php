<?php
/*
===============================================================================
user_edit.php
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

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] interface with LDAP server
===============================================================================
*/

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
$frm = new form;

// security layer
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_SESSION["_admin"][MENU_ADMINS]) || strcmp($_SESSION["_admin"][MENU_ADMINS], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ADMINS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save'])&&!$ro){
  // required fields
  
 if (!$error) {
   if (!empty($_GET["user_id"])) {
    // get save data
    $data['SEC']=getSecurityItemsList();
    $i=0;
    $db->delete('admin_rights', "USER_ID=".$_GET["user_id"]);
    while(isset($data['SEC'][$i]['ITEM']))
     {
      $value=$_POST['item_'.$data['SEC'][$i]['ITEM']];
      if (empty($value))
        $value='NA';
      unset($sdata);
      $sql="INSERT INTO admin_rights (user_id, item_code, access_level)
            VALUES (".$_GET["user_id"]."
            ,".$data['SEC'][$i]['ITEM']."
            ,'".$value."')";
      //echo $sql; 
      $db->query($sql);
      $i++;
     }
   } 
    
    // redirect to list page
  //  $db->close();
//    header('Location: '.$HTTP_POST_VARS['referer']);
//    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------


// build data
$data['MENU'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];
$data['SECURITY'] = getSecurityItems();

// new or edit?
if (isset($_GET['user_id'])) {
  // edit
  $fields = 'USER_ID, ITEM_CODE, ACCESS_LEVEL';
  $db->select('admin_rights', $fields, "USER_ID=".$_GET['user_id']);

  $i=0;
//  foreach(data['SECURITY'] as $value)

  while ($row = $db->nextRow()) {
   if (!empty($row['ACCESS_LEVEL'])) {
//     echo $_admin[$row['ITEM_CODE']];
     $data['SECURITY'][$row['ITEM_CODE']]['NA'] = '';
     $data['SECURITY'][$row['ITEM_CODE']][$row['ACCESS_LEVEL']] = 'checked';
   }
//   else $data['SECURITY'][$row['ITEM_CODE']]['NA'] = 'checked';
//   echo $data['SECURITY'][$i]['NA'];
   $i++;
  }
  $db->free();
}
else {
  // adding records
    header('Location: admin.php');
    exit;

}

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/admin_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
