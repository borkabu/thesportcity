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
include('class/shop.inc.php');
// --- build content data -----------------------------------------------------
//else 


if (isset($_POST['empty_basket'])) {
  $basket= new Basket();
  $basket->emptyBasket();
}

if (isset($_POST['update_quantity']) && isset($_POST['basket_item_id']) && $_POST['quantity'] > 0) {
  $basket= new Basket();
  $basket->updateBasketItem($_POST['basket_item_id'], $_POST['quantity']);
}

// add to basket
if (isset($_POST['add_item']) && isset($_POST['item_id']) && $_POST['quantity'] > 0) {
  $basket= new Basket();
  $basket_item = new BasketItem($_POST['item_id']);
  // get attributes from _POST;
  $attributes = array();
  foreach ($_POST as $key => $value) {
    if (strpos($key, 'attr') !== false && !empty($value)) {
      $values =  explode("_", $value);
      $attribute['ATTRIBUTE_ID'] = $values[0];
      $attribute['VALUE_ID'] = $values[1];
      $attributes[] = $attribute;
    }
  }

  $basket_item->setAttributes($attributes);
  $basket->addBasketItem($basket_item, $_POST['quantity']);
}

// add to basket
if (isset($_POST['delete_item']) && isset($_POST['basket_item_id'])) {
  $basket= new Basket();
  $basket->deleteBasketItem($_POST['basket_item_id']);
}

$content = $commmenu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 5);

if (!$auth->userOn()) {
  $errorbox = new ErrorBox($langs, $_SESSION["_lang"]);
  $errorbox1 = $errorbox->getErrorBox('ERROR_SHOP_LOGIN');
} 
else if ($auth->userOn()) {
   $basketbox = new BasketBox($langs, $_SESSION['_lang']);
   $content .= $basketbox->getBasket();
}
// include common header
include('inc/top.inc.php');

echo  $content;
// include common footer
include('inc/bot.inc.php');

include('class/db_close.inc.php');
?>