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
if(isset($_POST['delete_news']) && $auth->userOn()){
  if ($_POST['news_id'] > 0 && is_numeric($_POST['news_id'])) {
    // verify that post can be really deleted
      $sql="SELECT * FROM news WHERE news_id='".$_POST['news_id']."' AND USER_ID='".$auth->getUserId()."' AND VOTED='N'";
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $db->delete('news', "news_id='".$_POST['news_id']."' AND USER_ID='".$auth->getUserId()."'");
        $db->delete('news_details', "news_id='".$_POST['news_id']."'");
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getMessageBox('MESSAGE_DELETE_NEWS_SUCCESS');
      } else {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_DELETE_NEWS');
      }
  }
}

// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>