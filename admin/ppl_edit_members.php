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

if (empty($_SESSION["_admin"][MENU_BASKET]) || strcmp($_SESSION["_admin"][MENU_BASKET], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = false;
if (strcmp($_SESSION["_admin"][MENU_BASKET], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
  if (!isset($_GET["user_id"])) 
   {
    header('Location: '.$_POST['referer']);
    exit;
   }

if(isset($_POST['form_save']) && !$ro){
  // required fields
  $s_fields = '';
  $i_fields = array('num');
  $d_fields = array('date_started', 'date_expired');
  $c_fields = '';
  $r_fields = array('date_started_y');
  
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
 
      // teams
      $sdata['USER_ID']      = $_GET['user_id'];
      $sdata['POSITION_ID1'] = evalIntSql($_POST['position_id1']);
      $sdata['POSITION_ID2'] = evalIntSql($_POST['position_id2']);
      $sdata['TEAM_ID']      = $_POST['team_id'];
      
     if (!isset($_GET['id'])) {
        $db->insert('members', $sdata);
      }
     else
      {
        $db->update('members', $sdata, " ID=".$_GET['id']);       
      }

    header('Location: ppl_edit.php?user_id='.$_GET['user_id']);
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------


// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);

  $db->select('busers', 'SPORT_ID', "USER_ID=".$_GET['user_id']);
  $row = $db->nextRow();
  $sport_id = $row['SPORT_ID'];

// new or edit?
if (isset($_GET['id'])) {
 
  // generate membership list
  $db->select('members', '*', "ID=".$_GET['id']);
  if ($row = $db->nextRow()) {
      // team membership
      $teams = array(
        'team_id'      => $row['TEAM_ID'],
        'position_id1' => $row['POSITION_ID1'],
        'position_id2' => $row['POSITION_ID2'],
        'num'          => $row['NUM']
        );
  }
    while (list($key, $val) = each($row)) {
         $PRESET_VARS[strtolower($key)] = $val;
         $data[$key] = $val;
        }
  $db->free();
}
else {
  // adding records
  $PRESET_VARS['publish'] = 'Y';
}

// generate membership items
$opt['class']= "input";
$opt['options'] = $position_types[$sport_id];
  // team memberships
    // try to use db values
    $data['TEAM'][0]['TEAMS'][0]['TEAM_ID'] =  inputTeamsFiltered('team_id', $teams['team_id'], 'SPORT_ID='.$sport_id);    
    $data['TEAM'][0]['TEAMS'][0]['POSITION_ID1'] = $frm->getInput(FORM_INPUT_SELECT, 'position_id1', $teams['position_id1'], $opt, $teams['position_id1']);   
    $data['TEAM'][0]['TEAMS'][0]['POSITION_ID2'] = $frm->getInput(FORM_INPUT_SELECT, 'position_id2', $teams['position_id2'], $opt, $teams['position_id2']);   
    if ($teams['num'] == '0')
      $teams['num'] = ' 0';

    $data['TEAM'][0]['TEAMS'][0]['NUM'] = $frm->getField('adm_user', 'num', $teams['num'], $teams['num']);
  
// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/ppl_members.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>