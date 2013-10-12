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
include('../class/form.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

// security layer
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_SESSION["_admin"][MENU_USERS_EDIT]) || strcmp($_SESSION["_admin"][MENU_USERS_EDIT], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

$ro = false;
if (strcmp($_SESSION["_admin"][MENU_USERS_EDIT], 'RO') == 0)
  $ro = TRUE;

if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_USERS_EDIT], 'FA') == 0) {
  // delete membership
  $db->delete('user_awards', 'ID='.$_GET['del']);
}

if (isset($_GET['del_group']) && !$ro && strcmp($_SESSION["_admin"][MENU_USERS_EDIT], 'FA') == 0) {
  // delete membership
  $db->delete('forum_groups_members', 'GROUP_ID='.$_GET['del_group'].' AND USER_ID='.$_GET['user_id']);
  unset($sdata); 
  $sdata['group_members'] = 'group_members+1';
  $db->update('forum_groups', $sdata);
  header('Location: user_edit.php?user_id='.$_GET['user_id']);
  exit;

}

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save']) && !$ro){
  // required fields
  $s_fields = array('first_name', 'last_name', 'user_name',
                    'email', 'phone', 'mobile_phone',
                    'address1', 'address2', 'town', 'postcode', 'admin_default');
  $i_fields = array('credit', 'country');
  $d_fields = array('birth_date');
  $c_fields = array('gender', 'publish', 'active', 'admin', 'allow_blog');
  $r_fields = array('first_name', 'last_name');
  
  $dupe_fields = array('user_name');
  $dupe_except = array('user_id' => $_GET['user_id']);
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  // check for duplicate records
  if (!dupeFieldsOk('users', $dupe_fields, $_POST, $dupe_except)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG'] = $langs['LANG_ERROR_DUPE_UNAME_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
    
    // proceed to database updates
    if (!empty($_GET['user_id'])) {
      // UPDATE
      $db->update('users', $sdata, "USER_ID=".$_GET['user_id']);
    }
    else {
      // INSERT
      $sdata['REG_DATE'] = 'SYSDATE()';
      $db->insert('users', $sdata);      
    }
    
    // redirect to list page
    header('Location: '.$_POST['referer']);
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------


// build data
$data['MENU'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);

// new or edit?
if (isset($_GET['user_id'])) {
  // edit
  $fields = 'USER_ID, FIRST_NAME, LAST_NAME, USER_NAME, PASSWORD, EMAIL, 
             PHONE, MOBILE_PHONE, ADDRESS1, ADDRESS2, TOWN, COUNTRY, ALLOW_BLOG,
             GENDER, SUBSTRING(BIRTH_DATE, 1, 16) BIRTH_DATE, POSTCODE,
             SUBSTRING(REG_DATE, 1, 16) REG_DATE, 
             ADMIN, PUBLISH, ACTIVE, CREDIT
             ';
  $db->select('users', $fields, "USER_ID=".$_GET['user_id']);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header('Location: user.php');
    exit;
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
    }
  }
  $db->free();
  $data['USER_ID']=$_GET['user_id'];
  $data['COUNTRY'] = inputCountries('country', $PRESET_VARS['country']);
  $data['SEX_M'] = $frm->getInputWithValue(FORM_INPUT_RADIO, 'gender', 
                                      array('value_force' => 'Y', 'class' => ''));
  $data['SEX_F'] = $frm->getInputWithValue(FORM_INPUT_RADIO, 'gender', 
                                    array('value_force' => 'N', 'class' => ''));

    $sql='SELECT UA.USER_ID, UA.DATE_AWARDED, UA.ID, A.TITLE
             FROM awards A, user_awards UA
             WHERE A.AWARD_ID=UA.AWARD_ID
                  AND UA.USER_ID='.$_GET['user_id'].'
            ORDER BY UA.DATE_AWARDED ASC';
     $db->query($sql);

     $t=0;
     while ($row = $db->nextRow()) {
       $data['AWARDS'][$t] = $row;
      if (strcmp($_SESSION["_admin"][MENU_USERS], 'FA') == 0)  
        $data['AWARDS'][$t]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['ID']);
      if ($t & 2 > 0) 
        $data['AWARDS'][$t]['ODD'][0]['X'] = 1;
      $t++;    
     }
     if ($t == 0) {
        $data['AWARDS_NORECORDS'][0]['X'] = 1;     
     }


    $sql='SELECT UA.USER_ID, UA.DATE_JOINED, UA.GROUP_ID, AD.GROUP_NAME, UA.LEVEL
             FROM forum_groups_members UA, forum_groups A
		LEFT JOIN forum_groups_details AD ON
			AD.GROUP_ID=A.GROUP_ID and AD.LANG_ID='.$_SESSION['lang_id'].'
             WHERE A.GROUP_ID=UA.GROUP_ID
                  AND UA.USER_ID='.$_GET['user_id'].'
            ORDER BY AD.GROUP_NAME ASC';
//echo $sql;
     $db->query($sql);

     $t=0;
     while ($row = $db->nextRow()) {
       $data['GROUPS'][$t] = $row;
       $data['GROUPS'][$t]['LEVEL'] = $group_member_level[$row['LEVEL']];
       if (strcmp($_SESSION["_admin"][MENU_USERS], 'FA') == 0)  
         $data['GROUPS'][$t]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del_group', $row['GROUP_ID'], 'user_id', $row['USER_ID']);
       if ($t & 2 > 0) 
         $data['GROUPS'][$t]['ODD'][0]['X'] = 1;
       $t++;    
     }
     if ($t == 0) {
        $data['GROUPS_NORECORDS'][0]['X'] = 1;     
     }

}
else {
  // adding records
  $PRESET_VARS['publish'] = 'Y';
  $PRESET_VARS['allow_blog'] = 'N';
}

ob_end_flush();

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/user_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>