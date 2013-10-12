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
include('class/AES.class.php');

//echo $_GET['idstring'];

//echo $_GET['id'];
$sql = "SELECT * from authentication_seeds where id=".$_GET['id']." AND ISSUE_DATE > DATE_ADD(NOW(), INTERVAL -5 MINUTE)";
//echo $sql;
$db->query($sql);
$url = "";
if ($row = $db->nextRow()) {
  // seed exist
//echo "1".$_SERVER["REMOTE_ADDR"];
//echo $row['SEED'];
  if ($row['USER_IP'] == $_SERVER["REMOTE_ADDR"]) {
    $aes = new AES($row['SEED']);
    $decrypted = $aes->decrypt($_GET['idstring']);
  //  echo "<br>".$decrypted;
    $data = explode("|", $decrypted);
    if (trim($data[1]) != "") {
      if (isset($_GET['source']) && $clients[$_GET['source']]['encoding'] != "UTF-8")
        $external_authentication['USER_NAME'] = iconv($clients[$_GET['source']]['encoding'], "UTF-8", $data[1]);
      else $external_authentication['USER_NAME'] = $data[1];
    }
    $external_authentication['USER_EMAIL'] = $data[2];
    if ($_SERVER["REMOTE_ADDR"] == $data[3]) // valid
      $external_authentication['VALID'] = 1;
    if (isset($data[4]))
      $url = $data[4];
    //$row['']
  }
}

if (isset($_GET['source']))
  $external_authentication['SOURCE'] = $_GET['source'];
if (isset($_GET['host']))
  $external_authentication['HOST'] = $_GET['host'];

$reset_login = true;

include('include.php');
include('class/user_session.inc.php');

// http header
include('class/headers_no_cache.inc.php');
include('class/inputs.inc.php');
include('class/manager.inc.php');

//print_r($external_authentication);
  if (isset($url) && !empty($url)) {
    $_SESSION['external_authentication'] =  $external_authentication;
    header("location: ".$url);
    exit;
  }
  $content = '';
//  $content = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 2);

  $manager = new Manager();

  $seasons = $managerbox->getManagerSeasonBox(false, '', '', false, true);
  $timeline = $timelinebox->getTimelineBox('manager');

  $smarty->assign("seasons", $seasons);
  $smarty->assign("timeline", $timeline);

  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/f_manager_dashboard.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/f_manager_dashboard.smarty'.($stop-$start);

  define("FANTASY_MANAGER", 1);

//print_r($_SESSION);
  include('inc/top.inc.php');
  echo $content;
// content

// include common footer
include('inc/bot.inc.php');

// close connections
include('class/db_close.inc.php');
?>