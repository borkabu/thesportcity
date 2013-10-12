<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
res_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit game records
  - edit keywords
  - create new game record

TABLES USED: 
  - BASKET.GAMES
  - BASKET.RESULTS
  - BASKET.MEMBERS
  - BASKET.USERS
  - BASKET.TEAMS

STATUS:
  - [STAT:FNCTNL] functional

TODO:
  - [TODO:ADMVAR] SMS result sending
===============================================================================
*/

// includes
include('../class/conf.inc.php');
include('../class/func.inc.php');
include('../class/adm_menu.php');
include('../class/update.inc.php');
 
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
include('../class/managerbox.inc.php');

if (empty($_SESSION["_admin"][MENU_GAMES_RESULTS]) || strcmp($_SESSION["_admin"][MENU_GAMES_RESULTS], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_GAMES_RESULTS], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;

$sql = "SELECT S.SPORT_ID FROM games G, seasons S WHERE G.SEASON_ID=S.SEASON_ID AND G.GAME_ID=".$_GET['game_id'];
$db->query($sql);
$row = $db->nextRow();
$sport_id=$row['SPORT_ID'];
$data['SPORT_ID'] = $sport_id;
//$db->showquery=true;
if(isset($_POST['form_save']) && !$ro){
  // update fields
  $s_fields = '';
  $i_fields = array('score1', 'score2');
  $d_fields = '';
  $c_fields = array('publish');
  
  // required fields
  $r_fields = '';
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    if (!empty($_GET['game_id'])) {
      // update RESULTS
      $db->delete('results', "GAME_ID=".$_GET['game_id']);
      $uf = $result_parameters[$sport_id];
      for ($c = 0; $c < sizeof($_POST['team_id']); $c++) {
        reset($uf);
        unset($sdata);
        $upd = FALSE;
	$result_type = array_keys($uf);
	foreach($result_type as $rt) {
          $key = strtolower($rt);
          $val = $_POST[$key.'_'.$_POST['team_id'][$c].'-'.$_POST['user_id'][$c]];
          if (strlen($val) > 0) {
            $sdata[$key] = $val;
            $upd = TRUE;
          }
        }
        $sdata['TEAM_ID'] = $_POST['team_id'][$c];
        $sdata['GAME_ID'] = $_GET['game_id'];
        if (!empty($_POST['user_id'][$c])) {
          $sdata['USER_ID'] = $_POST['user_id'][$c];
        }
        else {
         // update team scores for saving into GAMES table
          if ($_POST['team_id'][$c] == $_POST['team_id1'])
            $_POST['score1'] = $sdata['score'];
          else
            $_POST['score2'] = $sdata['score'];
        }
        if ($upd) {
          $db->insert('results', $sdata);
        }       
      }
    
      if (isset($_POST['goals']) && trim($_POST['goals']) != '') {
          $db->delete('goals', "GAME_ID=".$_GET['game_id']);
          $events = explode("\n", $_POST['goals']);
	  $length= count($events); 
	  for ($i=0; $i<$length; $i++) {
             if (trim($events[$i]) != "") {
  	       $fields=explode(",", $events[$i]);
               unset($sdata);
	       $sdata['MIN'] = trim($fields[0]);
	       $sdata['TEAM_ID'] = trim($fields[1]);
	       $sdata['USER_ID'] = trim($fields[2]);
	       $sdata['EVENT_TYPE'] = trim($fields[3]);
	       $sdata['GAME_ID'] = $_GET['game_id'];
               $db->insert('goals', $sdata);
             }
          }
      }

    // check for score fields
      if (empty($_POST['score1']) && empty($_POST['score2'])) {
        $error = TRUE;
        $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
      }
    }
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
    
    $sdata['CUSER_ID']=$_SESSION['_user']['USER_ID'];
    // proceed to database updates
    if (!empty($_GET['game_id']) && $_POST['score1'] != '' && $_POST['score2'] != '') {
      // UPDATE
      $db->update('games', $sdata, "GAME_ID=".$_GET['game_id']);
      // update wagers for this game 
      header('Location: wager_update.php?update=true&game_id='.$_GET['game_id']);                 
    }

//echo $_POST['referer'];
//    header('Location: '.$_POST['referer']);             
    exit;
 }
}
// --- END SAVE ---------------------------------------------------------------


// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);

// new or edit?
if (isset($_GET['game_id'])) {
  // edit
  $sql = "SELECT G.GAME_ID, G.PUBLISH, G.SCORE1, G.SCORE2,
		G.TEAM_ID1, G.TEAM_ID2, SUBSTRING(G.START_DATE, 1, 16) START_DATE,
            T1.TEAM_NAME as TEAM_NAME1, T2.TEAM_NAME as TEAM_NAME2, SD.SEASON_ID,
            SD.SEASON_TITLE, S.SPORT_ID
          FROM  seasons S
	          left JOIN seasons_details SD ON S.SEASON_ID = SD.SEASON_ID AND SD.LANG_ID=".$_SESSION['lang_id'].",
		games G
               LEFT JOIN teams T1 ON T1.TEAM_ID=G.TEAM_ID1
               LEFT JOIN teams T2 ON T2.TEAM_ID=G.TEAM_ID2
          WHERE G.GAME_ID=".$_GET['game_id']."
            AND G.SEASON_ID=S.SEASON_ID";
  $db->query($sql);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
    header('Location: res.php');
    exit;
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
      $data[$key] = $val;
    }
  }
  $db->free();

  // prepare parameters
  $result_type = array_keys($result_parameters[$PRESET_VARS['sport_id']]);
  $result_parameter = '';
  $pre = "";
  foreach($result_type as $rt) {
	$result_parameter .= $pre."R.".$rt;
	$pre = ",";
  }

  // generate team stats  

  $sql = 'SELECT T.TEAM_ID M_TEAM_ID, T.TEAM_NAME, '.$result_parameter.'
        FROM teams T
            LEFT JOIN results R ON R.GAME_ID='.$_GET['game_id'].' AND R.TEAM_ID=T.TEAM_ID AND R.USER_ID = 0
        WHERE T.TEAM_ID IN ('.$data['TEAM_ID1'].','.$data['TEAM_ID2'].') 
        ORDER BY T.TEAM_ID';

  $db->query($sql);
  $result_name = $result_parameters[$PRESET_VARS['sport_id']];
  while ($row = $db->nextRow()) {
    $data['TEAM'][$row['M_TEAM_ID']] = $row;
    $c = 0;
    foreach($result_name as $rt) {
	$data['TEAM'][$row['M_TEAM_ID']]['HEADER'][$c]['HEADER'] = $rt;
	$c++;
    }
    $t = 0;
    foreach($result_type as $rt) {
	$data['TEAM'][$row['M_TEAM_ID']]['STATS'][$t]['VALUE'] = $row[$rt];
	$data['TEAM'][$row['M_TEAM_ID']]['STATS'][$t]['M_TEAM_ID'] = $row['M_TEAM_ID'];
	$data['TEAM'][$row['M_TEAM_ID']]['STATS'][$t]['STATS'] = strtolower($rt);
	$t++;
    }

  }
  $db->free();

  $sql = "SELECT MS.SEASON_ID 
		FROM manager_subseasons MSS, manager_seasons MS 
	  WHERE MSS.SEASON_ID=".$data['SEASON_ID']."
		AND MSS.MSEASON_ID=MS.SEASON_ID
		AND MS.PUBLISH='Y'
		and MS.START_DATE < NOW() 
		AND MS.END_DATE > NOW() ";
  $db->query($sql);
  $mseasons = '';
  $pre = '';
  while ($row = $db->nextRow()) {
    $mseasons .= $pre.$row['SEASON_ID'];
    $pre = ',';
  }

  $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);
  // generate teams with stats
  $sql = "SELECT DISTINCT M.ID, M.USER_ID M_USER_ID, M.TEAM_ID M_TEAM_ID, M.NUM, 
               U.FIRST_NAME, U.LAST_NAME, T.TEAM_NAME, R.*, MK.INJURY, MK.PLAYER_STATE, MK.SEASON_ID
	        FROM busers U, teams T, games G, members M
        	    LEFT JOIN results R ON M.USER_ID=R.USER_ID AND R.GAME_ID =".$_GET['game_id']."
  		    left JOIN manager_market MK on MK.SEASON_ID in (".$mseasons.") AND M.USER_ID=MK.USER_ID

	        WHERE M.TEAM_ID IN (".$data['TEAM_ID1'].",".$data['TEAM_ID2'].") 
        	  AND G.GAME_ID=".$_GET['game_id']."
        	  AND M.USER_ID=U.USER_ID
	          AND M.TEAM_ID=T.TEAM_ID
        	  AND M.DATE_STARTED <= G.START_DATE 
	          AND (M.DATE_EXPIRED >= G.START_DATE OR M.DATE_EXPIRED IS NULL)
	        ORDER BY M.TEAM_ID, M.NUM+0";
  $db->query($sql);
//echo $sql;
  $curteam = 0;
  $c = 0;
  while ($row = $db->nextRow()) {
    $data['TEAM'][$row['M_TEAM_ID']]['ROW'][$c] = $row;
//echo $row['PLAYER_STATE'];
    $data['TEAM'][$row['M_TEAM_ID']]['ROW'][$c]['PLAYER_STATE_DIV'] = $managerbox->getPlayerStateDiv($row['M_USER_ID'], $row['SEASON_ID'], $row['PLAYER_STATE'], true);
    if ($c & 2 > 0)
      $data['TEAM'][$row['M_TEAM_ID']]['ROW'][$c]['ODD'][0]['X'] = 1;
    else
      $data['TEAM'][$row['M_TEAM_ID']]['ROW'][$c]['EVEN'][0]['X'] = 1;
    $t = 0;
    foreach($result_type as $rt) {
	$data['TEAM'][$row['M_TEAM_ID']]['ROW'][$c]['STATS'][$t]['VALUE'] = $row[$rt];
	$data['TEAM'][$row['M_TEAM_ID']]['ROW'][$c]['STATS'][$t]['M_TEAM_ID'] = $row['M_TEAM_ID'];
	$data['TEAM'][$row['M_TEAM_ID']]['ROW'][$c]['STATS'][$t]['M_USER_ID'] = $row['M_USER_ID'];
	$data['TEAM'][$row['M_TEAM_ID']]['ROW'][$c]['STATS'][$t]['STATS'] = strtolower($rt);
	$t++;
    }

    $c++;
  }
  $db->free();

  if ($PRESET_VARS['sport_id'] == 2) {
    $data['GOALS'][0]['X'] = 1;

    // extract goals
    $sql="SELECT G.MIN, B.LAST_NAME, B.FIRST_NAME, T.TEAM_NAME, G.EVENT_TYPE FROM 
		goals G, busers B, teams T
	  WHERE G.USER_ID=B.USER_ID
		AND G.TEAM_ID=T.TEAM_ID
		and G.GAME_ID=".$_GET['game_id'];

    $db->query($sql);
    $c = 0;
    while ($row = $db->nextRow()) {
       $data['EVENTS'][0]['ITEMS'][$c] = $row;
       $data['EVENTS'][0]['ITEMS'][$c]['EVENT_TYPE'] = $goal_type[$row['EVENT_TYPE']];
       $c++;  
    }
  }
}
else {
  // redirect back to list
  header('Location: '.$_POST['referer']);
  exit;
}

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/res_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>