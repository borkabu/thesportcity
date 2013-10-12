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

include('lib/genre_types.inc.php');

if (!$auth->userOn()) {
   $blog['ERROR']['MSG']=$langs['LANG_ERROR_CONTENT_LOGIN_U'];
}

$opt['class'] = 'input';
$opt['options'] = $reality_types;
if ($auth->userOn()) {
  $forumPermission = new ForumPermission();
  switch ($forumPermission->canAddNews()) {
     case 0: 
	$blog['LOGGED']['LANG_ID']=$_SESSION['lang_id'];
  if(isset($_POST['news_id']) && is_numeric($_POST['news_id'])) {
	// news is being edited
        $sql = "SELECT N.NEWS_ID, ND.TITLE, ND.DESCR, N.CAT_ID, SUBSTRING(N.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED,  SUBSTRING(N.DATE_CREATED, 1, 16) DATE_CREATED, N.PUBLISH, ND.SOURCE, ND.SOURCE_NAME, N.GENRE, N.PRIORITY, N.REALITY 
		FROM news N LEFT JOIN news_details ND ON ND.NEWS_ID=N.NEWS_ID AND ND.LANG_ID=".$_SESSION['lang_id']."
		WHERE N.NEWS_ID='".$_POST['news_id']."'";
	$db->query($sql);
	if(!$row=$db->nextRow()){
		// ERROR! No such news item. redirect to list
	//        header('Location: index.php');
		exit;
	}else{
		// populate $PRESET_VARS with data so form class can use their values
             while (list($key, $val) = each($row)) {
               $PRESET_VARS[strtolower($key)] = $val;
               $blog['LOGGED'][$key] = $val;
             }
	     $blog['LOGGED']['LANG_ID']=$_SESSION['lang_id'];
	};
  }

	$reality = '';
	$blog['LOGGED']['REALITY'] = $frm->getInput(FORM_INPUT_SELECT, 'reality', $reality, $opt, $reality);
        break;
     case 1:
	 // flood protection
     case 2:
        // not enough comment trust
        $blog['ERROR']['MSG']=$langs['LANG_ERROR_CONTENT_ADD_NEWS_U'];
        break;
  }
}

$added = false;
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['post_news']) && $auth->userOn() &&
   ((isset($_POST['news_id']) && is_numeric($_POST['news_id'])) || 
    empty($_POST['news_id']))){

      $news_id = $newsbox->addNewsItem($_POST['news_id'], 2);
      if ($news_id == '') {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_ADD_NEWS');
      }
      else {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getMessageBox('MESSAGE_ADD_CONTENT_SUCCESS');
        $added = true;
      }
}

// --- END SAVE ---------------------------------------------------------------

// get common inputs
// content
//print_r($data);
$content = '';
if (!$added) {
  $smarty->assign("blog", $blog); 
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/blog_add.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/blog_add.smarty'.($stop-$start);
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