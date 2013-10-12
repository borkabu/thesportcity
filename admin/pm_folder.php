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

if (empty($_SESSION["_admin"][MENU_FORUM]) || strcmp($_SESSION["_admin"][MENU_FORUM], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_FORUM], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN DELETE -----------------------------------------------------------
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_FORUM], 'FA') == 0) {
  $db->delete('pm_folder_details', 'FOLDER_ID='.$_GET['del']);
  $db->delete('pm_folder', 'FOLDER_ID='.$_GET['del']);
  header('Location: pm_folder.php');
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('pm_folder', array('PUBLISH' => "'Y'"),'CAT_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('pm_folder', array('PUBLISH' => "'N'"),'CAT_ID='.$_GET['deactivate']);
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
  $order = 'TITLE asc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('TITLE');
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

$sql = "SELECT * FROM languages ORDER BY ID";
$db->query($sql);
while ($row = $db->nextRow()) {
  $languages[$row['ID']] = $row;
}


$sql = "SELECT C.FOLDER_ID, CD.TITLE,               
	       GROUP_CONCAT(CD2.LANG_ID) as LANGUAGES
        FROM pm_folder  C
		left JOIN pm_folder_details CD ON C.FOLDER_ID = CD.FOLDER_ID  AND CD.LANG_ID=".$_SESSION['lang_id']."
		left join pm_folder_details CD2 ON CD2.FOLDER_ID=C.FOLDER_ID
        WHERE ".$param['where']."
	GROUP BY C.FOLDER_ID
        ORDER BY ".$param['order'];

// get source list
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
      $data['ITEM'][$c]['LANGS'][$language['ID']]['USED'][0]['FOLDER_ID'] = $row['FOLDER_ID'];
    }
    else {
      $data['ITEM'][$c]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
      $data['ITEM'][$c]['LANGS'][$language['ID']]['NOTUSED'][0]['FOLDER_ID'] = $row['FOLDER_ID'];
    }
  }

  if (strcmp($_SESSION["_admin"][MENU_FORUM], 'FA') == 0)
     $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['FOLDER_ID']);
  
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
$tpl->setTemplateFile('../tpl/adm/pm_folder.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>