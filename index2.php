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
// http header
include('class/headers.inc.php');
include('class/user_session.inc.php');
// page requirements
include('class/inputs.inc.php');

include('class/timelinebox.inc.php');
 $timelinebox = new TimelineBox($langs, $_SESSION['_lang']);

include("class/manager_reports.inc.php");
include('class/manager_reportsbox.inc.php');
$manager_reportsbox = new ManagerReportsBox($langs, $_SESSION['_lang']);
include('class/countdownbox.inc.php');
$countdownbox = new CountdownBox($langs, $_SESSION['_lang']);
include('class/gamebox.inc.php');
$gamebox = new GameBox($langs, $_SESSION['_lang']);

// --- build content data -----------------------------------------------------
//else 
$timeline = $timelinebox->getTimelineBox( '',1, 5, 2);
$countdown = $countdownbox->getCountdownBox();
$announcements = $announcementbox->getAnnouncementBox('',1,1);
$blogs = $newsbox->getNewsHeaders( '',1, 5, 2);
$news = $newsbox->getNewsHeaders( '',1,3);
$external_news = $newsbox->getExternalNewsBox ();
$video = $videobox->getVideoNewsHeaders( '',1,1);
$reports = $manager_reportsbox->getManagerReportsBox(3);
$clubs = $clubbox->getClubsEventsBox();
$shop = $shopbox->getShopStockItemBox();
$manager_game = $gamebox->getGameBox($langs["LANG_FANTASY_MANAGER_U"], $langs["LANG_FANTASY_MANAGER_DESCR_U"]);
$rvs_game = $gamebox->getGameBox($langs["LANG_FANTASY_LEAGUE_U"], $langs["LANG_FANTASY_LEAGUE_DESCR_U"]);
$wager_game = $gamebox->getGameBox($langs["LANG_WAGER_U"], $langs["LANG_WAGER_DESCR_U"]);
$arranger_game = $gamebox->getGameBox($langs["LANG_ARRANGER_U"], $langs["LANG_ARRANGER_DESCR_U"]);
$smarty->debugging = true;
$smarty->clearAllAssign();
$smarty->assign("announcements", $announcements);
$smarty->assign("news", $news);
$smarty->assign("external_news", $external_news);
$smarty->assign("blogs", $blogs);
$smarty->assign("video", $video);
$smarty->assign("clubs", $clubs);
$smarty->assign("reports", $reports);
$smarty->assign("timeline", $timeline);
$smarty->assign("countdown", $countdown);
$smarty->assign("shop", $shop);
$smarty->assign("manager_game", $manager_game);
$smarty->assign("rvs_game", $rvs_game);
$smarty->assign("wager_game", $wager_game);
$smarty->assign("arranger_game", $arranger_game);

//print_r( $data['VIDEO']);

    $start = getmicrotime();
$content = $smarty->fetch('smarty_tpl/index.smarty');
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/index.smarty'.($stop-$start);


// include common header
include('inc/top_index.inc.php');
//$db->showquery=true;
// content
echo $content;

// include common footer
include('inc/bot_index.inc.php');

include('class/db_close.inc.php');
?>