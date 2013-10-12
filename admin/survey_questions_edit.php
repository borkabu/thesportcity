<?php
/*
===============================================================================
poll_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit poll
  - create new poll
  - edit poll answers
  - create new poll answers
  - delete poll answers

TABLES USED: 
  - BASKET.VOTING
  - BASKET.VOTING_ANSWERS

STATUS:
  - [STAT:FINSHD] finished
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

if (empty($_SESSION["_admin"][MENU_ACTIONS]) || strcmp($_SESSION["_admin"][MENU_ACTIONS], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
$question_id = isset($_GET['question_id']) ? $_GET['question_id'] : '';

//$db->showquery=true;
if(isset($_POST['form_save'])&&!$ro){
  // update fields
  $s_fields = array('survey_id');
  $i_fields = array('priority');
  $d_fields = '';
  $c_fields = array('publish');

  $s_fields_d = array('question');
  $d_fields_d = '';
  $c_fields_d = '';
  $i_fields_d = array('lang_id');
  
  // required fields
  $r_fields = array('question');
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);    
    $tdata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d,  $_POST);

    // proceed to database updates
    if (!empty($_GET['question_id'])) {
      // UPDATE
      $db->update('survey_questions', $sdata, "QUESTION_ID=".$_GET['question_id']);
      $question_id = $_GET['question_id'];

      $tdata['question_id'] = $_GET["question_id"];
      $db->select('survey_questions_details', "*", "QUESTION_ID=".$_GET['question_id']." AND LANG_ID=".$_POST['lang_id']);
      if ($row = $db->nextRow())
	  $db->update('survey_questions_details', $tdata, "QUESTION_ID=".$_GET['question_id']." AND LANG_ID=".$_POST['lang_id']);
      else $db->insert('survey_questions_details', $tdata);
    }
    else {
      // INSERT
      $db->insert('survey_questions', $sdata);
      $tdata['question_id'] = $db->id();
      $db->insert('survey_questions_details',$tdata);

      $question_id = $tdata['question_id'];
    }

    // redirect to news page
    header('Location: '.$_POST['referer']);
    exit;
  }
}
// --- END SAVE ---------------------------------------------------------------

// build data
$data['MENU'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);
$data['lang_id']=$_SESSION['lang_id'];
$data['ID']=$_GET['survey_id'];

// new or edit?
if (isset($_GET['question_id'])) {
  // edit
  $sql = "SELECT C.QUESTION_ID, C.SURVEY_ID, CD.QUESTION, C.PUBLISH, C.PRIORITY
		FROM survey_questions C 
			LEFT JOIN survey_questions_details CD ON CD.QUESTION_ID=C.QUESTION_ID AND CD.LANG_ID=".$_SESSION['lang_id']."			    
		WHERE C.QUESTION_ID=".$_GET['question_id'] ;
  $db->query($sql);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header('Location: survey_edit.php?survey_id='.$_GET['survey_id']);
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
  $data['NO_ANSWERS'][0]['X'] = 1;
}

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/survey_questions_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
