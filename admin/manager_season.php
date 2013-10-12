<?php
/*
===============================================================================
season.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of tournament seasons
  - deletes tournament seasons

TABLES USED: 
  - BASKET.SEASONS
  - BASKET.TOURNAMENTS

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

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------
// delete
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA') == 0) {
  $db->delete('manager_seasons', 'SEASON_ID='.$_GET['del']);
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('manager_seasons', array('PUBLISH' => "'Y'"),'SEASON_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('manager_seasons', array('PUBLISH' => "'N'"),'SEASON_ID='.$_GET['deactivate']);
}

if (isset($_GET['copy_season']) && !$ro) {
  $sql = "INSERT INTO manager_seasons (START_DATE, END_DATE, PUBLISH, TRANSACTIONS,
	 		MONEY, MONEY_STOCK, PIC_LOCATION, PIC2_LOCATION, PRIZE_FUND, DONATED, NEWSLETTER_ID,
	 		MAX_PLAYERS, SPORT_ID, CAPTAINCY) 
		select START_DATE, END_DATE, PUBLISH, TRANSACTIONS,
	 		MONEY, MONEY_STOCK, PIC_LOCATION, PIC2_LOCATION, PRIZE_FUND, DONATED, NEWSLETTER_ID,
	 		MAX_PLAYERS, SPORT_ID, CAPTAINCY
		from manager_seasons where season_id=".$_GET['season_id'];
  $db->query($sql);
  $new_season_id = $db->id();

  $sql = "INSERT INTO manager_seasons_details (season_id, season_title, prizes, lang_id)  
		select ".$new_season_id.", concat('copy of ', season_title), prizes,lang_ID 
		from manager_seasons_details
		where season_id=".$_GET['season_id'];
  $db->query($sql);
  unset ( $sdata );
  $sdata ['SEASON_ID'] = $new_season_id;
  $sdata ['DATE_CREATED'] = "NOW()";
  $sdata ['MAXIMUM'] = 0;
  $sdata ['MINIMUM'] = 0;
  $sdata ['AVER'] = 0;
  $sdata ['AGGREG'] = 0;
  $sdata ['MARKET'] = "'N'";
  $sdata ['STATE'] = 0;
  $sdata ['MAX_POINTS'] = 0;
  $db->insert ( 'manager_statistics', $sdata );

  header('Location: manager_season.php');
  exit;

}
// --- END UPDATE -------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));

// presets
if (!isset($_GET['page']))
  $_GET['page'] = 1;
if (!isset($perpage))
  $perpage = $page_size;
if (empty($order))
  $order = 'START_DATE desc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('START_DATE', 'END_DATE', 'SEASON_TITLE', 'PUBLISH');
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
    'SEASON_TITLE' => 'Pavadinimas'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

$param['where'] = '';
if (!empty($where)) {
  $param['where'] = "UPPER($where) like UPPER('%$query%')";
  $data['FILTERED'][0]['X'] = 1;
}


$sql = "SELECT * FROM languages ORDER BY ID";
$db->query($sql);
while ($row = $db->nextRow()) {
  $languages[$row['ID']] = $row;
}

  $sql = "SELECT F.SEASON_ID, FD.SEASON_TITLE, F.PUBLISH, F.INVITED,
                SUBSTRING(F.START_DATE, 1, 10) START_DATE, MT.START_DATE > NOW() as CAN_INVITE,
                SUBSTRING(F.END_DATE, 1, 10) END_DATE,
 	        GROUP_CONCAT(FD2.LANG_ID) as LANGUAGES
	  FROM manager_seasons F 
		left JOIN manager_seasons_details FD ON F.SEASON_ID = FD.SEASON_ID AND FD.LANG_ID=".$_SESSION['lang_id']."
		left join manager_seasons_details FD2 ON FD2.SEASON_ID=F.SEASON_ID 
		left join manager_tours MT ON MT.SEASON_ID=F.SEASON_ID AND MT.NUMBER=1
        WHERE 1=1 ".$param['where']."
	GROUP BY F.SEASON_ID
        ORDER BY ".$param['order'];
  $db->query($sql);

$db->setPage($_GET['page'], $perpage);
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

  if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'FA') == 0) {
    $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['SEASON_ID']);
    if ($row['CAN_INVITE'] == 1 && $row['INVITED'] == 0)
      $data['ITEM'][$c]['INVITE'][0]['SEASON_ID'] = $row['SEASON_ID'];
  }
  
  if ($row['PUBLISH'] == 'Y')
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['SEASON_ID']);
  else
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['SEASON_ID']);

  $data['ITEM'][$c]['EDIT_URL'] = 'manager_season_edit.php'; 
  $data['ITEM'][$c]['EDIT_PLAYERS_URL'] = 'manager_ppl.php'; 
  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
  $c++;
}
$db->free();

if ($c == 0) {
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
$tpl->setTemplateFile('../tpl/adm/manager_season.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>