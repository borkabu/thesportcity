<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
help.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows help item
  = shows help topic index

TABLES USED: 
  - BASKET.HELP

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
include('class/update.inc.php');

// classes
include('class/db.class.php');
include('class/template.class.php');
include('class/language.class.php');
include('class/form.class.php');

// connections
include('class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
include('class/survey.inc.php');

// --- build content data -----------------------------------------------------
// ----------------------------------------------------------------------------

$content = '';
if ($auth->userOn() && isset($_POST['answer_id']) && isset($_POST['question_id'])) {
   // check that user already has voted
   $survey = new Survey();    
   $survey->addVote($_POST['question_id'], $_POST['answer_id']);
  // show results 
   if (isset($_POST['box'])) {
     $content .= $surveybox->getSurveyQuestionBox(true);
   } else {
     $data['QUESTION'][0] = $survey->getQuestionResults($_POST['question_id']);

     $tpl->setCacheLevel(TPL_CACHE_NOTHING);
     $tpl->setTemplateFile('tpl/survey_answer_question.tpl.html');
     $tpl->addData($data);
     $content = $tpl->parse();
   }
   echo $content;
} else if ($auth->userOn() && isset($_POST['reset']) && isset($_POST['question_id'])) {
   $survey = new Survey();    
   $survey->resetVote($_POST['question_id']);
   $content .= $surveybox->getSurveyQuestion($_POST['question_id']);
   echo $content;
}
// close connections
include('class/db_close.inc.php');
?>