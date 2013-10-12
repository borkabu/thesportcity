<?php
/*
===============================================================================
events.inc.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - generate event list data

TABLES USED: 
  - BASKET.EVENTS

STATUS:
  - [STAT:INPRGS] in progress

TODO:
  - [TODO:PUBVAR] main functionality
===============================================================================
*/

class BasketBox extends Box{

  function getBasket () {
    global $_SESSION;
    global $smarty;
    
    $this->getBasketData ();
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/basket.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/basket.smarty'.($stop-$start);
    return $output;

  } 

  function getBasketCheckoutCredit () {
    global $_SESSION;
    global $smarty;
    
    $this->getBasketData ();
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/basket_checkout_credit.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/basket.smarty'.($stop-$start);
    return $output;

  } 

  function getBasketCheckout () {
    global $_SESSION;
    global $smarty;
    
    $this->getBasketData ();
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/basket_checkout.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/basket.smarty'.($stop-$start);
    return $output;

  } 

  function getBasketBox () {
    global $_SESSION;
    global $smarty;
    
    $this->getBasketData ();
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/bar_basket.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/bar_basket.smarty'.($stop-$start);
    return $output;

  } 

  function getBasketData () {
    global $auth;
    global $_SESSION;
    global $smarty;

    $basket=array();
    $basket_totals=array();

    if ($auth->userOn()) {
      $user_basket = new Basket();
      $basket = $user_basket->getBasketItems();       
      $basket_totals = $user_basket->getBasketTotals();       
    }
    if (count($basket) > 0) {
      $smarty->assign("basket", $basket);
      $smarty->assign("basket_totals", $basket_totals);
      if ($basket_totals['CREDITS'] > $_SESSION['_user']['CREDIT']) {
        $smarty->assign("not_enough_credits", 1);        
      } else
        $smarty->assign("not_enough_credits", 0);        
    }
  }
}

?>