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
include('class/order.inc.php');
include('class/shop.inc.php');
// --- build content data -----------------------------------------------------
//else 

$content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 17);

if (!$auth->userOn() || !$auth->isAdmin()) {
  $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
  $errorbox1 = $errorbox->getErrorBox('ERROR_SHOP_LOGIN');
} 
else {
  if (isset($_POST['mark_dispatched']) && isset($_POST['order_id'])) {
    $order = new Order($_POST['order_id'], 1);
    $order->markDispatched();    
  }

  if (isset($_GET['order_id'])) {
    $order_item = $shopbox->getIncompleteShopOrderItem($_GET['order_id'], 1);
    if ($order_item != '') { 
      $content .= $order_item;
    }
  } else {
      $content .= $shopbox->getIncompleteShopOrders(isset($_GET['page']) ? $_GET['page'] : 1,PAGE_SIZE);
      $content .= $pagingbox->getPagingBox($shopbox->getRows(), isset($_GET['page']) ? $_GET['page'] : 0);
  }
}

// include common header
include('inc/top.inc.php');

echo  $content;
// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>