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
include('class/moderatorbox.inc.php');

// --- build content data -----------------------------------------------------
//else 
$content = '';
//$db->showquery=true;
if ($auth->userOn()) {
  if ($auth->userOn() && $auth->isGroupModerator() > 0) {
    // add member  
    $user = new User($auth->getUserId());
    $groups = $user->getGroupsData(true);
    $group_members = '';
    if (isset($_GET['group_id']) && $auth->isGroupModerator2($_GET['group_id'])) {
      $group = new Group($_GET['group_id']);

      if (isset($_POST['delete_member'])) {
        $group->removeMember($_POST['user_id']);
      }

      if (isset($_POST['add_member'])) {
        $new_user = new User();
        if ($new_user->getUserIdFromUsername($_POST['user_name']) > 0)
          $group->addNewMember($new_user->user_id);
        else {
	  $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
          $errorbox1 = $errorbox->getErrorBox('ERROR_REMIND_NO_USER');
        }
      }

      $group_members = $group->getGroupMembersData(true);
    }

    $smarty->assign("groups", $groups);
    $smarty->assign("group_members", $group_members);
    $start = getmicrotime();
    $content .= $smarty->fetch('smarty_tpl/group_management_panel.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/group_management_panel.smarty'.($stop-$start);
    
  } else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_NOT_ENOUGH_TRUST_TO_MODERATE');
  }
}
else {
       $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
       $errorbox1 = $errorbox->getErrorBox('ERROR_NOT_LOGGED_IN');
}

  // include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>