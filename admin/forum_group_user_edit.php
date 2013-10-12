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

if (empty($_SESSION["_admin"][MENU_USERS]) || strcmp($_SESSION["_admin"][MENU_USERS], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}
$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_USERS], 'RO') == 0)
  $ro = TRUE;


// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
  if (!isset($_GET['user_id'])) 
   {
    header('Location: '.$_POST['referer']);
    exit;
   }
if(isset($_POST['form_save'])&&!$ro){
  // required fields
  $s_fields = '';
  $i_fields = array('group_id', 'user_id', 'level');
  $d_fields = '';
  $c_fields = '';
  $r_fields = array('group_id', 'user_id');
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
     if (empty($_GET['group_id'])) {
	$sdata['date_joined'] = 'NOW()';
        $db->insert('forum_groups_members', $sdata);
        unset($sdata); 
	$sdata['group_members'] = 'group_members+1';
        $db->update('forum_groups', $sdata);
     } else {
        $db->update('forum_groups_members', $sdata, " GROUP_ID=".$_POST['group_id']." AND USER_ID=".$_POST['user_id']);
     }
            
    // redirect to news page
    header('Location: '.$_POST['referer']);
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);
$data['USER_ID'] = $_GET['user_id'];
$data['GROUP_ID'] = isset($_GET['group_id']) ? $_GET['group_id'] : '';

// new or edit?
if (isset($_GET['group_id'])) {
  // edit
  $db->select('forum_groups_members', "*", "GROUP_ID=".$_GET['group_id']." AND USER_ID=".$_GET['user_id']);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header('Location: user.php?user_id='.$_GET['user_id']);
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

$data['GROUP_ID'] = inputForumGroups('group_id');
$data['GROUP_LEVEL'] = inputGroupMemberLevels('level');

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/forum_group_user_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>