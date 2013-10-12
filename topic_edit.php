<?php
/*
===============================================================================
news_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit news message
  - edit news keywords
  - create new news message

TABLES USED: 
  - BASKET.NEWS
  - BASKET.KEYWORDS
  - BASKET.SOURCES

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] additional buttons
===============================================================================
*/

include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
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

if (!$auth->userOn() || !isset($_POST['topic_id'])) {
   header('Location: index.php');
   exit;
}

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
 $sql = "SELECT F.TOPIC_ID, F.FORUM_ID, F.TOPIC_NAME, F.PUBLISH, F.USER_ID TOPIC_OWNER, TOPIC_DESCR
	FROM topic F 
	WHERE F.TOPIC_ID=".$_POST['topic_id']." AND PUBLISH='Y'";
	$db->query($sql);

if(isset($_POST['form_save']) && $auth->userOn()){
   if ($row=$db->nextRow()) {
    $forumPermission = new ForumPermission();
    if ($forumPermission->canEditTopic($row['FORUM_ID'], $row['TOPIC_ID'], $row['TOPIC_OWNER'], $auth->isForumModerator($row['FORUM_ID']))) {
      $forumbox->editTopic($_POST['topic_id']);
      header('Location: forum.php?topic_id='.$_POST['topic_id']);
      exit;
    }
   }
};
// --- END SAVE ---------------------------------------------------------------

  if(isset($_POST['topic_id']) && is_numeric($_POST['topic_id'])){
	// news is being edited
	if(!$row=$db->nextRow()){
		// ERROR! No such news item. redirect to list
//	        header('Location: index.php');
		exit;
	}else{
		// populate $PRESET_VARS with data so form class can use their values
             while (list($key, $val) = each($row)) {
               $PRESET_VARS[strtolower($key)] = $val;
               $topic[$key] = $val;
             }
	     $topic['lang_id']=$_SESSION['lang_id'];
	};
	$db->free();
  }else{
	// adding news
//        header('Location: index.php');
	exit;
  }

// get common inputs
  $smarty->assign("topic", $topic);

  $start = getmicrotime();
  $content = $smarty->fetch('smarty_tpl/topic_edit.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/topic_edit.smarty'.($stop-$start);

// ----------------------------------------------------------------------------

// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

// close connections
include('class/db_close.inc.php');

?>