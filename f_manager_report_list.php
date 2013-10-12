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
include('class/manager.inc.php');
include('class/manager_reports.inc.php');

// --- build content data -----------------------------------------------------

 $content = '';
 $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);

 $manager = new Manager();
 $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
 $manager_user = '';
 $players_count = 0;

 $manager_reports = new ManagerReports();

 $manager_filter_box = $managerbox->getManagerFilterBox($manager->mseason_id);

//  if (isset($data['PLAYERS']))
  $reports['REPORT'] = $manager_reports->getReportList($manager->mseason_id, isset($_GET['page']) ? $_GET['page'] : 1,PAGE_SIZE);     
  $reports['PAGING'] = $pagingbox->getPagingBox($manager_reports->getRows(), isset($_GET['page']) ? $_GET['page'] : 0);


  $smarty->assign("manager_filter_box", $manager_filter_box);
  $smarty->assign("reports", $reports);    

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_report_list.smarty');
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_report_list.smarty'.($stop-$start);

// ----------------------------------------------------------------------------
    define("FANTASY_MANAGER", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_manager.inc.php');

// close connections
include('class/db_close.inc.php');

?>