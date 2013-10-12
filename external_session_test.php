<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
index.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - first page of a portal

TABLES USED: 
  - BASKET.NEWS
  - BASKET.EVENTS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/update.inc.php');
// classes
include('class/db.class.php');
include('class/template.class.php');
include('class/language.class.php');
include('class/form.class.php');
$tpl = new template;
$frm = new form;

// connections
include('class/db_connect.inc.php');

// user session
include('class/ss_const.inc.php');

//echo $_GET['idstring'];

//echo $_GET['id'];
$sql = "SELECT * from authentication_seeds where id=".$_GET['id']." AND ISSUE_DATE > DATE_ADD(NOW(), INTERVAL -5 MINUTE)";
//echo $sql;
$db->query($sql);
if ($row = $db->nextRow()) {
  // seed exist
//echo "1".$_SERVER["REMOTE_ADDR"];
//echo $row['SEED'];
  if ($row['USER_IP'] == $_SERVER["REMOTE_ADDR"]) {
    $decrypted = decrypt($_GET['idstring'], $row['SEED']);
    echo "<br>".$decrypted;
    $data = explode("|", $decrypted);
    if (trim($data[1]) != "")
      $external_authentication['USER_NAME'] = $data[1];
    $external_authentication['USER_EMAIL'] = $data[2];
    if ($_SERVER["REMOTE_ADDR"] == $data[3]) // valid
      $external_authentication['VALID'] = 1;
    //$row['']
  }
}
if (isset($_GET['source']))
  $external_authentication['SOURCE'] = $_GET['source'];

if (isset($_GET['host']))
  $external_authentication['HOST'] = $_GET['host'];

include('include.php');
include('class/user_session.inc.php');
// http header
include('class/headers.inc.php');
include('class/inputs.inc.php');

print_r($external_authentication);
  define("FANTASY_MANAGER", 1);

print_r($_SESSION);
  include('inc/top.inc.php');
  //echo $content;
// content

// include common footer
include('inc/bot.inc.php');

// close connections
include('class/db_close.inc.php');
?>