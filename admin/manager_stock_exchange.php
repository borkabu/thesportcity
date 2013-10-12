<?php
/*
===============================================================================
season.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of tournament seasons
  - deletes tournament seasons

TABLES USED: 
  - BASKET.SEASONS
  - BASKET.TOURNAMENTS

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

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------

// --- END UPDATE -------------------------------------------------------------

// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));

// presets
if (!isset($_GET['page']))
  $_GET['page'] = 1;
if (!isset($perpage))
  $perpage = $page_size;
if (empty($order))
  $order = 'START_DATE desc';

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('START_DATE', 'END_DATE', 'SEASON_TITLE', 'PUBLISH');
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
    'SEASON_TITLE' => 'Pavadinimas'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER['PHP_SELF'].url('', '', array('order'));

$param['where'] = '';
if (!empty($where)) {
  $param['where'] = "UPPER($where) like UPPER('%$query%')";
  $data['FILTERED'][0]['X'] = 1;
}

    $sql = "SELECT DISTINCT U.USER_NAME, MT.ENTRY_ID, MU.MONEY_STOCK, MT.PLAYER_ID, B.LAST_NAME, B.FIRST_NAME, 
                MM.USER_ID, MM.POSITION_ID1, MM.POSITION_ID2, IF(T.TEAM_TYPE = 1, T.TEAM_NAME2, CD.COUNTRY_NAME) TEAM_NAME2,
                SUBSTRING( MT.BUYING_DATE, 1, 16 ) BUYING_DATE, 
                MT.BUYING_PRICE, MM.CURRENT_VALUE_MONEY, MMS.TEAMS, MMS.SHARES,
                MM.START_VALUE, MT.SIZE, MMS.PENALTY, MT.FINE
		FROM  users U, manager_users MU, manager_stock_exchange MT
		LEFT JOIN manager_market MM ON MM.USER_ID = MT.PLAYER_ID AND MM.SEASON_ID =".$_GET['season_id']." and MM.PUBLISH='Y'    
		LEFT JOIN teams T ON MM.TEAM_ID=T.TEAM_ID 
		LEFT JOIN countries_details CD ON CD.ID=T.COUNTRY AND T.TEAM_TYPE=2 AND CD.LANG_ID=".$_SESSION['lang_id']."
		LEFT JOIN busers B ON B.USER_ID = MT.PLAYER_ID
		LEFT JOIN manager_market_stats MMS ON MM.USER_ID = MMS.PLAYER_ID AND MMS.SEASON_ID=".$_GET['season_id']."
            WHERE 
	      MU.SEASON_ID=".$_GET['season_id']."
             AND MT.USER_ID=MU.USER_ID
             AND U.USER_ID=MU.USER_ID
             AND MT.SIZE>0
             AND (MMS.PENALTY>0 OR MT.FINE > 0)
 	     AND MT.SEASON_ID=".$_GET['season_id']."
		ORDER BY MMS.PENALTY DESC, MT.SIZE DESC";
   $db->query($sql);

$rows = $db->rows();

$c = 0;
while ($row = $db->nextRow()) {
  $data['ITEM'][$c] = $row;
  $data['ITEM'][$c]['SELLING_PRICE'] = 1 + $row['TEAMS']/10 + $row['SHARES']/1000 - 0.8 + round($row['CURRENT_VALUE_MONEY']/50000, 3) - $row['PENALTY']/10;
  if ($data['ITEM'][$c]['SELLING_PRICE'] > 
       $data['ITEM'][$c]['BUYING_PRICE'])
     $data['ITEM'][$c]['UP'][0]['X'] = 0;
  else if ($data['ITEM'][$c]['SELLING_PRICE'] < 
           $data['ITEM'][$c]['BUYING_PRICE'])
         $data['ITEM'][$c]['DOWN'][0]['X'] = 0;

  $data['ITEM'][$c]['PROFIT'] = ($data['ITEM'][$c]['SELLING_PRICE'] - $data['ITEM'][$c]['BUYING_PRICE']) * $row['SIZE'];

  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;
  $c++;
}
$db->free();

    $sql = "SELECT U.USER_NAME, MM.LAST_NAME, MM.FIRST_NAME, 
		   SUM(if(event_type=7, VALUE2, 0)) - SUM(if(event_type=6, VALUE2, 0)) PROFIT		   
		FROM users U, manager_users_log MUL
		     LEFT JOIN manager_market MM ON MM.USER_ID = MUL.PLAYER_ID AND MM.SEASON_ID =".$_GET['season_id']." and MM.PUBLISH='Y'     
		WHERE U.USER_ID=MUL.USER_ID
			AND MUL.EVENT_TYPE IN (6, 7)
			AND MUL.SEASON_ID=".$_GET['season_id']."
		GROUP BY U.USER_NAME, MUL.PLAYER_ID, MM.LAST_NAME, MM.FIRST_NAME
		HAVING PROFIT > 0
		ORDER BY PROFIT DESC";

   $db->query($sql);

   $c = 0;
   while ($row = $db->nextRow()) {
     $data['PROFITS'][$c] = $row;
     $c++;
   }


if ($c == 0) {
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
$tpl->setTemplateFile('../tpl/adm/manager_stock_exchange.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>