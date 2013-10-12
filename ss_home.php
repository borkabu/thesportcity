<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
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
 $content = $menu->getSubmenu('ss_outside.php');
$tpl->setTemplateFile('tpl/ss_home.tpl.html');
$tpl->setCacheLevel(TPL_CACHE_NOTHING);

// $db->showquery = true;
 if ($auth->userOn()) {

  $db->select("ss_users", "*", "USER_ID=".$_SESSION["_user"]['USER_ID']);
//echo $_SESSION["_user"]['USER_ID'];
  if (!$row = $db->nextRow()) {
    header("location: ss_manager.php");
    exit; 
  }
  else {
      $_SESSION["_user"]['SS'][0] = $row;
      $data['SS'][0] = $row;
      if (isset($_POST['equip']) && isset($_POST['id'])) {
        // check if item exist
        $sql = "SELECT SUI.GENERAL_ID, SU.ITEM_ID, SU.EQUIP_POINT, SU.LEVEL 
                  FROM ss_users_items SUI, ss_items SU
                 WHERE SUI.USER_ID=".$_SESSION["_user"]['USER_ID']." 
                       AND SUI.GENERAL_ID=".$_POST['id']." 
                       AND SUI.EQUIPED=0
                       AND SU.ITEM_ID=SUI.ITEM_ID";
        $db->query($sql);
        if ($row = $db->nextRow()) { 
//          check if required slot is empty
          $equip_point = $row['EQUIP_POINT'];
          $item_level = $row['LEVEL'];
          $item_id = $row['ITEM_ID'];
          $sql = "SELECT COUNT(*) SLOTS
                    FROM ss_users_items SUI
                   WHERE USER_ID=".$_SESSION["_user"]['USER_ID']." 
                         AND EQUIPED=1 
                         AND SLOT_USED=".$equip_point;
          $db->query($sql);
          if ($row = $db->nextRow()) { 
            if ($row['SLOTS'] < $slots_equip[$equip_point]) {
              // equip
              unset($sdata);
              $sdata['EQUIPED'] = 1;
              $sdata['SLOT_USED'] = $equip_point;
              $db->update("ss_users_items", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']." AND GENERAL_ID=".$_POST['id']);                 
              unset($sdata);
              $sdata['EQUIPED_LEVEL'] = $_SESSION["_user"]['SS'][0]['EQUIPED_LEVEL'] + $item_level;
              $_SESSION["_user"]['SS'][0]['EQUIPED_LEVEL'] = $_SESSION["_user"]['SS'][0]['EQUIPED_LEVEL'] + $item_level;
              $db->update("ss_users", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']);                 
            }
            else {
              unset($sdata);
              $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
              $sdata['EVENT_TYPE'] = 7;
              $sdata['EVENT_DATE'] = 'SYSDATE()';
              $sdata['STATUS'] = 1;
              $sdata['ITEM_ID'] = $item_id;
              $db->insert("ss_users_log", $sdata);   
            }
          }
        }
        else {
           unset($sdata);
           $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
           $sdata['EVENT_TYPE'] = 71;
	   $sdata['EVENT_DATE'] = 'SYSDATE()';
           $sdata['STATUS'] = 1;
           $db->insert("ss_users_log", $sdata);   
        } 
        header("location: ss_home.php");
      }

      if (isset($_POST['deequip']) && isset($_POST['id'])) {
        // check if item exist
        $sql = "SELECT SUI.GENERAL_ID, SU.ITEM_ID, SU.EQUIP_POINT, SU.LEVEL 
                  FROM ss_users_items SUI, ss_items SU
                 WHERE SUI.USER_ID=".$_SESSION["_user"]['USER_ID']." 
                       AND SUI.GENERAL_ID=".$_POST['id']." 
                       AND SUI.EQUIPED=1
                       AND SU.ITEM_ID=SUI.ITEM_ID";
        $db->query($sql);
        if ($row = $db->nextRow()) { 
          $item_level = $row['LEVEL'];
//          check if stash has enough room
          $equip_point = $row['EQUIP_POINT'];
          $sql = "SELECT COUNT(*) SLOTS
                    FROM ss_users_items SUI
                   WHERE USER_ID=".$_SESSION["_user"]['USER_ID']." 
                         AND EQUIPED=0 
                         AND SLOT_USED=".$equip_point;
          $db->query($sql);
          if ($row = $db->nextRow()) { 
            if ($row['SLOTS'] <= 9) {
              // equip
              unset($sdata);
              $sdata['EQUIPED'] = 0;
              $sdata['SLOT_USED'] = 0;
              $db->update("ss_users_items", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']." AND GENERAL_ID=".$_POST['id']);                 
              unset($sdata);
              $sdata['EQUIPED_LEVEL'] = $_SESSION["_user"]['SS'][0]['EQUIPED_LEVEL'] - $item_level;
              $_SESSION["_user"]['SS'][0]['EQUIPED_LEVEL'] = $_SESSION["_user"]['SS'][0]['EQUIPED_LEVEL'] - $item_level;
              $db->update("ss_users", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']);                 
            }
  //        else log in failure    
          }
        }
//        else log in failure
//        header("location: ss_home.php");
      }


      if (isset($_POST['buy'])) {
        if ($_SESSION["_user"]['SS'][0]['MONEY'] >= $level_price[$_SESSION["_user"]['SS'][0]['LEVEL']+1]) {
          unset($sdata);
          $sdata['SPARE_POINTS'] = $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] + 1;
          $sdata['LEVEL'] = $_SESSION["_user"]['SS'][0]['LEVEL'] + 1;
          $sdata['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'] - $level_price[$_SESSION["_user"]['SS'][0]['LEVEL']+1];
          $db->update("ss_users", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']);   
          $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] = $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] + 1;
          $_SESSION["_user"]['SS'][0]['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'] - $level_price[$_SESSION["_user"]['SS'][0]['LEVEL'] + 1];
          $_SESSION["_user"]['SS'][0]['LEVEL'] = $_SESSION["_user"]['SS'][0]['LEVEL'] + 1;

          unset($sdata);
          $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
          $sdata['EVENT_TYPE'] = 9;
          $sdata['EVENT_DATE'] = 'SYSDATE()';
          $sdata['STATUS'] = 0;
          $sdata['LEVEL'] = $_SESSION["_user"]['SS'][0]['LEVEL'];
          $db->insert("ss_users_log", $sdata);   

        } else {
          unset($sdata);
          $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
          $sdata['EVENT_TYPE'] = 9;
          $sdata['EVENT_DATE'] = 'SYSDATE()';
          $sdata['STATUS'] = 1;
          $db->insert("ss_users_log", $sdata);   
        }
        header("location: ss_home.php");
      }
      if (isset($_POST['sell'])) {
        if ($_SESSION["_user"]['SS'][0]['LEVEL'] > 0 && $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] > 0) {
          unset($sdata);
          $sdata['SPARE_POINTS'] = $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] - 1;
          $sdata['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'] + $level_price[$_SESSION["_user"]['SS'][0]['LEVEL']];
          $sdata['LEVEL'] = $_SESSION["_user"]['SS'][0]['LEVEL'] - 1;
          $db->update("ss_users", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']);   
          $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] = $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] - 1;
          $_SESSION["_user"]['SS'][0]['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'] + $level_price[$_SESSION["_user"]['SS'][0]['LEVEL']];
          $_SESSION["_user"]['SS'][0]['LEVEL'] = $_SESSION["_user"]['SS'][0]['LEVEL'] - 1;

          unset($sdata);
          $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
          $sdata['EVENT_TYPE'] = 10;
          $sdata['EVENT_DATE'] = 'SYSDATE()';
          $sdata['STATUS'] = 0;
          $sdata['LEVEL'] = $_SESSION["_user"]['SS'][0]['LEVEL']+1;
          $db->insert("ss_users_log", $sdata);   

        } else {
          unset($sdata);
          $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
          $sdata['EVENT_TYPE'] = 10;
          $sdata['EVENT_DATE'] = 'SYSDATE()';
          $sdata['STATUS'] = 1;
          $db->insert("ss_users_log", $sdata);   
        }

        header("location: ss_home.php");
      }
$db->showquery=true;    
//print_r($_SESSION["_user"]['SS'][0]);
//echo $_POST['attrib'].$_SESSION["_user"]['SS'][0][$_POST['attrib']];
//exit;
      if (isset($_POST['increase']) && isset($_POST['attrib'])) {
        if ($_SESSION["_user"]['SS'][0]['SPARE_POINTS'] > 0) {
//echo 1;
          unset($sdata);
          $sdata[$_POST['attrib']] = $_SESSION["_user"]['SS'][0][$_POST['attrib']] + 1;
          $sdata['SPARE_POINTS'] = $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] - 1;
          $sdata['EQUIPED_LEVEL'] = $_SESSION["_user"]['SS'][0]['EQUIPED_LEVEL'] + 1;
          $db->update("ss_users", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']);   
          $_SESSION["_user"]['SS'][0][$_POST['attrib']] = $_SESSION["_user"]['SS'][0][$_POST['attrib']] + 1;
          $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] = $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] - 1;
          $_SESSION["_user"]['SS'][0]['EQUIPED_LEVEL'] = $_SESSION["_user"]['SS'][0]['EQUIPED_LEVEL'] + 1;
        }  
        header("location: ss_home.php");
      }  
      if (isset($_POST['decrease']) && isset($_POST['attrib'])) {
        if ($_SESSION["_user"]['SS'][0][$_POST['attrib']] > 5) {
          unset($sdata);
          $sdata[$_POST['attrib']] = $_SESSION["_user"]['SS'][0][$_POST['attrib']] - 1;
          $sdata['SPARE_POINTS'] = $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] + 1;
          $sdata['EQUIPED_LEVEL'] = $_SESSION["_user"]['SS'][0]['EQUIPED_LEVEL'] - 1;
          $db->update("ss_users", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']);   
          $_SESSION["_user"]['SS'][0][$_POST['attrib']] = $_SESSION["_user"]['SS'][0][$_POST['attrib']] - 1;
          $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] = $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] + 1;
          $_SESSION["_user"]['SS'][0]['EQUIPED_LEVEL'] = $_SESSION["_user"]['SS'][0]['EQUIPED_LEVEL'] - 1;
        }  
        header("location: ss_home.php");
      }  
//exit;
    }
 }
 else {
    header("location: ss_manager.php");
    exit; 
 }
 $db->free();

 $data['SS'][0] = $_SESSION["_user"]['SS'][0];
 // buy sell level

 if ($_SESSION["_user"]['SS'][0]['MONEY'] >= $level_price[$_SESSION["_user"]['SS'][0]['LEVEL']+1]) {
   // buy new level
   $data['SS'][0]['LEVELS'][0]['BUY_LEVEL'][0]['LEVEL'] = $_SESSION["_user"]['SS'][0]['LEVEL']+1;
   $data['SS'][0]['LEVELS'][0]['BUY_LEVEL'][0]['PRICE'] = $level_price[$_SESSION["_user"]['SS'][0]['LEVEL']+1];
 }  
 else {
   $data['SS'][0]['LEVELS'][0]['NOT_BUY_LEVEL'][0]['LEVEL'] = $_SESSION["_user"]['SS'][0]['LEVEL']+1;
   $data['SS'][0]['LEVELS'][0]['NOT_BUY_LEVEL'][0]['PRICE'] = $level_price[$_SESSION["_user"]['SS'][0]['LEVEL']+1];
 }

 if ($_SESSION["_user"]['SS'][0]['LEVEL'] > 0 && $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] > 0) {
   // sell new level
   $data['SS'][0]['LEVELS'][0]['SELL_LEVEL'][0]['LEVEL'] = $_SESSION["_user"]['SS'][0]['LEVEL'];
   $data['SS'][0]['LEVELS'][0]['SELL_LEVEL'][0]['PRICE'] = $level_price[$_SESSION["_user"]['SS'][0]['LEVEL']];
 } else if ($_SESSION["_user"]['SS'][0]['LEVEL'] > 0 && $_SESSION["_user"]['SS'][0]['SPARE_POINTS'] == 0) {
   $data['SS'][0]['LEVELS'][0]['NOT_SELL_SPARE'][0]['X'] = 1;
 } 

 if ($_SESSION["_user"]['SS'][0]['SPARE_POINTS'] > 0) {
   $data['SS'][0]['INC_SPEED'][0]['X'] = 1;
   $data['SS'][0]['INC_STRENGTH'][0]['X'] = 1;
   $data['SS'][0]['INC_COORDINATION'][0]['X'] = 1;
   $data['SS'][0]['INC_ENDURANCE'][0]['X'] = 1;
   $data['SS'][0]['INC_LUCK'][0]['X'] = 1;
 }

 if ($_SESSION["_user"]['SS'][0]['SPEED'] > 5) {   
   $data['SS'][0]['DEC_SPEED'][0]['X'] = 1;
 }
 if ($_SESSION["_user"]['SS'][0]['STRENGTH'] > 5) {   
   $data['SS'][0]['DEC_STRENGTH'][0]['X'] = 1;
 }
 if ($_SESSION["_user"]['SS'][0]['COORDINATION'] > 5) {   
   $data['SS'][0]['DEC_COORDINATION'][0]['X'] = 1;
 }
 if ($_SESSION["_user"]['SS'][0]['ENDURANCE'] > 5) {   
   $data['SS'][0]['DEC_ENDURANCE'][0]['X'] = 1;
 }
 if ($_SESSION["_user"]['SS'][0]['LUCK'] > 5) {   
   $data['SS'][0]['DEC_LUCK'][0]['X'] = 1;
 }

 // get stash
  $data['SS'][0]['STASH'][0]['ROWS'] = $utils->getStash($auth->getUserID());
  
  // get equipment
  $data['SS'][0]['INVENTORY'][0]['EMPTY'][0]=1;
  $data['SS'][0]['INVENTORY'][0] = $utils->getEquippedInventory($auth->getUserID());

// get skills
 $data['SS'][0]['OWNED_SKILLS'][0] = $utils->getSkills($auth->getUserID());

 $utils->setLocation(SS_HOME);
 $data['SS'][0]['LOG'][0] = $utils->getLog($auth->getUserID());
   
 
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