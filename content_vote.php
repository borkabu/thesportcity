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
include('class/headers_no_cache.inc.php');
// page requirements
include('class/inputs.inc.php');

// --- build content data -----------------------------------------------------
//else 

//$db->showquery=true;
// check if user can vote
$trust = new Trust();
$forum = new Forum();
$forumpermissions = new ForumPermission();
$thumb = '';
if ($_GET['mode'] == 'blogs') 
  $can_vote = true;
else $can_vote = $forumpermissions->canVoteContent();
if ($auth->userOn() && $can_vote) { 

  if (isset($_GET['item_id']) && is_numeric($_GET['item_id'])) {
    $can_process = false;
    $votee = -1;
    $cctl = -1;
    if ($_GET['mode'] == 'news' || $_GET['mode'] == 'blogs') {
      $sql = "SELECT PV.VOTE_ID, P.USER_ID, P.CCTL, P.VOTED from news P 
                    left join news_votes PV ON P.NEWS_ID=PV.NEWS_ID 
                         AND PV.USER_ID = ".$auth->getUserId()."
             WHERE P.USER_ID <> ".$auth->getUserId()."
                AND P.NEWS_ID='".$_GET['item_id']."'";
    } else if ($_GET['mode'] == 'video') {
      $sql = "SELECT PV.VOTE_ID, P.USER_ID, P.CCTL, P.VOTED from video P 
                    left join video_votes PV ON P.VIDEO_ID=PV.VIDEO_ID 
                         AND PV.USER_ID = ".$auth->getUserId()."
             WHERE P.USER_ID <> ".$auth->getUserId()."
                AND P.VIDEO_ID='".$_GET['item_id']."'";
    }
    $db->query($sql);
    if ($row = $db->nextRow()) {    
      if (empty($row['VOTE_ID']) && 
          $row['VOTED']  == 'N' &&
          $forumpermissions->canContentBeVoted($row['CCTL']))
        $can_process = true;
    }

    if ($can_process) {
      $votee = $row['USER_ID'];
      $cctl = $row['CCTL'];
      $accepted = false;
      $sdata['USER_ID'] = $auth->getUserId();
      if ($_GET['mode'] == 'news' || $_GET['mode'] == 'blogs') {
        $sdata['NEWS_ID'] = $_GET['item_id'];
      } else if ($_GET['mode'] == 'video') {
        $sdata['VIDEO_ID'] = $_GET['item_id'];
      }
      if (isset($_GET['action']) && $_GET['action'] == 'thumbup') {
        $thumb['THUMB_UP'] = 1;
        $sdata['VOTE'] = $trust->getContentTrustLevel($_SESSION['_user']['CONTENT_TRUST']);
	$accepted = true;
      }
      if (isset($_GET['action']) && $_GET['action'] == 'thumbdown') {
        $thumb['THUMB_DOWN'] = 1;
        $sdata['VOTE'] = -1 * $trust->getContentTrustLevel($_SESSION['_user']['CONTENT_TRUST']);
	$accepted = true;
      } 
      if ($accepted) {
        $sdata['DATE_VOTED'] = 'NOW()';
        if ($_GET['mode'] == 'news' || $_GET['mode'] == 'blogs') {
          $db->insert('news_votes', $sdata);
        } else if ($_GET['mode'] == 'video') {
          $db->insert('video_votes', $sdata);
        } 
      }  
     }  

    // check if vote is decisive
    if ($votee > 0) {
      if ($_GET['mode'] == 'news' || $_GET['mode'] == 'blogs') {
        $sql="SELECT SUM(VOTE) VOTES, COUNT(VOTE) QUORUM FROM news_votes WHERE NEWS_ID='".$_GET['item_id']."'";
      } else if ($_GET['mode'] == 'video') {
        $sql="SELECT SUM(VOTE) VOTES, COUNT(VOTE) QUORUM FROM video_votes WHERE VIDEO_ID='".$_GET['item_id']."'";
      }
      $db->query($sql);
      if ($row = $db->nextRow()) {    
        $votes = $row['VOTES'];
        $quorum = $row['QUORUM'];
        $threshold = 3; 
        if ($_GET['mode'] == 'blogs') 
          $threshold = 5;

        if ($quorum >= $threshold && ($votes >= $threshold*2 || $votes <= -1*$threshold*2)) {
          // critical mass gathered
          // let's update rating
          $moderate = false;
          if ($_GET['mode'] == 'news' || $_GET['mode'] == 'blogs') {
            $sql = "SELECT PV.VOTE_ID, P.USER_ID, P.CCTL, PV.VOTE, U.CONTENT_TRUST, U.USER_NAME 			
                    from news P, news_votes PV, users U 
                     WHERE U.USER_ID = PV.USER_ID
			AND P.NEWS_ID=PV.NEWS_ID
                        AND P.NEWS_ID='".$_GET['item_id']."'";
          } else if ($_GET['mode'] == 'video') {
            $sql = "SELECT PV.VOTE_ID, P.USER_ID, P.CCTL, PV.VOTE, U.CONTENT_TRUST, U.USER_NAME 			
                    from video P, video_votes PV, users U 
                     WHERE U.USER_ID = PV.USER_ID
			AND P.VIDEO_ID=PV.VIDEO_ID
                        AND P.VIDEO_ID='".$_GET['item_id']."'";
          }
          $db->query($sql);
          $vdata = array();
	  $c = 0;
          while ($row = $db->nextRow()) {    
            if ($trust->getContentTrustLevel($row['CONTENT_TRUST']) <= 3)  {
              $moderate = true;
            } 
            $vd['USER_NAME'] = $row['USER_NAME'];
            $vd['CONTENT_TRUST'] = $trust->getContentTrustLevel($row['CONTENT_TRUST']);
            $vd['VOTE'] = $row['VOTE'];
	    $vdata[] = $vd;
          }
           
          if ($moderate) {
            $actkey = gen_rand_string(0, 10);           
	    unset($sdata);
 	    $sdata['ACTKEY'] = "'".$actkey."'";
            if ($_GET['mode'] == 'news' || $_GET['mode'] == 'blogs') {
   	      $db->update('news', $sdata, "NEWS_ID=".$_GET['item_id']);
              $sql="SELECT TITLE, DESCR, USER_NAME 
			FROM news N, news_details ND, users U
			WHERE N.NEWS_ID=ND.NEWS_ID
			      AND N.USER_ID=U.USER_ID
				AND N.NEWS_ID=".$_GET['item_id'];
              $db->query($sql);
	      if ($row = $db->nextRow()) {     
	        $edata['TITLE'] =  $row['TITLE'];
	        $edata['DESCR'] =  $row['DESCR'];
	        $edata['USER_NAME'] =  $row['USER_NAME'];
              }
            } else if ($_GET['mode'] == 'video') {
   	      $db->update('video', $sdata, "VIDEO_ID=".$_GET['item_id']);
            }

  	    $edata['VOTING_DETAILS'] = $vdata;
  	    $edata['VOTES'] = $votes;
            // assign points
	    $edata['URL_APPROVE'] = $conf_site_url."voting_moderation.php?mode=voting_approve&type=".$_GET['mode']."&item_id=".$_GET['item_id']."&actkey=".$actkey;
	    // do not assign anything
	    $edata['URL_DISAPPROVE'] = $conf_site_url."voting_moderation.php?mode=voting_disapprove&type=".$_GET['mode']."&item_id=".$_GET['item_id']."&actkey=".$actkey;

	    $email = new Email($langs, $_SESSION['_lang']);
 	    $email->getEmailFromTemplate ('email_content_voting_moderate', $edata) ;
            $subject = $langs['LANG_EMAIL_CONTENT_VOTING_MODERATE_LINE_1'];
	    $email->sendAdmin($subject);
          }
          else {
            unset($sdata);
            $sdata['VOTED'] = "'Y'";
	    $sdata['VOTES'] = $votes;
            if ($votes < 0) {
	      $sdata['PUBLISH'] = "'N'";
            }
            if ($_GET['mode'] == 'news' || $_GET['mode'] == 'blogs') {
              $db->update('news', $sdata, 'NEWS_ID='.$_GET['item_id']);
            } else if ($_GET['mode'] == 'video') {            
              $db->update('video', $sdata, 'VIDEO_ID='.$_GET['item_id']);
            }

            $trust = new Trust();	
            $trust->changeContentTrust($votes/abs($votes), $votee);

            if ($_GET['mode'] == 'blogs') {
              // reward
              if ($votes > 0) { 
                $credits = new Credits();
	 	$credits->updateCredits ($auth->getUserId(), 3);
  		$credit_log = new CreditsLog();
	  	$credit_log->logEvent ($auth->getUserId(), 8, 3);
	      }
            }
          }
        }  
      }
    }
  }
}

 $smarty->assign("thumb", $thumb);

 $start = getmicrotime();
 $content = $smarty->fetch('smarty_tpl/comment_vote.smarty');
 $stop = getmicrotime();
 if (isset($_GET['debugphp']))
   echo 'smarty_tpl/comment_vote.smarty'.($stop-$start);

echo $content;

include('class/db_close.inc.php');
?>