<?php
/*
===============================================================================
ppl.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of people records
  - deletes people records

TABLES USED:
  - BASKET.USERS
  - BASKET.MEMBERS
  - BASKET.TEAMS
  - BASKET.TOURNAMENTS
  - BASKET.ORGANIZATIONS

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
$lng = new language;
$frm = new form;

// security layer
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_admin[MENU_ADMINS]) || strcmp($_admin[MENU_ADMINS], 'NA') == 0)
{
  $db->close();
  header('Location: access_denied.php');
  exit;
}

if (strcmp($_admin[MENU_ADMINS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATES ----------------------------------------------------------
// delete
if (isset($del) && !$ro && strcmp($_admin[MENU_ADMINS], 'FA') == 0) {
  $db->select('admin_group_members', 'ADMIN_GROUP_ID', "ADMIN_GROUP_ID=$del");
  if ($db->nextRow()) {
    $data['ERROR'][0]['MSG'] = 'Grupes paðalinti negalima, '
                               .'kadangi yra su ja susijusiu adminu.';
  }
}
// activate
/*if (isset($activate) && !$ro) {
  $db->update('admin_group_members', array('PUBLISH' => "'Y'"),'USER_ID='.$activate);
}
// deactivate
if (isset($deactivate) && !$ro) {
  $db->update('busers', array('PUBLISH' => "'N'"),'USER_ID='.$deactivate);
} */
// --- END UPDATES ------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($PHP_SELF));

// presets
if (!isset($page))
  $page = 1;
if (!isset($perpage))
  $perpage = $page_size;
if (empty($order))
  $order = 'GROUP_NAME asc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('GROUP_NAME');
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
    'GROUP_NAME' => 'Pavadinimas'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', $where, $opt, $where);
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', $query, array('class' => 'input'));
$data['FORM_URL'] = $PHP_SELF.url('', '', array('order'));

if (!empty($where)) {
  $param['where'] = "AND UPPER($where) like UPPER('%$query%')";
  $data['FILTERED'][0]['X'] = 1;
}

// get user list list
    $sql = 'SELECT *
            FROM admin_groups AG
            WHERE 1=1
              '.$param['where'].'
            ORDER BY '.$order.'
            ';
  echo $sql;
  $db->query($sql);
  $db->setPage($page, $perpage);
  $rows = $db->rows();

  $c = 0;
  while ($row = $db->nextRow()) {
    $data['ITEM'][$c] = $row;
  
    if ($c & 2 > 0)
      $data['ITEM'][$c]['ODD'][0]['X'] = 1;
    else
      $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
  
    $data['ITEM'][$c]['DEL_URL'] = $PHP_SELF.url('del', $row['ADMIN_GROUP_ID']);
  
  /*  if ($row['PUBLISH'] == 'Y')
      $data['ITEM'][$c]['ACT_URL'] = $PHP_SELF.url('deactivate', $row['ADMIN_GROUP_ID']);
    else
      $data['ITEM'][$c]['ACT_URL'] = $PHP_SELF.url('activate', $row['ADMIN_GROUP_ID']);*/
    $c++;
  }
    
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
    
// }
$db->free();

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm_admin_group.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>
