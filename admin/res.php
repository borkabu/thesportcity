<?php
/*
===============================================================================
sched.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of games
  - deletes games
  - activates/deactivates games

TABLES USED:
  - BASKET.GAMES

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/adm_menu.php');

// classes
include('../class/db.class.php');
include('../class/template.class.php');
include('../class/language.class.php');
include('../class/form.class.php');

// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

// security layer
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_SESSION["_admin"][MENU_GAMES_RESULTS]) || strcmp($_SESSION["_admin"][MENU_GAMES_RESULTS], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_GAMES_RESULTS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATES ----------------------------------------------------------
// delete
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_GAMES], 'FA') == 0) {
  // delete user record itself
  $db->delete('games', 'GAME_ID='.$_GET['del']);
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('games', array('PUBLISH' => "'Y'"), 'GAME_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('games', array('PUBLISH' => "'N'"), 'GAME_ID='.$_GET['deactivate']);
}

$stat_from = '';
$stat_to = '';
if (isset($_POST['stat_select'])) {
  $stat_from = $stat_from_y.'-'.$stat_from_m.'-'.$stat_from_d;
  $stat_to = $stat_to_y.'-'.$stat_to_m.'-'.$stat_to_d;
}
$PRESET_VARS['stat_from'] = $stat_from;
$PRESET_VARS['stat_to'] = $stat_to;

// --- END UPDATES ------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));

// presets
if (!isset($_GET['page']))
  $_GET['page'] = 1;
if (!isset($perpage))
  $perpage = $page_size;
if (empty($order))
  $order = 'START_DATE asc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('START_DATE', 'TEAM_NAME1', 'TEAM_NAME2', 'SEASON_TITLE', 
                   'SCORE1', 'PUBLISH');
for ($c=0; $c<sizeof($so_fields); $c++) {
  if ($order == $so_fields[$c].' desc') {
    $data[$so_fields[$c].'_DESC_A'][0]['URL'] = 'xxx';
    $data[$so_fields[$c].'_ASC'][0]['URL'] = url('order', $so_fields[$c].' asc');
  }
  elseif ($order == $so_fields[$c].' asc') {
    $data[$so_fields[$c].'_DESC'][0]['URL'] = url('order', $so_fields[$c].' desc');
    $data[$so_fields[$c].'_ASC_A'][0]['URL'] = 'xxx';
  }
  else {
    $data[$so_fields[$c].'_DESC'][0]['URL'] = url('order', $so_fields[$c].' desc');
    $data[$so_fields[$c].'_ASC'][0]['URL'] = url('order', $so_fields[$c].' asc');
  }
}

// filtering
$opt = array(
  'class' => 'input',
  'options' => array(
    'TEAM_NAME' => 'Komanda',
    'SEASON_TITLE' => 'Lyga/Sezonas'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));
$data['TODAY_URL'] = $_SERVER['PHP_SELF'].'?today=true';

// filtering by query
$param['where'] = '';
if (isset($_GET['where']) && $_GET['where'] == 'TEAM_NAME') {
//  $param['where'] = " (UPPER(TEAM_NAME1) LIKE UPPER('%$query%') OR UPPER(TEAM_NAME2) LIKE UPPER('%$query%'))";
  $param['where'] = " AND (G.TEAM_ID1 IN 
                            (SELECT TEAM_ID FROM teams WHERE UPPER(TEAM_NAME) LIKE UPPER('%$query%'))
                          OR G.TEAM_ID2 IN 
                            (SELECT TEAM_ID FROM teams WHERE UPPER(TEAM_NAME) LIKE UPPER('%$query%'))
                         )";
  $data['FILTERED'][0]['X'] = 1;
}
elseif (!empty($_GET['where'])) {
  if ($_GET['where'] == 'SEASON_TITLE')
    $param['where'] = " AND UPPER(S.SEASON_TITLE) like UPPER('%".$_GET['query']."%')";
  else $param['where'] = " AND UPPER($where) like UPPER('%".$_GET['query']."%')";
  $data['FILTERED'][0]['X'] = 1;
}

$param['today'] = '';
if (isset($_GET['today'])) {
  $param['today'] = ' AND SUBSTRING(G.START_DATE, 1, 10) = SUBSTRING(SYSDATE(), 1, 10) ';
  $data['FILTERED'][0]['X'] = 1;
}

$param['date'] = '';
if (isset($_POST['stat_select']) && !empty($_POST['stat_from']) && !empty($_POST['stat_to'])) {
  $param['date'] = " AND DATEDIFF(G.START_DATE, DATE_FORMAT('".$_POST['stat_from']."', '%Y-%m-%d'))>=0 AND DATEDIFF(G.START_DATE, DATE_FORMAT('".$_POST['stat_to']."', '%Y-%m-%d'))<=0 ";
}

// get games list list
$sql = "SELECT G.GAME_ID, G.PUBLISH, G.SCORE1, G.SCORE2,
          SUBSTRING(G.START_DATE, 1, 16) START_DATE,
          T1.TEAM_NAME TEAM_NAME1, T2.TEAM_NAME TEAM_NAME2, 
          SD.SEASON_TITLE, U.USER_NAME
        FROM  seasons S
               left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id'].",
              games G
               LEFT JOIN users U ON G.CUSER_ID=U.USER_ID
               LEFT JOIN teams T1 ON T1.TEAM_ID=G.TEAM_ID1 
               LEFT JOIN teams T2 ON T2.TEAM_ID=G.TEAM_ID2
        WHERE G.SEASON_ID=S.SEASON_ID
          ".$param['where'].$param['today'].$param['date']."
        ORDER BY ".$order.", G.GAME_ID";
//echo $sql;
$db->query($sql);
$db->setPage($_GET['page'], $perpage);
$rows = $db->rows();

$c = 0;
while ($row = $db->nextRow()) {
  $data['ITEM'][$c] = $row;
  
  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
  if (strcmp($_SESSION["_admin"][MENU_GAMES_RESULTS], 'FA') == 0)  
    $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['GAME_ID']);
  
  if ($row['PUBLISH'] == 'Y')
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['GAME_ID']);
  else
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['GAME_ID']);
  $c++;
}
$db->free();

if ($rows == 0) {
  $data['NORECORDS'][0]['X'] = 1;
}

// paging
$data['PAGING'][0]['NUMROWS'] = $rows;
$page_tmp = 0;
for ($c = 0; $c < $rows; $c += $perpage) {
  $page_tmp++;
  if (isset($_GET['page']) && $page_tmp == $_GET['page']) {
    $data['PAGING'][0]['PAGES'][$page_tmp-1]['SELECTED'][0]['PAGENUM'] = $page_tmp;
  }
  else {
    $data['PAGING'][0]['PAGES'][$page_tmp-1]['NORMAL'][0]['PAGENUM'] = $page_tmp;
    $data['PAGING'][0]['PAGES'][$page_tmp-1]['NORMAL'][0]['URL'] = url('page', $page_tmp);
  }
}

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/res.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>