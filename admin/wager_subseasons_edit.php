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

if (empty($_SESSION["_admin"][MENU_ACTIONS_WAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_WAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_WAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if (!isset($_GET['wseason_id'])) {
  header('Location: '.$_POST['referer']);
  exit;
}

if(isset($_POST['form_save'])&&!$ro){
  // required fields
  $s_fields = '';
  $i_fields = array('season_id', 'wseason_id');
  $d_fields = '';
  $c_fields = '';
  $r_fields = array('season_id', 'wseason_id');
  

  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);

    if (empty($_GET['id'])) {
        $db->insert('wager_subseasons', $sdata);
    } else {
        $db->update('wager_subseasons', $sdata, " ID=".$_GET['id']);       
    }
           
    // redirect to news page
    header('Location: '.$_POST['referer']);
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------


// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);

// new or edit?
if (isset($_GET['id'])) {
  $db->select('wager_subseasons', '*', "ID=".$_GET['id']);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header ( 'Location: wager_season.php' );
    exit;
   }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
    }
  }
  $db->free();
//  $data['ID'] = $_GET['id'];
}
else {
  // adding records
  $PRESET_VARS['publish'] = 'Y';
}

$data['SEASON_ID'] = inputManagerSeasons('season_id');
$data['WSEASON_ID'] = $_GET['wseason_id'];

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/wager_subseasons_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>