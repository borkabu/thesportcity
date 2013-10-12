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
  $db->delete('forum_cats_details', 'CAT_ID='.$_GET['del']);
  $db->delete('forum_cats', 'CAT_ID='.$_GET['del']);
  header('Location: forum_cats.php');
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('forum_cats', array('PUBLISH' => "'Y'"),'CAT_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('forum_cats', array('PUBLISH' => "'N'"),'CAT_ID='.$_GET['deactivate']);
}

if (isset($_GET['promote']) && !$ro) {
  $sql="SELECT CAT_ID, PRIORITY FROM forum_cats WHERE PRIORITY < ".$_GET['priority']." ORDER BY PRIORITY DESC LIMIT 0,1";
  $db->query($sql);
  if ($row = $db->nextRow()) {
    $next_value = $row['PRIORITY'];
    $prev_value = $_GET['priority'];
    $db->update('forum_cats', 'PRIORITY='.$prev_value, 'CAT_ID='.$row['CAT_ID']);
    $db->update('forum_cats', 'PRIORITY='.$next_value, 'CAT_ID='.$_GET['cat_id']);
  }  
//  $db->update('forum_cats', 'ORDER_NO='.$promote_to, 'CAT_ID='.$_GET['promote']);
}
// move down the list
if (isset($_GET['demote']) && !$ro) {
  $sql="SELECT CAT_ID, PRIORITY FROM forum_cats WHERE PRIORITY > ".$_GET['priority']." ORDER BY PRIORITY ASC LIMIT 0,1";
  $db->query($sql);
  if ($row = $db->nextRow()) {
    $next_value = $row['PRIORITY'];
    $prev_value = $_GET['priority'];
    $db->update('forum_cats', 'PRIORITY='.$prev_value, 'CAT_ID='.$row['CAT_ID']);
    $db->update('forum_cats', 'PRIORITY='.$next_value, 'CAT_ID='.$_GET['cat_id']);
  }  

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
  $order = 'PRIORITY asc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('CAT_NAME', 'PUBLISH');
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
    'CAT_NAME' => 'LANG_ITEM_NAME_U'
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


$sql = "SELECT C.CAT_ID, CD.CAT_NAME, C.PUBLISH, C.PRIORITY,
	       GROUP_CONCAT(CD2.LANG_ID) as LANGUAGES
        FROM forum_cats  C
		left JOIN forum_cats_details CD ON C.CAT_ID = CD.CAT_ID  AND CD.LANG_ID=".$_SESSION['lang_id']."
		left join  forum_cats_details CD2 ON CD2.CAT_ID=C.CAT_ID
        WHERE ".$param['where']."
	GROUP BY C.CAT_ID
        ORDER BY ".$param['order'];
//echo $sql; 

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
      $data['ITEM'][$c]['LANGS'][$language['ID']]['USED'][0]['CAT_ID'] = $row['CAT_ID'];
    }
    else {
      $data['ITEM'][$c]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
      $data['ITEM'][$c]['LANGS'][$language['ID']]['NOTUSED'][0]['CAT_ID'] = $row['CAT_ID'];
    }
  }

  if (strcmp($_SESSION["_admin"][MENU_FORUM], 'FA') == 0)
     $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['CAT_ID']);
  
  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
  
  if ($row['PUBLISH'] == 'Y')
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['CAT_ID']);
  else
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['CAT_ID']);
  
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
$tpl->setTemplateFile('../tpl/adm/forum_cat.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>