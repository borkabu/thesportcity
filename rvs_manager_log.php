<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);

//return '';

// includes
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
include('class/manager.inc.php');
include('class/rvs_manager_user.inc.php');

// --- build content data -----------------------------------------------------

 $content = '';

 $manager = new Manager('', 'rvs');
 $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
 $manager_user = '';
 $players_count = 0;

 if ($auth->userOn()) {
    $rvs_manager_user = new RvsManagerUser($manager->mseason_id);

    $manager_logbox = new LogBox($langs, $_SESSION["_lang"]);
    $manager_log['MANAGER_LOG'] = $manager_logbox->getRvsManagerLogBox($rvs_manager_user->league_id, isset($_GET['page']) ? $_GET['page'] : 1);
    $manager_log['MANAGER_LOG_PAGING'] = $pagingbox->getPagingBox($manager_logbox->getRows(), isset($_GET['page']) ? $_GET['page'] : 1);
  } else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_RVS_MANAGER_LOGIN');
  }

  $smarty->assign("manager_log", $manager_log);
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/rvs_manager_log.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/rvs_manager_log.smarty'.($stop-$start);

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
