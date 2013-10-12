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

class BasketItem {
  var $item_id;
  var $attributes;

  function BasketItem($item_id) { 
     $this->item_id = $item_id;
     $this->attributes = array();
  }

  function setAttributes($attributes) {
     $this->attributes = $attributes;
  }

  function getAttributes() {
     return $this->attributes;
  }

  function getAttributesValues() {
    global $db;
    global $_SESSION;

    $values= "";
    $pre = "";
    foreach($this->attributes as $attribute) {
      $values .= $pre . $attribute['VALUE_ID'];
      $pre = ",";
    }

    $attribute_values = array();
   
    if ($values != "") {
      $sql = "SELECT DISTINCT SSA.ENTRY_ID, SSA.ITEM_ID, SSA.ATTRIBUTE_ID, SSA.VALUE_ID, SAD.ITEM_NAME AS ATTR_NAME, SAVD.ITEM_NAME as VALUE, SSA.PRICE_CREDITS, SSA.PRICE_EURO
		FROM shop_stock_attributevalues SSA
			left JOIN shop_attributes_details SAD ON SSA.attribute_ID = SAD.attribute_ID  AND SAD.LANG_ID=".$_SESSION['lang_id']."
			left JOIN shop_attributes_values_details SAVD ON SSA.VALUE_ID = SAVD.VALUE_ID AND SAVD.LANG_ID=".$_SESSION['lang_id']."
	   WHERE SSA.ITEM_ID = ".$this->item_id."
		AND SSA.VALUE_ID in (".$values.")
	   ORDER BY SAD.ITEM_NAME, SAVD.ITEM_NAME";
      $db->query($sql);
      while ($row = $db->nextRow()) {
        $attribute_values[] = $row;
      }
    }
    return $attribute_values;
  }

  function getUniqueId() {
     $id = $this->item_id;
     $pre = "_";     
     foreach ($this->attributes as $attribute)
       $id .= $pre . $attribute['VALUE_ID'];
     return $id;
  }
}

?>