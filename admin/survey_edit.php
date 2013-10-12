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

include('../class/prepare.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS]) || strcmp($_SESSION["_admin"][MENU_ACTIONS], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS], 'RO') == 0)
  $ro = TRUE;

if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_BASKET], 'FA') == 0) {
  $db->delete('survey_answers', "QUESTION_ID=".$_GET['del']);
  $db->delete('survey_answers_details', "QUESTION_ID=".$_GET['del']);
  $db->delete('survey_questions', "QUESTION_ID=".$_GET['del']);
  $db->delete('survey_questions_details', "QUESTION_ID=".$_GET['del']);
}

if (isset($_GET['del_answer']) && !$ro && strcmp($_SESSION["_admin"][MENU_BASKET], 'FA') == 0) {
  $db->delete('survey_answers', "ANSWER_ID=".$_GET['del_answer']);
  $db->delete('survey_answers_details', "ANSWER_ID=".$_GET['del_answer']);
  $db->delete('survey_votes', "ANSWER_ID=".$_GET['del_answer']);
}

// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('survey_questions', array('PUBLISH' => "'Y'"),'QUESTION_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('survey_questions', array('PUBLISH' => "'N'"),'QUESTION_ID='.$_GET['deactivate']);
}

// activate
if (isset($_GET['activate_answer']) && !$ro) {
  $db->update('survey_answers', array('PUBLISH' => "'Y'"),'ANSWER_ID='.$_GET['activate_answer']);
}
// deactivate
if (isset($_GET['deactivate_answer']) && !$ro) {
  $db->update('survey_answers', array('PUBLISH' => "'N'"),'ANSWER_ID='.$_GET['deactivate_answer']);
}

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save'])&&!$ro){
  // required fields
  $s_fields = '';
  $i_fields = '';
  $d_fields = array('start_date', 'end_date');
  $c_fields = array('allow_change');
  $r_fields = '';
  
  $s_fields_d = array('title');
  $d_fields_d = '';
  $c_fields_d = '';
  $i_fields_d = array('lang_id');

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
    if (!empty($_GET['id'])) {
      // UPDATE
      $db->update('survey', $sdata, "ID=".$_GET['id']);
      $tdata['id'] = $_GET["id"];
      $db->select('survey_details', "*", "ID=".$_GET["id"]." AND LANG_ID=".$_POST['lang_id']);
      if ($row = $db->nextRow())
	  $db->update('survey_details', $tdata, "ID=".$_GET["id"]." AND LANG_ID=".$_POST['lang_id']);
      else $db->insert('survey_details', $tdata);
    }
    else {
      // INSERT
      $db->insert('survey', $sdata);
      $tdata['id'] = $db->id();
      $db->insert('survey_details',$tdata);
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
$data['lang_id']=$_SESSION['lang_id'];

// new or edit?
if (isset($_GET['id'])) {
  // edit
  $data['ID'] = $_GET['id'];
  $sql = "SELECT C.ID, C.START_DATE, C.END_DATE, C.ALLOW_CHANGE, CD.TITLE, C.PUBLISH
		FROM survey C LEFT JOIN survey_details CD ON CD.ID=C.ID AND CD.LANG_ID=".$_SESSION['lang_id']."			    
		WHERE C.ID=".$_GET['id'] ;

  $db->query($sql);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header('Location: survey.php');
    exit;
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
    }

   $sql = "SELECT * FROM languages ORDER BY ID";
   $db->query($sql);
   while ($row = $db->nextRow()) {
     $languages[$row['ID']] = $row;
   }

  // read team listings
    $sql = "SELECT SQ.SURVEY_ID, SQ.QUESTION_ID, SQD.QUESTION, SQ.PUBLISH, SQ.PRIORITY,
		GROUP_CONCAT(SQD2.LANG_ID) as LANGUAGES, SA.PUBLISH AS PUBLISH_ANSWER,
		SA.ANSWER_ID, SAD.ANSWER, GROUP_CONCAT(SAD2.LANG_ID) as LANGUAGES2
            FROM survey_questions SQ
		left JOIN survey_questions_details SQD ON SQ.QUESTION_ID = SQD.QUESTION_ID  AND SQD.LANG_ID=".$_SESSION['lang_id']."
		left join survey_questions_details SQD2 ON SQD2.QUESTION_ID=SQ.QUESTION_ID
		left join survey_answers SA ON SQ.QUESTION_ID = SA.QUESTION_ID 
		left JOIN survey_answers_details SAD ON SA.ANSWER_ID = SAD.ANSWER_ID  AND SAD.LANG_ID=".$_SESSION['lang_id']."
		left join survey_answers_details SAD2 ON SAD2.ANSWER_ID=SA.ANSWER_ID
            WHERE SQ.SURVEY_ID=".$_GET['id']."

	    GROUP BY SQ.QUESTION_ID, SA.ANSWER_ID 
            ORDER BY PRIORITY, QUESTION";

    $db->query($sql);
    $t = 0;
    while ($row = $db->nextRow()) {
      $data['QUESTION'][$row['PRIORITY']]['SURVEY_ID'] = $row['SURVEY_ID'];
      $data['QUESTION'][$row['PRIORITY']]['PUBLISH'] = $row['PUBLISH'];
      $data['QUESTION'][$row['PRIORITY']]['PRIORITY'] = $row['PRIORITY'];
      $data['QUESTION'][$row['PRIORITY']]['QUESTION'] = $row['QUESTION'];
      $data['QUESTION'][$row['PRIORITY']]['QUESTION_ID'] = $row['QUESTION_ID'];
      $data['QUESTION'][$row['PRIORITY']]['SURVEY_ID'] = $_GET['id'];
      if (strcmp($_SESSION["_admin"][MENU_ACTIONS], 'FA') == 0)  
        $data['QUESTION'][$row['PRIORITY']]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['QUESTION_ID']);

      if ($row['PRIORITY'] & 2 > 0) 
        $data['QUESTION'][$row['PRIORITY']]['ODD'][0]['X'] = 1;

      if ($row['PUBLISH'] == 'Y')
        $data['QUESTION'][$row['PRIORITY']]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['QUESTION_ID']);
      else
        $data['QUESTION'][$row['PRIORITY']]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['QUESTION_ID']);      

      $used_langs = explode(",", $row['LANGUAGES']);
      foreach ($languages as $language) {
        if (in_array($language['ID'], $used_langs)) {
          $data['QUESTION'][$row['PRIORITY']]['LANGS'][$language['ID']]['USED'][0] = $language;
          $data['QUESTION'][$row['PRIORITY']]['LANGS'][$language['ID']]['USED'][0]['QUESTION_ID'] = $row['QUESTION_ID'];
          $data['QUESTION'][$row['PRIORITY']]['LANGS'][$language['ID']]['USED'][0]['SURVEY_ID'] = $row['SURVEY_ID'];
        }
        else {
          $data['QUESTION'][$row['PRIORITY']]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
          $data['QUESTION'][$row['PRIORITY']]['LANGS'][$language['ID']]['NOTUSED'][0]['QUESTION_ID'] = $row['QUESTION_ID'];
          $data['QUESTION'][$row['PRIORITY']]['LANGS'][$language['ID']]['NOTUSED'][0]['SURVEY_ID'] = $row['SURVEY_ID'];
        }
      }

      if (!empty($row['ANSWER_ID'])) {
        $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['SURVEY_ID'] = $row['SURVEY_ID'];
        $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['QUESTION_ID'] = $row['QUESTION_ID'];
        $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['ANSWER_ID'] = $row['ANSWER_ID'];
        $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['ANSWER'] = $row['ANSWER'];
        $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['PUBLISH'] = $row['PUBLISH_ANSWER'];
        if ($row['PUBLISH_ANSWER'] == 'Y')
          $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate_answer', $row['ANSWER_ID'], '', 'id', $row['SURVEY_ID']);
        else
          $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate_answer', $row['ANSWER_ID'], '', 'id', $row['SURVEY_ID']);      
  
        if (strcmp($_SESSION["_admin"][MENU_ACTIONS], 'FA') == 0)  
          $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del_answer', $row['ANSWER_ID'], '', 'id', $row['SURVEY_ID']);
  
        $used_langs2 = explode(",", $row['LANGUAGES2']);
        foreach ($languages as $language) {
          if (in_array($language['ID'], $used_langs2)) {
            $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['LANGS'][$language['ID']]['USED'][0] = $language;
            $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['LANGS'][$language['ID']]['USED'][0]['ANSWER_ID'] = $row['ANSWER_ID'];
            $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['LANGS'][$language['ID']]['USED'][0]['QUESTION_ID'] = $row['QUESTION_ID'];
            $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['LANGS'][$language['ID']]['USED'][0]['SURVEY_ID'] = $row['SURVEY_ID'];
          }
          else {
            $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
            $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['LANGS'][$language['ID']]['NOTUSED'][0]['ANSWER_ID'] = $row['ANSWER_ID'];
            $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['LANGS'][$language['ID']]['NOTUSED'][0]['QUESTION_ID'] = $row['QUESTION_ID'];
            $data['QUESTION'][$row['PRIORITY']]['ANSWERS'][$t]['LANGS'][$language['ID']]['NOTUSED'][0]['SURVEY_ID'] = $row['SURVEY_ID'];
          }
        }
      }
      $t++;      
    }
    if ($t == 0)
     $data['QUESTION_NORECORDS'][0]['X'] = 1;
//    print_r($data['QUESTION']);
  }
  $db->free();
}
else {
  // adding records
}

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/survey_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>