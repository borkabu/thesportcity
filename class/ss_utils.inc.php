<?php

class SS_Utils {

   function SS_Utils() {
   }
   
   function setLocation($location) {
     global $db;
     global $auth;

     $sdata['LOCATION'] = $location;
     $db->update("ss_users", $sdata, "USER_ID=".$auth->getUserId());   
   }

   function clearOldBattles() {
     global $db;
     global $battle_timeout;

     $db->select("ss_battle", "BATTLE_ID", "(STATUS=1 OR STATUS=0)
                           AND UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(START_DATE) >= ".$battle_timeout."
                           AND END_DATE IS NULL ");
     while ($row = $db->nextRow()) {
       $db->delete("ss_battle_status", "BATTLE_ID=".$row['BATTLE_ID']);   
     }
     $db->delete("ss_battle", "(STATUS=1 OR STATUS=0)
                           AND UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(START_DATE) >= ".$battle_timeout."
                           AND END_DATE IS NULL ");

   }

   function getLog($user_id, $types='') {
    global $log_events_descr_success;
    global $log_events_descr_failure;
    global $db;
    global $_SESSION;
    
    $data='';
      $sql="SELECT SL.LANG_ID FROM ss_langs SL
                WHERE SL.LANG_ABR = '".$_SESSION["_lang"]."'";
      $db->query($sql);
      $row = $db->nextRow();
      $lang_id = $row['LANG_ID'];
    $where_types = '';
    if ($types != '') {
      $pre  = "";
      for ($i= 0; $i < count($types); $i++) {
	$where_types .= $pre.$types[$i];
	$pre = ",";
      }
      $where_types = " AND SUL.EVENT_TYPE IN (".$where_types.")";
    }
   
    $sql="SELECT DISTINCT SUL.*, ST1.ITEM_NAME, ST2.ATTR_NAME
            FROM ss_langs SL, ss_users_log SUL
                 LEFT JOIN ss_items SI ON SI.ITEM_ID=SUL.ITEM_ID
                 LEFT JOIN ss_items_details ST1 ON ST1.ITEM_ID=SUL.ITEM_ID 
                                              AND ST1.LANG_ID=".$lang_id."
                 LEFT JOIN ss_skills SD ON SD.ATTR_ID=SUL.SKILL
                 LEFT JOIN ss_skills_details ST2 ON ST2.ATTR_ID=SD.ATTR_ID 
                                              AND ST2.LANG_ID=".$lang_id."   
           WHERE SUL.USER_ID=".$user_id."
                 ".$where_types."
           ORDER BY SUL.ENTRY_ID DESC
           LIMIT 10";

    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
      if ($row['STATUS'] == 0)
        $log_entry = $log_events_descr_success[$row['EVENT_TYPE']];
      else $log_entry = $log_events_descr_failure[$row['EVENT_TYPE']];
      $log_entry = str_replace("%i", $row['ITEM_NAME'], $log_entry);
      $log_entry = str_replace("%l", $row['LEVEL'], $log_entry);
      $log_entry = str_replace("%s", $row['ATTR_NAME'], $log_entry);
      $log_entry = str_replace("%q", $row['QUANTITY'], $log_entry);
      $log_entry = str_replace("%m", $row['MONEY'], $log_entry);
      $data['ITEMS'][$c]['LOG_ENTRY'] = $log_entry;
      $c++;
    }
   //print_r($data);
    return $data;
   } 

   function getStash($user_id) {
     global $db;
     global $_SESSION;

     for ($i=0; $i < 3; $i++) {
       for ($j=0; $j < 3; $j++) {
        $data[$i]['COLS'][$j]['EMPTY'][0] = 1;
       }
     }

     $sql = "SELECT SUI.GENERAL_ID, SU.ITEM_ID, ST.ITEM_NAME, 
                      SUI.SLOT_USED, SU.EQUIP_POINT, SU.ITEM_TYPE, SU.PIC_LOCATION, SU.PRICE_SELL
           FROM ss_users_items SUI, ss_item_types SIT, ss_items SU
                LEFT JOIN ss_items_details ST ON ST.ITEM_ID=SU.ITEM_ID 
                                              AND ST.LANG_ID=".$_SESSION['lang_id']."
          WHERE SUI.USER_ID=".$user_id." 
                AND SUI.EQUIPED=0
                AND SU.ITEM_ID=SUI.ITEM_ID
		AND SIT.ITEM_TYPE_ID=SU.ITEM_TYPE
         ORDER BY SU.ITEM_ID";

       $db->query($sql);
       $c=0;
       while ($row = $db->nextRow()) {
         $data[$c/3]['COLS'][$c%3]['EQUIP'][0] = $row;
         $c++;
       }
       return $data;
   }


   function getInventory($user_id, $equiped, $effect_type = '') {
     global $db;
     global $_SESSION;

     $data='';
     $where_effect = '';
     if (isset($effect_type))
       $where_effect = " AND SIT.EFFECT_TYPE=".$effect_type;

     $sql = "SELECT SUI.GENERAL_ID, SU.ITEM_ID, ST.ITEM_NAME, 
                      SUI.SLOT_USED, SU.EQUIP_POINT, SU.ITEM_TYPE
           FROM ss_users_items SUI, ss_item_types SIT, ss_items SU
                LEFT JOIN ss_items_details ST ON ST.ITEM_ID=SU.ITEM_ID 
                                              AND ST.LANG_ID=".$_SESSION['lang_id']."
          WHERE SUI.USER_ID=".$user_id." 
                AND SUI.EQUIPED=".$equiped."
                AND SU.ITEM_ID=SUI.ITEM_ID
		AND SIT.ITEM_TYPE_ID=SU.ITEM_TYPE
		".$where_effect."
         ORDER BY SU.ITEM_ID";

       $db->query($sql);
       $c=0;
       while ($row = $db->nextRow()) {
         $data[$row['ITEM_TYPE']]['COLS'][$c] = $row;
         $data[$row['ITEM_TYPE']]['COLS'][$c]['USE'][0] = $row;
         $c++;

         $c++;
       }
       return $data;
   }
  
   function getEquippedInventory($user_id) {
     global $db;
     global $_SESSION;
     global $slots_equip;

     $data='';
     $sql = "SELECT SUI.GENERAL_ID, SU.ITEM_ID, ST.ITEM_NAME, 
                SUI.SLOT_USED, SU.EQUIP_POINT, SU.PIC_LOCATION
           FROM ss_users_items SUI, ss_items SU
                LEFT JOIN ss_items_details ST ON ST.ITEM_ID=SU.ITEM_ID 
                                              AND ST.LANG_ID=".$_SESSION['lang_id']."
          WHERE SUI.USER_ID=".$user_id." 
                AND SUI.EQUIPED=1 
                AND SU.ITEM_ID=SUI.ITEM_ID
         ORDER BY SU.ITEM_ID";
     $db->query($sql);
     $c=0;
     $slots_used='';
     while ($row = $db->nextRow()) {
      if ($slots_equip[$row['EQUIP_POINT']] > 1) {
        if (isset($slots_used[$row['EQUIP_POINT']]))
          $slots_used[$row['EQUIP_POINT']]++;
        else $slots_used[$row['EQUIP_POINT']] = 1;
        $data['SLOT'.$row['EQUIP_POINT'].$slots_used[$row['EQUIP_POINT']]][0] = $row; 
      } else {
        $data['SLOT'.$row['EQUIP_POINT']][0] = $row;
      }
      $c++;
     }
     return $data;
   }

   function getSkills($user_id) {
     global $db;
     global $_SESSION;
     global $sports;

     $data = '';
     $sql = "SELECT *, ST.ATTR_NAME
              FROM ss_users_da SUD, ss_skills SD
                     LEFT JOIN ss_skills_details ST ON ST.ATTR_ID=SD.ATTR_ID 
                                            AND ST.LANG_ID=".$_SESSION['lang_id']."
             WHERE SD.ATTR_ID = SUD.ATTR_ID
                   AND SUD.USER_ID=".$user_id;
   
     $db->query($sql);
     $c = 0;
     while ($row = $db->nextRow()) {
       $data['ITEMS'][$c] = $row;  
       $data['ITEMS'][$c]['SPORT'] = $sports[$row['SPORT_ID']];
       $c++;
     }

     if ($c==0) {
       $data['NO_SKILLS'][0]['X'] = 1;
     }

     return $data; 
   }

}
   
?>