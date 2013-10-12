<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
ppl.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of people records
  - deletes people records

TABLES USED:
  - BASKET.USERS
  - BASKET.MEMBERS
  - BASKET.TEAMS
  - BASKET.TOURNAMENTS
  - BASKET.ORGANIZATIONS

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
include('../class/manager.inc.php');
include('../class/manager_log.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------

$error = FALSE;
  if (!isset($_GET['player_id']) || !isset($_GET['season_id']))
  {
    header('Location: manager_season.php');
    exit;
  }

  $manager = new Manager($_GET['season_id']);
//echo $tours;
if(isset($_POST['form_save'])&&!$ro) {

  if (!$manager->setPrice($_POST['player_id'], $_POST['start_value'])) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  } else {
    header('Location: '.$_POST['referer']);
    exit;
  }

}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);

// new or edit?
if (isset($_GET['player_id'])) {
  $sql = "SELECT U.LAST_NAME, U.FIRST_NAME, MP.* FROM busers U left join manager_players MP
	    ON  MP.SEASON_ID=".$_GET['season_id']." AND MP.PLAYER_ID=".$_GET['player_id']." 
            WHERE U.USER_ID=".$_GET['player_id'];

  $db->query($sql);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
   }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    $data['LAST_NAME'] = $row['LAST_NAME'];
    $data['FIRST_NAME'] = $row['FIRST_NAME'];
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;      
    }
  }
  $db->free();
}

 $data['SEASON_ID'] = $_GET['season_id'];
 $data['PLAYER_ID'] = $_GET['player_id'];


// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/manager_price_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();
// close connections
include('../class/db_close.inc.php');
?>