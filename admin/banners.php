<?php
/*
===============================================================================
shortcut.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of shortcuts
  - deletes shortcuts

TABLES USED: 
  - BASKET.SHORTCUTS

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
include('../lib/banner_positions.inc.php');
// connections
include('../class/db_connect.inc.php');
$tpl = new template;
$frm = new form;

// security layer
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_SESSION["_admin"][MENU_PARAMETERS]) || strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN DELETE -----------------------------------------------------------
if (isset($del) && !$ro && strcmp($_admin[MENU_PARAMETERS], 'FA') == 0) {
  $db->delete('banners', 'BANNER_ID='.$del);
}
// activate
if (isset($activate) && !$ro) {
  $db->update('banners', array('PUBLISH' => "'Y'"),'BANNER_ID='.$activate);
}
// deactivate
if (isset($deactivate) && !$ro) {
  $db->update('banners', array('PUBLISH' => "'N'"),'BANNER_ID='.$deactivate);
}
// move up the list
if (isset($promote) && !$ro) {
  $db->update('banners', 'ORDER_NO='.$promote_to, 'BANNER_ID='.$promote);
}
// move down the list
if (isset($demote) && !$ro) {
  $db->update('banners', 'ORDER_NO='.$demote_to, 'BANNER_ID='.$demote);
}
// --- END DELETE -------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER["PHP_SELF"]));

// presets
if (!isset($page))
  $page = 1;
if (!isset($perpage))
  $perpage = $page_size;
if (empty($order))
  $order = 'POSITION, PRIORITY asc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('TITLE', 'FILENAME', 'PRIORITY', 'PUBLISH', 'FORMAT');
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
    'TITLE' => 'Pavadinimas',
    'FILENAME' => 'Nuoroda'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER["PHP_SELF"].url('', '', array('order'));

$param['where'] = '';
if (!empty($where)) {
  $param['where'] = "UPPER($where) like UPPER('%$query%')";
  $data['FILTERED'][0]['X'] = 1;
}

// get source list
$db->select('banners', '*', $param['where'], $param['order']);
$db->setPage($page, $perpage);
$rows = $db->rows();

$c = 0;
$orders = array();
while ($row = $db->nextRow()) {
  $data['ITEM'][$c] = $row;
  if (strcmp($_admin[MENU_PARAMETERS], 'FA') == 0)
    $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $PHP_SELF.url('del', $row['BANNER_ID']);

  $data['ITEM'][$c]['POSITION'] = $banner_positions[$row['POSITION']];
  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
  
  if ($row['PUBLISH'] == 'Y')
    $data['ITEM'][$c]['ACT_URL'] = $PHP_SELF.url('deactivate', $row['BANNER_ID']);
  else
    $data['ITEM'][$c]['ACT_URL'] = $PHP_SELF.url('activate', $row['BANNER_ID']);
  
  $ord_pos[$row['BANNER_ID']] = $c;
  
  $c++;
}
$db->free();

if ($rows == 0) {
  $data['NORECORDS'][0]['X'] = 1;
}

$c = 0;
$db->select('banners', 'BANNER_ID, ORDER_NO', '', 'ORDER_NO');
while ($row = $db->nextRow()) {
  $orders[$c]['order_no'] = $row['ORDER_NO'];
  $orders[$c]['banner_id'] = $row['BANNER_ID'];
  $c++;
}
$db->free();

for ($c = 0; $c < sizeof($orders); $c++) {
  // promote/demote buttons
  $pos = $ord_pos[$orders[$c]['banner_id']];
  if ($c == 0)
    $data['ITEM'][$pos]['PROMOTE'][0]['X'] = 1;
  else
    $data['ITEM'][$pos]['PROMOTE_A'][0]['URL'] = $PHP_SELF
                                   .url('promote', $orders[$c]['banner_id'])
                                   .'&demote='.$orders[$c-1]['banner_id']
                                   .'&promote_to='.$orders[$c-1]['order_no']
                                   .'&demote_to='.$orders[$c]['order_no'];
  
  if ($c == (sizeof($orders)-1))
    $data['ITEM'][$pos]['DEMOTE'][0]['X'] = 1;
  else
    $data['ITEM'][$pos]['DEMOTE_A'][0]['URL'] = $PHP_SELF
                                  .url('demote', $orders[$c]['banner_id'])
                                  .'&promote='.$orders[$c+1]['banner_id']
                                  .'&demote_to='.$orders[$c+1]['order_no']
                                  .'&promote_to='.$orders[$c]['order_no'];
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
$tpl->setTemplateFile('../tpl/adm/banners.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
