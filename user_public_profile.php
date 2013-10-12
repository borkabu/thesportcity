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

// --- build content data -----------------------------------------------------

if (isset($_POST['user_name'])) {
  $user = new User();
  if ($user->getUserIdFromUsername($_POST['user_name']) > 0) {
    header('Location: user_public_profile.php?user_id='.$user->user_id);
  } else {              
    $error['MSG'] = $langs['LANG_ERROR_NO_USER_FOUND_U'];
    $smarty->assign("error",  $error);
  }
}
else if ((isset($_GET['user_id']) && is_numeric($_GET['user_id'])) || $auth->userOn()) {
  $user = new User(isset($_GET['user_id']) ? $_GET['user_id'] : $auth->getUserId());
  $profile = $user->getUserProfile();

  $smarty->assign("profile",  $profile);
}

  $start = getmicrotime();
  $content = $smarty->fetch('smarty_tpl/user_public_profile.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/user_public_profile.smarty'.($stop-$start);


// ----------------------------------------------------------------------------

// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_small.inc.php');
// close connections
include('class/db_close.inc.php');

?>