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

ob_start();
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
//include('class/ss_conf.inc.php');
include('ss_include.php');
// --- build content data -----------------------------------------------------

 // locations
// $db->showquery = true;
 
 $attack = 1;
 $defence = 2;

 if (!empty($_GET['battle_id'])) {
   $db->select("ss_battle", "*", "BATTLE_ID=".$_GET['battle_id']);
   if ($row = $db->nextRow()) {
     $battle_data = $row;
   }    
 
   if ($battle_data['SPORT_ID'] == 1) {
     include('class/ss_func_krep.inc.php');
     include('class/ss_lang_krep_'.$_SESSION["_lang"].'.inc.php');
   } else if ($battle_data['SPORT_ID'] == 2) {
     include('class/ss_func_foot.inc.php');
     include('class/ss_lang_foot_'.$_SESSION["_lang"].'.inc.php');
   }
 
   $protocol = new SS_Battle_Protocol($langs, $_SESSION['_lang']);
   $data['PROTOCOL'][0] = $protocol->getProtocol($_GET['battle_id'], 1, 2, $battle_data['RESULT1'], $battle_data['RESULT2'], 300);

 }

$tpl->setTemplateFile('tpl/ss_battle_protocol.tpl.html');
$tpl->addData($data);
$content = $tpl->parse();
// ----------------------------------------------------------------------------

ob_end_flush();
// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');
?>