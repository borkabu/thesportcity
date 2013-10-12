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
$auth->refreshEssensials();
$content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 5);

if ($auth->userOn()) {
  if (isset($_POST['confirm_order'])) {
    $basket= new Basket();
    if ($basket->checkoutBasket(1)) {
//      $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
//      $errorbox1 = $errorbox->getMessageBox('MESSAGE_SHOP_ORDER_SAVED');
      $order_item = $shopbox->getShopOrderItem($basket->order_id);
      if ($order_item != '') { 
        $content .= $order_item;
      }
    }
  } else {
   $basketbox = new BasketBox($langs, $_SESSION['_lang']);
   $content .= $pagebox->getPage(25);
   $content .= $basketbox->getBasketCheckout();
  }
} else {
  $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
  $errorbox1 = $errorbox->getErrorBox('ERROR_SHOP_LOGIN');
} 

// include common header
include('inc/top.inc.php');

echo  $content;
// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>