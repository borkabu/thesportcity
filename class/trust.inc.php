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

class Trust {
 
  function Trust() {
  }

  function change($delta, $user_id) {
    global $db;

    $sdata['TRUST'] = 'TRUST+'.$delta;
    $db->update('users', $sdata, "USER_ID='".$user_id."'");
  }

  function changeCommentTrust($delta, $user_id) {
    global $db;
    global $log;

    $sdata['COMMENT_TRUST'] = 'COMMENT_TRUST+'.$delta;
    $db->update('users', $sdata, "USER_ID='".$user_id."' AND COMMENT_TRUST+".$delta." >= 0");

    $comment_trust = $this->getCommentTrustForUser($user_id);

    $level = $this->getCommentTrustLevel($comment_trust);

    $event_type = 1; 
    if ($delta > 0)
      $event_type = 1;
    else if ($delta < 0)
      $event_type = 2;
    $log->logEvent($user_id, $delta, $event_type, $comment_trust, 1);

    $current_level = $this->getCommentTrustLevel($comment_trust);
    if ($current_level != $level) {
      $event_type = 3; 
      if ($current_level > $level)
        $event_type = 3;
      else if ($current_level < $level)
        $event_type = 4;
      $log->logEvent($user_id, $current_level - $level, $event_type, $current_level, 1);
    }
  }

  function changeContentTrust($delta, $user_id) {
    global $db;
    global $log;

    $sdata['CONTENT_TRUST'] = 'CONTENT_TRUST+'.$delta;
    $db->update('users', $sdata, "USER_ID='".$user_id."'");

    $content_trust = $this->getContentTrustForUser($user_id);

    $level = $this->getContentTrustLevel($content_trust);

    $event_type = 6; 
    if ($delta > 0)
      $event_type = 6;
    else if ($delta < 0)
      $event_type = 7;
    $log->logEvent($user_id, $delta, $event_type, $content_trust, 2);

    $current_level = $this->getContentTrustLevel($content_trust);
    if ($current_level != $level) {
      $event_type = 8; 
      if ($current_level > $level)
        $event_type = 8;
      else if ($current_level < $level)
        $event_type = 9;
      $log->logEvent($user_id, $current_level - $level, $event_type, $current_level, 2);
    }
  }

  function refreshTrusts() {
    global $_SESSION;
    global $db;
    global $auth;

    if ($auth->userOn()) {

      $sql = "SELECT * FROM users where user_id=".$auth->getUserId();
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $_SESSION['_user']['TRUST'] = $row['TRUST'];
        $_SESSION['_user']['COMMENT_TRUST'] = $row['COMMENT_TRUST'];
        $_SESSION['_user']['CONTENT_TRUST'] = $row['CONTENT_TRUST'];
//        $_SESSION['_user']['TRUST'] = $row['TRUST'];
      }
    }
  }

  function getCommentTrustForUser($user_id) {
    global $db;

    if (is_numeric($user_id)) {
      $sql="SELECT COMMENT_TRUST FROM users where USER_ID=".$user_id;
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return $row['COMMENT_TRUST'];
      }
    }
    return 0;
  }

  function getContentTrustForUser($user_id) {
    global $db;

    if (is_numeric($user_id)) {
      $sql="SELECT CONTENT_TRUST FROM users where USER_ID=".$user_id;
      $db->query($sql);
      if ($row = $db->nextRow()) {
        return $row['CONTENT_TRUST'];
      }
    }
    return 0;
  }

  function getCommentTrustLevel($comment_trust) {
    if (isset($comment_trust)) {
      switch(true) {
	case ($comment_trust < 1):
	    return 0; 
	case ($comment_trust >= 1 && 
		$comment_trust < 4 ):
	    return 1; 
	case ($comment_trust >= 4 && 
		$comment_trust < 10 ):
	    return 2;
	case ($comment_trust >= 10 && 
		$comment_trust < 20 ):
	    return 3;     
	case ($comment_trust >= 20 && 
		$comment_trust < 40):
	    return 4;
	case ($comment_trust >= 40 && 
		$comment_trust < 70):
	    return 5;
	case ($comment_trust >= 70):
	    return 6;
	default: return 0;
      }
    }
    return 0;
  } 

  function getContentTrustLevel($content_trust) {
    if (isset($content_trust)) {
      switch(true) {
	case ($content_trust < 1):
	    return 0; 
	case ($content_trust >= 1 && 
		$content_trust < 4 ):
	    return 1; 
	case ($content_trust >= 4 && 
		$content_trust < 10 ):
	    return 2;
	case ($content_trust >= 10 && 
		$content_trust < 20 ):
	    return 3;     
	case ($content_trust >= 20 && 
		$content_trust < 40):
	    return 4;
	case ($content_trust >= 40 && 
		$content_trust < 70):
	    return 5;
	case ($content_trust >= 70):
	    return 6;
	default: return 0;
      }
    }
    return 0;
  } 

  function getCommentTrustNextLevel($comment_trust) {
    if (isset($comment_trust)) {
      switch(true) {
	case ($comment_trust < 1):
	    return 1 - $comment_trust; 
	case ($comment_trust >= 1 &&
               $comment_trust < 4):
	    return 4 - $comment_trust; 
	case ($comment_trust >= 4 && 
		$comment_trust < 10 ):
   	    return 10 - $comment_trust; 
	case ($comment_trust >= 10 && 
		$comment_trust < 20 ):
	    return 20 - $comment_trust; 
	case ($comment_trust >= 20 && 
		$comment_trust < 40):
	    return 40 - $comment_trust; 
	case ($comment_trust >= 40 && 
		$comment_trust < 70):
	    return 70 - $comment_trust; 
      }
    }
    return 0;
  } 

  function getContentTrustNextLevel($content_trust) {
    if (isset($content_trust)) {
      switch(true) {
	case ($content_trust < 1):
	    return 1 - $content_trust; 
	case ($content_trust >= 1 &&
               $content_trust < 4):
	    return 4 - $content_trust; 
	case ($content_trust >= 4 && 
		$content_trust < 10 ):
   	    return 10 - $content_trust; 
	case ($content_trust >= 10 && 
		$content_trust < 20 ):
	    return 20 - $content_trust; 
	case ($content_trust >= 20 && 
		$content_trust < 40):
	    return 40 - $content_trust; 
	case ($content_trust >= 40 && 
		$content_trust < 70):
	    return 70 - $content_trust; 
      }
    }
    return 0;
  } 


  function getCommentTrustLevelQuote() {
    global $_SESSION;
    global $auth;
    if ($auth->hasSupporter()) 
      return -10;
    $level = $this->getCommentTrustLevel($_SESSION['_user']['COMMENT_TRUST']);
    switch($level) {
	case 0:
	    return 1; 
	case 1:
	    return 10; 
	default: return -10;
    } 
  }

  function getContentTrustLevelQuote() {
    global $_SESSION;
    global $auth;
    if ($auth->hasSupporter()) 
      return -10;

    $level = $this->getContentTrustLevel($_SESSION['_user']['CONTENT_TRUST']);
    switch($level) {
	case 0:
	    return 1; 
	case 1:
	    return 5; 
	case 2:
	    return 10; 
	case 3:
	    return 20; 
	default: return -10;
    } 
  }

  function getCommentTrustLevelQuoteLeft() {
    global $auth;
    global $db;
    
    $quote = $this->getCommentTrustLevelQuote();
    if ($quote > -1) {
      $sql = "SELECT count(POST_ID) POSTS 
		FROM post where user_id =".$auth->getUserId()." and reviewed ='N'
			AND DATE_SUB(date_posted, INTERVAL -1 DAY) > NOW()";
      $db->query($sql);
      $posts = 0;
      if ($row = $db->nextRow()) {
        $posts = $row['POSTS'];
      }

      $sql = "SELECT count(MESSAGE_ID) MESSAGES 
		FROM pm_folder_messages where user_id =".$auth->getUserId()." and folder_id=3";
      $db->query($sql);

      $messages = 0;
      if ($row = $db->nextRow()) {
        $messages =  $row['MESSAGES'];
      }
      
      return $quote - ($posts + $messages);
    }
    return $quote;
  }  

  function getContentTrustLevelQuoteLeft() {
    global $auth;
    global $db;
    
    $quote = $this->getContentTrustLevelQuote();
    if ($quote > -1) {
      $sql = "SELECT SUM(NEWS) AS NEWS FROM
 	      (SELECT count(NEWS_ID) NEWS 
		FROM news where user_id =".$auth->getUserId()." and reviewed ='N'
			AND DATE_SUB(date_created, INTERVAL -1 DAY) > NOW()
 	      UNION
	      SELECT count(VIDEO_ID) NEWS 
		FROM video where user_id =".$auth->getUserId()." and reviewed ='N'
			AND DATE_SUB(date_created, INTERVAL -1 DAY) > NOW()) s";
      $db->query($sql);

      if ($row = $db->nextRow()) {
        return $quote - $row['NEWS'];
      }
      else return $quote;
    }
    return $quote;
  }  

}


class ForumPermission {
  var $status;
  var $timegap;
   
  function ForumPermission() {
	$status = 0; // 0 - ok, 1 - flood protection is on, 2 - quote exceeded
	$timegap = -1;
  }

  function canReadTopic($topic_id) {
    global $db;
    global $auth;

     $where_groups = " AND F.GROUP_ID IS NULL";
     if ($auth->userOn()) {
         $user = new User($auth->getUserId());
         if ($user->getGroups() != '') {
           $where_groups = " AND (F.GROUP_ID IS NULL OR F.GROUP_ID IN (".$user->group_str."))";
         }
     }

     $sql_count = "SELECT COUNT(P.POST_ID) ROWS, T.POSTS
                     FROM post P, forum F ,topic T
                     WHERE P.TOPIC_ID='".$topic_id."'
			 AND P.TOPIC_ID=T.TOPIC_ID
                         AND T.FORUM_ID=F.FORUM_ID
			 AND F.PUBLISH='Y' ".$where_groups; 
     $db->query($sql_count);
     if ($row = $db->nextRow()) {
       $count = $row['ROWS'];
       $posts = $row['POSTS'];
     }
     if ($count == 0 && $posts > 0)
       return false;
     else return true;
  }

  function canEditComment($post_id) {
    global $db;
    global $auth;

    if ($auth->userOn() && is_numeric($post_id)) {
      $sql = "SELECT * FROM post WHERE post_id='".$post_id."' 
			AND user_id='".$auth->getUserId()."'";

      $db->query($sql);

      if ($row = $db->nextRow()) {
        return true;
      }
    }    
    return false;
  }

  function canAddPM() {
    global $db;
    global $auth;
    global $flood_protection;

    $trust = new Trust();
    $trust->refreshTrusts();
    $decided = false;
    if (!$decided) {
      if ($trust->getCommentTrustLevelQuoteLeft() <= 0 && $trust->getCommentTrustLevelQuoteLeft() > -10) {
           $this->status = 2;
           $decided = true;
      }  
    }
    return $this->status;
  }

  function canAddComment($topic_id) {
    global $db;
    global $auth;
    global $_SESSION;
    global $flood_protection;

    $trust = new Trust();
    $trust->refreshTrusts();
    $decided = false;

    if (!$decided) {
      if ($trust->getCommentTrustLevel($_SESSION['_user']['COMMENT_TRUST']) >= 5) {
           $this->status = 0;
           $decided = true;
      }  
    }

    if (!$decided) {
      if ($trust->getCommentTrustLevelQuoteLeft() <= 0 && $trust->getCommentTrustLevelQuoteLeft() > -10) {
           $this->status = 2;
           $decided = true;
      }  
    }

    // cannot add topic if already commented that topic
    if (!$decided && is_numeric($topic_id)) {
      $sql = "SELECT P.USER_ID, TIME_TO_SEC(TIMEDIFF(now() ,P.date_posted)) TIMEGAP, F.GROUP_ID
		 FROM post P, topic T, forum F
           	 WHERE P.topic_id='".$topic_id."'
			and T.TOPIC_ID = P.TOPIC_ID
			and T.FORUM_ID = F.FORUM_ID			 
  	      ORDER BY POST_ID DESC
	      LIMIT 1";

      $db->query($sql);
      if ($row = $db->nextRow()) {
        if ($row['USER_ID'] == $auth->getUserId()) {
        // check date
          if ($row['GROUP_ID'] > 0) {
            $this->status = 0;
            return $this->status;
          }
          if ($auth->hasSupporter()) 
            $flood_protection = 30;

          if ($row['TIMEGAP'] < $flood_protection) {
            // flood protection
            $this->timegap = $flood_protection - $row['TIMEGAP'];
  	    $this->status = 1;
	    $decided = true;
          }
        }
      } 
    }
    return $this->status;
  }

  function canDeletePost($post_id, $user_id, $voted) {
    global $_SESSION;
    global $auth;

    if (is_numeric($post_id)) {
        $trust = new Trust();
        if ($trust->getCommentTrustLevel($_SESSION["_user"]['COMMENT_TRUST']) >= 5
            || $user_id == $auth->getUserId()) {
          return true;
        }
    }
    return false;
  }

  function canStartTopic($forum_id) {
    global $db;
    global $_SESSION;
    global $forums;

    $trust = new Trust();
    $trust->refreshTrusts();
    if (is_numeric($forum_id)) {
      if ($trust->getCommentTrustLevel($_SESSION["_user"]['COMMENT_TRUST']) >= 2 && !in_array($forum_id, $forums)) {
        return 0;
      } else if (in_array($forum_id, $forums))
		return 3;
    }
    return 2;
  }

  function canDeleteTopic($forum_id, $topic_id, $topic_owner, $posts, $moderator) {
    global $auth;
    global $_SESSION;
    global $conf_news_forum;

    if (is_numeric($topic_id) && is_numeric($topic_owner)) {
      $trust = new Trust();

      if ($auth->userOn()) {
     // check if comment trust is more than 100 (moderator)
     // else check if user is topic owner and if there no more than 1 post and that it is no news forum
         if ($trust->getCommentTrustLevel($_SESSION["_user"]['COMMENT_TRUST']) >= 100 || $moderator) {      
           return true; 
         } else if ($topic_owner == $auth->getUserId() && $posts <= 1 && $forum_id != $conf_news_forum) {
           return true;
         }
      }
    }
    return false;
  }

  function canEditTopic($forum_id, $topic_id, $topic_owner, $moderator=false) {
    global $auth;
    global $_SESSION;
    global $conf_news_forum;

    if (is_numeric($topic_id) && is_numeric($topic_owner)) {
      $trust = new Trust();

      if ($auth->userOn()) {
     // check if comment trust is more than 100 (moderator)
     // else check if user is topic owner and if there no more than 1 post and that it is no news forum
         if ($trust->getCommentTrustLevel($_SESSION["_user"]['COMMENT_TRUST']) >= 5 || $moderator) {      
           return true; 
         } else if ($topic_owner == $auth->getUserId() && $forum_id != $conf_news_forum) {
           return true;
         } 
      }
    }
    return false;
  }

  function canVoteComment() {
    global $db;
    global $_SESSION;
    global $auth;
    if ($auth->userOn()) {
      $trust = new Trust();
      $trust->refreshTrusts();
      if ($trust->getCommentTrustLevel($_SESSION["_user"]['COMMENT_TRUST']) >= 2) {
        return true;
      }
    }
    return false;
  } 

  function canVoteContent() {
    global $db;
    global $_SESSION;
    global $auth;
    if ($auth->userOn()) {
      $trust = new Trust();
      $trust->refreshTrusts();
      if ($trust->getContentTrustLevel($_SESSION["_user"]['CONTENT_TRUST']) >= 0) {
        return true;
      }
    }
    return false;
  } 

  function canCommentBeVoted($cctl) {
     if (isset($cctl) && is_numeric($cctl)) {
       if ($cctl > 1)
         return true;
     }
     return false;
  }

  function canContentBeVoted($cctl) {
     if (isset($cctl) && is_numeric($cctl)) {
       if ($cctl >= 0)
         return true;
     }
     return false;
  }

  function canAddNews() {
    global $db;
    global $auth;
    global $flood_protection;
    global $_SESSION;

    $trust = new Trust();
    $trust->refreshTrusts();
    $decided = false;
    if (!$decided) {
      if ($trust->getContentTrustLevelQuoteLeft() <= 0 && $trust->getContentTrustLevelQuoteLeft() > -10) {
           $this->status = 1;
           $decided = true;
      }  
    }

    if ($trust->getContentTrustLevel($_SESSION["_user"]['CONTENT_TRUST']) >= 0) {
      $this->status = 0;
    } else
         $this->status = 2;

    return $this->status;
  }


  function canChat() {
    global $db;
    global $_SESSION;
    global $auth;
    if ($auth->userOn()) {
      $trust = new Trust();
      $trust->refreshTrusts();
      if ($trust->getCommentTrustLevel($_SESSION["_user"]['COMMENT_TRUST']) >= 2) {
        return true;
      }
    }
    return false;
  } 

  function canPinTopic($forum_id, $topic_id, $moderator=false) {
    global $auth;
    global $_SESSION;
    global $conf_news_forum;

    if (is_numeric($topic_id)) {
      $trust = new Trust();

      if ($auth->userOn()) {
     // check if comment trust is more than 100 (moderator)
     // else check if user is topic owner and if there no more than 1 post and that it is no news forum
         if ($trust->getCommentTrustLevel($_SESSION["_user"]['COMMENT_TRUST']) >= 5 || $moderator) {      
           return true; 
         } 
      }
    }
    return false;
  }

  function canPinPost($post_id, $moderator=false) {
    global $auth;
    global $_SESSION;

    if (is_numeric($post_id)) {
      $trust = new Trust();

      if ($auth->userOn()) {
     // check if comment trust is more than 100 (moderator)
     // else check if user is topic owner and if there no more than 1 post and that it is no news forum
         if ($trust->getCommentTrustLevel($_SESSION["_user"]['COMMENT_TRUST']) >= 5 || $moderator) {      
           return true; 
         } 
      }
    }
    return false;
  }


}
?>