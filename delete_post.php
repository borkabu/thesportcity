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

$forum = new Forum();
// --- build content data -----------------------------------------------------
//else 

$content = '';
if(isset($_GET['action']) && $_GET['action'] == 'delete_post' && $auth->userOn()){
  if ($_GET['post_id'] > 0 && is_numeric($_GET['post_id'])) {
    // verify that post can be really deleted
      $trust = new Trust();
      $user_cond = " AND USER_ID='".$auth->getUserId()."'";
      if ($trust->getCommentTrustLevel($_SESSION["_user"]['COMMENT_TRUST']) >= 5)
        $user_cond = "";
      $sql="SELECT * FROM post WHERE post_id='".$_GET['post_id']."' ".$user_cond;
//echo $sql;
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $sql="DELETE FROM post WHERE post_id='".$_GET['post_id']."'";
        $db->query($sql);

        unset($sdata);
        $sdata['POSTS'] ='POSTS-1';
        if (is_numeric($_GET['topic_id']))
          $db->update('topic',$sdata, "TOPIC_ID='".$_GET['topic_id']."'");
      } 
  }
}

if (isset($_GET['topic_id'])) {
  if ($forum->doesTopicExist($_GET['topic_id'])) {
    $content .= $forumbox->getPosts(isset($_GET['topic_id']) ? $_GET['topic_id'] : '', 1, PAGE_SIZE, -1, -1, true);
  } 
}

echo $content;

include('class/db_close.inc.php');
?>