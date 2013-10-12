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
include('class/newsletter.inc.php');
include('class/moderatorbox.inc.php');

// --- build content data -----------------------------------------------------
//else 
$content = '';
//$db->showquery = true;

if (isset($_GET['mode']) && isset($_GET['actkey'])) {
  $moderator_queue = new ModeratorBox($langs, $_SESSION["_lang"]);
  if (isset($_GET['post_id']))
    $errorbox1 .= $moderator_queue->moderateComment($_GET['mode'], $_GET['post_id'], $_GET['actkey']);
  if (isset($_GET['news_id']))
    $errorbox1 .= $moderator_queue->moderateContent($_GET['mode'], $_GET['news_id'], $_GET['actkey']);
  if (isset($_GET['answer_id']))
    $errorbox1 .= $moderator_queue->moderateContent($_GET['mode'], $_GET['answer_id'], $_GET['actkey']);

}

// include common header
include('inc/top.inc.php');

echo  $content;
// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>