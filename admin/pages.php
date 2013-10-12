<?php
/*
===============================================================================
news.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of news messages
  - deletes news messages

TABLES USED: 
  - BASKET.NEWS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] correct search through dates
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

//echo $_admin[MENU_NEWS];
if (empty($_SESSION["_admin"][MENU_MENU]) || strcmp($_SESSION["_admin"][MENU_MENU], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_MENU], 'RO') == 0)
  $ro = TRUE;

$db->showquery=true;
// --- BEGIN UPDATES ----------------------------------------------------------
// delete
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_MENU], 'FA') == 0) {
  $db->delete('pages_details', 'PAGE_ID='.$_GET['del']);
  $db->delete('pages', 'PAGE_ID='.$_GET['del']);
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('pages', array('PUBLISH' => "'Y'"),'PAGE_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('pages', array('PUBLISH' => "'N'"),'PAGE_ID='.$_GET['deactivate']);
}
// --- END UPDATES ------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));

// presets
if (!isset($page))
  $page = 1;
if (!isset($perpage))
  $perpage = $page_size;
if (empty($_GET['order']))
  $param['order'] = 'PD.TITLE asc';
else $param['order'] = $_GET['order'];
// sorting

$data['ORDER'] = $param['order'];
$so_fields = array('DATE_PUBLISHED', 'TITLE', 'PUBLISH');
for ($c=0; $c<sizeof($so_fields); $c++) {
  if (isset($_GET['order']) && $_GET['order'] == $so_fields[$c].' desc') {
    $data[$so_fields[$c].'_DESC_A'][0]['URL'] = 'xxx';
    $data[$so_fields[$c].'_ASC'][0]['URL'] = url('order', $so_fields[$c].' asc');
  }
  elseif (isset($_GET['order']) && $_GET['order'] == $so_fields[$c].' asc') {
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
    'TITLE' => 'LANG_ITEM_NAME_U',
    'DESCR' => 'LANG_CONTENT_U',
    'SOURCE' => 'LANG_SOURCE_U',
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

if (!empty($_GET['where']) && !empty($_GET['query'])) {
  $param['where'] = "UPPER(PD.".$_GET['where'].") like UPPER('%".$_GET['query']."%')";
  $data['FILTERED'][0]['X'] = 1;
}

// get news list
$db->showquery = true;

if (empty($param['where'])) {
  $param['where'] = ' 1=1 ';
} 

$sql = "SELECT * FROM languages ORDER BY ID";
$db->query($sql);
while ($row = $db->nextRow()) {
  $languages[$row['ID']] = $row;
}

$sql = "SELECT P.PAGE_ID, PD.TITLE, P.PUBLISH,
               PD.CUSER_ID, SUBSTRING(P.DATE_CREATED, 1, 16) DATE_CREATED, 
	       GROUP_CONCAT(PD2.LANG_ID) as LANGUAGES
        FROM pages  P 
		left JOIN pages_details PD ON P.PAGE_ID = PD.PAGE_ID  AND PD.LANG_ID=".$_SESSION['lang_id']."
		left join  pages_details PD2 ON PD2.page_id=P.PAGE_ID
        WHERE ".$param['where']."
	GROUP BY P.PAGE_ID
        ORDER BY ".$param['order'];
//echo $sql; 
$db->query($sql);

$db->setPage($page, $perpage);
$rows = $db->rows();

$c = 0;
while ($row = $db->nextRow()) {
  $data['PAGES'][$c] = $row;
  $used_langs = explode(",", $row['LANGUAGES']);
  foreach ($languages as $language) {
    if (in_array($language['ID'], $used_langs)) {
      $data['PAGES'][$c]['LANGS'][$language['ID']]['USED'][0] = $language;
      $data['PAGES'][$c]['LANGS'][$language['ID']]['USED'][0]['PAGE_ID'] = $row['PAGE_ID'];
    }
    else {
      $data['PAGES'][$c]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
      $data['PAGES'][$c]['LANGS'][$language['ID']]['NOTUSED'][0]['PAGE_ID'] = $row['PAGE_ID'];
    }
  }
  
  if ($c & 2 > 0)
    $data['PAGES'][$c]['ODD'][0]['X'] = 1;
  else
    $data['PAGES'][$c]['EVEN'][0]['X'] = 1;
  
   if (strcmp($_SESSION["_admin"][MENU_MENU], 'FA') == 0)
     $data['PAGES'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['PAGE_ID']);
  
  if ($row['PUBLISH'] == 'Y')
    $data['PAGES'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['PAGE_ID']);
  else
    $data['PAGES'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['PAGE_ID']);
  $c++;
}
$db->free();

if ($rows == 0) {
  $data['NOPAGES'][0]['X'] = 1;
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
$tpl->setTemplateFile('../tpl/adm/pages.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>