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

if (!$auth->userOn()) {
   $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_CONTENT_LOGIN_U'];
}

if ($auth->userOn()) {
  $forumPermission = new ForumPermission();
  switch ($forumPermission->canAddNews()) {
     case 0: 
	$data['LOGGED'][0]['lang_id']=$_SESSION['lang_id'];
  if(isset($_POST['video_id']) && is_numeric($_POST['video_id'])) {
	// news is being edited
        $sql = "SELECT N.VIDEO_ID, ND.TITLE, ND.DESCR, N.LINK, N.THUMBNAIL, N.CAT_ID, SUBSTRING(N.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED,  N.PUBLISH, N.SOURCE, N.SOURCE_NAME
		FROM video N LEFT JOIN video_details ND ON ND.VIDEO_ID=N.VIDEO_ID AND ND.LANG_ID=".$_SESSION['lang_id']."
		WHERE N.VIDEO_ID='".$_POST['video_id']."'";
	$db->query($sql);
	if(!$row=$db->nextRow()){
		// ERROR! No such news item. redirect to list
	//        header('Location: index.php');
		exit;
	}else{
		// populate $PRESET_VARS with data so form class can use their values
             while (list($key, $val) = each($row)) {
               $PRESET_VARS[strtolower($key)] = $val;
               $data['LOGGED'][0][$key] = $val;
             }
	     $data['LOGGED'][0]['lang_id']=$_SESSION['lang_id'];
	};
  }

        break;
     case 1:
	 // flood protection
     case 2:
        // not enough comment trust
        $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_CONTENT_ADD_NEWS_U'];
        break;
  }
}

$added = false;
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['post_video']) && $auth->userOn() &&
   ((isset($_POST['video_id']) && is_numeric($_POST['video_id'])) || 
    empty($_POST['video_id']))){

      $video_id = $videobox->addVideoNewsItem($_POST['video_id'], 1);
      if ($video_id == '') {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_ADD_NEWS');
      }
      else {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getMessageBox('MESSAGE_ADD_CONTENT_SUCCESS');
        $added = true;
	header("Location: video.php");
      }
}

// --- END SAVE ---------------------------------------------------------------

// get common inputs
// content
//print_r($data);
$content = '';
if (!$added) {
  $tpl->setCacheLevel(TPL_CACHE_NOTHING);
  $tpl->setTemplateFile('tpl/video_add.tpl.html');
  $tpl->addData($data);
  $content = $tpl->parse();
}

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