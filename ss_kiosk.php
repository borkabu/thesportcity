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

// $db->showquery = true;

 $content = $menu->getSubmenu('ss_outside.php');
 $utils->setLocation(SS_KIOSK);

 if (isset($_POST["restore"]) && isset($_POST["points"])) {
   $sql = "SELECT SU.STAMINA
             FROM ss_users SU 
            WHERE SU.USER_ID=".$_SESSION["_user"]['USER_ID'];

   $db->query($sql);

   if ($row = $db->nextRow()) {  
      if ($_SESSION["_user"]['SS'][0]['MONEY'] > ($_POST["points"]/10)+1) {
        unset($sdata);
        $sdata['STAMINA'] = $row['STAMINA']+$_POST["points"];
        if ($sdata['STAMINA'] > 100)
          $sdata['STAMINA'] = 100;
        $_SESSION["_user"]['SS'][0]['STAMINA'] = $sdata['STAMINA'];
        $sdata['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'] - (($_POST["points"]/10)+1);
        $_SESSION["_user"]['SS'][0]['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'] - (($_POST["points"]/10)+1);
        $db->update("ss_users", $sdata, "USER_ID=".$_SESSION["_user"]['USER_ID']);   
        unset($sdata);
        $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
        $sdata['EVENT_TYPE'] = 8;
        $sdata['EVENT_DATE'] = 'SYSDATE()';
        $sdata['QUANTITY'] = $_POST["points"]/10;
        $sdata['STATUS'] = 0;
        $sdata['MONEY'] = ($points/10)+1;
        $db->insert("ss_users_log", $sdata);   

// log operation success
     } else {
// log operation failure
        unset($sdata);
        $sdata['USER_ID'] = $_SESSION["_user"]['USER_ID'];
        $sdata['EVENT_TYPE'] = 8;
        $sdata['EVENT_DATE'] = 'SYSDATE()';
        $sdata['QUANTITY'] = 1;
        $sdata['STATUS'] = 1;
        $sdata['MONEY'] = ($points/10)+1;
        $db->insert("ss_users_log", $sdata);   
     }     
//   header("location: ".$_SERVER["REQUEST_URI"]);
   }
 } 

 if ($_SESSION["_user"]['SS'][0]['MONEY'] > 2) {
   $data['ITEMS'][0]['POINTS'] = 20;
   $data['ITEMS'][0]['PRICE'] = 3;
   $data['ITEMS'][0]['INSTR'] = str_replace("%p", $data['ITEMS'][0]['POINTS'], $data['LANG_RESTORE_STAMINA_INSTR_U']);
   $data['ITEMS'][0]['INSTR'] = str_replace("%m", $data['ITEMS'][0]['PRICE'], $data['ITEMS'][0]['INSTR']);
 }
 if ($_SESSION["_user"]['SS'][0]['MONEY'] > 1) {
   $data['ITEMS'][1]['POINTS'] = 10;
   $data['ITEMS'][1]['PRICE'] = 2;
   $data['ITEMS'][1]['INSTR'] = str_replace("%p", $data['ITEMS'][1]['POINTS'], $data['LANG_RESTORE_STAMINA_INSTR_U']);
   $data['ITEMS'][1]['INSTR'] = str_replace("%m", $data['ITEMS'][1]['PRICE'], $data['ITEMS'][1]['INSTR']);
 }  
 $data['MONEY'] = $_SESSION["_user"]['SS'][0]['MONEY'];
 $data['STAMINA'] = $_SESSION["_user"]['SS'][0]['STAMINA'];
 
//print_r($data);
$tpl->setTemplateFile('tpl/ss_kiosk.tpl.html');
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