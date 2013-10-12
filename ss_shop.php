<?php
/*
===============================================================================
thanks.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows thank you message

TABLES USED: 
  - none

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');

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
include('class/inputs.inc.php');
// extras
include('class/ss_conf.inc.php');
include('ss_include.php');
// --- build content data -----------------------------------------------------
//$db->showquery=true;

 if (isset($_POST["buy"]) && isset($_POST["item_id"]) && $_POST["quantity"] > 0) {
   $db->select("ss_items", "*", "ITEM_ID=".$_POST["item_id"]);
   if ($row = $db->nextRow()) {
     if ($_SESSION["_user"]['SS'][0]['MONEY'] >= $row['PRICE']*$_POST["quantity"]) {
       for ($i=0; $i< $_POST["quantity"]; $i++) {
         unset($sdata);
         $sdata['ITEM_ID'] = $_POST["item_id"];
         $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
         $db->insert("ss_users_items", $sdata);
       }
       unset($sdata);
       $sdata['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'] - $row['PRICE']*$_POST["quantity"];
       $_SESSION["_user"]['SS'][0]['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'] - $row['PRICE']*$_POST["quantity"];
       $db->update("ss_users", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']);   
// log operation success
       unset($sdata);
       $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
       $sdata['EVENT_TYPE'] = 1;
       $sdata['ITEM_ID'] = $_POST["item_id"];
       $sdata['QUANTITY'] = $_POST["quantity"];
       $sdata['STATUS'] = 0;
       $sdata['MONEY'] = $row['PRICE']*$_POST["quantity"];
       $db->insert("ss_users_log", $sdata);   
     } else {
// log operation failure
       unset($sdata);
       $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
       $sdata['EVENT_TYPE'] = 1;
       $sdata['ITEM_ID'] = $_POST["item_id"];
       $sdata['QUANTITY'] = $_POST["quantity"];
       $sdata['STATUS'] = 1;
       $sdata['MONEY'] = $row['PRICE']*$_POST["quantity"];
       $db->insert("ss_users_log", $sdata);   
     }     
   }
//exit;
   header("location: ".$_SERVER["REQUEST_URI"]);
 } 

// $db->showquery = true;
//echo $sell.$id;
 if (isset($_POST["sell"]) && isset($_POST["id"])) {
   $sql = "SELECT SU.ITEM_ID, SU.PRICE_SELL 
           FROM ss_users_items SUI, ss_items SU
          WHERE SUI.USER_ID=".$_SESSION["_user"]['USER_ID']." 
                AND SUI.EQUIPED=0 
                AND SU.ITEM_ID=SUI.ITEM_ID
                AND SUI.GENERAL_ID=".$_POST["id"]." 
         ORDER BY SU.ITEM_ID";

   $db->query($sql);
   if ($row = $db->nextRow()) {
       unset($sdata);
       $db->delete("ss_users_items", "USER_ID=".$_SESSION["_user"]['USER_ID']." AND GENERAL_ID=".$_POST["id"]);
       unset($sdata);
       $sdata['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'] + $row['PRICE_SELL'];
       $_SESSION["_user"]['SS'][0]['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'] + $row['PRICE_SELL'];
       $db->update("ss_users", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']);   
       unset($sdata);
       $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
       $sdata['EVENT_TYPE'] = 2;
       $sdata['EVENT_DATE'] = 'SYSDATE()';
       $sdata['ITEM_ID'] = $row['ITEM_ID'];
       $sdata['QUANTITY'] = 1;
       $sdata['STATUS'] = 0;
       $sdata['MONEY'] = $row['PRICE_SELL'];
       $db->insert("ss_users_log", $sdata);   

// log operation success
   } else {
       unset($sdata);
       $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
       $sdata['EVENT_TYPE'] = 2;
       $sdata['QUANTITY'] = 1;
       $sdata['STATUS'] = 1;
       $sdata['MONEY'] = $row['PRICE_SELL'];
       $db->insert("ss_users_log", $sdata);   
   }
   header("location: ".$_SERVER["REQUEST_URI"]);
 } 


 if (isset($_GET["type"])) {
 $sql = "SELECT SU.*, ST.ITEM_NAME, ST.DESCR 
           FROM ss_items SU
                LEFT JOIN ss_items_details ST ON ST.ITEM_ID=SU.ITEM_ID 
                                              AND ST.LANG_ID=".$_SESSION['lang_id']."
          WHERE SU.ITEM_TYPE=".$_GET["type"]."
         ORDER BY SU.ITEM_ID";

   $db->query($sql);
   $c = 0;
   while ($row = $db->nextRow()) {
     $data['STUFF'][0]['ITEMS'][$c] = $row;
     if ($row['PRICE'] > $_SESSION["_user"]['SS'][0]['MONEY']) {
       $data['STUFF'][0]['ITEMS'][$c]['NO_MONEY'][0]['X'] = 1;
     } else {
       $data['STUFF'][0]['ITEMS'][$c]['BUY'][0]['ITEM_ID'] = $row['ITEM_ID'];
     }
     $c++;
   }
 }

 $content = $menu->getSubmenu('ss_outside.php');
 $data['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'];
 $data['STASH'][0]['ROWS'] = $utils->getStash($auth->getUserID());
 $utils->setLocation(SS_SHOP);
 $data['LOG'][0] = $utils->getLog($auth->getUserID(), array(1,2));
 
  $sql = "SELECT C.ITEM_TYPE_ID as TYPE, CD.ITEM_TYPE_NAME AS VALUE
        FROM ss_item_types  C 
		left JOIN ss_item_types_details CD ON C.ITEM_TYPE_ID = CD.ITEM_TYPE_ID  AND CD.LANG_ID=".$_SESSION['lang_id']."
        WHERE 1=1
	GROUP BY C.ITEM_TYPE_ID
        ORDER BY VALUE";
  $data['TYPE'] = $menu->getMenuFromQuery($sql, 'type');

//print_r($data);
$tpl->setTemplateFile('tpl/ss_shop.tpl.html');
$tpl->addData($data);
$content .= $tpl->parse();
// ----------------------------------------------------------------------------

// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot.inc.php');

// close connections
include('class/db_close.inc.php');
?>