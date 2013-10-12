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

// check if user can vote
$trust = new Trust();
$forum = new Forum();
$forumpermissions = new ForumPermission();
$thumb = '';
if ($auth->userOn() && $forumpermissions->canVoteComment()) {

  if (isset($_GET['post_id']) && is_numeric($_GET['post_id'])) {
    $can_process = false;
    $votee = -1;
    $cctl = -1;
    $sql = "SELECT PV.VOTE_ID, P.USER_ID, P.CCTL, P.VOTED from post P 
                    left join post_votes PV ON P.POST_ID=PV.POST_ID 
                         AND PV.USER_ID = ".$auth->getUserId()."
             WHERE P.USER_ID <> ".$auth->getUserId()."
                AND P.POST_ID='".$_GET['post_id']."'";
    $db->query($sql);
    if ($row = $db->nextRow()) {    
      if (empty($row['VOTE_ID']) && 
          $row['VOTED']  == 'N' &&
          $forumpermissions->canCommentBeVoted($row['CCTL']))
        $can_process = true;
    }

    if ($can_process) {
      $votee = $row['USER_ID'];
      $cctl = $row['CCTL'];
      $accepted = false;
      $sdata['USER_ID'] = $_SESSION['_user']['USER_ID'];
      $sdata['POST_ID'] = $_GET['post_id'];
      $sdata['DATE_VOTED'] = "NOW()";
      if (isset($_GET['action']) && $_GET['action'] == 'thumbup') {
        $thumb['THUMB_UP'] = 1;
        $sdata['VOTE'] = $trust->getCommentTrustLevel($_SESSION['_user']['COMMENT_TRUST']);
	$accepted = true;
      }
      if (isset($_GET['action']) && $_GET['action'] == 'thumbdown') {
        $thumb['THUMB_DOWN'] = 1;
        $sdata['VOTE'] = -1 * $trust->getCommentTrustLevel($_SESSION['_user']['COMMENT_TRUST']);
	$accepted = true;
      } 
      if ($accepted)
        $db->insert('post_votes', $sdata);
     }  

    // check if vote is decisive
    if ($votee > 0) {
      $sql="SELECT SUM(VOTE) VOTES, COUNT(VOTE) QUORUM FROM post_votes WHERE POST_ID='".$_GET['post_id']."'";
      $db->query($sql);
      if ($row = $db->nextRow()) {    
        $votes = $row['VOTES'];
        $quorum = $row['QUORUM'];
        if ($quorum >=3 && ($votes >= 3*$cctl || $votes <= -3*$cctl)) {
          // critical mass gathered
          // let's update rating
          $moderate = false;
          $sql = "SELECT PV.VOTE_ID, P.USER_ID, P.CCTL, PV.VOTE, U.COMMENT_TRUST, U.USER_NAME
                    from post P, post_votes PV, users U 
                     WHERE U.USER_ID = PV.USER_ID
			AND P.POST_ID=PV.POST_ID 
                        AND P.POST_ID='".$_GET['post_id']."'";
          $db->query($sql);
          $vdata = array();
	  $c = 0;
          while ($row = $db->nextRow()) {    
            if ($trust->getCommentTrustLevel($row['COMMENT_TRUST']) <= 3)  {
              $moderate = true;
            } 
            $vd['USER_NAME'] = $row['USER_NAME'];
            $vd['COMMENT_TRUST'] = $row['COMMENT_TRUST'];
            $vd['COMMENT_TRUST_LEVEL'] = $trust->getCommentTrustLevel($row['COMMENT_TRUST']);
            $vd['VOTE'] = $row['VOTE'];
	    $vdata[] = $vd;
          }
           
          if ($moderate) {
            $actkey = gen_rand_string(0, 10);           
	    unset($sdata);
 	    $sdata['ACTKEY'] = "'".$actkey."'";
 	    $db->update('post', $sdata, "POST_ID=".$_GET['post_id']);

            $edata = $forum->getPostInfo($_GET['post_id']);
  	    $edata['VOTING_DETAILS'] = $vdata;
  	    $edata['VOTES'] = $votes;
            // assign points
	    $edata['URL_APPROVE'] = $conf_site_url."voting_moderation.php?mode=voting_approve&post_id=".$_GET['post_id']."&actkey=".$actkey;
	    // do not assign anything
	    $edata['URL_DISAPPROVE'] = $conf_site_url."voting_moderation.php?mode=voting_disapprove&post_id=".$_GET['post_id']."&actkey=".$actkey;

	    $email = new Email($langs, $_SESSION['_lang']);
 	    $email->getEmailFromTemplate ('email_comment_voting_moderate', $edata) ;
            $subject = $langs['LANG_EMAIL_COMMENT_VOTING_MODERATE_LINE_1'];
	    $email->sendAdmin($subject);
          }
          else {
            unset($sdata);
            $sdata['VOTED'] = "'Y'";
	    $sdata['VOTES'] = $votes;
            $db->update('post', $sdata, 'POST_ID='.$_GET['post_id']);

            $trust = new Trust();	
            $trust->changeCommentTrust($votes/abs($votes), $votee);
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