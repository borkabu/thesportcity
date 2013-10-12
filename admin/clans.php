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
include('../class/box.inc.php');
include('../class/email.inc.php');


if (empty($_SESSION["_admin"][MENU_USERS]) || strcmp($_SESSION["_admin"][MENU_USERS], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_USERS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATES ----------------------------------------------------------
// delete
/*if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_USERS], 'FA') == 0) {
  // delete user record itself
  $db->delete('users', 'USER_ID='.$_GET['del']);
  
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('users', array('PUBLISH' => "'Y'"),'USER_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('users', array('PUBLISH' => "'N'"),'USER_ID='.$_GET['deactivate']);
} */

// --- END UPDATES ------------------------------------------------------------

$db->showquery=true;

// build data
$data['menu'] = getMenu(scriptName($_SERVER["PHP_SELF"]));

// presets
if (!isset($page))
  $page = 1;
if (!isset($perpage))
  $perpage = $page_size;
if (empty($order))
  $order = 'CLAN_NAME asc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('CLAN_NAME');
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
    'CLAN_NAME' => 'LANG_TITLE_U',
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER["PHP_SELF"].url('', '', array('order'));

$param['where'] = '';
// filtering by letter
if (!empty($_GET['let'])) {
  $param['where'] .= " AND UPPER(CLAN_NAME) like UPPER('".$_GET['let']."%')";
  $data['FILTERED'][0]['X'] = 1;
}
// filtering by query
elseif (!empty($_GET['where']) && !empty($_GET['query'])) {
  $param['where'] .= " AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%')";
  $data['FILTERED'][0]['X'] = 1;
}

 $sql_count = 'SELECT COUNT(CLAN_ID) ROWS
                 FROM clans
                WHERE 1=1 '.$param['where']; 
 $db->query($sql_count);
 while ($row = $db->nextRow()) {
   $count = $row['ROWS'];
  }

 $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

// get user list list

$sql = 'SELECT CLAN_ID, CLAN_NAME, MEMBERS, CLAN_FUND
                 FROM clans
                WHERE 1=1 '.$param['where'].'
                ORDER BY '.$param['order'].' '.$limitclause; 

 echo $sql; 
$db->query($sql);
//$db->setPage($page, $perpage);
$rows = $count;

$c = 0;
while ($row = $db->nextRow()) {
  $data['ITEM'][$c] = $row;
  
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

// letter filtering
$sql = 'SELECT SUBSTRING(UPPER(CLAN_NAME), 1, 1) LET
        FROM users
        GROUP BY SUBSTRING(UPPER(CLAN_NAME), 1, 1)
        ORDER BY SUBSTRING(UPPER(CLAN_NAME), 1, 1)';
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
$tpl->setTemplateFile('../tpl/adm/clans.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>