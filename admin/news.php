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

if (empty($_SESSION["_admin"][MENU_NEWS]) || strcmp($_SESSION["_admin"][MENU_NEWS], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_NEWS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATES ----------------------------------------------------------
// delete
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_NEWS], 'FA') == 0) {
  $db->delete('news', 'NEWS_ID='.$_GET['del']);
  $db->delete('news_details', 'NEWS_ID='.$_GET['del']);
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('news', array('PUBLISH' => "'Y'"),'NEWS_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('news', array('PUBLISH' => "'N'"),'NEWS_ID='.$_GET['deactivate']);
}
// --- END UPDATES ------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));

// presets
if (!isset($_GET['page']))
  $page = 1;
else $page = $_GET['page'];
if (!isset($perpage))
  $perpage = $page_size;
if (empty($order))
  $order = 'DATE_PUBLISHED desc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('DATE_PUBLISHED', 'TITLE', 'PUBLISH');
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
    'ND.TITLE' => 'LANG_ITEM_NAME_U',
    'DESCR' => 'LANG_CONTENT_U',
    'DATE_PUBLISHED' => 'LANG_DATE_PUBLISHED_U',
    'SOURCE' => 'LANG_SOURCE_U',
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

// get news list

 $count = 0;
 $sql_count = "SELECT COUNT(N.NEWS_ID) ROWS
                 FROM news N 
		    LEFT JOIN news_details ND ON ND.NEWS_ID=N.NEWS_ID AND ND.LANG_ID=".$_SESSION['lang_id']."
               WHERE N.GENRE NOT IN (8,9) ".$param['where']; 
 $db->query($sql_count);
 while ($row = $db->nextRow()) {
   $count = $row['ROWS'];
 }

 $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

 $sql = "SELECT N.NEWS_ID, ND.TITLE, N.PUBLISH,
    	       SUBSTRING(DATE_PUBLISHED, 1, 10) DATE_PUBLISH,
                N.USER_ID, N.PRIORITY,
                SUBSTRING(N.DATE_CREATED, 1, 16) DATE_CREATED, U.USER_NAME,
		GROUP_CONCAT(ND2.LANG_ID) as LANGUAGES
        FROM news N LEFT JOIN users U ON N.USER_ID=U.USER_ID       
		    LEFT JOIN news_details ND ON ND.NEWS_ID=N.NEWS_ID AND ND.LANG_ID=".$_SESSION['lang_id']."
                    LEFT JOIN news_details ND2 ON ND2.NEWS_ID=N.NEWS_ID
        WHERE N.GENRE NOT IN (8,9) ".$param['where']."        
	GROUP BY N.NEWS_ID
        ORDER BY ".$param['order'].",DATE_CREATED DESC ".$limitclause;
 echo $sql; 
$db->query($sql);
//$db->setPage($page, $perpage);
$rows = $count;

$c = 0;
while ($row = $db->nextRow()) {
  $data['NEWS'][$c] = $row;

  $used_langs = explode(",", $row['LANGUAGES']);
  foreach ($languages as $language) {
    if (in_array($language['ID'], $used_langs)) {
      $data['NEWS'][$c]['LANGS'][$language['ID']]['USED'][0] = $language;
      $data['NEWS'][$c]['LANGS'][$language['ID']]['USED'][0]['NEWS_ID'] = $row['NEWS_ID'];
    }
    else {
      $data['NEWS'][$c]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
      $data['NEWS'][$c]['LANGS'][$language['ID']]['NOTUSED'][0]['NEWS_ID'] = $row['NEWS_ID'];
    }
  }

  if ($c & 2 > 0)
    $data['NEWS'][$c]['ODD'][0]['X'] = 1;
  else
    $data['NEWS'][$c]['EVEN'][0]['X'] = 1;
  
   if (strcmp($_SESSION["_admin"][MENU_NEWS], 'FA') == 0)
     $data['NEWS'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['NEWS_ID']);
  
  if ($row['PUBLISH'] == 'Y')
    $data['NEWS'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['NEWS_ID']);
  else
    $data['NEWS'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['NEWS_ID']);
  $c++;
}
$db->free();

if ($rows == 0) {
  $data['NONEWS'][0]['X'] = 1;
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
$tpl->setTemplateFile('../tpl/adm/news.tpl.html');
$tpl->addData($data);
$content= $tpl->parse();

// close connections
include('../class/db_close.inc.php');

echo $content;
?>