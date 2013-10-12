<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
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
$frm = new form;

// security layer
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');
include('../class/prepare.inc.php');

$db->showquery=true;

if (empty($_SESSION["_admin"][MENU_BASKET]) || strcmp($_SESSION["_admin"][MENU_BASKET], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_BASKET], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATES ----------------------------------------------------------
// delete
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_BASKET], 'FA') == 0) {
  $db->select('results', 'GAME_ID', "USER_ID=".$_GET['del']);
  if ($db->nextRow()) {
    $data['ERROR'][0]['MSG'] = 'Zhaidejo paðalinti negalima, '
                               .'kadangi yr su jo susijusø rezultatø.';
  }
  else {
   // delete membership
   $db->delete('members', 'USER_ID='.$_GET['del']);
   
   // delete user record itself
   $db->delete('busers', 'USER_ID='.$_GET['del']);
  }
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('busers', array('PUBLISH' => "'Y'"),'USER_ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('busers', array('PUBLISH' => "'N'"),'USER_ID='.$_GET['deactivate']);
}

if (isset($_POST['set_number']) && !$ro) {
  foreach ($_POST as $key => $value) {
    if (strpos($key, 'player_') !== false) {
      echo substr($key, strpos($key, '_') + 1).":".$value."<br>";
      $player_id = substr($key, strpos($key, '_') + 1);
      
      unset($sdata);
      $sdata['NUM'] = "'".$value."'";
      $db->update('members', $sdata, 'ID='.$player_id); 
    }
  }
}

if (isset($_POST['prolong']) && !$ro){
  unset($sdata);
  $sdata['DATE_EXPIRED'] = 'NULL';
  $db->update('members', $sdata, "ID=".$_POST['prolong']);
}

// --- END UPDATES ------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));

// presets
if (!isset($_GET['page']))
  $_GET['page'] = 1;
if (!isset($perpage))
  $perpage = $page_size;
if (empty($order))
  $order = 'LOWER(LAST_NAME) asc, FIRST_NAME';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('LAST_NAME', 'FIRST_NAME', 'PUBLISH');
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
    'LAST_NAME' => 'LANG_SURNAME_U',
    'FIRST_NAME' => 'LANG_NAME_U',
    'USER_NAME' => 'LANG_USER_NAME_U',
    'CURRENT_TEAM_NAME' => 'LANG_TEAM_NAME_U',
    'TEAM_NAME' => 'LANG_TEAM_NAME2_U'
  )
);

$opt2['class'] = 'input';
$msports[0] = '';
$opt2['options'] = $msports;
;
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['SPORT_ID'] = $frm->getInput(FORM_INPUT_SELECT, 'sport_id', isset($_GET['sport_id']) ? $_GET['sport_id'] : '', $opt2, isset($_GET['sport_id']) ? $_GET['sport_id'] : '');
$data['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

$param['where'] = '';
$param['sport_id'] = '';
// filtering by letter
if (!empty($_GET['let'])) {
  $param['where'] = "AND UPPER(LAST_NAME) like UPPER('".$_GET['let']."%')";
  $data['FILTERED'][0]['X'] = 1;
}
// filtering by query
elseif (!empty($_GET['where']) && $_GET['where'] != 'TEAM_NAME' && $_GET['where'] != 'CURRENT_TEAM_NAME') {
  $param['where'] = "AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%')";
  $data['FILTERED'][0]['X'] = 1;
}
elseif (!empty($_GET['where']) && $_GET['where'] == 'CURRENT_TEAM_NAME') {
  $param['where'] = "AND U.USER_ID IN (SELECT USER_ID FROM members M, teams T WHERE UPPER(T.TEAM_NAME) like UPPER('%".$_GET['query']."%')
			AND T.TEAM_ID=M.TEAM_ID AND M.DATE_STARTED < NOW() AND (M.DATE_EXPIRED> NOW() OR M.DATE_EXPIRED IS NULL))";
 $data['FILTERED'][0]['X'] = 1;
} elseif (!empty($_GET['where']) && $_GET['where'] == 'TEAM_NAME') {
  $param['where'] = "AND U.USER_ID IN (SELECT USER_ID FROM members M, teams T WHERE UPPER(T.TEAM_NAME) like UPPER('%".$_GET['query']."%')
			AND T.TEAM_ID=M.TEAM_ID AND M.DATE_STARTED < NOW())";
 $data['FILTERED'][0]['X'] = 1;
}


if (!empty($_GET['sport_id'])) {
  $param['sport_id'] = " AND SPORT_ID=".$_GET['sport_id'];
  $data['FILTERED'][0]['X'] = 1;
}

    $sql = 'SELECT COUNT(*) CNT
            FROM busers U
            WHERE 1=1
              '.$param['where'].$param['sport_id'];

    $db->query($sql);
    $row = $db->nextRow();
    $rows = $row['CNT'];

$limitclause = " LIMIT ".(((isset($_GET['page']) ? $_GET['page'] : 1)-1)*$perpage).",".$perpage;
// get user list list
    $sql = 'SELECT U.USER_ID, U.FIRST_NAME, U.LAST_NAME, U.ORIGINAL_NAME, U.NICKNAME, U.PUBLISH, U.SPORT_ID
            FROM busers U
            WHERE 1=1
              '.$param['where'].$param['sport_id'].'
            ORDER BY '.$order.$limitclause;
//echo $sql;
$db->query($sql);

    $c = 0;
    $users = array();
    $ulist = '';
    $pre = '';
    while ($row = $db->nextRow()) {
      $users[$row['USER_ID']] = $row;
      $ulist .= $pre.$row['USER_ID'];
      $pre = ',';

      $c++;

    }
    $db->free();
   
   if ($ulist != '') 
    {
    // get list of teams/tournaments/organizations bound to this user
    $sql = 'SELECT M.ID, M.USER_ID, M.USER_TYPE, SUBSTR(M.DATE_STARTED, 1, 10) DATE_STARTED, SUBSTR(M.DATE_EXPIRED, 1, 10) DATE_EXPIRED,
               T.TEAM_ID, T.TEAM_NAME, T.CITY, T.COUNTRY, M.POSITION_ID1, M.POSITION_ID2, M.NUM
            FROM members M
                 LEFT JOIN teams T ON M.TEAM_ID=T.TEAM_ID
            WHERE M.USER_ID IN ('.$ulist.')
            ORDER BY M.DATE_STARTED DESC';
    $db->query($sql);
    while ($row = $db->nextRow()) {
      $id = $row['USER_ID'];

      unset($td);
      $td['FROM'] = $row['DATE_STARTED'];      
      $td['TO'] = $row['DATE_EXPIRED'];      
      if (!empty($row['DATE_EXPIRED']))
        $td['PROLONG'][0]['ID'] = $row['ID'];
      $td['NUMBER'] = $row['NUM'];      
      $td['ID'] = $row['ID'];      
      // correct settings for each type of entity
      if ($row['TEAM_ID'] > 0) {
        $td['URL'] = 'team_edit.php?team_id='.$row['TEAM_ID'];
        $td['TITLE'] = truncateString($row['TEAM_NAME'], 30);
      }
      if ($row['POSITION_ID1'] > 0)
        $td['POSITION_ID1'] = $position_types[$users[$row['USER_ID']]['SPORT_ID']][$row['POSITION_ID1']];      
      if ($row['POSITION_ID2'] > 0)
        $td['POS2'][0]['POSITION_ID2'] = $position_types[$users[$row['USER_ID']]['SPORT_ID']][$row['POSITION_ID2']];      
      
      $users[$id]['BOUNDS'][] = $td;
    }
    $db->free();
    
    // build data
    $c = 0;
    while (list($key, $val) = each($users)) {
      $data['ITEMS'][$c]['LAST_NAME'] = $val['LAST_NAME'];
      $data['ITEMS'][$c]['FIRST_NAME'] = $val['FIRST_NAME'];
      $data['ITEMS'][$c]['NICKNAME'] = $val['NICKNAME'];
      $data['ITEMS'][$c]['ORIGINAL_NAME'] = $val['ORIGINAL_NAME'];
      if (isset($val['BOUNDS']))
        $data['ITEMS'][$c]['BOUNDS'] = $val['BOUNDS'];
      $data['ITEMS'][$c]['PUBLISH'] = $val['PUBLISH'];
      $data['ITEMS'][$c]['USER_ID'] = $val['USER_ID'];
      if ($c & 2 > 0)
        $data['ITEMS'][$c]['ODD'][0]['X'] = 1;
      else
        $data['ITEMS'][$c]['EVEN'][0]['X'] = 1;

      if (strcmp($_SESSION["_admin"][MENU_BASKET], 'FA') == 0)  
        $data['ITEMS'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $data['ITEMS'][$c]['USER_ID']);

      if ($data['ITEMS'][$c]['PUBLISH'] == 'Y')
        $data['ITEMS'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $data['ITEMS'][$c]['USER_ID']);
      else
        $data['ITEMS'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $data['ITEMS'][$c]['USER_ID']);
      
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
      if (isset($_GET['page']) && $page_tmp == $_GET['page']) {
        $data['PAGING'][0]['PAGES'][$page_tmp-1]['SELECTED'][0]['PAGENUM'] = $page_tmp;
      }
      else {
        $data['PAGING'][0]['PAGES'][$page_tmp-1]['NORMAL'][0]['PAGENUM'] = $page_tmp;
        $data['PAGING'][0]['PAGES'][$page_tmp-1]['NORMAL'][0]['URL'] = url('page', $page_tmp);
      }
    }
    
    // letter filtering
    $sql = 'SELECT SUBSTRING(LAST_NAME, 1, 1) LET
            FROM busers 
            GROUP BY SUBSTRING(LAST_NAME, 1, 1)
            ORDER BY SUBSTRING(LAST_NAME, 1, 1)';
    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
      if (isset($_GET["let"]) && $row['LET'] == $_GET["let"]) {
        $data['LET'][0]['LETTER'][$c]['SELECTED'][0]['TXT'] = $row['LET'];
      }
      else {
        $data['LET'][0]['LETTER'][$c]['NORMAL'][0]['TXT'] = $row['LET'];
        $data['LET'][0]['LETTER'][$c]['NORMAL'][0]['URL'] = $_SERVER['PHP_SELF'].'?let='.$row['LET'];
      }
      $c++;
    }
 }
 else {
      $data['NORECORDS'][0]['X'] = 1;
 }
$db->free();
// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/ppl.tpl.html');
$tpl->addData($data);

echo $tpl->parse();


// close connections
include('../class/db_close.inc.php');
?>