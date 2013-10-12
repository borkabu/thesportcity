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

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
  if (!isset($_GET['season_id'])) 
   {
    header('Location: '.$_POST['referer']);
    exit;
   }
if(isset($_POST['form_save'])&&!$ro){
  // required fields
  $s_fields = '';
  $i_fields = array('season_id', 'number');;
  $d_fields = array('start_date', 'end_date');
  $c_fields = '';
  $r_fields = array('number',
                    'start_date_y', 'start_date_m', 'start_date_d', 'start_date_h', 'start_date_i',
                    'end_date_y', 'end_date_m', 'end_date_d', 'end_date_h', 'end_date_i');
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, 
                           $i_fields, 
                           $d_fields, 
                           $c_fields, 
                           $_POST);

     $sdata['season_id']      = $_GET['season_id'];

     if (!isset($_GET['tour_id'])) {
        $db->insert('manager_tours', $sdata);
      }
     else
      {
        $db->update('manager_tours', $sdata, " TOUR_ID=".$_GET['tour_id']);       
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
if (isset($_GET['tour_id'])) {
  $db->select('manager_tours', '*', "TOUR_ID=".$_GET['tour_id']);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header('Location: manager_seasons.php');
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

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/manager_season_tours_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>