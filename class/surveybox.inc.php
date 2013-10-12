<?php
/*
===============================================================================
events.inc.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - generate event list data

TABLES USED: 
  - BASKET.EVENTS

STATUS:
  - [STAT:INPRGS] in progress

TODO:
  - [TODO:PUBVAR] main functionality
===============================================================================
*/

class SurveyBox extends Box{

  function SurveyBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getSurveyQuestionBox ($box=false) {
    global $tpl;
    global $db;
    global $auth;
    
    // content
    if ($auth->userOn()) {
      $data = $this->getSurveyQuestionData(); 
      if ($data != '') {
        $this->data['SURVEY'][0] = $this->getSurveyQuestionData(); 
        $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	

        if ($box) {
          $tpl->setTemplateFile('tpl/bar_survey_question2.tpl.html');
        } else {
          $tpl->setTemplateFile('tpl/bar_survey_question.tpl.html');
        }
        $tpl->addData($this->data);
        return $tpl->parse();
      }
    }
  } 

  function getSurveyQuestion ($question_id) {
    global $tpl;
    global $db;
    global $auth;
    
    // content
    if ($auth->userOn()) {
      $data = $this->getSurveyQuestionData($question_id); 
      if ($data != '') {
        $this->data['SURVEY'][0] = $data; 
        $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
        $tpl->setTemplateFile('tpl/bar_survey_question3.tpl.html');
        $tpl->addData($this->data);
        return $tpl->parse();
      }
    }
  } 

  function getSurveys ($page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    global $_SESSION;
    
    // content

    $surveys = $this->getSurveyData($page, $perpage);
    $this->rows = count($surveys);	

    $smarty->assign("surveys", $surveys);
    $start = getmicrotime();
    $content = $smarty->fetch('smarty_tpl/surveys.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/surveys.smarty'.($stop-$start);
    return $content;
  } 

  function getSurveyItem ($survey_id) {
    global $tpl;
    global $db;
    global $_SESSION;
    
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/survey_item.tpl.html');
    $data = $this->getSurveyItemData($survey_id);
    if ($data != '') {
      $this->data['SURVEY'][0] = $data;
//print_r($this->data['SURVEY']);
      $tpl->addData($this->data);
      return $tpl->parse();
    }
    else {
      return '';
    }
   
  } 

  function getSurveyItemData($survey_id) {
    global $db;
    global $_SESSION;
    global $auth;
    global $langs;
    global $html_page;

    $data='';

    $forumpermissions = new ForumPermission();
    $can_vote = $forumpermissions->canVoteContent();
    if (is_numeric($survey_id)) {

      $questions = '';
      $sql = "SELECT SQ.PRIORITY, SQD.QUESTION_ID,
	       SUM(SA.VOTED) SUM_VOTED, MAX(SA.VOTED) MAX_VOTED
             FROM survey_questions SQ 
                  left join survey_questions_details SQD on SQD.QUESTION_ID=SQ.QUESTION_ID AND SQD.QUESTION IS NOT NULL
			AND SQD.LANG_ID=".$_SESSION['lang_id']."
		  left join survey_answers SA on SA.QUESTION_ID=SQ.QUESTION_ID 
		  left join survey_answers_details SAD on SAD.ANSWER_ID=SA.ANSWER_ID AND SAD.LANG_ID=".$_SESSION['lang_id']."
             WHERE SQ.SURVEY_ID=".$survey_id."
			AND SQ.PUBLISH='Y'
	     GROUP BY QUESTION_ID
             ORDER BY SQ.PRIORITY ASC, SQ.QUESTION_ID ASC" ;
      $db->query($sql);
      while ($row = $db->nextRow()) {
	$questions[$row['QUESTION_ID']]['MAX_VOTED'] = $row['MAX_VOTED'];
	$questions[$row['QUESTION_ID']]['SUM_VOTED'] = $row['SUM_VOTED'];
      }

      if ($auth->userOn()) {
             $sql = "SELECT 
                      N.ID, ND.TITLE, N.ALLOW_CHANGE, N.END_DATE < NOW() AS OVER,
                      SUBSTRING(N.START_DATE, 1, 16) START_DATE, 
                      SUBSTRING(N.END_DATE, 1, 16) END_DATE, SQ.PRIORITY,
                      SQD.QUESTION_ID, SQD.QUESTION, SAD.ANSWER_ID, SAD.ANSWER, 
		      SV.USER_ID, SA.VOTED, SAS.ANSWER_ID AS SUGGESTION, SAS.STATUS,
		      U.USER_NAME as SUGGESTER
                    FROM 
                      survey N, survey_details ND
		     left join topic T ON T.TOPIC_ID=ND.TOPIC_ID AND T.LANG_ID=".$_SESSION['lang_id']."
                             left join survey_questions SQ on SQ.SURVEY_ID=ND.ID AND SQ.PUBLISH = 'Y'
                             left join survey_questions_details SQD on SQD.QUESTION_ID=SQ.QUESTION_ID AND SQD.QUESTION IS NOT NULL
			AND SQD.LANG_ID=".$_SESSION['lang_id']."
			     left join survey_answers SA on SA.QUESTION_ID=SQ.QUESTION_ID 
			     left join survey_answers_details SAD on SAD.ANSWER_ID=SA.ANSWER_ID AND SAD.LANG_ID=".$_SESSION['lang_id']."
			     left join survey_answers_suggested SAS on SAS.QUESTION_ID=SQ.QUESTION_ID 
						AND SAS.LANG_ID=".$_SESSION['lang_id']."
						AND SAS.USER_ID=".$auth->getUserId()."
			     left join survey_answers_suggested SAS1 on SAS1.NEW_ANSWER_ID=SA.ANSWER_ID
			     left join users U on SAS1.USER_ID=U.USER_ID
		             LEFT JOIN survey_votes SV ON SV.QUESTION_ID=SA.QUESTION_ID AND SV.USER_ID=".$auth->getUserId()."
                    WHERE N.ID=".$survey_id."
			AND N.PUBLISH='Y'
			AND ND.ID=N.ID 
			AND ND.LANG_ID=".$_SESSION['lang_id']."
			and SAD.ANSWER_ID is not null 
                    ORDER BY SQ.PRIORITY ASC, SQ.QUESTION_ID ASC, SAD.ANSWER_ID ASC" ;
      } else {
             $sql = "SELECT 
                      N.ID, ND.TITLE, N.ALLOW_CHANGE, N.END_DATE < NOW() AS OVER,
                      SUBSTRING(N.START_DATE, 1, 16) START_DATE, 
                      SUBSTRING(N.END_DATE, 1, 16) END_DATE, SQ.PRIORITY,
                      SQD.QUESTION_ID, SQD.QUESTION, SAD.ANSWER_ID, SAD.ANSWER, 
		      SA.VOTED, U.USER_NAME as SUGGESTER
                    FROM 
                      survey N, survey_details ND
		     left join topic T ON T.TOPIC_ID=ND.TOPIC_ID AND T.LANG_ID=".$_SESSION['lang_id']."
                             left join survey_questions SQ on SQ.SURVEY_ID=ND.ID  AND SQ.PUBLISH = 'Y'
                             left join survey_questions_details SQD on SQD.QUESTION_ID=SQ.QUESTION_ID AND SQD.QUESTION IS NOT NULL
			AND SQD.LANG_ID=".$_SESSION['lang_id']."
			     left join survey_answers SA on SA.QUESTION_ID=SQ.QUESTION_ID 
			     left join survey_answers_details SAD on SAD.ANSWER_ID=SA.ANSWER_ID AND SAD.LANG_ID=".$_SESSION['lang_id']."
			     left join survey_answers_suggested SAS on SAS.NEW_ANSWER_ID=SA.ANSWER_ID
			     left join users U on SAS.USER_ID=U.USER_ID
                    WHERE N.ID=".$survey_id."
			AND N.PUBLISH='Y'
			AND ND.ID=N.ID 
			AND ND.LANG_ID=".$_SESSION['lang_id']."
			and SAD.ANSWER_ID is not null 
                    ORDER BY SQ.PRIORITY ASC, SQ.QUESTION_ID ASC, SAD.ANSWER_ID ASC" ;
      }
//echo $sql;
      $db->query($sql);
      while ($row = $db->nextRow()) {
	$data['TITLE'] = $row['TITLE']; 
	$allow_change = $row['ALLOW_CHANGE'];
        $data['LANG'] = $_SESSION['_lang']; 
	if (!empty($row['USER_ID'])) {
          $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['RESULTS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTION_ID'] = $row['QUESTION_ID']; 
          $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['RESULTS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTION'] = $row['QUESTION']; 
          $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['RESULTS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['ANSWERS'][$row['ANSWER_ID']] = $row;
          if (isset($questions[$row['QUESTION_ID']]) && $questions[$row['QUESTION_ID']]['SUM_VOTED'] > 0) {
            $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['RESULTS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['ANSWERS'][$row['ANSWER_ID']]['WIDTH'] = round($row['VOTED']*100/$questions[$row['QUESTION_ID']]['SUM_VOTED']);
	    $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['RESULTS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['ANSWERS'][$row['ANSWER_ID']]['VOTED'] = $row['VOTED'];
            $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['RESULTS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['VOTED'][0]['VOTED'] = $questions[$row['QUESTION_ID']]['SUM_VOTED'];
          }
	  else $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['RESULTS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['ANSWERS'][$row['ANSWER_ID']]['WIDTH'] = "0";
          if ($allow_change == 'Y' && !$row['OVER'])
            $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['RESULTS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['RESET'][0]['QUESTION_ID'] = $row['QUESTION_ID'];
            
        }
        else if ($auth->userOn()) {
          $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTION_ID'] = $row['QUESTION_ID']; 
          $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTION'] = $row['QUESTION']; 
          $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['ANSWERS'][$row['ANSWER_ID']] = $row;
  	  if (isset($row['SUGGESTION']) && $row['SUGGESTION'] > 0) {
            $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['SUGGESTED'][0][$row['STATUS']][0] = $row;
	    $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['SUGGESTED'][0]['QUESTION_ID'] = $row['QUESTION_ID'];
          } else {
            $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['SUGGEST'][0] = $row;	   
          }
          if (!empty($row['SUGGESTER']))
            $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['ANSWERS'][$row['ANSWER_ID']]['SUGGESTED_BY'][0]['USER_NAME'] = $row['SUGGESTER'];
          if ($questions[$row['QUESTION_ID']]['SUM_VOTED'] > 0)
            $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['VOTED'][0]['VOTED'] = $questions[$row['QUESTION_ID']]['SUM_VOTED'];
        } else {
          $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS_AUTH'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTION_ID'] = $row['QUESTION_ID']; 
          $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS_AUTH'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTION'] = $row['QUESTION']; 
          $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS_AUTH'][$row['PRIORITY']."_".$row['QUESTION_ID']]['ANSWERS'][$row['ANSWER_ID']] = $row;
          $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS_AUTH'][$row['PRIORITY']."_".$row['QUESTION_ID']]['ANSWERS'][$row['ANSWER_ID']]['WIDTH'] = round($row['VOTED']*100/$questions[$row['QUESTION_ID']]['SUM_VOTED']);
          if ($questions[$row['QUESTION_ID']]['SUM_VOTED'] > 0)
            $data['ITEMS'][$row['PRIORITY']."_".$row['QUESTION_ID']]['QUESTIONS_AUTH'][$row['PRIORITY']."_".$row['QUESTION_ID']]['VOTED'][0]['VOTED'] = $questions[$row['QUESTION_ID']]['SUM_VOTED'];
	}
        $html_page->page_title = $row['TITLE'];
      }
      $db->free();
      $data['LANG'] = $_SESSION['_lang']; 
    }      

    return $data;
  }

  function getSurveyData ($page=1,$perpage=PAGE_SIZE){
    global $db;
    global $_SESSION;

    $where = "N.PUBLISH='Y' AND N.START_DATE <= NOW()";
  
    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;
      
    $sql_count = "SELECT COUNT(N.ID) ROWS
                   FROM survey N, survey_details ND 
                   WHERE ND.ID=N.ID 
			AND ND.LANG_ID=".$_SESSION['lang_id']." AND ".$where; 
    $db->query($sql_count);
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

     $sql = "SELECT 
              N.ID, ND.TITLE, 
              SUBSTRING(N.START_DATE, 1, 16) START_DATE, 
              SUBSTRING(N.END_DATE, 1, 16) END_DATE, T.POSTS,
              COUNT(SQD.QUESTION_ID) AS QUESTIONS
            FROM 
              survey N, survey_details ND
		     left join topic T ON T.TOPIC_ID=ND.TOPIC_ID AND T.LANG_ID=".$_SESSION['lang_id']."
                     left join survey_questions SQ on SQ.SURVEY_ID=ND.ID 
                     left join survey_questions_details SQD on SQD.QUESTION_ID=SQ.QUESTION_ID AND SQD.QUESTION IS NOT NULL
			AND SQD.LANG_ID=".$_SESSION['lang_id']."
            WHERE ND.ID=N.ID AND ND.LANG_ID=".$_SESSION['lang_id']." AND
              ".$where."
	    GROUP BY N.ID
            ORDER BY 
              N.START_DATE DESC, N.ID DESC
            ".$limitclause;
    $db->query($sql);

    $c = 0;
    $ids = '';
    $pre  = '';
    $surveys = array();
    while ($row = $db->nextRow()) {
      $survey = $row; 
      $survey['LANG'] = $_SESSION['_lang']; 
      $ids .= $pre.$row['ID'];
      $pre = ',';
      if (empty($row['POSTS']))
        $survey['POSTS'] = 0; 
      $surveys[$perpage - $row['ID']] = $survey;
    }
    $db->free();

    $sql = "SELECT COUNT(DISTINCT USER_ID) USERS, SQ.SURVEY_ID
		FROM survey_questions SQ, survey_votes SV
		WHERE SQ.SURVEY_ID IN (".$ids.")
			AND SV.QUESTION_ID=SQ.QUESTION_ID
			AND SQ.PUBLISH='Y'
		GROUP BY SQ.SURVEY_ID";

    $db->query($sql);
    while ($row = $db->nextRow()) {
      $surveys[$perpage - $row['SURVEY_ID']]['USERS'] = $row['USERS'];
    }
   
    return $surveys;
  }

  function getSurveyQuestionData ($question_id = '') {
    global $db;
    global $_SESSION;
    global $auth;

    $data = '';
    $question_filter = '';

    if ($question_id != '')
      $question_filter = " AND SQ.QUESTION_ID=".$question_id;
    else {
      $question_filter = " AND SQ.QUESTION_ID not in 
				(select question_id from survey_votes SV where SV.USER_ID=".$auth->getUserId().")";

    }
    $sql = "SELECT S.ID, S.ALLOW_CHANGE, SD.TITLE, SQ.QUESTION_ID 
		FROM survey_details SD, survey S
			left join survey_questions SQ ON SQ.SURVEY_ID=S.ID			
				".$question_filter."
		WHERE S.START_DATE < NOW() AND S.END_DATE > NOW()
			AND S.ID=SD.ID and SD.LANG_ID=".$_SESSION['lang_id']."
			AND S.PUBLISH='Y'			
		ORDER BY SQ.PRIORITY ASC
		LIMIT 1";

    $db->query($sql);
    if ($row = $db->nextRow()) {
      $allow_change = $row['ALLOW_CHANGE'];
      $data['TITLE'] = $row['TITLE'];
      $data['QUESTION_ID'] = $row['QUESTION_ID'];
      $data['SURVEY_ID'] = $row['ID'];

      $sql = "SELECT COUNT(DISTINCT USER_ID) USERS
		FROM survey S, survey_questions SQ, survey_votes SV
		WHERE  S.ID=".$data['SURVEY_ID']."
			AND SQ.SURVEY_ID = S.ID 
			AND SV.QUESTION_ID=SQ.QUESTION_ID";
      $db->query($sql);
      $row = $db->nextRow();
      $data['USERS'] = $row['USERS'];

      if (!empty($data['QUESTION_ID'])) {
        $sql = "SELECT SQD.*, SA.*, SAD.*, 
			SAS.ANSWER_ID AS SUGGESTION, SAS.STATUS,
		      U.USER_NAME as SUGGESTER
			FROM 	survey_questions_details SQD,
			survey_answers SA,
			survey_answers_details SAD
			     left join survey_answers_suggested SAS on SAS.QUESTION_ID=".$data['QUESTION_ID']." 
						AND SAS.LANG_ID=".$_SESSION['lang_id']."
						AND SAS.USER_ID=".$auth->getUserId()."
			     left join survey_answers_suggested SAS1 on SAS1.NEW_ANSWER_ID=SAD.ANSWER_ID
			     left join users U on SAS1.USER_ID=U.USER_ID
		WHERE SQD.QUESTION_ID = ".$data['QUESTION_ID']."
			AND SQD.QUESTION_ID=SA.QUESTION_ID
			and SQD.LANG_ID=".$_SESSION['lang_id']."			
			AND SAD.ANSWER_ID = SA.ANSWER_ID
			and SAD.LANG_ID=".$_SESSION['lang_id']."			
		ORDER BY SAD.ANSWER_ID ASC";

        $db->query($sql);
        $c = 0;
        while ($row = $db->nextRow()) {
            $data['QUESTIONS'][0]['QUESTION_ID'] = $row['QUESTION_ID']; 
            $data['QUESTIONS'][0]['QUESTION'] = $row['QUESTION']; 
            $data['QUESTIONS'][0]['ANSWERS'][$row['ANSWER_ID']] = $row;
    	    if (isset($row['SUGGESTION']) && $row['SUGGESTION'] > 0) {
              $data['QUESTIONS'][0]['SUGGESTED'][0][$row['STATUS']][0] = $row;
	      $data['QUESTIONS'][0]['SUGGESTED'][0]['QUESTION_ID'] = $row['QUESTION_ID'];
            } else {
              $data['QUESTIONS'][0]['SUGGEST'][0] = $row;	   
            }
            if (!empty($row['SUGGESTER']))
              $data['QUESTIONS'][0]['ANSWERS'][$row['ANSWER_ID']]['SUGGESTED_BY'][0]['USER_NAME'] = $row['SUGGESTER'];
	    $c++;
        }
        if ($c == 0) {
          $data['NO_QUESTIONS'][0]['X'] = 1;
          if ($allow_change == 'Y')
            $data['NO_QUESTIONS'][0]['RESET'][0]['X'] = 1;
        }
      } else {
        $data['NO_QUESTIONS'][0]['X'] = 1;
        if ($allow_change == 'Y')
          $data['NO_QUESTIONS'][0]['RESET'][0]['X'] = 1;
      }
    }
/*    else {
      $data['NO_QUESTIONS'][0]['X'] = 1;
    }*/
    return $data;    
  }
}   
?>