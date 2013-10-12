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

//$db->showquery=true;
include('include.php');
//$db->showquery=false;
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
// page requirements
include('class/inputs.inc.php');

define('FORUM', true);  
$forum = new Forum();
// --- build content data -----------------------------------------------------
//else 
//$db->showquery=true;

if(isset($_POST['read_all']) && $auth->userOn()) {
  $notification = new Notification();
  $notification->markAllRead(!empty($_POST['forum_id']) ? $_POST['forum_id'] : '', !empty($_POST['cat_id']) ? $_POST['cat_id'] : '');
}

if(isset($_POST['pin_topic']) && $auth->userOn() && isset($_POST['forum_id']) && isset($_POST['topic_id'])) {
  if ($_POST['topic_id'] > 0 && is_numeric($_POST['topic_id'])) {
    $forumPermission = new ForumPermission();
    $moderator = $auth->isForumModerator($_POST['forum_id']);
    if ($forumPermission->canPinTopic($_POST['forum_id'], $_POST['topic_id'], $moderator)) {
      unset($sdata);
      $sdata['PINNED'] = 1;
      $db->update("topic", $sdata, "topic_id=".$_POST['topic_id']);
    }
  }
  header('Location: '.$_SERVER["REQUEST_URI"]);
  exit;
}

if(isset($_POST['pin_post']) && $auth->userOn() && isset($_POST['post_id']) && isset($_POST['topic_id'])) {
  if ($_POST['post_id'] > 0 && is_numeric($_POST['post_id'])) {
    $forumPermission = new ForumPermission();
    $forum = new Forum();
    $post_data = $forum->getPostInfo($_POST['post_id']);
    $moderator = $auth->isForumModerator($post_data['FORUM_ID']);
    if ($forumPermission->canPinPost($_POST['post_id'], $moderator)) {
      unset($sdata);
      $sdata['PINNED'] = 1;
      $db->update("post", $sdata, "post_id=".$_POST['post_id']);
    }
  }
  header('Location: '.$_SERVER["REQUEST_URI"]);
  exit;
}

if(isset($_POST['unpin_topic']) && $auth->userOn() && isset($_POST['forum_id']) && isset($_POST['topic_id'])) {
  if ($_POST['topic_id'] > 0 && is_numeric($_POST['topic_id'])) {
    $forumPermission = new ForumPermission();
    $moderator = $auth->isForumModerator($_POST['forum_id']);
    if ($forumPermission->canPinTopic($_POST['forum_id'], $_POST['topic_id'], $moderator)) {
      unset($sdata);
      $sdata['PINNED'] = 0;
      $db->update("topic", $sdata, "topic_id=".$_POST['topic_id']);
    }
  }
  header('Location: '.$_SERVER["REQUEST_URI"]);
  exit;
}

if(isset($_POST['unpin_post']) && $auth->userOn() && isset($_POST['post_id']) && isset($_POST['topic_id'])) {
  if ($_POST['post_id'] > 0 && is_numeric($_POST['post_id'])) {
    $forumPermission = new ForumPermission();
    $forum = new Forum();
    $post_data = $forum->getPostInfo($_POST['post_id']);
    $moderator = $auth->isForumModerator($post_data['FORUM_ID']);
    if ($forumPermission->canPinPost($_POST['post_id'], $moderator)) {
      unset($sdata);
      $sdata['PINNED'] = 0;
      $db->update("post", $sdata, "post_id=".$_POST['post_id']);
    }
  }
  header('Location: '.$_SERVER["REQUEST_URI"]);
  exit;
}
if(isset($_POST['delete_topic']) && $auth->userOn() && isset($_POST['forum_id']) && isset($_POST['topic_id'])) {
  if ($_POST['topic_id'] > 0 && is_numeric($_POST['topic_id'])) {
    // verify that topic can be really deleted
     $sql="SELECT POSTS, USER_ID TOPIC_OWNER FROM topic WHERE topic_id='".$_POST['topic_id']."'";
     $db->query($sql);
     if ($row = $db->nextRow()) {
       $forumPermission = new ForumPermission();
       $moderator = $auth->isForumModerator($_POST['forum_id']);
       if ($forumPermission->canDeleteTopic($_POST['forum_id'], $_POST['topic_id'], $row['TOPIC_OWNER'], $row['POSTS'], $moderator)) {
         $sql="DELETE FROM post WHERE topic_id='".$_POST['topic_id']."'";
         $db->query($sql);

         $sql="DELETE FROM topic WHERE topic_id='".$_POST['topic_id']."'";
         $db->query($sql);

         unset($sdata);
         $sdata['TOPICS'] ='TOPICS-1';
         if (is_numeric($_POST['forum_id']))
           $db->update('forum_details',$sdata, "FORUM_ID='".$_POST['forum_id']."' AND LANG_ID='".$_SESSION['lang_id']."'");
     
//echo $sql;
         header('Location: forum.php?forum_id='.$_POST['forum_id']);
         exit;
       } else {
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getErrorBox('ERROR_DELETE_TOPIC');
       }
     }
  }
}

if(isset($_POST['delete_post']) && $auth->userOn()){
  if ($_POST['post_id'] > 0 && is_numeric($_POST['post_id'])) {
    // verify that post can be really deleted
      $trust = new Trust();
      $user_cond = " AND USER_ID='".$auth->getUserId()."'";
      if ($trust->getCommentTrustLevel($_SESSION["_user"]['COMMENT_TRUST']) >= 5)
        $user_cond = "";
      $sql="SELECT * FROM post WHERE post_id='".$_POST['post_id']."' ".$user_cond;
//echo $sql;
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $sql="DELETE FROM post WHERE post_id='".$_POST['post_id']."'";
        $db->query($sql);

        unset($sdata);
        $sdata['POSTS'] ='POSTS-1';
        if (is_numeric($_POST['topic_id']))
          $db->update('topic',$sdata, "TOPIC_ID='".$_POST['topic_id']."'");

      } 
      header('Location: forum.php?topic_id='.$_POST['topic_id']);
      exit;
  }
}

if(isset($_POST['post_comment']) && $auth->userOn()){
  $forumPermission = new ForumPermission();
  if (!isset($_POST['topic_id'])) {
    // add topic
    if (isset($_POST['forum_id']) && is_numeric($_POST['forum_id']) &&
        $forumPermission->canStartTopic($_POST['forum_id']) == 0) {
      $topic_id = $forumbox->addPost($_POST['forum_id']);
      if ($topic_id == -1) {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_CREATE_TOPIC');
      }
      else {
        header('Location: forum.php?topic_id='.$topic_id);
        exit;
      }
    } 
  } else if (is_numeric($_POST['topic_id']) && 
             is_numeric($_POST['forum_id']) &&
             $forumPermission->canAddComment($_POST['topic_id']) == 0) {
     $forumbox->addPost($_POST['forum_id'], $_POST['topic_id']);
     header('Location: '.$_SERVER["REQUEST_URI"]);
     exit;
  }
}

$content = '';

// content
if (isset($_GET['forum_id'])) {
  $content .= $forumbox->getTopics(isset($_GET['forum_id']) ? $_GET['forum_id'] : '', isset($_GET['page']) ? $_GET['page'] : 1);
  $content .= $pagingbox->getPagingBox($forumbox->getRows(), isset($_GET['page']) ? $_GET['page'] : 1);
}
else if (isset($_GET['topic_id'])) {
  if ($forum->doesTopicExist($_GET['topic_id'])) {
    $forumPermission = new ForumPermission();
    if ($forumPermission->canReadTopic($_GET['topic_id'])) {
      $content .= $forumbox->getPosts(isset($_GET['topic_id']) ? $_GET['topic_id'] : '', isset($_GET['page']) ? $_GET['page'] : 1, $page_size, $forum->forum_id);
    } else {
      header('Location: forum.php');
      exit;
    }
  } else {
    $forum_id = $forum->getForumFromTopic($_GET['topic_id']);
    if ($forum_id == -1) {
      header('Location: forum.php');
      exit;
    } else {
      header('Location: forum.php?forum_id='.$forum_id);
      exit;
    }
  } 
}
else {
  if ($auth->userOn() && $auth->isAdmin())
    $content .= $forumbox->getForumsSummary();
  $content .= $forumbox->getForums(isset($_GET['cat_id']) ? $_GET['cat_id'] : '');
}

$submenu = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);
// include common header
include('inc/top.inc.php');

 echo $content;
// include common footer
include('inc/bot_forum.inc.php');

include('class/db_close.inc.php');
?>