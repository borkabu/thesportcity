<?php
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
include('class/wager.inc.php');
include('class/wager_user.inc.php');

// --- build content data -----------------------------------------------------

 $content = '';
 $content = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 6);

 $wager = new Wager();
 $wagerbox = new WagerBox($langs, $_SESSION["_lang"]);
 $wager_user = '';
 $wager_log="";

if ($auth->userOn()) {
 $wager_user = new WagerUser($wager->tseason_id);

  $wager_log['WAGER_FILTER_BOX'] = $wagerbox->getWagerFilterBox($wager->tseason_id);

  $wager_logbox = new LogBox($langs, $_SESSION["_lang"]);
  $wager_log['WAGER_LOG'] = $wager_logbox->getWagerUserLogBox($auth->getUserId(), $wager->tseason_id, isset($_GET['page']) ? $_GET['page'] : 1);
  $wager_log['WAGER_LOG_PAGING'] = $pagingbox->getPagingBox($wager_logbox->getRows(), isset($_GET['page']) ? $_GET['page'] : 1);
  $smarty->assign("wager", $wager_log);
} else {
    $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
    $errorbox1 = $errorbox->getErrorBox('ERROR_WAGER_LOGIN');
}
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/wager_log.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/wager_log.smarty'.($stop-$start);

// ----------------------------------------------------------------------------
  define("WAGER", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_wager.inc.php');

// close connections
include('class/db_close.inc.php');

?>