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

class Basket {
  var $basket_items;
  var $basket_totals;
  var $order_id;

  function Basket() { 
     $this->basket_items = array();
     $this->basket_totals = array();
     $this->getBasket();
  }

  function addBasketItem($basket_item, $quantity) {
    global $_SESSION;
    global $auth;

    if ($auth->userOn()) {
      if (isset($_SESSION['_user']['basket'][$basket_item->getUniqueId()]))
        $_SESSION['_user']['basket'][$basket_item->getUniqueId()]['QUANTITY'] += $quantity;
      else {
	$_SESSION['_user']['basket'][$basket_item->getUniqueId()]['QUANTITY'] = $quantity;
        $_SESSION['_user']['basket'][$basket_item->getUniqueId()]['ITEM'] = serialize($basket_item);
      }
    }
  }

  function removeBasketItem($item_id, $quantity) {
    global $_SESSION;
    global $auth;

    if ($auth->userOn()) {
      if (isset($_SESSION['_user']['basket'][$item_id]))
        $_SESSION['_user']['basket'][$item_id] -= $quantity;
    }
  }

  function updateBasketItem($item_id, $quantity) {
    global $_SESSION;
    global $auth;

    if ($auth->userOn()) {
      if (isset($_SESSION['_user']['basket'][$item_id]) && $quantity > 0)
        $_SESSION['_user']['basket'][$item_id]['QUANTITY'] = $quantity;
    }
  }

  function deleteBasketItem($item_id) {
    global $_SESSION;
    global $auth;

    if ($auth->userOn()) {
      if (isset($_SESSION['_user']['basket'][$item_id]))
        unset($_SESSION['_user']['basket'][$item_id]);
    }
  }
  
  function emptyBasket() {
    global $_SESSION;
    global $auth;

    if ($auth->userOn())
      unset($_SESSION['_user']['basket']);

  }

  function getBasketItems() {
     return $this->basket_items;
  }

  function getBasketTotals() {
     return $this->basket_totals;
  }

  function getBasket() {
    global $_SESSION;
    global $auth;
    global $db;
    global $delivery_price_credits;
    global $delivery_price_euro;

    $this->basket_totals = array();
    if ($auth->userOn() && isset($_SESSION['_user']['basket'])) {
      $zone = $auth->getZone();
      $items = "";
      $pre = "";
      reset($_SESSION['_user']['basket']);
      while (list($key, $val) = each($_SESSION['_user']['basket'])) {
        $basket_item = unserialize($val['ITEM']);

        $sql = "SELECT * 
		FROM shop_stock SS
			left join shop_stock_details SSD on SSD.ITEM_ID=SS.ITEM_ID AND SSD.LANG_ID=".$_SESSION["lang_id"]."
		WHERE SS.ITEM_ID in (".$basket_item->item_id.")";
        $db->query($sql);
        if ($row = $db->nextRow()) {
          $row['BASKET_ITEM_ID'] = $basket_item->getUniqueId();
	  $attribute_values = $basket_item->getAttributesValues();
          $row['ATTRIBUTES'] = $attribute_values;
          foreach ($attribute_values as $attribute_value) {
	    $row['PRICE_CREDITS'] += $attribute_value['PRICE_CREDITS'];
	    $row['PRICE_EURO'] += $attribute_value['PRICE_EURO'];
	  }
          $row['QUANTITY'] = $val['QUANTITY'];
          if (isset($this->basket_totals['CREDITS']))
	    $this->basket_totals['CREDITS'] += $row['PRICE_CREDITS']*$row['QUANTITY'];
          else $this->basket_totals['CREDITS'] = $row['PRICE_CREDITS']*$row['QUANTITY'];
          if (isset($this->basket_totals['EURO']))
	    $this->basket_totals['EURO'] += $row['PRICE_EURO']*$row['QUANTITY'];
	  else $this->basket_totals['EURO'] = $row['PRICE_EURO']*$row['QUANTITY'];
          $this->basket_items[] = $row;
        }
      }

      if (count($this->basket_items) > 0) {
        $this->basket_totals['DELIVERY_CREDITS'] = $delivery_price_credits[$zone];
        $this->basket_totals['CREDITS'] += $this->basket_totals['DELIVERY_CREDITS'];
        $this->basket_totals['DELIVERY_EURO'] = $delivery_price_euro[$zone];;
        $this->basket_totals['EURO'] += $this->basket_totals['DELIVERY_EURO'];
      }        
    }
  }

  function checkoutBasket() {
    global $_SESSION;
    global $auth;
    global $db;

    if ($auth->userOn() && isset($_SESSION['_user']['basket'])) {
      $db->query("start transaction");
      // create order
      $s_fields = array("note");
      $i_fields = "";
      $d_fields = "";
      $c_fields = "";
      $sdata=buildSaveData($s_fields,$i_fields,$d_fields,$c_fields,$_POST);
      $sdata['USER_ID'] = $auth->getUserId();
      $sdata['ORDER_DATE'] = "NOW()";
      $sdata['PRICE_TYPE'] = 0;
      $sdata['TOTAL_CREDITS'] = $this->basket_totals['CREDITS'];
      $sdata['TOTAL_EURO'] = $this->basket_totals['EURO'];
      $db->insert("shop_order", $sdata);
      $order_id= $db->id();
      $this->order_id = $order_id;
      foreach ($this->basket_items as $basket_item) {
        unset($sdata);
        $sdata['ORDER_ID'] = $order_id;
        $sdata['ITEM_ID'] = $basket_item['ITEM_ID'];
        $sdata['BASKET_ITEM_ID'] = "'".$basket_item['BASKET_ITEM_ID']."'";
        $sdata['QUANTITY'] = $basket_item['QUANTITY'];
        $sdata['ITEM_PRICE_CREDITS'] = $basket_item['PRICE_CREDITS'];
        $sdata['ITEM_PRICE_EURO'] = $basket_item['PRICE_EURO'];
        $db->insert("shop_order_items", $sdata);
  	$attribute_values = $basket_item['ATTRIBUTES'];
        foreach ($attribute_values as $attribute_value) {
          unset($tdata);
          $tdata['ORDER_ID'] = $order_id;
          $tdata['ITEM_ID'] = $basket_item['ITEM_ID'];
          $tdata['ATTRIBUTE_ID'] = $attribute_value['ATTRIBUTE_ID'];
          $tdata['BASKET_ITEM_ID'] = "'".$basket_item['BASKET_ITEM_ID']."'";
          $tdata['VALUE_ID'] = $attribute_value['VALUE_ID'];
          $db->insert("shop_order_items_attributes", $tdata);
	}
      
      }
      // remove credits

      $this->emptyBasket();    
      $db->query("commit");
      return true;
    }
    else return false;
  }

}

?>