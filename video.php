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

$content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 1);
$tpl->setCacheLevel(TPL_CACHE_NOTHING);

$tpl->setTemplateFile('tpl/video.tpl.html');

$tpl->addData($data);

$content .= $tpl->parse();

//$db->showquery=true;

$forumPermission = new ForumPermission();
if (isset($_POST['post_comment']) && $auth->userOn() &&
     $forumPermission->canAddComment($_POST['topic_id']) == 0) {
     $forumbox->addPost($forums['VIDEO'], $_POST['topic_id'], $_POST['item_id']);
}

// content
if (isset($_GET['video_id'])) {
  $video = $videobox->getVideoNewsItem($_GET['video_id']);
  if ($video != '') { 
    $content .= $video;
    $content .= $forumbox->getComments($_GET['video_id'], 'VIDEO');
  }
  else header('Location: video.php');
} else {
      $content .= $videobox->getVideoNews(isset($_GET['cat_id']) ? $_GET['cat_id'] : '',isset($_GET['page']) ? $_GET['page'] : 1,PAGE_SIZE);
      $content .= $pagingbox->getPagingBox($videobox->getRows(), isset($_GET['page']) ? $_GET['page'] : 0);
}
// include common header
include('inc/top.inc.php');

echo  $content;
// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>