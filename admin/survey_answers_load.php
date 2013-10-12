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

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if (isset($_POST['answers']) && isset($_POST['question_id']) && !$ro){
  $answers = explode('\n', $_POST['answers']);
  reset($answers);
  foreach($answers as $answer) {
    $sql = "SELECT C.ANSWER_ID, CD.ANSWER
        FROM survey_answers C
		left JOIN survey_answers_details CD ON C.ANSWER_ID = CD.ANSWER_ID AND CD.LANG_ID=".$_SESSION['lang_id']."
        WHERE C.QUESTION_ID=".$_POST['question_id']."
		AND CD.ANSWER IS NULL
        ORDER BY C.ANSWER_ID
	LIMIt 1";
    $db->query($sql);

    if ($row = $db->nextRow()) {
      unset($tdata);
      $tdata['answer_id'] = $row['ANSWER_ID'];
      $tdata['lang_id'] = $_SESSION['lang_id'];
      $tdata['answer'] = "'".$answer."'";
      $db->insert('survey_answers_details',$tdata);
    } else {
      unset($sdata);
      unset($tdata);
      $sdata['question_id'] = $_POST['question_id'];
      $sdata['publish'] = "'Y'";
      $db->insert('survey_answers', $sdata);
      $tdata['answer_id'] = $db->id();
      $tdata['lang_id'] = $_SESSION['lang_id'];
      $tdata['answer'] = "'".$answer."'";
      $db->insert('survey_answers_details',$tdata);
    }
  }
}

 $sql = "SELECT * FROM languages ORDER BY ID";
 $db->query($sql);
 while ($row = $db->nextRow()) {
   $languages[$row['ID']] = $row;
 }

$sql = "SELECT SQ.SURVEY_ID, C.QUESTION_ID, C.ANSWER_ID, CD.ANSWER, 
	       GROUP_CONCAT(CD2.LANG_ID) as LANGUAGES, C.PUBLISH
        FROM survey_questions SQ, survey_answers C
		left JOIN survey_answers_details CD ON C.ANSWER_ID = CD.ANSWER_ID AND CD.LANG_ID=".$_SESSION['lang_id']."
		left join survey_answers_details CD2 ON CD2.ANSWER_ID=C.ANSWER_ID
        WHERE C.QUESTION_ID=".$_POST['question_id']."
		AND SQ.QUESTION_ID=C.QUESTION_ID
	GROUP BY C.ANSWER_ID
        ORDER BY C.ANSWER_ID";

$db->query($sql);

$c = 0;
while ($row = $db->nextRow()) {
  $data['ANSWERS'][$c] = $row;

  $used_langs = explode(",", $row['LANGUAGES']);
  foreach ($languages as $language) {
    if (in_array($language['ID'], $used_langs)) {
      $data['ANSWERS'][$c]['LANGS'][$language['ID']]['USED'][0] = $language;
      $data['ANSWERS'][$c]['LANGS'][$language['ID']]['USED'][0]['ANSWER_ID'] = $row['ANSWER_ID'];
    }
    else {
      $data['ANSWERS'][$c]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
      $data['ANSWERS'][$c]['LANGS'][$language['ID']]['NOTUSED'][0]['ANSWER_ID'] = $row['ANSWER_ID'];
    }
  }
  
  if ($row['PUBLISH'] == 'Y')
    $data['ANSWERS'][$c]['ACT_URL'] = "survey_edit.php".url('deactivate_answer', $row['ANSWER_ID'], '', 'id', $row['SURVEY_ID']);
  else
    $data['ANSWERS'][$c]['ACT_URL'] = "survey_edit.php".url('activate_answer', $row['ANSWER_ID'], '', 'id', $row['SURVEY_ID']);

  if (strcmp($_SESSION["_admin"][MENU_ACTIONS], 'FA') == 0)  
    $data['ANSWERS'][$c]['DEL'][0]['DEL_URL'] = "survey_edit.php".url('del_answer', $row['ANSWER_ID'], '', 'id', $row['SURVEY_ID']);
  
  $c++;
}
$db->free();


$data['QUESTION_ID'] = $_POST['question_id'];
// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/survey_answer_load.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>