<?php
/*
===============================================================================
tot.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - shows a list of totalizators
  - deletes totalizators
  - activates/deactivates totalizators

TABLES USED:
  - BASKET.TOTLIZATORS
  - BASKET.TOTALIZATOR_VOTES
  - BASKET.GAMES

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/

// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/inputs.inc.php');
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
include('../class/session.inc.php');
include('../class/headers.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_WAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_WAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = false;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_WAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATES ----------------------------------------------------------
// delete
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_ACTIONS_WAGER], 'FA') == 0) {
  // delete totalizator votes
  $sql = "SELECT * 
		FROM wager_votes WV
		WHERE WV.WAGER_ID=".$_GET['del'];
  $db->query ( $sql );
  $players=array();
  while ( $row = $db->nextRow () ){
     $players[] = $row;
  }

  echo "Updating winnings: <br>";
  foreach ($players as $player) {
     unset($sdata);
     $sdata['MONEY'] = "MONEY+".($player['STAKE']);
     $sdata['STAKES'] = "STAKES-".$player['STAKE'];
     $db->update("wager_users", $sdata, "USER_ID=".$player['USER_ID']); 
  }

  $db->delete('wager_votes', "WAGER_ID=".$_GET['del']);
  
  // delete totalizator record itself
  $db->delete('wager_games', "WAGER_ID=".$_GET['del']);
}
// activate
if (isset($activate) && !$ro) {
  $db->update('totalizators', array('PUBLISH' => "'Y'"), "TOTALIZATOR_ID=$activate");
}
// deactivate
if (isset($deactivate) && !$ro) {
  $db->update('totalizators', array('PUBLISH' => "'N'"), "TOTALIZATOR_ID=$deactivate");
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
if (empty($_GET['order']))
  $order = 'END_DATE asc';
else $order = $_GET['order'];

// sorting
$param['order'] = $order;
$data['ORDER'] = $param['order'];
$so_fields = array('START_DATE', 'END_DATE', 'TEAM_NAME1', 'TEAM_NAME2', 
                   'VOTES', 'WINNERS', 'PUBLISH');
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
    'TEAM_NAME' => 'Komanda'
  )
);
$data['WHERE'] = $frm->getInput(FORM_INPUT_SELECT, 'where', isset($_GET['where']) ? $_GET['where'] : '', $opt, isset($_GET['where']) ? $_GET['where'] : '');
$data['QUERY'] = $frm->getInput(FORM_INPUT_TEXT, 'query', isset($_GET['query']) ? $_GET['query'] : '', array('class' => 'input'));
$data['FORM_URL'] = $_SERVER["PHP_SELF"].url('', '', array('order'));

$param['where'] = "";
// filtering by query
if (isset($_GET['where']) && $_GET['where'] == 'TEAM_NAME') {
  $param['where'] = "AND (UPPER(TEAM_NAME1) LIKE UPPER('%".$_GET['query']."%') OR UPPER(TEAM_NAME2) LIKE UPPER('%".$_GET['query']."%'))";
  $param['where'] = "AND (G.TEAM_ID1 IN 
                            (SELECT TEAM_ID FROM teams WHERE UPPER(TEAM_NAME) LIKE UPPER('%".$_GET['query']."%'))
                          OR G.TEAM_ID2 IN 
                            (SELECT TEAM_ID FROM teams WHERE UPPER(TEAM_NAME) LIKE UPPER('%".$_GET['query']."%'))
                         )";
  $data['FILTERED'][0]['X'] = 1;
}
elseif (!empty($_GET['where'])) {
  $param['where'] = "AND UPPER(".$_GET['where'].") like UPPER('%".$_GET['query']."%')";
  $data['FILTERED'][0]['X'] = 1;
}

 $sql_count = 'SELECT COUNT(T.WAGER_ID) ROWS
                 FROM wager_games T, wager_seasons TS, seasons S, games G
                WHERE T.GAME_ID=G.GAME_ID
	              AND T.WSEASON_ID=TS.SEASON_ID
		      AND G.SEASON_ID = S.SEASON_ID
		      AND T.WSEASON_ID='.$_GET['season_id'].'
                  '.$param['where']; 
echo $sql_count;
 $db->query($sql_count);
 while ($row = $db->nextRow()) {
   $count = $row['ROWS'];
  }

// get votes
$sql = "SELECT COUNT(*) VOTES, V.WAGER_ID 
        FROM wager_votes V 
        GROUP BY V.WAGER_ID";

$db->query($sql);
while ($row = $db->nextRow()) {
  $votes[$row['WAGER_ID']] = $row['VOTES'];
}
$db->free();

// get winners
$sql = "SELECT COUNT(*) WINNERS, V.WAGER_ID 
        FROM wager_votes V 
        WHERE V.WON='Y' GROUP BY V.WAGER_ID";

$db->query($sql);
while ($row = $db->nextRow()) {
  $winners[$row['WAGER_ID']] = $row['WINNERS'];
}
$db->free();

 $limitclause = "LIMIT ".(($page-1)*$perpage).",".$perpage;

// get totalizators list
$sql = "SELECT T.WAGER_ID, T.GAME_ID, T.PUBLISH, TSD.TSEASON_TITLE, 
          T.START_DATE, T.PROCESSED,
          DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) END_DATE, 
	  if (DATE_ADD(G.START_DATE, INTERVAL -1 HOUR) < NOW(), 1, 0) GAME_OVER,
          G.TEAM_ID1, G.TEAM_ID2, G.SCORE1, G.SCORE2,
          T1.TEAM_NAME TEAM_NAME1, T2.TEAM_NAME TEAM_NAME2,
          SD.SEASON_TITLE
        FROM
          wager_games T, wager_seasons TS
		left JOIN wager_seasons_details TSD ON TS.SEASON_ID = TSD.SEASON_ID  AND TSD.LANG_ID=".$_SESSION['lang_id']."
		, seasons S
		left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id']."
		, games G
              left join teams T1 on T1.TEAM_ID=G.TEAM_ID1
              left join teams T2 on T2.TEAM_ID=G.TEAM_ID2
        WHERE
          T.GAME_ID=G.GAME_ID
          AND G.SEASON_ID = S.SEASON_ID
          AND T.WSEASON_ID=TS.SEASON_ID
          AND T.WSEASON_ID=".$_GET['season_id']."
          ".$param['where']."
        ORDER BY ".$order." ".$limitclause;
echo $sql;
$db->query($sql);
//$db->setPage($page, $perpage);
$rows = $count;

$c = 0;
while ($row = $db->nextRow()) {
 // if ($row['CONFIRMATION_SENT'] == 'Y')
//    $row['WINNERS']="+++";
  $data['ITEM'][$c] = $row;
  //$data['ITEM'][$c]['VOTES'] = $votes[$row['TOTALIZATOR_ID']];
  //$data['ITEM'][$c]['WINNERS'] = $winners[$row['TOTALIZATOR_ID']];
  if ($row['SCORE1'] > -1 && $row['SCORE2'] > -1 && $row['GAME_OVER']) {
    $data['ITEM'][$c]['CAN_UPDATE'][0]['GAME_ID'] = $row['GAME_ID'];
    if ($row['PROCESSED'] == 1)   
      $data['ITEM'][$c]['CAN_UNDO'][0]['WAGER_ID'] = $row['WAGER_ID'];
  }

  if ($c & 2 > 0)
    $data['ITEM'][$c]['ODD'][0]['X'] = 1;
  else
    $data['ITEM'][$c]['EVEN'][0]['X'] = 1;

  if (strcmp($_SESSION["_admin"][MENU_ACTIONS], 'FA') == 0 
	&& $row['PROCESSED'] == 0)  
    $data['ITEM'][$c]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['WAGER_ID']);
  
  if ($row['PUBLISH'] == 'Y')
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['WAGER_ID']);
  else
    $data['ITEM'][$c]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['WAGER_ID']);

  $c++;
}
$db->free();

if ($rows == 0) {
  $data['NORECORDS'][0]['X'] = 1;
}

$data['SEASON_ID'] = $_GET['season_id'];
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
$tpl->setTemplateFile('../tpl/adm/wager_games.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>