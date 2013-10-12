<?php
/*
===============================================================================
sched_races_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit game records
  - edit keywords
  - create new game record

TABLES USED: 
  - BASKET.GAMES
  - BASKET.GAMES_ON_TV
  - BASKET.KEYWORDS

STATUS:
  - [STAT:FINSHD] finished
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

//$db->showquery=true;

if (empty($_SESSION["_admin"][MENU_GAMES]) || strcmp($_SESSION["_admin"][MENU_GAMES], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_GAMES], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save']) && !$ro){
  // update fields
  $s_fields = array('title');;
  $i_fields = array('season_id');
  $d_fields = array('start_date');
  $c_fields = array('publish');
  
  // required fields
  $r_fields = array('title', 
                    'start_date_y', 'start_date_m', 'start_date_d');
  
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
    
//    echo $game_id;
    // proceed to database updates
    if (!empty($_GET['game_id'])) {
      // UPDATE
      $db->update('games_races', $sdata, "GAME_ID=".$_GET['game_id']);
    }
    else {
      // INSERT
      $db->insert('games_races', $sdata);
    }
    
    // redirect to news page
    header('Location: sched_races.php');
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);

// new or edit?
if (isset($_GET['game_id'])) {
  // edit
  $fields = 'GAME_ID, SEASON_ID, TITLE,
            SUBSTRING(START_DATE, 1, 16) START_DATE,
            PUBLISH';
  $db->select('games_races', $fields, "GAME_ID=".$_GET['game_id']);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    $db->close();
    header('Location: sched_races.php');
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

// get common inputs
$data['SEASON_ID'] = inputSeasons('season_id');

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/sched_races_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>