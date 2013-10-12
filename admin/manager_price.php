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
include('../class/box.inc.php');
include('../class/managerbox.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$manager = new Manager($_GET['season_id']);
$managerbox = new ManagerBox($langs, $_SESSION["_lang"]);

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// get seasons

    $db->select('manager_subseasons', 'SEASON_ID', 'MSEASON_ID='.$_GET['season_id']);
    $c = 0;
    $ulist = '';
    $pre = '';
    while ($row = $db->nextRow()) {
      $ulist .= $pre.$row['SEASON_ID'];
      $pre = ',';
      $c++;
    }
    $db->free();
// --- BEGIN UPDATE -----------------------------------------------------------

if (isset($_GET['del']) && !$ro && strcmp($_admin[MENU_ACTIONS_MANAGER], 'FA') == 0) {
   $db->delete('manager_market', 'USER_ID='.$_GET['del'].' AND SEASON_ID='.$_GET['season_id']); 
}

// activate
if (isset($_GET['injured']) && !$ro) {
  $manager->setHealth($_GET['injured'], true);
}
// deactivate
if (isset($_GET['healthy']) && !$ro) {
  $manager->setHealth($_GET['healthy'], false);
}

$db->showquery=true;
// activate
if (isset($_POST['synchronise']) && !$ro) {
$db->showquery=true;
    $sql = "SELECT DISTINCT MM.TEAM_ID, MM.TEAM_NAME2
        FROM manager_market MM 
        WHERE MM.SEASON_ID=".$_GET['season_id']."
             AND MM.USER_ID = ".$_POST['player_id'];
    $db->query($sql);
    $rowold = $db->nextRow();
    // get new team

    $sql ="SELECT T.TEAM_NAME2, T.TEAM_ID, M.POSITION_ID1, M.POSITION_ID2
                FROM team_seasons TS, teams T, seasons S, members M
                WHERE TS.SEASON_ID  IN (".$ulist.") 
                     AND S.SEASON_ID = TS.SEASON_ID 
                     AND M.TEAM_ID = TS.TEAM_ID 
                     AND M.USER_ID = ".$_POST['player_id']."
                     AND T.TEAM_ID = TS.TEAM_ID 
                     AND ((M.DATE_STARTED >= S.START_DATE AND M.DATE_STARTED <= S.END_DATE) 
                          OR (M.DATE_EXPIRED >= S.START_DATE AND M.DATE_EXPIRED <= S.END_DATE)  
                          OR (M.DATE_STARTED < S.START_DATE 
                             AND (M.DATE_EXPIRED > S.END_DATE  OR M.DATE_EXPIRED IS NULL) )
                         )         
		ORDER BY M.DATE_STARTED DESC LIMIT 1";

    $db->query($sql);
    $rownew = $db->nextRow();
    // perform synchronisation
    unset($sdata);
    $sdata['TEAM_ID'] = $rownew['TEAM_ID'];
    $sdata['POSITION_ID1'] = $rownew['POSITION_ID1'];
    $sdata['POSITION_ID2'] = empty($rownew['POSITION_ID2']) ? "NULL" : $rownew['POSITION_ID2'];
    $sdata['TEAM_NAME2'] = "'".$rownew['TEAM_NAME2']."'";
    $db->update('manager_market', $sdata, "USER_ID = ".$_POST['player_id']." AND SEASON_ID=".$_GET['season_id']);
    
    if ($rowold['TEAM_ID'] != $rownew['TEAM_ID']) {
      $manager_log = new ManagerLog();
      $manager_log->logEvent($_POST['player_id'], 5, 0, $_GET['season_id'], $rowold['TEAM_ID'], $rownew['TEAM_ID']);
    }
}

if (isset($_POST['set_price']) && !$ro) {
  if (!isset($_GET['season_id']))
  {
    header('Location: manager_season.php');
    exit;
  }

  foreach ($_POST as $key => $value) {
    if (strpos($key, 'player_') !== false && !empty($value)) {
      echo substr($key, strpos($key, '_') + 1).":".$value."<br>";
      $player_id = substr($key, strpos($key, '_') + 1);
      
      $manager->setPrice($player_id, $value);
    }
  }
 
}


if (isset($_POST['extend_expiry_date']) && !$ro) {
  if (!isset($_GET['season_id']))
  {
    header('Location: manager_season.php');
    exit;
  }

  $sql = "SELECT DISTINCT M.ID
           FROM team_seasons TS, teams T, seasons S, manager_seasons MSS, members M 
             LEFT JOIN busers U ON U.USER_ID = M.USER_ID 
           WHERE TS.SEASON_ID IN (".$ulist.")
             AND S.SEASON_ID = TS.SEASON_ID 
 	     AND MSS.SEASON_ID=".$_GET['season_id']."
             AND M.TEAM_ID = TS.TEAM_ID 
             AND T.TEAM_ID = TS.TEAM_ID 
             AND ((M.DATE_STARTED >= MSS.START_DATE AND M.DATE_STARTED <= MSS.END_DATE) 
                OR (M.DATE_EXPIRED >= MSS.START_DATE AND M.DATE_EXPIRED  <= MSS.END_DATE)  
                OR (M.DATE_STARTED <= MSS.START_DATE 
                 AND (M.DATE_EXPIRED >= MSS.END_DATE OR M.DATE_EXPIRED IS NULL))
               ) 
             AND M.DATE_EXPIRED > NOW() AND M.DATE_EXPIRED < S.END_DATE";

  $db->query($sql);
  $ids = "";
  $pre = "";
  while ($row = $db->nextRow()) {
    $ids .= $pre.$row['ID'];
    $pre = ",";
  }


  $sql = "UPDATE members SET DATE_EXPIRED = '".date('Y', strtotime('+1 year'))."-08-01' 
	   WHERE ID IN (".$ids.")";

  $db->query($sql);
 
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
$order = 'TEAM_NAME2, LAST_NAME';
if (!empty($_GET['order']))
  $order = $_GET['order'];

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('LAST_NAME', 'TEAM_NAME2');
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
    'U.LAST_NAME' => 'LANG_SURNAME_U',
    'U.FIRST_NAME' => 'LANG_NAME_U',
    'T.TEAM_NAME2' => 'LANG_TEAM_NAME_U'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER['PHP_SELF'].url('season_id', $_GET['season_id'], array('order'));

$param['where'] = '';
// filtering by letter
if (!empty($_GET['let'])) {
  $param['where'] = "AND UPPER(U.LAST_NAME) like UPPER('".$_GET['let']."%')";
  $data['FILTERED'][0]['X'] = 1;
}
// filtering by query
elseif (!empty($_GET['where']) && $_GET['where'] != 'T.TEAM_NAME2') {
  $param['where'] = "AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%')";
  $data['FILTERED'][0]['X'] = 1;
}
elseif (!empty($_GET['where']) && $_GET['where'] == 'T.TEAM_NAME2') {
  $param['where'] = "AND ((T.TEAM_TYPE=1 AND UPPER(T.TEAM_NAME2) like UPPER('%".$_GET['query']."%')) OR (T.TEAM_TYPE=2 AND UPPER(CD.COUNTRY_NAME) like UPPER('%".$_GET['query']."%')))";
  $data['FILTERED'][0]['X'] = 1;
}

$param['injury_list'] = '';
if (isset($_GET['injury_list'])) {
  $param['injury_list'] = " AND MM.INJURY='Y'";
}

$limitclause = "LIMIT ".(((isset($_GET['page']) ? $_GET['page'] : 1)-1)*$perpage).",".$perpage;


  $sql = "SELECT COUNT(DISTINCT M.USER_ID) ROWS
        FROM team_seasons TS, teams T
		     LEFT JOIN countries_details CD on CD.ID=T.COUNTRY AND CD.LANG_ID=".$_SESSION['lang_id'].",
	    seasons S, manager_seasons MSS, members M 
             LEFT JOIN manager_market MM ON MM.USER_ID = M.USER_ID AND MM.SEASON_ID=".$_GET['season_id']." 
             LEFT JOIN busers U ON M.USER_ID = U.USER_ID
             LEFT JOIN manager_players MP ON MP.PLAYER_ID = U.USER_ID AND MP.SEASON_ID=".$_GET['season_id']."
        WHERE TS.SEASON_ID IN (".$ulist.")
             ".$param['where'].$param['injury_list']."
             AND S.SEASON_ID = TS.SEASON_ID 
 	     AND MSS.SEASON_ID=".$_GET['season_id']."
             AND M.TEAM_ID = TS.TEAM_ID 
             AND T.TEAM_ID = TS.TEAM_ID 
           AND ((M.DATE_STARTED >= MSS.START_DATE AND M.DATE_STARTED <= MSS.END_DATE) 
                OR (M.DATE_EXPIRED >= MSS.START_DATE AND M.DATE_EXPIRED  <= MSS.END_DATE)  
                OR (M.DATE_STARTED <= MSS.START_DATE 
                 AND (M.DATE_EXPIRED >= MSS.END_DATE OR M.DATE_EXPIRED IS NULL))
               ) 
            AND (M.DATE_EXPIRED IS NULL OR M.DATE_EXPIRED > NOW())";

  $db->query($sql);
  while ($row = $db->nextRow()) {
    $count = $row['ROWS'];
  }
  $rows = $count;
  
  $suggest_price_sql ='';
  $suggest_price_sql2 ='';
  if (isset($_POST['suggest_price'])) {
    $suggest_price_sql = "LEFT JOIN manager_market MM2 ON MM2.season_id=".$_POST['ref_season_id']." AND MM2.USER_ID=M.USER_ID";
    $suggest_price_sql2 = ", (MM2.CURRENT_VALUE_MONEY/1000 - 1) SUGGESTED_VALUE";
  }

  $sql = "SELECT DISTINCT M.USER_ID, M.NUM, M.POSITION_ID1, 1 as TYPE, T.TEAM_TYPE,
               M.POSITION_ID2, M.USER_TYPE, U.FIRST_NAME, MSS.SPORT_ID,
               U.LAST_NAME, MM.MALE, T.TEAM_ID, IF(T.TEAM_TYPE=1, T.TEAM_NAME2, CD.COUNTRY_NAME) as TEAM_NAME2, MP.START_VALUE, MP.START_VALUE as START_VALUE_MONEY,
               M.DATE_EXPIRED, S.END_DATE, MM.CURRENT_VALUE_MONEY, MP.PUBLISH, MM.INJURY, MM.PLAYER_STATE, MM.TEAM_NAME2 AS TEAM_NAME_MARKET, MM.TEAM_ID AS TEAM_ID_MARKET,
	       MM.POSITION_ID1 as POSITION1_MARKET, MM.POSITION_ID2 as POSITION2_MARKET ".$suggest_price_sql2."
        FROM team_seasons TS, teams T
	     LEFT JOIN countries_details CD on CD.ID=T.COUNTRY AND CD.LANG_ID=".$_SESSION['lang_id'].",
		 seasons S, manager_seasons MSS, members M 
             LEFT JOIN manager_market MM ON MM.USER_ID = M.USER_ID AND MM.SEASON_ID=".$_GET['season_id']." 
		".$suggest_price_sql."
             LEFT JOIN manager_players MP ON MP.PLAYER_ID = MM.USER_ID AND MP.SEASON_ID=".$_GET['season_id']." 
             LEFT JOIN busers U ON U.USER_ID = M.USER_ID 
        WHERE TS.SEASON_ID IN (".$ulist.")
             ".$param['where'].$param['injury_list']."
             AND S.SEASON_ID = TS.SEASON_ID 
 	     AND MSS.SEASON_ID=".$_GET['season_id']."
             AND M.TEAM_ID = TS.TEAM_ID 
             AND T.TEAM_ID = TS.TEAM_ID 
           AND ((M.DATE_STARTED >= MSS.START_DATE AND M.DATE_STARTED <= MSS.END_DATE) 
                OR (M.DATE_EXPIRED >= MSS.START_DATE AND M.DATE_EXPIRED  <= MSS.END_DATE)  
                OR (M.DATE_STARTED <= MSS.START_DATE 
                 AND (M.DATE_EXPIRED >= MSS.END_DATE OR M.DATE_EXPIRED IS NULL))
               ) 
            AND (M.DATE_EXPIRED IS NULL OR M.DATE_EXPIRED > NOW())
 
        GROUP BY M.USER_ID  
        ORDER BY ".$param['order']."
        ".$limitclause;

echo $sql;
  $db->query($sql);

  $c = 0;
  $t = 0;
  $data['START_VALUE_SUM'] = 0;
  $data['CURRENT_VALUE_SUM'] = 0;
  while ($row = $db->nextRow()) {
    $data['ITEMS'][$c] = $row;
    $data['ITEMS'][$c]['SEASON_ID'] = $_GET['season_id'];
    if (!empty($row['POSITION_ID2'])) {
      $data['ITEMS'][$c]['TYPE_NAME'] = $position_types[$row['SPORT_ID']][$row['POSITION_ID1']]."/".$position_types[$row['SPORT_ID']][$row['POSITION_ID2']];
    }
    else if (!empty($row['POSITION_ID1'])) {
      $data['ITEMS'][$c]['TYPE_NAME'] = $position_types[$row['SPORT_ID']][$row['POSITION_ID1']];
    }

    if (($row['TEAM_ID'] != $row['TEAM_ID_MARKET'] ||
	 $row['POSITION_ID1'] != $row['POSITION1_MARKET'] || 
	 ($row['POSITION_ID2'] != $row['POSITION2_MARKET'] )) 
	&& $row['PUBLISH'] != '') {
echo $row['POSITION_ID2']." ".$row['POSITION2_MARKET']."<br>";
	$data['ITEMS'][$c]['SYNCHRONISE'][0]['PLAYER_ID'] = $row['USER_ID'];
    }

    if (empty($row['START_VALUE'])) {
      $data['ITEMS'][$c]['NO_VALUE'][0]['USER_ID'] = $row['USER_ID'];
      if (isset($row['SUGGESTED_VALUE'])) {
        if ($row['SUGGESTED_VALUE'] > 0)
          $data['ITEMS'][$c]['NO_VALUE'][0]['SUGGESTED_VALUE'] = $row['SUGGESTED_VALUE'];
        else if ($row['SUGGESTED_VALUE'] < 0) 
               $data['ITEMS'][$c]['NO_VALUE'][0]['SUGGESTED_VALUE'] = 1;
      }
    }

    if ($c & 2 > 0)
      $data['ITEMS'][$c]['ODD'][0]['X'] = 1;
    else
      $data['ITEMS'][$c]['EVEN'][0]['X'] = 1;  

    if ($row['PUBLISH'] == 'Y') {
      $data['ITEMS'][$c]['ACTIVATED'][0]['TYPE'] = 'manager_price';
      $data['ITEMS'][$c]['ACTIVATED'][0]['USER_ID'] = $row['USER_ID'];
      $data['ITEMS'][$c]['ACTIVATED'][0]['SEASON_ID'] = $_GET['season_id'];
    }
    else {
      $data['ITEMS'][$c]['DEACTIVATED'][0]['TYPE'] = 'manager_price';
      $data['ITEMS'][$c]['DEACTIVATED'][0]['USER_ID'] = $row['USER_ID'];
      $data['ITEMS'][$c]['DEACTIVATED'][0]['SEASON_ID'] = $_GET['season_id'];
    }

    if ($row['INJURY'] == 'Y')
      $data['ITEMS'][$c]['INJURY_URL'] = $_SERVER['PHP_SELF'].url('healthy', $row['USER_ID']);
    else
      $data['ITEMS'][$c]['INJURY_URL'] = $_SERVER['PHP_SELF'].url('injured', $row['USER_ID']);

    $data['ITEMS'][$c]['PLAYER_STATE_DIV'] = $managerbox->getPlayerStateDiv($row['USER_ID'], $_GET['season_id'], $row['PLAYER_STATE'], true);

    if ($row['START_VALUE'] > 0) {
      $data['START_VALUE_SUM'] += $row['START_VALUE'];
      $data['CURRENT_VALUE_SUM'] += $row['CURRENT_VALUE_MONEY']/1000 - 1;
      $t++;
    }
    $c++;
  }
  $db->free();
  if ($t > 0) {
    $data['START_VALUE_SUM'] = $data['START_VALUE_SUM']/$t;
    $data['CURRENT_VALUE_SUM'] = $data['CURRENT_VALUE_SUM']/$t;
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
    $sql = 'SELECT SUBSTRING(U.LAST_NAME, 1, 1) LET
            FROM busers U 
            GROUP BY SUBSTRING(LAST_NAME, 1, 1)
            ORDER BY SUBSTRING(LAST_NAME, 1, 1)';
    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
      if (isset($_GET['let']) && $row['LET'] == $_GET['let']) {
        $data['LET'][0]['LETTER'][$c]['SELECTED'][0]['TXT'] = $row['LET'];
      }
      else {
        $data['LET'][0]['LETTER'][$c]['NORMAL'][0]['TXT'] = $row['LET'];
        $data['LET'][0]['LETTER'][$c]['NORMAL'][0]['URL'] = $_SERVER['PHP_SELF'].url('let', $row['LET']);
      }
      $c++;
    }

  $data['REF_SEASON_ID'] = inputManagerSeasons('ref_season_id', isset($_POST['ref_season_id']) ? $_POST['ref_season_id'] : '', 80, true);
// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/manager_price.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>