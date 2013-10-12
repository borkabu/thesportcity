<?php
/*
===============================================================================
team_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit team records
  - edit keywords
  - create new team record

TABLES USED: 
  - BASKET.TEAMS
  - BASKET.TEAM_TOURNAMENTS
  - BASKET.TOURNAMENTS
  - BASKET.KEYWORDS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] additional buttons
===============================================================================
*/

// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/inputs.inc.php');
include('../class/adm_menu.php');
include('../class/update.inc.php');

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
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_WAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_WAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = false;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_WAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------
// delete
$db->showquery=true;
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_ACTIONS_WAGER], 'FA') == 0) {
  $db->delete('wager_seasons', 'SEASON_ID='.$_GET['del']);
  $db->delete('wager_seasons_details', 'SEASON_ID='.$_GET['del']);
}

// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('wager_seasons', array('PUBLISH' => "'Y'"),'SEASON_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('wager_seasons', array('PUBLISH' => "'N'"),'SEASON_ID='.$_GET['deactivate']);
}


if (isset($_GET['copy_season']) && !$ro) {
  $sql = "INSERT INTO wager_seasons (START_DATE, END_DATE, 
	 		MONEY, PRIZE_FUND, NEWSLETTER_ID) 
		select START_DATE, END_DATE, MONEY, PRIZE_FUND, NEWSLETTER_ID
		from wager_seasons where season_id=".$_GET['season_id'];
  $db->query($sql);
  $new_season_id = $db->id();

  $sql = "INSERT INTO wager_seasons_details (season_id, tseason_title, prizes, lang_id)  
		select ".$new_season_id.", concat('copy of ', tseason_title), prizes, lang_ID 
		from wager_seasons_details
		where season_id=".$_GET['season_id'];
  $db->query($sql);

  header('Location: wager_season.php');
  exit;

}

// --- END UPDATE -------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));

// presets
if (!isset($_GET['page']))
  $page = 1;
else $page = $_GET['page'];
if (!isset($perpage))
  $perpage = $page_size;
if (empty($_GET['order']))
  $order = 'START_DATE desc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('START_DATE', 'END_DATE', 'TSEASON_TITLE', 'PUBLISH');
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
    'SEASON_TITLE' => 'LANG_TITLE_U'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER["PHP_SELF"].url('', '', array('order'));

$param['where'] = '';
if (!empty($where)) {
  $param['where'] = " AND UPPER($where) like UPPER('%$query%')";
  $data['FILTERED'][0]['X'] = 1;
}

$sql = "SELECT * FROM languages ORDER BY ID";
$db->query($sql);
while ($row = $db->nextRow()) {
  $languages[$row['ID']] = $row;
}

// get source list
$sql = "SELECT TS.SEASON_ID, TSD.TSEASON_TITLE, TS.PUBLISH,               
           SUBSTRING(START_DATE, 1, 10) START_DATE, 
           SUBSTRING(END_DATE, 1, 10) END_DATE,
	       GROUP_CONCAT(TSD2.LANG_ID) as LANGUAGES
        FROM wager_seasons  TS
		left JOIN wager_seasons_details TSD ON TS.SEASON_ID = TSD.SEASON_ID  AND TSD.LANG_ID=".$_SESSION['lang_id']."
		left join wager_seasons_details TSD2 ON TSD2.SEASON_ID=TS.SEASON_ID
        WHERE 1=1 ".$param['where']."
	GROUP BY TS.SEASON_ID
        ORDER BY ".$param['order'];

// get source list
$db->query($sql);
//$db->setPage($page, $perpage);
$rows = $db->rows();

$c = 0;
while ($row = $db->nextRow()) {
  $data['ITEM'][$c] = $row;

  $used_langs = explode(",", $row['LANGUAGES']);
  foreach ($languages as $language) {
    if (in_array($language['ID'], $used_langs)) {
      $data['ITEM'][$c]['LANGS'][$language['ID']]['USED'][0] = $language;
      $data['ITEM'][$c]['LANGS'][$language['ID']]['USED'][0]['SEASON_ID'] = $row['SEASON_ID'];
    }
    else {
      $data['ITEM'][$c]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
      $data['ITEM'][$c]['LANGS'][$language['ID']]['NOTUSED'][0]['SEASON_ID'] = $row['SEASON_ID'];
    }
  }

  if (strcmp($_SESSION["_admin"][MENU_ACTIONS], 'FA') == 0)
     $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['SEASON_ID']);

  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
  
  if ($row['PUBLISH'] == 'Y')
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['SEASON_ID']);
  else
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['SEASON_ID']);

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
  if ($page_tmp == $page) {
    $data['PAGING'][0]['PAGES'][$page_tmp-1]['SELECTED'][0]['PAGENUM'] = $page_tmp;
  }
  else {
    $data['PAGING'][0]['PAGES'][$page_tmp-1]['NORMAL'][0]['PAGENUM'] = $page_tmp;
    $data['PAGING'][0]['PAGES'][$page_tmp-1]['NORMAL'][0]['URL'] = url('page', $page_tmp);
  }
}

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/wager_season.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>