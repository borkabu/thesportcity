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
include('class/survey.inc.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');
// --- build content data -----------------------------------------------------
//else 

$content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 6);
/*$tpl->setCacheLevel(TPL_CACHE_NOTHING);

$tpl->setTemplateFile('tpl/clubs_events.tpl.html');

$tpl->addData($data);

$content .= $tpl->parse();*/

$forumPermission = new ForumPermission();
if (isset($_POST['post_comment']) && $auth->userOn() &&
     $forumPermission->canAddComment($_POST['topic_id']) == 0) {
//$db->showquery=true;
     $forumbox->addPost($forums['CLUBS_EVENTS'], $_POST['topic_id'], $_POST['item_id']);
}

if ($auth->userOn() && isset($_POST['remove_participant']) && isset($_POST['event_id']) && isset($_POST['user_id'])) {
  $club = new Group($_POST['club_id']);
  if ($club->isGroupModerator($auth->getUserId())) {
    if ($club->hasMember($_POST['user_id']) 
	&& $club->hasEventParticipant($_POST['user_id'], $_POST['event_id'])) {
//$db->showquery=true;
       $db->delete('forum_groups_events_members', "EVENT_ID=".$_POST['event_id']. " AND USER_ID=".$_POST['user_id']);
    }

  }
}

if ($auth->userOn() && isset($_POST['join_event']) && isset($_POST['event_id'])  && isset($_POST['club_id'])) {
  // validation 
  $sql="SELECT * FROM forum_groups_events FGE where group_id=".$_POST['club_id']." and event_id=".$_POST['event_id']. " AND recruitment_active = 'Y'";
  $db->query($sql);
  if ($row = $db->nextRow()) { 
    $club = new Group($_POST['club_id']);
    if ($club->hasMember($auth->getUserId()) 
	&& !$club->hasEventParticipant($auth->getUserId(), $_POST['event_id']) 
        && $_SESSION['_user']['CREDIT'] >= $row['ENTRY_FEE']) {
      unset($sdata);
      $sdata['user_id'] = $auth->getUserId();
      $sdata['event_id'] = $_POST['event_id'];
      $sdata['date_joined'] = "NOW()";
      $db->insert('forum_groups_events_members', $sdata);  
      unset($sdata); 
      $sql = "SELECT count(user_ID) USERS FROM forum_groups_events_members WHERE event_id=".$_POST['event_id'];
      $db->query($sql);
      $row2 = $db->nextRow();
      if ($row['PARTICIPANTS'] <= $row2['USERS'] && $row['PARTICIPANTS'] > 0)
	$sdata['recruitment_active'] = "'N'";
      $sdata['players'] = "players+1";
      $db->update('forum_groups_events', $sdata, 'EVENT_ID='.$_POST['event_id']);  
      unset($sdata);
      if ($row['ENTRY_FEE'] > 0) {
                  // transfer credits
           $credits = new Credits();
           $credit_log = new CreditsLog();
           $prezident = $club->getGroupPrezident();
           $credits->updateCredits($prezident, $row['ENTRY_FEE']);
           $credit_log->logEvent ($prezident, 10, $row['ENTRY_FEE'], $auth->getUserId());

           $credits->updateCredits($auth->getUserId(), -1*$row['ENTRY_FEE']); 
           $credit_log->logEvent ($auth->getUserId(), 5, $row['ENTRY_FEE'], $prezident);
      }
    }
  }
}

if ($auth->userOn() && isset($_POST['set_info']) && isset($_POST['event_id'])) {
  $club = new Group($_POST['club_id']);
  if ($club->isGroupModerator($auth->getUserId())) {
//$db->showquery=true;
    $s_fields = '';
    $d_fields = '';
    $c_fields = array('finished', 'recruitment_active');
    $i_fields = array('participants', 'entry_fee');

    $s_fields_d = array('title', 'descr', 'results');
    $d_fields_d = '';
    $c_fields_d = '';
    $i_fields_d = '';

    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST); 
    $udata = buildSaveData($s_fields_d, $i_fields_d, $d_fields_d, $c_fields_d, $_POST);

    $db->update('forum_groups_events', $sdata, 'EVENT_ID='.$_POST['event_id']);  
    $db->update('forum_groups_events_details', $udata, 'EVENT_ID='.$_POST['event_id']." AND LANG_ID=".$_SESSION['lang_id']);  
    unset($sdata);
    unset($udata);

    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getMessageBox('MESSAGE_ADD_CONTENT_SUCCESS');
  }
}

// content
//$db->showquery=true;
if (isset($_GET['club_id']) && isset($_GET['event_id'])) {
  $event = $clubbox->getClubEventItem($_GET['event_id']);
  if ($event != '') { 
    $content .= $event;
    $content .= $forumbox->getComments($_GET['event_id'], 'CLUBS_EVENTS', isset($_GET['page']) ? $_GET['page'] : 1);
  }
} else {
      $content .= $clubbox->getClubsEvents(isset($_GET['page']) ? $_GET['page'] : 1,PAGE_SIZE);
      $content .= $pagingbox->getPagingBox($clubbox->getRows(), isset($_GET['page']) ? $_GET['page'] : 0);
}
  define("CLUBS", 1);
// include common header
include('inc/top.inc.php');

echo  $content;
// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>