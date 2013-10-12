<?php

class Survey {
 
  function Survey() {
  }
   
  function addVote($question_id, $answer_id) {
     global $auth;
     global $db;

     if ($auth->userOn()) {
       $sql = "SELECT * from survey_votes WHERE USER_ID=".$auth->getUserId()." AND QUESTION_ID=".$question_id;
       $db->query($sql);
       if (!$row = $db->nextRow()) {
          // insert vote
          unset($sdata);
	  $sdata['USER_ID'] = $auth->getUserId();
	  $sdata['ANSWER_ID'] = $answer_id;
	  $sdata['QUESTION_ID'] = $question_id;
	  $sdata['DATE_VOTED'] = "NOW()";
	  $db->insert('survey_votes', $sdata);
          unset($sdata);          
	  $sdata['VOTED'] = "VOTED+1";
	  $db->update('survey_answers', $sdata, "answer_id=".$answer_id);
       }
     }
  }
 
  function resetVote($question_id) {
     global $auth;
     global $db;
//$db->showquery=true;
     if ($auth->userOn()) {
       $sql = "SELECT * from survey_votes WHERE USER_ID=".$auth->getUserId()." AND QUESTION_ID=".$question_id;
       $db->query($sql);
       if ($row = $db->nextRow()) {
	  $db->delete('survey_votes',  "USER_ID=".$auth->getUserId()." AND QUESTION_ID=".$question_id);
          unset($sdata);          
	  $sdata['VOTED'] = "VOTED-1";
	  $db->update('survey_answers', $sdata, "answer_id=".$row['ANSWER_ID']);       
       }
     }
  }
 
  function addAnswer($question_id, $answer) {
    global $db;
    global $auth;
    global $_SESSION;
    global $langs;
    global $conf_site_url;

    $sql="SELECT * FROM survey_questions_details
		WHERE question_id=".$question_id."
		  AND lang_id=".$_SESSION['lang_id'];
    $db->query($sql);
    if ($row=$db->nextRow()) {    
      unset($sdata);
      $sdata['QUESTION_ID'] = $question_id;
      $sdata['ANSWER'] = "'".$answer."'";
      $sdata['USER_ID'] = "'".$auth->getUserId()."'";
      $sdata['LANG_ID'] = "'".$_SESSION['lang_id']."'";
      $sdata['STATUS'] = 0;
      $sdata['DATE_SUGGESTED'] = "NOW()";
      $actkey = gen_rand_string(0, 10);
      $sdata['ACTKEY'] = "'".$actkey."'";
      $db->insert('survey_answers_suggested', $sdata);
      $answer_id = $db->id();

      $edata['USER_NAME'] = $_SESSION['_user']['USER_NAME'];
      $edata['QUESTION'] = $row['QUESTION'];
      $edata['ANSWER'] = $answer;
      $edata['URL_APPROVE'] = $conf_site_url."user_survey_answer_activation.php?mode=answer_approve&answer_id=".$answer_id."&actkey=".$actkey;
      $edata['URL_IGNORE'] = $conf_site_url."user_survey_answer_activation.php?mode=answer_ignore&answer_id=".$answer_id."&actkey=".$actkey;
      $edata['URL_DISAPPROVE'] = $conf_site_url."user_survey_answer_activation.php?mode=answer_disapprove&answer_id=".$answer_id."&actkey=".$actkey;
      
      $email = new Email($langs, $_SESSION['_lang']);
      $email->getEmailFromTemplate ('email_survey_answer_suggest', $edata) ;
      $subject = $langs['LANG_EMAIL_SURVEY_ANSWER_SUGGEST_LINE_1'];
      if ($email->sendAdmin($subject))
        return true;
      else return false;
    }
    else return false;
    // send email
  }

  function getQuestionResults($question_id) {
    global $db;


    $questions = '';
    $sql = "SELECT  
	      SQ.PRIORITY,
             SQD.QUESTION_ID,
	       SUM(SA.VOTED) SUM_VOTED, MAX(SA.VOTED) MAX_VOTED
           FROM survey_questions SQ 
                left join survey_questions_details SQD on SQD.QUESTION_ID=SQ.QUESTION_ID AND SQD.QUESTION IS NOT NULL
			AND SQD.LANG_ID=".$_SESSION['lang_id']."
		  left join survey_answers SA on SA.QUESTION_ID=SQ.QUESTION_ID 
		  left join survey_answers_details SAD on SAD.ANSWER_ID=SA.ANSWER_ID AND SAD.LANG_ID=".$_SESSION['lang_id']."
           WHERE SQ.QUESTION_ID=".$question_id."
	     GROUP BY QUESTION_ID
           ORDER BY SQ.PRIORITY ASC, SQ.QUESTION_ID ASC" ;
    $db->query($sql);
    while ($row = $db->nextRow()) {
	$questions[$row['QUESTION_ID']]['MAX_VOTED'] = $row['MAX_VOTED'];
	$questions[$row['QUESTION_ID']]['SUM_VOTED'] = $row['SUM_VOTED'];
    }

    $data = '';
    $sql = "SELECT SQD.QUESTION_ID, QUESTION, ANSWER, VOTED 
		FROM 
		  survey_questions_details SQD, 
		  survey_answers SA 
  	          left join survey_answers_details SAD on SAD.ANSWER_ID=SA.ANSWER_ID AND SAD.LANG_ID=".$_SESSION['lang_id']."
            WHERE SQD.QUESTION_ID=".$question_id." AND SQD.LANG_ID=".$_SESSION['lang_id']."
		   AND SA.QUESTION_ID=SQD.QUESTION_ID
	    ORDER BY SA.ANSWER_ID ASC";
    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
      $data['QUESTION'] = $row['QUESTION'];
      $data['ANSWERS'][$c] = $row;
      $data['ANSWERS'][$c]['WIDTH'] = round($row['VOTED']*100/$questions[$row['QUESTION_ID']]['SUM_VOTED']);
      $c++;
    }
  
    return $data;
  }
}

?>