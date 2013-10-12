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

if (!$auth->userOn() || !isset($_GET['forum_id'])) {
   header('Location: index.php');
   exit;
}

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save']) && $auth->userOn()){
   $topic_id = $forumbox->addPost($_POST['forum_id']);
//echo $topic_id;      
   header('Location: forum.php?topic_id='.$topic_id);
   exit;
};
// --- END SAVE ---------------------------------------------------------------

  if(isset($_GET['forum_id']) && is_numeric($_GET['forum_id'])){
	// news is being edited
        $sql = "SELECT F.FORUM_ID, FD.FORUM_NAME, F.PUBLISH
	FROM forum F LEFT JOIN forum_details FD ON FD.FORUM_ID=F.FORUM_ID AND FD.LANG_ID=".$_SESSION['lang_id']."
	WHERE F.FORUM_ID=".$_GET['forum_id']." AND PUBLISH='Y'";
	$db->query($sql);

	if(!$row=$db->nextRow()){
		// ERROR! No such news item. redirect to list
	        header('Location: index.php');
		exit;
	}else{
		// populate $PRESET_VARS with data so form class can use their values
             while (list($key, $val) = each($row)) {
               $PRESET_VARS[strtolower($key)] = $val;
               $data['TOPIC'][0][$key] = $val;
             }
	     $data['TOPIC'][0]['lang_id']=$_SESSION['lang_id'];
	};
	$db->free();
  }else{
	// adding news
        header('Location: index.php');
	exit;
  }

// get common inputs

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('tpl/topic_add.tpl.html');
$tpl->addData($data);

$content = $tpl->parse();
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