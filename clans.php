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
include('class/manager.inc.php');
// --- build content data -----------------------------------------------------
//else 

$content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 7);

$forumPermission = new ForumPermission();
if (isset($_POST['post_comment']) && $auth->userOn() &&
     $forumPermission->canAddComment($_POST['topic_id']) == 0) {
//$db->showquery=true;
     $forumbox->addPost($forums['CLANS_PUBLIC'], $_POST['topic_id'], $_POST['item_id']);
}

// content
if (isset($_GET['clan_id'])) {
  $clan = $clanbox->getClanItem($_GET['clan_id']);
  if ($clan != '') { 
    $content .= $clan;
    $content .= $forumbox->getComments($_GET['clan_id'], 'CLANS_PUBLIC', isset($_GET['page']) ? $_GET['page'] : 1);
  }
  else header('Location: clans.php');
} else {
      $content .= $clanbox->getClans(isset($_GET['page']) ? $_GET['page'] : 1,PAGE_SIZE);
      $content .= $pagingbox->getPagingBox($clanbox->getRows(), isset($_GET['page']) ? $_GET['page'] : 0);
}
// include common header
  define("CLANS", 1);
include('inc/top.inc.php');

echo  $content;
// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>