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
include('../class/newsletter.inc.php');

if (empty($_SESSION["_admin"][MENU_PARAMETERS]) || strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN DELETE -----------------------------------------------------------
if (isset($_GET['subscribe']) && $_GET['subscribe']='all') {
  $queues = '';
  $newsletter = new Newsletter();
  if ($_GET['type'] == 0) {
    $sql="SELECT USER_ID FROM users WHERE ACTIVE='Y' and USER_ID NOT IN (SELECT USER_ID FROM newsletter_subscribers WHERE ID=".$_GET['id'].")";
  } else if ($_GET['type'] == 1) {
    $sql="SELECT USER_ID FROM manager_users WHERE USER_ID NOT IN (SELECT USER_ID FROM newsletter_subscribers WHERE ID=".$_GET['id'].")";    
  }

  $db->query($sql);
  $c = 0;
  while ($row = $db->nextRow()) {
      $queues[$c] = $row;
      $c++;
  }

  if (is_array($queues)) {
    foreach ($queues as $queue) {
      $newsletter->subscribe($_GET['id'], $queue['USER_ID']);
    }
  }
}

if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'FA') == 0) {
  $db->delete('newsletter_details', 'ID='.$_GET['del']);
  $db->delete('newsletter', 'ID='.$_GET['del']);
  header('Location: newsletter.php');
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('newsletter', array('publish' => "'Y'"),'ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('newsletter', array('publish' => "'N'"),'ID='.$_GET['deactivate']);
}


if (isset($_GET['copy_newsletter']) && !$ro) {
  $sql = "INSERT INTO newsletter (TYPE, FREQUENCY, NAME, PUBLISH) 
		select TYPE, FREQUENCY, CONCAT('copy of ', NAME) , PUBLISH
		from newsletter where id=".$_GET['newsletter_id'];
  $db->query($sql);
  $new_newsletter_id = $db->id();

  $sql = "INSERT INTO newsletter_details (id, descr, lang_id, title, header, footer)  
		select ".$new_newsletter_id.", descr, lang_id, concat('copy of ', title), header, footer 
		from newsletter_details
		where id=".$_GET['newsletter_id'];
  $db->query($sql);
  unset ( $sdata );

  header('Location: newsletter.php');
  exit;

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
$so_fields = array('TITLE', 'publish');
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

$param['where'] = '';
if (!empty($_GET['where']) && !empty($_GET['query'])) {
  $param['where'] = " AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%')";
  $data['FILTERED'][0]['X'] = 1;
}

$sql = "SELECT * FROM languages ORDER BY ID";
$db->query($sql);
while ($row = $db->nextRow()) {
  $languages[$row['ID']] = $row;
}

  $sql = "SELECT F.ID, F.NAME, FD.TITLE, F.PUBLISH, F.FREQUENCY, F.TYPE,
                SUBSTRING(F.END_DATE, 1, 10) END_DATE,
 	        GROUP_CONCAT(FD2.LANG_ID) as LANGUAGES
	  FROM newsletter F 
		left JOIN newsletter_details FD ON F.ID = FD.ID AND FD.LANG_ID=".$_SESSION['lang_id']."
		left join newsletter_details FD2 ON FD2.ID=F.ID 
        WHERE 1=1 ".$param['where']."
	GROUP BY F.ID
        ORDER BY ".$param['order'];

//		left join newsletter_subscribers NS on NS.ID=F.ID and NS.ACTIVE=1
  $db->query($sql);
echo $sql;
// get source list
$db->query($sql);
$db->setPage($page, $perpage);
$rows = $db->rows();

$c = 0;
while ($row = $db->nextRow()) {
  $data['ITEM'][$c] = $row;

  $used_langs = explode(",", $row['LANGUAGES']);
  $used_langs = array_unique($used_langs);
//print_r($used_langs);
  foreach ($languages as $language) {
    if (in_array($language['ID'], $used_langs)) {
      $data['ITEM'][$c]['LANGS'][$language['ID']]['USED'][0] = $language;
      $data['ITEM'][$c]['LANGS'][$language['ID']]['USED'][0]['ID'] = $row['ID'];
    }
    else {
      $data['ITEM'][$c]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
      $data['ITEM'][$c]['LANGS'][$language['ID']]['NOTUSED'][0]['ID'] = $row['ID'];
    }
  }

  if (strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'FA') == 0)
     $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['ID']);
  
  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
  
  if ($row['PUBLISH'] == 'Y')
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['ID']);
  else
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['ID']);
  
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
$tpl->setTemplateFile('../tpl/adm/newsletter.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>