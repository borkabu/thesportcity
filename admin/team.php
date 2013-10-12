<?php
/*
===============================================================================
team.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of teams
  - deletes teams

TABLES USED: 
  - BASKET.TEAMS

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

if (empty($_SESSION["_admin"][MENU_BASKET]) || strcmp($_SESSION["_admin"][MENU_BASKET], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_BASKET], 'RO') == 0)
  $ro = TRUE;
// --- BEGIN UPDATE -----------------------------------------------------------
// delete
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_BASKET], 'FA') == 0) {
  // check if there are no games associated with this team
  $db->select('games', 'GAME_ID', "TEAM_ID1=".$_GET['del']." OR TEAM_ID2=".$_GET['del']);
  if ($db->nextRow()) {
    $data['ERROR'][0]['MSG'] = 'Komandos paðalinti negalima, '
                               .'kadangi yr su ja susijusø rezultatø.';
  }
  else {
    $db->delete('teams', 'TEAM_ID='.$_GET['del']);
    $db->delete('team_tournaments', 'TEAM_ID='.$_GET['del']);
  }
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('teams', array('PUBLISH' => "'Y'"),'TEAM_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('teams', array('PUBLISH' => "'N'"),'TEAM_ID='.$_GET['deactivate']);
}
// --- END UPDATE -------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));

// presets
if (!isset($_GET['page']))
  $_GET['page'] = 1;
if (!isset($perpage))
  $perpage = $page_size;
$param['order'] = '';
if (empty($_GET['order']))
  $param['order'] = 'TEAM_NAME asc';
else $param['order'] = $_GET['order'];
$data['ORDER'] = $param['order'];
$so_fields = array('TEAM_NAME', 'TEAM_TYPE', 'PUBLISH', 'CITY', 'COUNTRY');
for ($c=0; $c<sizeof($so_fields); $c++) {
  if ($param['order'] == $so_fields[$c].' desc') {
    $data[$so_fields[$c].'_DESC_A'][0]['URL'] = 'xxx';
    $data[$so_fields[$c].'_ASC'][0]['URL'] = url('order', $so_fields[$c].' asc');
  }
  elseif ($param['order'] == $so_fields[$c].' asc') {
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
    'TEAM_NAME' => 'LANG_TITLE_U',
    'CITY' => 'LANG_TOWN_U',
    'COUNTRY' => 'LANG_COUNTRY_U'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

$param['where'] = '1=1 ';
// filtering by letter
if (!empty($_GET['let'])) {
  $param['where'] .= "AND UPPER(TEAM_NAME) like UPPER('".$_GET['let']."%')";
  $data['FILTERED'][0]['X'] = 1;
}
// filtering by query
elseif (!empty($_GET['where'])) {
  $param['where'] .= " AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%')";
  $data['FILTERED'][0]['X'] = 1;
}

$db->showquery=true;
// get source list
$db->select('teams', '*', $param['where'], $param['order']);
$db->setPage($_GET['page'], $perpage);
$rows = $db->rows();

$c = 0;
while ($row = $db->nextRow()) {
  $data['ITEM'][$c] = $row;
  $data['ITEM'][$c]['SPORT_ID'] = $msports[$row['SPORT_ID']];
  // user type
  if (strcmp($_SESSION["_admin"][MENU_BASKET], 'FA') == 0)
    $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['TEAM_ID']);
  
  if ($row['PUBLISH'] == 'Y')
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['TEAM_ID']);
  else
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['TEAM_ID']);
  
  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
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

// letter filtering
$sql = 'SELECT SUBSTRING(UPPER(TEAM_NAME), 1, 1) LET
        FROM teams
        GROUP BY SUBSTRING(UPPER(TEAM_NAME), 1, 1)
        ORDER BY SUBSTRING(UPPER(TEAM_NAME), 1, 1)';
$db->query($sql);
$c = 0;
while ($row = $db->nextRow()) {
  if (isset($_GET["let"]) && $row['LET'] == $_GET["let"]) {
    $data['LET'][0]['LETTER'][$c]['SELECTED'][0]['TXT'] = $row['LET'];
  }
  else {
    $data['LET'][0]['LETTER'][$c]['NORMAL'][0]['TXT'] = $row['LET'];
    $data['LET'][0]['LETTER'][$c]['NORMAL'][0]['URL'] = $_SERVER['PHP_SELF'].url('let', $row['LET']);
  }
  $c++;
}
$db->free();

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/team.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>