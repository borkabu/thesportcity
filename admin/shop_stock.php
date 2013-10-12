<?php
/*
===============================================================================
cat.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of categories
  - deletes categories

TABLES USED: 
  - BASKET.CATS

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

if (empty($_SESSION["_admin"][MENU_SHOP]) || strcmp($_SESSION["_admin"][MENU_SHOP], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_SHOP], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN DELETE -----------------------------------------------------------
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_SHOP], 'FA') == 0) {
  $db->delete('shop_stock_details', 'ITEM_ID='.$_GET['del']);
  $db->delete('shop_stock', 'ITEM_ID='.$_GET['del']);
  header('Location: shop_stock.php');
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('shop_stock', array('PUBLISH' => "'Y'"),'ITEM_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('shop_stock', array('PUBLISH' => "'N'"),'ITEM_ID='.$_GET['deactivate']);
}
// --- END DELETE -------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));

// presets
if (!isset($page))
  $page = 1;
if (!isset($perpage))
  $perpage = $page_size;
if (empty($order))
  $order = 'ITEM_NAME asc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('ITEM_NAME', 'PUBLISH');
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
    'TITLE' => 'LANG_ITEM_NAME_U'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

$param['where'] = '1=1';
if (!empty($_GET['where']) && !empty($_GET['query'])) {
  $param['where'] = " UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%')";
  $data['FILTERED'][0]['X'] = 1;
}

$db->showquery=true;

$sql = "SELECT * FROM languages ORDER BY ID";
$db->query($sql);
while ($row = $db->nextRow()) {
  $languages[$row['ID']] = $row;
}


$sql = "SELECT C.ITEM_ID, CD.ITEM_NAME, C.PUBLISH, C.PRICE_EURO, C.PRICE_CREDITS,
	       GROUP_CONCAT(CD2.LANG_ID) as LANGUAGES
        FROM shop_stock  C 
		left JOIN shop_stock_details CD ON C.ITEM_ID = CD.ITEM_ID AND CD.LANG_ID=".$_SESSION['lang_id']."
		left join shop_stock_details CD2 ON CD2.ITEM_ID=C.ITEM_ID
        WHERE ".$param['where']." 
	GROUP BY C.ITEM_ID
        ORDER BY ".$param['order'];

// get source list
echo $sql;
$db->query($sql);
$db->setPage($page, $perpage);
$rows = $db->rows();

$c = 0;
while ($row = $db->nextRow()) {
  $data['ITEM'][$c] = $row;

  $used_langs = explode(",", $row['LANGUAGES']);
  foreach ($languages as $language) {
    if (in_array($language['ID'], $used_langs)) {
      $data['ITEM'][$c]['LANGS'][$language['ID']]['USED'][0] = $language;
      $data['ITEM'][$c]['LANGS'][$language['ID']]['USED'][0]['ITEM_ID'] = $row['ITEM_ID'];
    }
    else {
      $data['ITEM'][$c]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
      $data['ITEM'][$c]['LANGS'][$language['ID']]['NOTUSED'][0]['ITEM_ID'] = $row['ITEM_ID'];
    }
  }

  if (strcmp($_SESSION["_admin"][MENU_SHOP], 'FA') == 0)
     $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['ITEM_ID']);
  
  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
  
  if ($row['PUBLISH'] == 'Y')
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['ITEM_ID']);
  else
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['ITEM_ID']);
  
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
$tpl->setTemplateFile('../tpl/adm/shop_stock.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
