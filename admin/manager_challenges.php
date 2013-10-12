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
include('../class/manager.inc.php');
include('../class/manager_log.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$manager = new Manager($_GET['season_id']);

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------

if (!$ro && isset($_POST['remove_challenge']) && $_POST['remove_challenge'] == 'y' && $auth->userOn()) {
	$sql="SELECT * from manager_challenges
		WHERE CHALLENGE_ID=".$_POST['challenge_id']." 
			AND TYPE=2 
			AND STATUS=2";
	$db->query($sql);
	if ($row = $db->nextRow()) {
                $sdata['STATUS'] = 6;
		$db->update('manager_challenges', $sdata, "CHALLENGE_ID=".$_POST['challenge_id']);
		// unfreeze credits
		$credits = new Credits();
		$credits->unfreezeCredits($row['USER_ID'], $row['STAKE']);
		$credits = new Credits();
		$credits->unfreezeCredits($row['USER2_ID'], $row['STAKE']);
	}
}

// --- BEGIN SAVE -------------------------------------------------------------

if (!isset($_GET['season_id'])) 
 {
  header('Location: '.$_POST['referer']);
  exit;
 }

// build data
$data['SEASON_ID'] = $_GET['season_id'];
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);

// presets
if (!isset($_GET['page']))
  $_GET['page'] = 1;
if (!isset($perpage))
  $perpage = 40;
$order = 'USER_NAME1';
if (!empty($_GET['order']))
  $order = $_GET['order'];

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('USER_NAME1', 'USER_NAME2');
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
    'USER_NAME1' => 'LANG_USER_NAME_U',
    'USER_NAME2' => 'LANG_USER_NAME_U'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER['PHP_SELF'].url('season_id', $_GET['season_id'], array('order'));

$param['where'] = '';
if (!empty($_GET['where']) && $_GET['where'] != 'T.TEAM_NAME2') {
  $param['where'] = "AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%')";
  $data['FILTERED'][0]['X'] = 1;
}

$limitclause = "LIMIT ".(((isset($_GET['page']) ? $_GET['page'] : 1)-1)*$perpage).",".$perpage;


  $sql = "SELECT COUNT(*) ROWS
        FROM manager_challenges MC
        WHERE MC.STATUS=2
		AND MC.SEASON_ID=".$_GET['season_id']."
             ".$param['where'];

  $db->query($sql);
  while ($row = $db->nextRow()) {
    $count = $row['ROWS'];
  }
  $rows = $count;
  
  $sql = "SELECT DISTINCT MC.CHALLENGE_ID, U1.USER_NAME as USER_NAME1, 
		U2.USER_NAME as USER_NAME2, MC.STAKE, MC.TYPE
        FROM manager_challenges MC
             LEFT JOIN users U1 ON MC.USER_ID = U1.USER_ID
             LEFT JOIN users U2 ON MC.USER2_ID = U2.USER_ID
        WHERE MC.STATUS=2
		AND MC.SEASON_ID=".$_GET['season_id']."
             ".$param['where']."
        ORDER BY ".$param['order'];
  $db->query($sql);

  $c = 0;
  while ($row = $db->nextRow()) {
    $data['ITEMS'][$c] = $row;

    if ($c & 2 > 0)
      $data['ITEMS'][$c]['ODD'][0]['X'] = 1;
    else
      $data['ITEMS'][$c]['EVEN'][0]['X'] = 1;  

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

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/manager_challenges.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>