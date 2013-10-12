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

if (empty($_SESSION["_admin"][MENU_NEWS_VIDEO]) || strcmp($_SESSION["_admin"][MENU_NEWS_VIDEO], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
} 

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_NEWS_VIDEO], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATES ----------------------------------------------------------
// delete
if (isset($del) && !$ro && strcmp($_admin[MENU_NEWS_VIDEO], 'FA') == 0) {
  $db->delete('video', 'VIDEO_ID='.$del);
}
// activate
if (isset($activate) && !$ro) {
  $db->update('video', array('PUBLISH' => "'Y'"), 'VIDEO_ID='.$activate);


}
// deactivate
if (isset($deactivate) && !$ro) {
  $db->update('video', array('PUBLISH' => "'N'"),'VIDEO_ID='.$deactivate);
}
// --- END UPDATES ------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER["PHP_SELF"]));

// presets
if (!isset($page))
  $page = 1;
if (!isset($perpage))
  $perpage = $page_size;
if (empty($order))
  $order = 'DATE_PUBLISHED desc ';

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
    'TITLE' => 'Pavadinimas',
    'DATE_CREATED' => 'Publikavimo data'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER["PHP_SELF"].url('', '', array('order'));

$param['where'] = '';
if (!empty($_GET['where'])) {
  $param['where'] = " AND UPPER(".$_GET['where'].") like UPPER('%$query%')";
  $data['FILTERED'][0]['X'] = 1;
}

$sql = "SELECT * FROM languages ORDER BY ID";
$db->query($sql);
while ($row = $db->nextRow()) {
  $languages[$row['ID']] = $row;
}

 $sql_count = 'SELECT COUNT(V.VIDEO_ID) ROWS
                 FROM video V 
               WHERE 1=1 '.$param['where']; 
 $db->query($sql_count);
 while ($row = $db->nextRow()) {
   $count = $row['ROWS'];
  }

 $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

$sql = "SELECT V.VIDEO_ID, VD.TITLE, V.PUBLISH,
               SUBSTRING(V.DATE_PUBLISHED, 1, 16) DATE_PUBLISHED,
		GROUP_CONCAT(VD2.LANG_ID) as LANGUAGES
        FROM video V 
	    LEFT JOIN video_details VD ON VD.VIDEO_ID=V.VIDEO_ID AND VD.LANG_ID=".$_SESSION['lang_id']."
            LEFT JOIN video_details VD2 ON VD2.VIDEO_ID=V.VIDEO_ID
        WHERE 1=1 ".$param['where']."        
	GROUP BY V.VIDEO_ID
        ORDER BY ".$param['order'].$limitclause;
 echo $sql; 
$db->query($sql);
//$db->setPage($page, $perpage);
$rows = $count;

$c = 0;
while ($row = $db->nextRow()) {
  $data['VIDEO'][$c] = $row;

  $used_langs = explode(",", $row['LANGUAGES']);
  foreach ($languages as $language) {
    if (in_array($language['ID'], $used_langs)) {
      $data['VIDEO'][$c]['LANGS'][$language['ID']]['USED'][0] = $language;
      $data['VIDEO'][$c]['LANGS'][$language['ID']]['USED'][0]['VIDEO_ID'] = $row['VIDEO_ID'];
    }
    else {
      $data['VIDEO'][$c]['LANGS'][$language['ID']]['NOTUSED'][0] = $language;
      $data['VIDEO'][$c]['LANGS'][$language['ID']]['NOTUSED'][0]['VIDEO_ID'] = $row['VIDEO_ID'];
    }
  }

  if ($c & 2 > 0)
    $data['VIDEO'][$c]['ODD'][0]['X'] = 1;
  else
    $data['VIDEO'][$c]['EVEN'][0]['X'] = 1;
  
   if (strcmp($_admin[MENU_NEWS_VIDEO], 'FA') == 0)
     $data['VIDEO'][$c]['DEL'][0]['DEL_URL'] = $_SERVER["PHP_SELF"].url('del', $row['VIDEO_ID']);
  
  if ($row['PUBLISH'] == 'Y')
    $data['VIDEO'][$c]['ACT_URL'] = $_SERVER["PHP_SELF"].url('deactivate', $row['VIDEO_ID']);
  else
    $data['VIDEO'][$c]['ACT_URL'] = $_SERVER["PHP_SELF"].url('activate', $row['VIDEO_ID']);
  $c++;
}

$db->free();

if ($rows == 0) {
  $data['NOVIDEO'][0]['X'] = 1;
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
$tpl->setTemplateFile('../tpl/adm/video.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
