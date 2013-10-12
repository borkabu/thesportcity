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

class Order {
  var $order_id;
  var $order_details;
  var $user_id;
  var $mode;

  function Order($order_id, $mode = 0) { 
     $this->order_id = $order_id;
     $this->mode = $mode;
     $this->getOrderItemData ();
  }

  function getOrderDetails() {
     return $this->order_details;
  }

  function getOrderItemData (){
    global $db;
    global $_SESSION;
    global $auth;
    global $smarty;
    global $delivery_price_credits;
    global $delivery_price_euro;

    $user_filter = "";
    if ($this->mode == 0) // filter by user
      $user_filter = " AND N.USER_ID=".$auth->getUserId();
    $sql = "SELECT 
              N.ORDER_ID, N.USER_ID, count(ND.ITEM_ID) ITEMS, N.PRICE_TYPE, 
		ROUND(SUM(ND.QUANTITY*ND.ITEM_PRICE_CREDITS), 2) TOTAL_CREDITS, 
		ROUND(SUM(ND.QUANTITY*ND.ITEM_PRICE_EURO), 2) TOTAL_EURO,
		N.ORDER_DATE, N.NOTE, N.STATUS, U.USER_NAME, C.ZONE
            FROM users U, shop_order N, shop_order_items ND, countries C
            WHERE ND.ORDER_ID=N.ORDER_ID 
		".$user_filter."
                  AND N.ORDER_ID=".$this->order_id."
		  AND U.USER_ID=N.USER_ID
		  AND C.ID=U.COUNTRY
	    GROUP BY N.ORDER_ID 
            ORDER BY N.ORDER_ID DESC";
    $db->query($sql);
    $order = $db->nextRow();
    $this->user_id = $order['USER_ID'];
    
    $sql = "SELECT 
              N.ORDER_ID, ND.BASKET_ITEM_ID, ND.ITEM_ID, ND.QUANTITY, ND.ITEM_PRICE_EURO,
	      ROUND(SUM(ND.QUANTITY*ND.ITEM_PRICE_CREDITS), 2) TOTAL_CREDITS, 
	      ROUND(SUM(ND.QUANTITY*ND.ITEM_PRICE_EURO), 2) TOTAL_EURO,
	      N.ORDER_DATE, SSD.ITEM_NAME, N.PRICE_TYPE, SS.PIC_LOCATION, U.USER_NAME
            FROM users U, shop_order N, shop_order_items ND, shop_stock SS
		left join shop_stock_details SSD
			ON SS.ITEM_ID=SSD.ITEM_ID AND SSD.LANG_ID=".$_SESSION['lang_id']."
            WHERE ND.ORDER_ID=N.ORDER_ID 
		  ".$user_filter."
                  AND N.ORDER_ID=".$this->order_id."
		  AND U.USER_ID=N.USER_ID
                  AND ND.ITEM_ID=SS.ITEM_ID
            GROUP BY SSD.ITEM_ID, ND.BASKET_ITEM_ID
            ORDER BY ND.ITEM_ID DESC";
    $db->query($sql);
    $c = 0;
    $ids = '';
    $pre  = '';
    while ($row = $db->nextRow()) {
      $order_item = $row; 
      $order['ORDER_ITEMS'][] = $order_item;
      $c++;
    }
    $db->free();

    foreach ($order['ORDER_ITEMS'] as &$order_item) {
       //get attributes
       $sql = "SELECT DISTINCT SOIA.ITEM_ID, SOIA.ATTRIBUTE_ID, SOIA.VALUE_ID, SAD.ITEM_NAME AS ATTR_NAME, SAVD.ITEM_NAME as VALUE
		FROM shop_order_items_attributes SOIA
			left JOIN shop_attributes_details SAD ON SOIA.attribute_ID = SAD.attribute_ID  AND SAD.LANG_ID=".$_SESSION['lang_id']."
			left JOIN shop_attributes_values_details SAVD ON SOIA.VALUE_ID = SAVD.VALUE_ID AND SAVD.LANG_ID=".$_SESSION['lang_id']."
	   WHERE SOIA.ITEM_ID = ".$order_item['ITEM_ID']."
		AND SOIA.BASKET_ITEM_ID = '".$order_item['BASKET_ITEM_ID']."'
		AND SOIA.ORDER_ID = ".$order_item['ORDER_ID']."
	   ORDER BY SAD.ITEM_NAME, SAVD.ITEM_NAME";
      $db->query($sql);
      $attribute_values= array();
      while ($row = $db->nextRow()) {
        $attribute_values[] = $row;
      }
      $order_item['ATTRIBUTES'] = $attribute_values;
    }

    $order['DELIVERY_CREDITS'] = $delivery_price_credits[$order['ZONE']];
    $order['DELIVERY_EURO'] = $delivery_price_euro[$order['ZONE']];
    $this->order_details = $order;
  }

  function acceptPayment() {
    global $auth;
    global $db;
    $credits = new Credits();
    $credits->updateCredits($auth->getUserId(), -1 * ($this->order_details['TOTAL_CREDITS']+$this->order_details['DELIVERY_CREDITS']));
    $credit_log = new CreditsLog();
    $credit_log->logEvent ($auth->getUserId(), 18, $this->order_details['TOTAL_CREDITS']+$this->order_details['DELIVERY_CREDITS']);
  
    unset($sdata);
    $sdata['STATUS'] = 1;
    $db->update("shop_order", $sdata, "order_id=".$this->order_id);

    $this->sendConfirmationEmail();
    return true;
  }

  function acceptPaypalPayment() {
    global $db;
  
    unset($sdata);
    $sdata['STATUS'] = 1;
    $db->update("shop_order", $sdata, "order_id=".$this->order_id);

    $this->sendConfirmationEmail();
  }

  function sendConfirmationEmail() {
      global $auth;
      global $_SESSION;
      global $langs;
      global $conf_site_url;
      global $shopbox;

      $user = new User($this->user_id);
      $user_data = $user->getUserData();
      $edata['USER_NAME'] = $user_data['USER_NAME'];
      $edata['ORDER_ID'] = "TSC000".$this->order_id;
      $edata['ORDER'] = $shopbox->getShopOrderItem($this->order_id, $this->mode);
      $edata['ADDRESS1'] = $user_data['ADDRESS1'];
      $edata['ADDRESS2'] = $user_data['ADDRESS2'];
      $edata['TOWN'] = $user_data['TOWN'];
      $edata['POSTCODE'] = $user_data['POSTCODE'];
      $edata['COUNTRY'] = $user_data['COUNTRY_DB'][0]['COUNTRY_NAME'];
      $email = new Email($langs, $_SESSION['_lang']);
      $email->getEmailFromTemplate ('email_shop_order_confirmation', $edata) ;
      $subject = $langs['LANG_EMAIL_SHOP_ORDER_LINE_1'];

      if ($email->sendAdminHTML($subject) && $email->sendHTMLonly($user_data["EMAIL"], $subject))
        return true;
      else return false;

  }

  function cancel() {
     global $db;
     global $auth;

     $db->delete("shop_order", "ORDER_ID=".$this->order_id." AND USER_ID=".$auth->getUserId());
     $db->delete("shop_order_items", "ORDER_ID=".$this->order_id);
  }

  function markDispatched() {
     global $db;
     global $langs;
     global $shopbox;
     global $conf_site_url;

     $sdata['STATUS'] = 2;
     $db->update("shop_order", $sdata, "ORDER_ID=".$this->order_id);

      $user = new User($this->user_id);
      $user_data = $user->getUserData();
      $edata['USER_NAME'] = $user_data['USER_NAME'];
      $edata['ORDER_ID'] = "TSC000".$this->order_id;
      $edata['ORDER'] = $shopbox->getShopOrderItem($this->order_id, $this->mode);

     $email = new Email($langs, $user_data['LAST_LANG']);
     $email->getEmailFromTemplate ('email_shop_order_dispatched', $edata) ;
     $subject = $langs['LANG_EMAIL_SHOP_ORDER_DISPATCHED_LINE_1'];
     if ($email->send($user_data['EMAIL'], $subject))
       return true;
     else return false;

  }

}

?>