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

class ModeratorBox extends Box{

  function ModeratorBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getCommentQueue () {
    global $smarty;
    global $db;
    global $_SESSION;

    // content
    $items = $this->getCommentQueueData();
    $smarty->assign("items", $items);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_moderator_comment_queue.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_manager_tournament.smarty'.($stop-$start);
    return $output;
  } 

  function getContentQueue () {
    global $tpl;
    global $db;
    global $_SESSION;

    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/bar_moderator_content_queue.tpl.html');
    $this->data['CONTENT_QUEUE'][0] = $this->getContentQueueData();
    $this->rows = $this->data['CONTENT_QUEUE'][0]['_ROWS'];	
    $tpl->addData($this->data);
    return $tpl->parse();
  } 

  function getCommentQueueData () {
    global $db;
  
    $sql = "SELECT P.POST_ID, P.DATE_POSTED, P.ACTKEY, P.TEXT, U.USER_NAME, T.TOPIC_NAME 
		FROM post P
			LEFT JOIN topic T ON T.topic_id = P.TOPIC_ID
			LEFT JOIN users U ON U.USER_ID = P.USER_ID
		WHERE P.REVIEWED='N' and P.VISIBLE='N'
 			AND T.TOPIC_NAME IS NOT NULL";
    
    $db->query($sql);

    $items = array();    
    while ($row = $db->nextRow()) {
      $items[] = $row; 
    }
    $db->free();
   
    return $items;
  }

  function getContentQueueData () {
    global $langs;
    global $db;
    global $_SESSION;
 
    $sql_count = "SELECT COUNT(*) ROWS
                   FROM news WHERE REVIEWED='N' and PUBLISH='N'"; 
    $db->query($sql_count);
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
    }
  
    $sql = "SELECT N.NEWS_ID, N.DATE_CREATED, N.ACTKEY, U.USER_NAME, ND.DESCR, ND.SOURCE_NAME, ND.SOURCE, ND.NEWS_ID 
			FROM users U, news N  
				left join news_details ND on ND.NEWS_ID=N.NEWS_ID
			WHERE N.REVIEWED='N' and N.PUBLISH='N' and U.USER_ID=N.USER_ID";

    $db->query($sql);

    $c = 0;
    while ($row = $db->nextRow()) {
      $data['ITEMS'][$c] = $row; 
      $c++;
    }
    $db->free();


    $sql = "SELECT N.VIDEO_ID, N.DATE_PUBLISHED, N.ACTKEY, U.USER_NAME, ND.DESCR, N.SOURCE_NAME, N.SOURCE, ND.VIDEO_ID, N.LINK, N.THUMBNAIL
			FROM users U, video N  
				left join video_details ND on ND.VIDEO_ID=N.VIDEO_ID
			WHERE N.REVIEWED='N' and N.PUBLISH='N' and U.USER_ID=N.USER_ID";
    
    $db->query($sql);
    while ($row = $db->nextRow()) {
      $data['VIDEO'][$c] = $row; 
      $c++;
    }
    $db->free();


    $sql = "SELECT SAS.ANSWER_ID, SAS.DATE_PUBLISHED, SAS.ACTKEY, U.USER_NAME, SAS.ANSWER, SQD.QUESTION
			FROM users U, survey_answers_suggested SAS, survey_questions_details SQD 
			WHERE SAS.STATUS=0 and U.USER_ID=SAS.USER_ID			
				AND SAS.QUESTION_ID = SQD.QUESTION_ID and SAS.LANG_ID=SQD.LANG_ID";
    
    $db->query($sql);
    while ($row = $db->nextRow()) {
      $data['SURVEY_ANSWERS'][$c] = $row; 
      $c++;
    }
    $db->free();
   
  //  echo $count;
    $data['_ROWS'] = $count;
      // no records?
    if ($c == 0) {
      $data['NORECORDS'][0]['X'] = 1;
    }
    
    $db->free();
    return $data;
  }

  function moderateComment($action, $post_id, $actkey) {
    global $langs;
    global $db;

    $errorbox1 = '';
    if (isset($action) && $action == 'post_approve') {
      if (!empty($actkey) && isset($post_id) && is_numeric($post_id)) {
        $sql = "SELECT * FROM post WHERE post_id=".$post_id." AND LOWER(actkey)='".strtolower($actkey)."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $topic_id = $row['TOPIC_ID'];
          $user_id = $row['USER_ID'];
          unset($sdata);
          $sdata['VISIBLE'] = "'Y'";
          $sdata['ACTKEY'] = "''";
          $sdata['REVIEWED'] = "'Y'";
          $db->update('post', $sdata, 'POST_ID='.$post_id);
    
          $trust = new Trust();	
          $trust->changeCommentTrust(1, $row['USER_ID']);
    
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_COMMENT_APPROVED');
    
          $notification = new Notification();
          $notification->populateTopicEmails($topic_id, $user_id);
    
         // activation good
        }
       else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_COMMENT_NOT_FOUND');
       }
      }
    }

    if (isset($action) && $action == 'post_allow') {
      if (!empty($actkey) && isset($post_id) && is_numeric($post_id)) {
        $sql = "SELECT * FROM post WHERE post_id=".$post_id." AND LOWER(actkey)='".strtolower($actkey)."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $topic_id = $row['TOPIC_ID'];
          $user_id = $row['USER_ID'];
          unset($sdata);
          $sdata['VISIBLE'] = "'Y'";
          $sdata['ACTKEY'] = "''";
          $sdata['REVIEWED'] = "'Y'";
          $db->update('post', $sdata, 'POST_ID='.$post_id);
    
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_COMMENT_ALLOWED');
    
          $notification = new Notification();
          $notification->populateTopicEmails($topic_id, $user_id);
    
         // activation good
        }
       else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_COMMENT_NOT_FOUND');
       }
      }
    }

    if (isset($action) && $action == 'post_disapprove') {
      if (!empty($actkey) && isset($post_id) && is_numeric($post_id)) {
        $sql = "SELECT * FROM post WHERE post_id=".$post_id." AND LOWER(actkey)='".strtolower($actkey)."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['ACTKEY'] = "''";
          $sdata['REVIEWED'] = "'Y'";
          $db->update('post', $sdata, 'POST_ID='.$post_id);
    
          $trust = new Trust();	
          $trust->changeCommentTrust(-1, $row['USER_ID']);
    
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_COMMENT_DISAPPROVED');
    
         // activation good
        }
        else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_COMMENT_NOT_FOUND');
        }
      }
    }
    return $errorbox1;
  }

  function moderateContent($action, $item_id, $actkey) {
    global $langs;
    global $db;

    $errorbox1 = '';

    if (isset($action) && $action == 'news_approve') {
      if (!empty($actkey) && isset($item_id) && is_numeric($item_id)) {
        $sql = "SELECT * FROM news WHERE news_id=".$item_id." AND actkey='".$actkey."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['PUBLISH'] = "'Y'";
          $sdata['DATE_PUBLISHED'] = "NOW()";
          $sdata['ACTKEY'] = "''";
          $sdata['REVIEWED'] = "'Y'";
          $db->update('news', $sdata, 'NEWS_ID='.$item_id);
    
          $trust = new Trust();	
          $trust->changeContentTrust(1, $row['USER_ID']);
	  $credits = new Credits();
    	  $credits->updateCredits ($row['USER_ID'], 0.1);
  	  $credit_log = new CreditsLog();
  	  $credit_log->logEvent ($row['USER_ID'], 8, 0.1);
    
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_APPROVED');
    
         // activation good
        }
       else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
       }
      }
    }
    
    if (isset($action) && $action == 'news_disapprove') {
      if (!empty($actkey) && isset($item_id) && is_numeric($item_id)) {
        $sql = "SELECT * FROM news WHERE news_id=".$item_id." AND actkey='".$actkey."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['ACTKEY'] = "''";
          $sdata['REVIEWED'] = "'Y'";
          $db->update('news', $sdata, 'NEWS_ID='.$item_id);
    
          $trust = new Trust();	
          $trust->changeContentTrust(-1, $row['USER_ID']);
    
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_DISAPPROVED');
    
         // activation good
        }
       else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
       }
      }
    }

    if (isset($action) && $action == 'news_ignore') {
      if (!empty($actkey) && isset($item_id) && is_numeric($item_id)) {
        $sql = "SELECT * FROM news WHERE news_id=".$item_id." AND actkey='".$actkey."'";   
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['ACTKEY'] = "''";
          $sdata['REVIEWED'] = "'Y'";
          $db->update('news', $sdata, 'NEWS_ID='.$item_id);
    
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_IGNORED');
    
         // activation good
        } else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
        }
      }
    }

    if (isset($action) && $action == 'video_approve') {
      if (!empty($actkey) && isset($item_id) && is_numeric($item_id)) {
        $sql = "SELECT * FROM video WHERE video_id=".$item_id." AND actkey='".$actkey."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['PUBLISH'] = "'Y'";
          $sdata['DATE_PUBLISHED'] = "NOW()";
          $sdata['ACTKEY'] = "''";
          $sdata['REVIEWED'] = "'Y'";
          $db->update('video', $sdata, 'VIDEO_ID='.$item_id);
    
          $trust = new Trust();	
          $trust->changeContentTrust(1, $row['USER_ID']);
	  $credits = new Credits();
    	  $credits->updateCredits ($row['USER_ID'], 0.2);
  	  $credit_log = new CreditsLog();
  	  $credit_log->logEvent ($row['USER_ID'], 8, 0.2);
    
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_APPROVED');
    
         // activation good
        }
       else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
       }
      }
    }
    
    if (isset($action) && $action == 'video_disapprove') {
      if (!empty($actkey) && isset($item_id) && is_numeric($item_id)) {
        $sql = "SELECT * FROM video WHERE video_id=".$item_id." AND actkey='".$actkey."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['ACTKEY'] = "''";
          $sdata['REVIEWED'] = "'Y'";
          $db->update('video', $sdata, 'VIDEO_ID='.$item_id);
    
          $trust = new Trust();	
          $trust->changeContentTrust(-1, $row['USER_ID']);
    
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_DISAPPROVED');
    
         // activation good
        }
       else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
       }
      }
    }

    if (isset($action) && $action == 'video_ignore') {
      if (!empty($actkey) && isset($item_id) && is_numeric($item_id)) {
        $sql = "SELECT * FROM video WHERE video_id=".$item_id." AND actkey='".$actkey."'";   
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['ACTKEY'] = "''";
          $sdata['REVIEWED'] = "'Y'";
          $db->update('video', $sdata, 'VIDEO_ID='.$item_id);
    
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_IGNORED');
    
         // activation good
        } else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
        }
      }
    }

    if (isset($action) && $action == 'answer_approve') {
      if (!empty($actkey) && isset($item_id) && is_numeric($item_id)) {
        $sql = "SELECT * FROM survey_answers_suggested WHERE answer_id=".$item_id." AND actkey='".$actkey."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
	  $sdata['QUESTION_ID'] = $row['QUESTION_ID'];
	  $sdata['PUBLISH'] = "'Y'";
          $db->insert('survey_answers', $sdata);
          unset($sdata);
	  $sdata['QUESTION_ID'] = $row['QUESTION_ID'];
	  $sdata['PUBLISH'] = "'Y'";
          $answer_id = $db->id();
          unset($sdata);
	  $sdata['ANSWER_ID'] = $answer_id;
	  $sdata['ANSWER'] = "'".mysql_real_escape_string($row['ANSWER'])."'";
	  $sdata['LANG_ID'] = $row['LANG_ID'];
          $db->insert('survey_answers_details', $sdata);    
          unset($sdata);
	  $sdata['NEW_ANSWER_ID'] = $answer_id;
          $sdata['DATE_PUBLISHED'] = "NOW()";
          $sdata['ACTKEY'] = "''";
          $sdata['STATUS'] = 2;
          $db->update('survey_answers_suggested', $sdata, 'ANSWER_ID='.$row['ANSWER_ID']);

          $trust = new Trust();	
          $trust->changeContentTrust(0.1, $row['USER_ID']);
	  $credits = new Credits();
    	  $credits->updateCredits ($row['USER_ID'], 0.02);
  	  $credit_log = new CreditsLog();
  	  $credit_log->logEvent ($row['USER_ID'], 8, 0.02);
  
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_APPROVED');
    
         // activation good
        }
       else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
       }
      }
    }


    if (isset($action) && $action == 'answer_disapprove') {
      if (!empty($actkey) && isset($item_id) && is_numeric($item_id)) {
        $sql = "SELECT * FROM survey_answers_suggested WHERE answer_id=".$item_id." AND actkey='".$actkey."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['ACTKEY'] = "''";
          $sdata['STATUS'] = -1;
          $db->update('survey_answers_suggested', $sdata, 'ANSWER_ID='.$row['ANSWER_ID']);

          $trust = new Trust();	
          $trust->changeContentTrust(-0.1, $row['USER_ID']);
    
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_DISAPPROVED');
    
         // activation good
        }
       else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
       }
      }
    }

    if (isset($action) && $action == 'answer_ignore') {
      if (!empty($actkey) && isset($item_id) && is_numeric($item_id)) {
        $sql = "SELECT * FROM survey_answers_suggested WHERE answer_id=".$item_id." AND actkey='".$actkey."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['ACTKEY'] = "''";
          $sdata['STATUS'] = 1;
          $db->update('survey_answers_suggested', $sdata, 'ANSWER_ID='.$row['ANSWER_ID']);

          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_IGNORED');
    
         // activation good
        }
       else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
       }
      }
    }


    if (isset($action) && $action == 'cat_approve') {
      if (!empty($actkey) && isset($item_id) && is_numeric($item_id)) {
        $sql = "SELECT * FROM cats_suggested WHERE id=".$item_id." AND actkey='".$actkey."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
	  $sdata['PUBLISH'] = "'Y'";
          $db->insert('cats', $sdata);
          $cat_id = $db->id();
          unset($sdata);
	  $sdata['CAT_ID'] = $cat_id;
	  $sdata['CAT_NAME'] = "'".mysql_real_escape_string($row['CAT_NAME'])."'";
	  $sdata['LANG_ID'] = $row['LANG_ID'];
          $db->insert('cats_details', $sdata);    
          unset($sdata);
	  $sdata['CAT_ID'] = $cat_id;
          $sdata['DATE_PUBLISHED'] = "NOW()";
          $sdata['ACTKEY'] = "''";
          $sdata['STATUS'] = 2;
          $db->update('cats_suggested', $sdata, 'ID='.$row['ID']);

          $trust = new Trust();	
          $trust->changeContentTrust(0.1, $row['USER_ID']);
	  $credits = new Credits();
    	  $credits->updateCredits ($row['USER_ID'], 0.02);
  	  $credit_log = new CreditsLog();
  	  $credit_log->logEvent ($row['USER_ID'], 8, 0.02);
  
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_APPROVED');
    
         // activation good
        }
       else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
       }
      }
    }


    if (isset($action) && $action == 'cat_disapprove') {
      if (!empty($actkey) && isset($item_id) && is_numeric($item_id)) {
        $sql = "SELECT * FROM cats_suggested WHERE id=".$item_id." AND actkey='".$actkey."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['ACTKEY'] = "''";
          $sdata['STATUS'] = -1;
          $db->update('cats_suggested', $sdata, 'ID='.$row['ID']);

          $trust = new Trust();	
          $trust->changeContentTrust(-0.1, $row['USER_ID']);
    
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_DISAPPROVED');
    
         // activation good
        }
       else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
       }
      }
    }

    if (isset($action) && $action == 'cat_ignore') {
      if (!empty($actkey) && isset($item_id) && is_numeric($item_id)) {
        $sql = "SELECT * FROM cats_suggested WHERE id=".$item_id." AND actkey='".$actkey."'";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          unset($sdata);
          $sdata['ACTKEY'] = "''";
          $sdata['STATUS'] = 1;
          $db->update('cats_suggested', $sdata, 'ID='.$row['ID']);

          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_IGNORED');
    
         // activation good
        }
       else {
           $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
           $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
       }
      }
    }

    return $errorbox1;
  }

}   
?>