<?php
/*
===============================================================================
sched_races.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of games
  - deletes games
  - activates/deactivates games

TABLES USED:
  - BASKET.GAMES
  - BASKET.SEASONS

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

if (empty($_SESSION["_admin"][MENU_GAMES]) || strcmp($_SESSION["_admin"][MENU_GAMES], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_GAMES], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATES ----------------------------------------------------------
// delete
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_GAMES], 'FA') == 0) {
  // delete user record itself
  $db->delete('games_races', 'GAME_ID='.$_GET['del']);
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('games_races', array('PUBLISH' => "'Y'"), 'GAME_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('games_races', array('PUBLISH' => "'N'"), 'GAME_ID='.$_GET['deactivate']);
}
// --- END UPDATES ------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));

// presets
if (!isset($_GET['page']))
  $_GET['page'] = 1;
if (!isset($perpage))
  $perpage = $page_size;
if (empty($order))
  $order = 'G.START_DATE desc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('START_DATE', 'TEAM_NAME1', 'TEAM_NAME2',
                   'SEASON_TITLE', 'PUBLISH');
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
    'SEASON_TITLE' => 'Lyga/sezonas'
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
                            (SELECT TEAM_ID FROM teams WHERE UPPER(TEAM_NAME) LIKE UPPER('%".$_GET['query']."%'))
                          OR G.TEAM_ID2 IN 
                            (SELECT TEAM_ID FROM teams WHERE UPPER(TEAM_NAME) LIKE UPPER('%".$_GET['query']."%'))
                         )";
  $data['FILTERED'][0]['X'] = 1;
}
elseif (!empty($_GET['where'])) {
  if ($_GET['where'] == 'SEASON_TITLE')
    $param['where'] = " AND UPPER(SD.SEASON_TITLE) like UPPER('%".$_GET['query']."%') ";
  else $param['where'] = " AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%') ";
  $data['FILTERED'][0]['X'] = 1;
}

$param['today'] = '';
if (isset($_GET['today'])) {
  $param['today'] = ' AND SUBSTRING(G.START_DATE, 1, 10) = SUBSTRING(SYSDATE(), 1, 10) ';
  $data['FILTERED'][0]['X'] = 1;
}

  $sql_count = "SELECT COUNT(G.GAME_ID) ROWS
                 FROM games_races G
		   LEFT JOIN seasons S ON G.SEASON_ID=S.SEASON_ID                   
		   left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id']."
                 WHERE 1=1
                   ".$param['where'].$param['today']; 

echo $sql_count;
  $db->query($sql_count);
  $count = 0;
  while ($row = $db->nextRow()) {
    $count = $row['ROWS'];
  }

 $limitclause = "LIMIT ".(((isset($_GET['page']) ? $_GET['page'] : 1)-1)*$perpage).",".$perpage;
// get games list list
$sql = "SELECT G.GAME_ID, G.PUBLISH, SUBSTRING(G.START_DATE, 1, 16) START_DATE, SD.SEASON_TITLE, G.TITLE
        FROM games_races G LEFT JOIN seasons S ON G.SEASON_ID=S.SEASON_ID                   
		     left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id']."
        WHERE 1=1
        ".$param['where'].$param['today']."
        ORDER BY ".$order.", G.GAME_ID ".$limitclause;
$db->query($sql);
$rows = $count;
echo $sql;
$c = 0;
while ($row = $db->nextRow()) {
  $data['ITEM'][$c] = $row;
  
  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
  if (strcmp($_SESSION["_admin"][MENU_GAMES], 'FA') == 0)  
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
$tpl->setTemplateFile('../tpl/adm/sched_races.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
