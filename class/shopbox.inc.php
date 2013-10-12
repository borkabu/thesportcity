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

class ShopBox extends Box{

  function ShopBox($langs, $lang) {
    parent::Box($langs, $lang);
  }

  function getShopStockItemBox ($box=false) {
    global $tpl;
    global $db;
    global $_SESSION;
    global $langs;
    
    // content
    $this->data['SHOP'][0] = $this->getShopStockItemData(); 
    $basketbox = new BasketBox($langs, $_SESSION['_lang']);
    $this->data['SHOP'][0]['BASKET'] = $basketbox->getBasketBox();
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/bar_shop_stock_item.tpl.html');
    $tpl->addData($this->data);
    return $tpl->parse();
  } 

  function getShopBasketBox ($box=false) {
    global $tpl;
    global $db;
    
    // content
    $this->data['BASKET'][0] = $this->getShopBasketData(); 
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/bar_shop_basket.tpl.html');
    $tpl->addData($this->data);
    return $tpl->parse();
  } 

  function getShopStock ($page=1,$perpage=PAGE_SIZE) {
    global $tpl;
    global $db;
    global $_SESSION;
    
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/shop_stock.tpl.html');
    $this->data['SHOP'][0] = $this->getShopStockData($page, $perpage);
    $this->rows = $this->data['SHOP'][0]['_ROWS'];	
//print_r($this->data['NEWS']);
    $tpl->addData($this->data);
    return $tpl->parse();
  } 

  function getShopOrders ($page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    global $_SESSION;

    $orders = $this->getOrdersData ();
    $smarty->assign("orders", $orders);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/shop_user_orders.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/shop_user_orders.smarty'.($stop-$start);
    return $output;
    
    // content
  } 

  function getIncompleteShopOrders ($page=1,$perpage=PAGE_SIZE) {
    global $smarty;
    global $db;
    global $_SESSION;

    $orders = $this->getIncompleteOrdersData ();
    $smarty->assign("orders", $orders);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/shop_orders.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/shop_orders.smarty'.($stop-$start);
    return $output;
    
    // content
  } 

  function getShopOrderItem ($order_id, $mode = 0) {
    global $smarty;
    global $db;
    global $_SESSION;

    $order_item = new Order($order_id, $mode);
    $order = $order_item->getOrderDetails();
    $smarty->assign("order", $order);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/shop_user_order.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/shop_user_order.smarty'.($stop-$start);
    return $output;
    
    // content
  } 

  function getIncompleteShopOrderItem ($order_id, $mode = 0) {
    global $smarty;
    global $db;
    global $_SESSION;

    $order_item = new Order($order_id, $mode);
    $order = $order_item->getOrderDetails();
    $smarty->assign("order", $order);
    $start = getmicrotime();
    $output = $smarty->fetch('smarty_tpl/shop_order.smarty');    
    $stop = getmicrotime();
    if (isset($_GET['debugphp']))
      echo 'smarty_tpl/shop_user_order.smarty'.($stop-$start);
    return $output;
    
    // content
  } 


  function getShopStockItem ($item_id, $basket_item_id = '') {
    global $tpl;
    global $db;
    global $_SESSION;
    
    // content
    $tpl->setCacheLevel(TPL_CACHE_NOTHING);    	
    $tpl->setTemplateFile('tpl/shop_stock_item.tpl.html');
    $data = $this->getShopStockItemData($item_id, $basket_item_id);
    if ($data != '') {
      $this->data['SHOP'][0] = $data;
      $tpl->addData($this->data);
      return $tpl->parse();
    }
    else {
      return '';
    }  
  } 

  function getShopStockItemData($item_id='', $basket_item_id = '') {
    global $db;
    global $_SESSION;
    global $auth;
    global $langs;
    global $html_page;

    $data='';

    $where_item='';
    $order = '';
    if ($item_id == '') {
      $item_id=0;
      $order = " ORDER BY RAND() LIMIT 1";
    } else {
      $where_item = " AND N.ITEM_ID  = ".$item_id;
    }

    if (is_numeric($item_id)) {

      $sql = "SELECT 
              N.ITEM_ID, ND.ITEM_NAME, ND.DESCR, N.PIC_LOCATION,
	      N.PRICE_EURO, N.PRICE_CREDITS
            FROM shop_stock N, shop_stock_details ND
            WHERE ND.ITEM_ID=N.ITEM_ID 
		".$where_item."
		AND ND.LANG_ID=".$_SESSION['lang_id']." 
		AND N.PUBLISH= 'Y' ".$order;
      $db->query($sql);
      if ($row = $db->nextRow()) {
        $data['ITEM'][0] = $row; 
        if ($auth->userOn())          
	  $data['ITEM'][0]['BASKET_ADD'][0]['ITEM_ID'] = $row['ITEM_ID'];
        $html_page->page_title = $row['ITEM_NAME'];

        // get attributes
        $sql = "SELECT DISTINCT SSA.ATTRIBUTE_ID, SAD.ITEM_NAME
		FROM shop_stock_attributevalues  SSA
			left JOIN shop_attributes_details SAD ON SSA.attribute_ID = SAD.attribute_ID  AND SAD.LANG_ID=".$_SESSION['lang_id']."
			WHERE SSA.ITEM_ID = ".$item_id;
        $db->query($sql);
        $attributes = array();      

        while ($row = $db->nextRow()) {
          $attributes[] = $row;
        }

        $values = '';
        if ($basket_item_id != '') {
          $values = explode("_", $basket_item_id);
          $newvalues = array_shift($values);
        }

        foreach ($attributes as $attribute) {
           $data['ITEM'][0]['ATTRIBUTES'][$attribute['ATTRIBUTE_ID']]['ATTRIBUTE'] = $this->getShopStockItemAttributesValues("attr".$attribute['ATTRIBUTE_ID'], $item_id, $attribute['ATTRIBUTE_ID'], $values);
	   $data['ITEM'][0]['ATTRIBUTES'][$attribute['ATTRIBUTE_ID']]['ITEM_NAME'] = $attribute['ITEM_NAME'];
        }
      }
      $db->free();

    }      

    return $data;
  }

  function getShopStockItemAttributesValues($name, $item_id, $attribute_id, $sel = '') {
    global $db;
    global $frm;
    global $langs;
  
    // read from db
      $sopt = array();
    $sql = "SELECT DISTINCT SSA.ENTRY_ID, SSA.ITEM_ID, SSA.ATTRIBUTE_ID, SSA.VALUE_ID, SAD.ITEM_NAME AS ATTR_NAME, SAVD.ITEM_NAME as VALUE, SSA.PRICE_CREDITS, SSA.PRICE_EURO, SSA.DEFAULT_VALUE
		FROM shop_stock_attributevalues SSA
			left JOIN shop_attributes_details SAD ON SSA.attribute_ID = SAD.attribute_ID  AND SAD.LANG_ID=".$_SESSION['lang_id']."
			left JOIN shop_attributes_values_details SAVD ON SSA.VALUE_ID = SAVD.VALUE_ID AND SAVD.LANG_ID=".$_SESSION['lang_id']."
	   WHERE SSA.ITEM_ID = ".$item_id."
		and SSA.ATTRIBUTE_ID = ".$attribute_id."
	   ORDER BY SAD.ITEM_NAME, SAVD.ITEM_NAME";
    $db->query($sql);
    $db->setPage();
    while ($row = $db->nextRow()) {     
      $sopt[$row['ATTRIBUTE_ID']."_".$row['VALUE_ID']] = $row['VALUE']." (+".$row['PRICE_CREDITS']." ".$langs['LANG_CREDITS_U']."; +".$row['PRICE_EURO']." &euro;)";
      if ($sel == '' && $row['DEFAULT_VALUE'] == 'Y')  
        $sel = $row['ATTRIBUTE_ID']."_".$row['VALUE_ID'];
      else if (is_array($sel)) {
         if (array_search($row['VALUE_ID'], $sel) !== false)
           $sel = $row['ATTRIBUTE_ID']."_".$row['VALUE_ID'];              
      }
    }

    $db->free();
    $spara['options'] = $sopt;
    $spara['class'] = 'input';
    if (empty($sel)) {
      return $frm->getInputWithValue(FORM_INPUT_SELECT, $name, $spara);
    }
    else {
      return $frm->getInput(FORM_INPUT_SELECT, $name, $sel, $spara, $sel);
    }
  
  }
  
  function getShopStockData ($page=1,$perpage=PAGE_SIZE){
    global $db;
    global $_SESSION;
  
    $where = "N.PUBLISH='Y'";
  
    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;
      
    $sql_count = "SELECT COUNT(N.ITEM_ID) ROWS
                   FROM shop_stock N, shop_stock_details ND 
                   WHERE ND.ITEM_ID=N.ITEM_ID 
			AND ND.LANG_ID=".$_SESSION['lang_id']." AND ".$where; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
     }

    $sql = "SELECT 
              N.ITEM_ID, ND.ITEM_NAME, ND.DESCR, N.PIC_LOCATION,
	      N.PRICE_EURO, N.PRICE_CREDITS
            FROM shop_stock N, shop_stock_details ND
            WHERE ND.ITEM_ID=N.ITEM_ID AND ND.LANG_ID=".$_SESSION['lang_id']." AND
              ".$where."
	    GROUP BY N.ITEM_ID 
            ORDER BY N.PRICE_CREDITS, N.ITEM_ID DESC
            ".$limitclause;
    $db->query($sql);

    $c = 0;
    $ids = '';
    $pre  = '';
    while ($row = $db->nextRow()) {
      $data['ITEM'][$c] = $row; 
      $data['ITEM'][$c]['LANG'] = $_SESSION['_lang']; 
      $c++;
    }
    $db->free();

    $data['_ROWS'] = $count;
      // no records?
    if ($c == 0) {
      $data['NORECORDS'][0]['X'] = 1;
    }
    
    $db->free();
    return $data;
  }


  function getShopBasketData ($page=1,$perpage=PAGE_SIZE){
    global $db;
    global $_SESSION;



    return $data;
  }

  function getOrdersData ($page=1,$perpage=PAGE_SIZE){
    global $db;
    global $_SESSION;
    global $auth;
    global $delivery_price_credits;
    global $delivery_price_euro;
 
    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;
      
    $sql_count = "SELECT COUNT(ORDER_ID) ROWS
                   FROM shop_order 
                   WHERE user_id=".$auth->getUserId(); 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
    }
    $zone = $auth->getZone();

    $sql = "SELECT 
              N.ORDER_ID, count(ND.ITEM_ID) ITEMS, N.PRICE_TYPE, 
		SUM(ND.QUANTITY*ND.ITEM_PRICE_CREDITS) TOTAL_CREDITS, 
		SUM(ND.QUANTITY*ND.ITEM_PRICE_EURO) TOTAL_EURO,
		N.ORDER_DATE, N.STATUS
            FROM shop_order N, shop_order_items ND
            WHERE ND.ORDER_ID=N.ORDER_ID AND N.USER_ID=".$auth->getUserId()."
	    GROUP BY N.ORDER_ID 
            ORDER BY N.ORDER_ID DESC
            ".$limitclause;
    $db->query($sql);
    $c = 0;
    $ids = '';
    $pre  = '';
    $orders = array();
    while ($row = $db->nextRow()) {
      $order = $row; 
      $order['DELIVERY_CREDITS'] = $delivery_price_credits[$zone];
      $order['DELIVERY_EURO'] = $delivery_price_euro[$zone];
      $orders[] = $order;
      $c++;
    }
    
    $this->rows = $count;
    $db->free();
    return $orders;
  }

  function getIncompleteOrdersData ($page=1,$perpage=PAGE_SIZE){
    global $db;
    global $_SESSION;
    global $auth;
    global $delivery_price_credits;
    global $delivery_price_euro;
 
    $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;
      
    $sql_count = "SELECT COUNT(ORDER_ID) ROWS
                   FROM shop_order 
                   WHERE 1=1"; 
    $db->query($sql_count);
    $count = 0;
    while ($row = $db->nextRow()) {
      $count = $row['ROWS'];
    }

    $sql = "SELECT 
              N.ORDER_ID, count(ND.ITEM_ID) ITEMS, N.PRICE_TYPE, 
		SUM(ND.QUANTITY*ND.ITEM_PRICE_CREDITS) TOTAL_CREDITS, 
		SUM(ND.QUANTITY*ND.ITEM_PRICE_EURO) TOTAL_EURO,
		N.ORDER_DATE, N.STATUS, U.USER_NAME, C.ZONE
            FROM users U, shop_order N, shop_order_items ND,
		 countries C 
            WHERE ND.ORDER_ID=N.ORDER_ID 
		AND N.USER_ID=U.USER_ID
		AND C.id=U.COUNTRY
	    GROUP BY N.ORDER_ID 
            ORDER BY N.ORDER_ID DESC
            ".$limitclause;
    $db->query($sql);
    $c = 0;
    $ids = '';
    $pre  = '';
    $orders = array();
    while ($row = $db->nextRow()) {
      $order = $row; 
      $order['DELIVERY_CREDITS'] = $delivery_price_credits[$row['ZONE']];
      $order['DELIVERY_EURO'] = $delivery_price_euro[$row['ZONE']];
      $orders[] = $order;
      $c++;
    }
    
    $this->rows = $count;
    $db->free();
    return $orders;
  }

}   
?>