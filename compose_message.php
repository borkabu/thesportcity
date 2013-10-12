<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
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
include('class/pmbox.inc.php');

if (!$auth->userOn()) {
   $error['MSG']=$langs['LANG_ERROR_PM_LOGIN_U'];
}
//$db->showquery=true;
// --- BEGIN SAVE -------------------------------------------------------------
$pm = new PM();
if (isset($_POST['delete_pm']) && $auth->userOn()) {
  $pm->deleteMessage($_POST['pm_id'], $_POST['folder_id'], $auth->getUserId());
}

if (isset($_POST['delete_all']) && $auth->userOn()) {
  $pm->deleteAllMessages($_POST['msgs'], $_POST['folder_id'], $auth->getUserId());
}


$added = false;
$is_error = false;
$pmbox = new PMBox($langs, $_SESSION['_lang']);
if(isset($_POST['post_pm']) && $auth->userOn() &&
   ((isset($_POST['pm_id']) && is_numeric($_POST['pm_id'])) || 
    empty($_POST['pm_id']))){
    if (trim($_POST['subject']) == "") {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_PM_NO_SUBJECT');
        $is_error = true;
    } else if (trim($_POST['descr']) == "" && trim($_POST['simple_text']) == "") {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_PM_NO_MESSAGE');
        $is_error = true;
    } else if (!isset($_POST['receipient_user_ids']) && !isset($_POST['receipient_group_ids'])) {
        $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
        $errorbox1 = $errorbox->getErrorBox('ERROR_PM_NO_RECEIPIENT');
        $is_error = true;
    } else {
      if (isset($_POST['receipient_user_ids']) || isset($_POST['receipient_group_ids'])) {
        $status = $pmbox->addPM(isset($_POST['pm_id']) ? $_POST['pm_id'] : '');
        if ($status == 0) {
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getMessageBox('MESSAGE_ADD_CONTENT_SUCCESS');
          $added = true;
        } else if ($status == 1) {
          $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getErrorBox('ERROR_PM_WRONG_RECEIVER');
          $is_error = true;
        } 
      }
      /*if ($_POST['pm_group'] > 0) {
        $status = $pmbox->addGroupPM(isset($_POST['pm_id']) ? $_POST['pm_id'] : '');
      } */
    }
}


if ($auth->userOn()) {
  $forumPermission = new ForumPermission();
  switch ($forumPermission->canAddPM()) {
     case 0: 
     case 1:
        if ($is_error || isset($_POST['edit_pm']) || isset($_GET['reply_pm']))
  	  $logged['PM_ID']=isset($_GET['message_id']) ? $_GET['message_id'] : '';
        break;
     case 2:
        // not enough comment trust
        $error['MSG']=$langs['LANG_ERROR_COMMENT_ADD_PM_U'];
        break;
  }
}

// --- END SAVE ---------------------------------------------------------------
$pm_menu = $menu->getMenuFromArray($pm_folders, 'folder_id');
if ($auth->userOn()) {  
  $message = '';
  if (isset($_GET['message_id']))
    $message = $pmbox->getMessage($_GET['message_id'], $_GET['folder_id']);
}  
 if (!$added)
   $bar_compose_message = $pmbox->getComposeMessageBar(isset($logged) ? $logged : '', $message);

 if (!empty($error))
   $smarty->assign("error", $error);    
 $smarty->assign("pm_menu", $pm_menu);    
 if (isset($bar_compose_message)) 
   $smarty->assign("bar_compose_message", $bar_compose_message);    

 $start = getmicrotime();
 $content = $smarty->fetch('smarty_tpl/compose_message.smarty');
 $stop = getmicrotime();
 if (isset($_GET['debugphp']))
   echo 'smarty_tpl/compose_message.smarty'.($stop-$start);


if ($auth->userOn()) {  
  if ($message != '') 
    $content .= $message;
  else {
    $content .= $pmbox->getFolder($_GET['folder_id'], isset($_GET['page']) ? $_GET['page'] : 1, isset($_GET['perpage']) ? $_GET['perpage'] : PAGE_SIZE, isset($_GET['filter_user']) ? $_GET['filter_user'] : '');
    $content .= $pagingbox->getPagingBox($pmbox->getRows(), isset($_GET['page']) ? $_GET['page'] : 0);
  }
}

$smarty->clearAllAssign();

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