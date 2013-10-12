<?php
/*
===============================================================================
cat_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit individual category
  - create new category

TABLES USED: 
  - BASKET.CATS

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

if (empty($_SESSION["_admin"][MENU_PARAMETERS]) || strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'RO') == 0)
  $ro = TRUE;

//$db->showquery=true;
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save'])&&!$ro){
  // required fields
  $s_fields = array('name');
  $d_fields = array('end_date');;
  $c_fields = array('publish');
  $i_fields = array('type', 'season_id', 'frequency');
  $r_fields = array('name');

  $s_fields_d = array('descr', 'title', 'header', 'footer');
  $d_fields_d = '';
  $c_fields_d = '';
  $i_fields_d = array('lang_id');
  $r_fields_d = array('title');
	
	// check for required fields
  if (!requiredFieldsOk ( $r_fields, $_POST ) || 
            !requiredFieldsOk ( $r_fields_d, $_POST )) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields,  $_POST);
    $tdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d,  $_POST);

    // proceed to database updates
    if(!empty($_GET["id"])){
      // UPDATE
      $db->update('newsletter', $sdata, "ID=".$_GET["id"]);
      $tdata['id'] = $_GET["id"];
      $db->replace('newsletter_details', $tdata);
    }
    else {
      // INSERT
	$db->insert('newsletter', $sdata);
        $tdata['id'] = $db->id();
        $db->insert('newsletter_details',$tdata);
	unset ( $sdata );
    }
    
    // redirect to list page
    header('Location: newsletter.php');
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['id'])) {
  // edit
  $fields='*';
  $db->select('newsletter',$fields,"ID=".$_GET['id']);

  $sql = "SELECT F.ID, F.NAME, FD.TITLE, F.PUBLISH, F.TYPE, F.FREQUENCY, F.SEASON_ID,
                F.END_DATE, FD.DESCR, FD.HEADER, FD.FOOTER
			FROM newsletter F LEFT JOIN newsletter_details FD ON FD.ID=F.ID AND FD.LANG_ID=".$_SESSION['lang_id']."
		WHERE F.ID=".$_GET['id'] ;
  $db->query($sql);

  if (!$row = $db->nextRow()) {
    // ERROR! No such item. redirect to list
	$PRESET_VARS['publish']='Y';
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
  // adding record
  $PRESET_VARS['publish'] = 'Y';
}

if ($PRESET_VARS['type'] == 1)
  $data['SEASON_ID'] = inputManagerSeasons('season_id', isset($PRESET_VARS['season_id']) ? $PRESET_VARS['season_id'] : '', 80, true);
else if ($PRESET_VARS['type'] == 2)
  $data['SEASON_ID'] = inputWagerSeasons('season_id', isset($PRESET_VARS['season_id']) ? $PRESET_VARS['season_id'] : '', 80, true);
else if ($PRESET_VARS['type'] == 3)
  $data['SEASON_ID'] = inputBracketSeasons('season_id', isset($PRESET_VARS['season_id']) ? $PRESET_VARS['season_id'] : '', 80, true);

// filtering
$opt = array(
  'class' => 'input',
  'options' => array(
    0 => 'LANG_GENERAL_U',
    1 => 'LANG_MANAGER_U',
    2 => 'LANG_WAGER_U',
    3 => 'LANG_ARRANGER_U'
  )
);

$data['TYPE'] = $frm->getInput(FORM_INPUT_SELECT, 'type', isset($PRESET_VARS['type']) ? $PRESET_VARS['type'] : '', $opt, isset($PRESET_VARS['type']) ? $PRESET_VARS['type'] : '');

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/newsletter_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>