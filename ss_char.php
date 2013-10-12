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
$tpl->setTemplateFile('tpl/ss_char.tpl.html');

// $db->showquery = true;

  if (isset($_GET['user_id'])) {
    $sql = "SELECT * FROM users U, ss_users SU WHERE U.user_id=SU.user_id AND SU.USER_ID=".$_GET['user_id'];
    $db->query($sql);
    $row= $db->nextRow();
    $data['SS'][0] = $row;

    // get equipment
    $data['SS'][0]['INVENTORY'][0]['EMPTY'][0]=1;

    $data['SS'][0]['INVENTORY'][0] = $utils->getEquippedInventory($_GET['user_id']);
    // get skills

    $data['SS'][0]['OWNED_SKILLS'][0] = $utils->getSkills($_GET['user_id']);  
  }
 
//print_r($data);
$tpl->addData($data);
$content = $tpl->parse();
// ----------------------------------------------------------------------------

// include common header
include('inc/top_very_small.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_small.inc.php');

// close connections
include('class/db_close.inc.php');
?>