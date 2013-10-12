<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
index.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - first page of a portal

TABLES USED: 
  - BASKET.NEWS
  - BASKET.EVENTS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
// includes
include('class/conf.inc.php');
include('class/func.inc.php');
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

// user session
include('class/ss_const.inc.php');

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');

// --- build content data -----------------------------------------------------
//else 
$content = '';
$trust = new Trust();
//$db->showquery=true;
if (isset($_GET['mode']) && $_GET['mode'] == 'voting_approve') {
  if (!empty($_GET['actkey']) && isset($_GET['post_id']) && is_numeric($_GET['post_id'])) {
    $sql = "SELECT * FROM post WHERE post_id=".$_GET['post_id']." AND actkey='".$_GET['actkey']."'";
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $user_id = $row['USER_ID'];
      $sql="SELECT SUM(VOTE) VOTES FROM post_votes WHERE POST_ID='".$_GET['post_id']."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {    
        $votes = $row['VOTES'];

        unset($sdata);
        $sdata['ACTKEY'] = "''";
        $sdata['VOTED'] = "'Y'";
        $sdata['VOTES'] = $votes;

        $db->update('post', $sdata, 'POST_ID='.$_GET['post_id']);

        $trust->changeCommentTrust($votes/abs($votes), $user_id);

        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getMessageBox('MESSAGE_COMMENT_VOTING_APPROVED');

     // activation good
      }
      else {
        // situation has changed - more votes needed
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_COMMENT_VOTING_NEED_MORE_VOTES');
      }
    }
   else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_COMMENT_NOT_FOUND');
   }
  }
}


// decide strategy...
if (isset($_GET['mode']) && $_GET['mode'] == 'voting_disapprove') {
  if (!empty($_GET['actkey']) && isset($_GET['post_id']) && is_numeric($_GET['post_id'])) {
    $sql = "SELECT * FROM post WHERE post_id=".$_GET['post_id']." AND actkey='".$_GET['actkey']."'";
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $user_id = $row['USER_ID'];
      $sql="SELECT SUM(VOTE) VOTES FROM post_votes WHERE POST_ID='".$_GET['post_id']."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {    
        $votes = $row['VOTES'];

        unset($sdata);
        $sdata['ACTKEY'] = "''";
        $sdata['VOTED'] = "'Y'";
        $sdata['VOTES'] = $votes;

        $db->update('post', $sdata, 'POST_ID='.$_GET['post_id']);

        // no points for votee
        // reduce points from cheaters

        $sql = "SELECT PV.VOTE_ID, P.USER_ID, P.CCTL, PV.VOTE, U.COMMENT_TRUST, U.USER_NAME
                    from post P, post_votes PV, users U 
                     WHERE U.USER_ID = PV.USER_ID
			AND P.POST_ID=PV.POST_ID 
                        AND P.POST_ID='".$_GET['post_id']."'";
        $db->query($sql);
        $c = 0;
	$xdata = '';
        while ($row = $db->nextRow()) {    
          if ($trust->getCommentTrustLevel($row['COMMENT_TRUST']) <= 3 &&
              $row['VOTE']*$votes > 0)  {
            $xdata[$c] = $row;
            $c++;
          }
        } 
 
        for ($i = 0; $i < $c; $i++) {
          $trust->changeCommentTrust(-1, $xdata[$i]['USER_ID']);
        }

        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getMessageBox('MESSAGE_COMMENT_VOTING_DISAPPROVED');

     // activation good
      }
      else {
        // situation has changed - more votes needed
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_COMMENT_VOTING_NEED_MORE_VOTES');
      }
    }
    else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_COMMENT_NOT_FOUND');
    }
  }
}

if (isset($_GET['mode']) && $_GET['mode'] == 'voting_approve') {
  if (!empty($_GET['actkey']) && isset($_GET['item_id']) && is_numeric($_GET['item_id'])) {
    if ($_GET['type'] == 'news' || $_GET['type'] == 'blogs')
      $sql = "SELECT * FROM news WHERE news_id=".$_GET['item_id']." AND actkey='".$_GET['actkey']."'";
    else if ($_GET['type'] == 'video')
      $sql = "SELECT * FROM video WHERE video_id=".$_GET['item_id']." AND actkey='".$_GET['actkey']."'";
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $user_id = $row['USER_ID'];
      if ($_GET['type'] == 'news' || $_GET['type'] == 'blogs')
        $sql="SELECT SUM(VOTE) VOTES FROM news_votes WHERE NEWS_ID='".$_GET['item_id']."'";
      else if ($_GET['type'] == 'video')
        $sql="SELECT SUM(VOTE) VOTES FROM video_votes WHERE VIDEO_ID='".$_GET['item_id']."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {    
        $votes = $row['VOTES'];

        unset($sdata);
        $sdata['ACTKEY'] = "''";
        $sdata['VOTED'] = "'Y'";
        $sdata['VOTES'] = $votes;

        if ($votes < 0) {
          $sdata['PUBLISH'] = "'N'";
        }
        if ($_GET['type'] == 'news' || $_GET['type'] == 'blogs')
          $db->update('news', $sdata, 'NEWS_ID='.$_GET['item_id']);
        else if ($_GET['type'] == 'video')
          $db->update('video', $sdata, 'VIDEO_ID='.$_GET['item_id']);

        $trust->changeContentTrust($votes/abs($votes), $user_id);

        if ($_GET['type'] == 'blogs') {
          // reward
          if ($votes > 0) { 
            $credits = new Credits();
	    $credits->updateCredits ($user_id, 5);
  	    $credit_log = new CreditsLog();
	    $credit_log->logEvent ($user_id, 8, 5);
	  }
        }

        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_VOTING_APPROVED');

     // activation good
      }
      else {
        // situation has changed - more votes needed
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_VOTING_NEED_MORE_VOTES');
      }
    }
   else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
   }
  }
}


// decide strategy...
if (isset($_GET['mode']) && $_GET['mode'] == 'voting_disapprove') {
  if (!empty($_GET['actkey']) && isset($_GET['item_id']) && is_numeric($_GET['item_id'])) {
    if ($_GET['type'] == 'news')
      $sql = "SELECT * FROM news WHERE news_id=".$_GET['item_id']." AND actkey='".$_GET['actkey']."'";
    else if ($_GET['type'] == 'video')
      $sql = "SELECT * FROM video WHERE video_id=".$_GET['item_id']." AND actkey='".$_GET['actkey']."'";
    $db->query($sql);
    if ($row = $db->nextRow()) {
      $user_id = $row['USER_ID'];
      if ($_GET['type'] == 'news')
        $sql="SELECT SUM(VOTE) VOTES FROM news_votes WHERE NEWS_ID='".$_GET['item_id']."'";
      else if ($_GET['type'] == 'video')
        $sql="SELECT SUM(VOTE) VOTES FROM video_votes WHERE VIDEO_ID='".$_GET['item_id']."'";

      $db->query($sql);
      if ($row = $db->nextRow()) {    
        $votes = $row['VOTES'];

        unset($sdata);
        $sdata['ACTKEY'] = "''";
        $sdata['VOTED'] = "'Y'";
        $sdata['VOTES'] = $votes;

        if ($_GET['type'] == 'news')
          $db->update('news', $sdata, 'NEWS_ID='.$_GET['item_id']);
        else if ($_GET['type'] == 'video')
          $db->update('video', $sdata, 'VIDEO_ID='.$_GET['item_id']);

        // no points for votee
        // reduce points from cheaters

        if ($_GET['type'] == 'news') {
          $sql = "SELECT PV.VOTE_ID, P.USER_ID, P.CCTL, PV.VOTE, U.COMMENT_TRUST, U.USER_NAME
                    from news P, news_votes PV, users U 
                     WHERE U.USER_ID = PV.USER_ID
			AND P.NEWS_ID=PV.NEWS_ID 
                        AND P.NEWS_ID='".$_GET['item_id']."'";
        } else if ($_GET['type'] == 'video') {
          $sql = "SELECT PV.VOTE_ID, P.USER_ID, P.CCTL, PV.VOTE, U.COMMENT_TRUST, U.USER_NAME
                    from video P, video_votes PV, users U 
                     WHERE U.USER_ID = PV.USER_ID
			AND P.VIDEO_ID=PV.VIDEO_ID 
                        AND P.VIDEO_ID='".$_GET['item_id']."'";
        } 
        $db->query($sql);
        $c = 0;
	$xdata = '';
        while ($row = $db->nextRow()) {    
          if ($trust->getContentTrustLevel($row['CONTENT_TRUST']) <= 3 &&
              $row['VOTE']*$votes > 0)  {
            $xdata[$c] = $row;
            $c++;
          }
        } 
 
        for ($i = 0; $i < $c; $i++) {
          $trust->changeContentTrust(-1, $xdata[$i]['USER_ID']);
        }

        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getMessageBox('MESSAGE_CONTENT_VOTING_DISAPPROVED');

     // activation good
      }
      else {
        // situation has changed - more votes needed
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_VOTING_NEED_MORE_VOTES');
      }
    }
    else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_CONTENT_NOT_FOUND');
    }
  }
}

// include common header
include('inc/top.inc.php');

echo  $content;
// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>