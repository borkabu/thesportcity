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
include('lib/genre_types.inc.php');
// --- build content data -----------------------------------------------------
//else 

$content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 1);
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/news.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/news.smarty'.($stop-$start);

$forumPermission = new ForumPermission();
if (isset($_POST['post_comment']) && $auth->userOn() &&
     $forumPermission->canAddComment($_POST['topic_id']) == 0) {
     $forumbox->addPost($forums['BLOGS'], $_POST['topic_id'], $_POST['item_id']);
}

// content
if (isset($_GET['news_id'])) {
  $news = $newsbox->getNewsItem($_GET['news_id'], 2);
  if ($news != '') { 
    $content .= $news;
    $content .= $forumbox->getComments($_GET['news_id'], 'BLOGS');
  }
  else header('Location: news.php');
} else {
      $content .= $newsbox->getNews(isset($_GET['cat_id']) ? $_GET['cat_id'] : '',isset($_GET['page']) ? $_GET['page'] : 1,PAGE_SIZE, 2);
      $content .= $pagingbox->getPagingBox($newsbox->getRows());
} 
// include common header
include('inc/top.inc.php');

echo  $content;
// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>