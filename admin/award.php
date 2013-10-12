<?php
/*
===============================================================================
org.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of organizations
  - deletes organizations

TABLES USED: 
  - BASKET.ORGANIZATIONS
  - BASKET.ORGTYPES

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
$lng = new language;
$frm = new form;

// security layer
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_admin[MENU_USERS]) || strcmp($_admin[MENU_USERS], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

if (strcmp($_admin[MENU_ORGS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------
// delete
if (isset($del) && !$ro && strcmp($_admin[MENU_USERS], 'FA') == 0) {
  $db->delete('awards', 'AWARD_ID='.$del);
}
// --- END UPDATE -------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($PHP_SELF));

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
    'TITLE' => 'Pavadinimas',
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', $where, $opt, $where);
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', $query, array('class' => 'input'));
$data['FORM_URL'] = $PHP_SELF.url('', '', array('order'));

if (!empty($where)) {
  $param['where'] =  "WHERE UPPER($where) like UPPER('%$query%')";
  $data['FILTERED'][0]['X'] = 1;
}

// get source list
$sql = 'SELECT A.*
        FROM awards A
          '.$param['where'].'
        ORDER BY '.$order.'
        ';
$db->query($sql);
$db->setPage($page, $perpage);
$rows = $db->rows();

$c = 0;
while ($row = $db->nextRow()) {
  $data['ITEM'][$c] = $row;
  // user type
  if (strcmp($_admin[MENU_USERS], 'FA') == 0)
    $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $PHP_SELF.url('del', $row['AWARD_ID']);
  
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
$tpl->setTemplateFile('../tpl/adm_award.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
