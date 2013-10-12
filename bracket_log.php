<?php
//return '';

// includes
include('class/conf.inc.php');
include('class/func.inc.php');
include('class/common.inc.php');
include('class/update.inc.php');

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
// page requirements
include('class/inputs.inc.php');
include('class/bracket.inc.php');

// --- build content data -----------------------------------------------------

 $content = '';
 $submenu = $menu->getSubmenu(scriptName($_SERVER["PHP_SELF"]), 7);

 $bracket = new Bracket();
 $bracketbox = new BracketBox($langs, $_SESSION["_lang"]);
 $bracket_user = '';

// $Bracket_user = new BracketUser($Bracket->tseason_id);

  $bracket_log['BRACKET_FILTER_BOX'] = $bracketbox->getBracketFilterBox($bracket->tseason_id);

  $bracket_logbox = new LogBox($langs, $_SESSION["_lang"]);
  $bracket_log['BRACKET_LOG'] = $bracket_logbox->getBracketLogBox($bracket->tseason_id, isset($_GET['page']) ? $_GET['page'] : 1);
  $bracket_log['BRACKET_LOG_PAGING'] = $pagingbox->getPagingBox($bracket_logbox->getRows(), isset($_GET['page']) ? $_GET['page'] : 1);


  $smarty->assign("bracket_log", $bracket_log);
  $start = getmicrotime();
  $content .= $smarty->fetch('smarty_tpl/bracket_log.smarty');    
  $stop = getmicrotime();
  if (isset($_GET['debugphp']))
    echo 'smarty_tpl/bracket_log.smarty'.($stop-$start);

// ----------------------------------------------------------------------------
  define("ARRANGER", 1);
// include common header
include('inc/top.inc.php');

// content
echo $content;

// include common footer
include('inc/bot_arranger.inc.php');

// close connections
include('class/db_close.inc.php');

?>
