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

$pmbox = new PMBox($langs, $_SESSION['_lang']);
if ($auth->userOn() && $_GET['mode'] == 'users') {
  if (isset($_SESSION['_user']['PM'][$_GET['message_id']]['USER_RECEIPIENTS'][$_GET['user']]))
    unset($_SESSION['_user']['PM'][$_GET['message_id']]['USER_RECEIPIENTS'][$_GET['user']]);
  if ($_GET['message_id'] > 0) 
    $db->delete('pm_message_receiver', 'RECEIVER_ID='.$_GET['user'].' AND PM_ID='.$_GET['message_id'].' AND RECEIVER_TYPE=1');
}

if ($auth->userOn() && $_GET['mode'] == 'groups') {
  if (isset($_SESSION['_user']['PM'][$_GET['message_id']]['GROUP_RECEIPIENTS'][$_GET['group']]))
    unset($_SESSION['_user']['PM'][$_GET['message_id']]['GROUP_RECEIPIENTS'][$_GET['group']]);
  if ($_GET['message_id'] > 0) 
    $db->delete('pm_message_receiver', 'RECEIVER_ID='.$_GET['group'].' AND PM_ID='.$_GET['message_id'].' AND RECEIVER_TYPE=2');

}

$content = $pmbox->getAddReceipientsBox();

// ----------------------------------------------------------------------------
// content
echo $content;

// close connections
include('class/db_close.inc.php');

?>