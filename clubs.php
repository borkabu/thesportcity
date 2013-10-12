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

$forumPermission = new ForumPermission();
if (isset($_POST['post_comment']) && $auth->userOn() &&
     $forumPermission->canAddComment($_POST['topic_id']) == 0) {
     $forumbox->addPost($forums['CLUBS'], $_POST['topic_id'], $_POST['item_id']);
}

if ($auth->userOn() && isset($_POST['set_info']) && isset($_POST['club_id'])) {
  $club = new Group($_POST['club_id']);
  if ($club->isGroupModerator($auth->getUserId())) {
    $s_fields = array('descr');
    $d_fields = '';
    $c_fields = '';
    $i_fields = '';
 
    $udata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);

    $db->update('forum_groups_details', $udata, 'GROUP_ID='.$_POST['club_id'].' AND LANG_ID='.$_POST['lang_id']);  
    unset($udata);
  }
}

// content
if (isset($_GET['club_id'])) {
  $club = $clubbox->getClubItem($_GET['club_id']);
  if ($club != '') { 
    $content .= $club;
    $content .= $forumbox->getComments($_GET['club_id'], 'CLUBS');
  }
  else header('Location: clubs.php');
} else {
      $content .= $clubbox->getClubs(isset($_GET['page']) ? $_GET['page'] : 1,PAGE_SIZE);
      $content .= $pagingbox->getPagingBox($clubbox->getRows(), isset($_GET['page']) ? $_GET['page'] : 0);
}
// include common header
  define("CLUBS", 1);
include('inc/top.inc.php');

echo  $content;
// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>