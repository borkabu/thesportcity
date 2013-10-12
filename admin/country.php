<?php
/*
===============================================================================
user.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of people records (regular users)
  - deletes people records (regular users)

TABLES USED:
  - BASKET.USERS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] interface with LDAP server
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

if (empty($_SESSION["_admin"][MENU_PARAMETERS]) || strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATES ----------------------------------------------------------
// delete
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'FA') == 0) {
  // delete user record itself
  $db->delete('countries_details', 'ID='.$_GET['del']);
  $db->delete('countries', 'ID='.$_GET['del']);
  
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('countries', array('PUBLISH' => "'Y'"),'ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('countries', array('PUBLISH' => "'N'"),'ID='.$_GET['deactivate']);
}
// --- END UPDATES ------------------------------------------------------------

//$db->showquery=true;

// build data
$data['menu'] = getMenu(scriptName($_SERVER["PHP_SELF"]));

// presets
if (!isset($page))
  $page = 1;
if (!isset($perpage))
  $perpage = $page_size;
if (empty($order))
  $order = 'LATIN_NAME asc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('LATIN_NAME');
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
    'LATIN_NAME' => 'LANG_LATIN_NAME_U'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER["PHP_SELF"].url('', '', array('order'));

$param['where'] = '';
// filtering by letter
if (!empty($_GET['let'])) {
  $param['where'] .= " AND UPPER(LATIN_NAME) like UPPER('".$_GET['let']."%')";
  $data['FILTERED'][0]['X'] = 1;
}
// filtering by query
elseif (!empty($_GET['where']) && !empty($_GET['query'])) {
  $param['where'] .= " AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%')";
  $data['FILTERED'][0]['X'] = 1;
}

 $db->showquery=true;
 
 $sql = "SELECT * FROM languages ORDER BY ID";
 $db->query($sql);
 while ($row = $db->nextRow()) {
   $languages[$row['ID']] = $row;
 }

 $sql_count = 'SELECT COUNT(ID) ROWS
                 FROM countries
                WHERE 1=1 '.$param['where']; 
 $db->query($sql_count);
 while ($row = $db->nextRow()) {
   $count = $row['ROWS'];
 }

 $limitclause = " LIMIT ".(((isset($_GET['page']) ? $_GET['page'] : 1)-1)*$perpage).",".$perpage;

$sql = "SELECT C.ID, C.SHORT_CODE, C.CCTLD, LATIN_NAME, ORIGINAL, CD.COUNTRY_NAME, 
	       GROUP_CONCAT(CD2.LANG_ID) as LANGUAGES
        FROM countries  C
		left JOIN countries_details CD ON C.ID = CD.ID  AND CD.LANG_ID=".$_SESSION['lang_id']."
		left join  countries_details CD2 ON CD2.ID=C.ID
        WHERE 1=1 ".$param['where']."
	GROUP BY C.ID
        ORDER BY ".$param['order']
	.$limitclause;

 echo $sql; 
$db->query($sql);
//$db->setPage($page, $perpage);
$rows = $count;

$c = 0;
while ($row = $db->nextRow()) {
  $data['ITEM'][$c] = $row;

  $used_langs = explode(",", $row['LANGUAGES']);
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
  
  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
  if (strcmp($_SESSION["_admin"][MENU_PARAMETERS], 'FA') == 0)  
    $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER["PHP_SELF"].url('del', $row['ID']);
  
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
  if (isset($_GET['page']) && $page_tmp == $_GET['page']) {
    $data['PAGING'][0]['PAGES'][$page_tmp-1]['SELECTED'][0]['PAGENUM'] = $page_tmp;
  }
  else {
    $data['PAGING'][0]['PAGES'][$page_tmp-1]['NORMAL'][0]['PAGENUM'] = $page_tmp;
    $data['PAGING'][0]['PAGES'][$page_tmp-1]['NORMAL'][0]['URL'] = url('page', $page_tmp);
  }
}

// letter filtering
$sql = 'SELECT SUBSTRING(UPPER(LATIN_NAME), 1, 1) LET
        FROM countries
        GROUP BY SUBSTRING(UPPER(LATIN_NAME), 1, 1)
        ORDER BY SUBSTRING(UPPER(LATIN_NAME), 1, 1)';
$db->query($sql);
$c = 0;
while ($row = $db->nextRow()) {
  if (isset($_GET["let"]) && $row['LET'] == $_GET["let"]) {
    $data['LET'][0]['LETTER'][$c]['SELECTED'][0]['TXT'] = $row['LET'];
  }
  else {
    $data['LET'][0]['LETTER'][$c]['NORMAL'][0]['TXT'] = $row['LET'];
    $data['LET'][0]['LETTER'][$c]['NORMAL'][0]['URL'] = $_SERVER["PHP_SELF"].url('let', $row['LET']);
  }
  $c++;
}
$db->free();

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/country.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>