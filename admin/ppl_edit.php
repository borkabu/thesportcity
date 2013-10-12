<?php
/*
===============================================================================
ppl_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit people records
  - edit membership with teams
  - edit membership with tournaments
  - edit membership with organizations
  - edit keywords
  - create new person record

TABLES USED: 
  - BASKET.USERS
  - BASKET.MEMBERS
  - BASKET.TEAMS
  - BASKET.TOURNAMENTS
  - BASKET.ORGANIZATIONS
  - BASKET.KEYWORDS

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
include('../class/manager.inc.php');
include('../class/manager_log.inc.php');
include('../class/box.inc.php');
include('../class/managerbox.inc.php');
include('../class/page.inc.php');
include('../class/prepare.inc.php');
$html_page = new Page();

if (empty($_SESSION["_admin"][MENU_BASKET]) || strcmp($_SESSION["_admin"][MENU_BASKET], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = false;
if (strcmp($_SESSION["_admin"][MENU_BASKET], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATES ----------------------------------------------------------
if (isset($_GET['del']) && !$ro && strcmp($_SESSION["_admin"][MENU_BASKET], 'FA') == 0) {
  // delete membership
  $db->delete('members', 'ID='.$_GET['del']);
}
// activate
if (isset($_GET['activate']) && !$ro) {
  $db->update('members', array('APPROVED' => "'Y'"),'ID='.$_GET['activate']);
}
// deactivate
if (isset($_GET['deactivate']) && !$ro) {
  $db->update('members', array('APPROVED' => "'N'"),'ID='.$_GET['deactivate']);
}

$manager = new Manager();
// activate
if (isset($_GET['injured']) && !$ro) {
  $manager = new Manager($_GET['injured']);
  $manager->setHealth($_GET['user_id'], true);
}
// deactivate
if (isset($_GET['healthy']) && !$ro) {
  $manager = new Manager($_GET['healthy']);
  $manager->setHealth($_GET['user_id'], false);
}


$db->showquery=true;
// --- BEGIN SAVE -------------------------------------------------------------
$error = FALSE;
if(isset($_POST['form_save']) && !$ro){
  // required fields
  $s_fields = array('first_name', 'last_name', 'original_name', 'nickname');
  $i_fields = array('height', 'weight', 'citizenship', 'sport_id');
  $d_fields = array('birth_date', 'death_date');
  $c_fields = array('male', 'publish');
  $r_fields = array('last_name');
  
  // check for required fields
  if (!requiredFieldsOk($r_fields, $_POST)) {
    $error = TRUE;
    $data['ERROR'][0]['MSG']=$langs['LANG_ERROR_MAND_U'];
  }
  
  if (!$error) {
    // get save data
    $sdata = buildSaveData($s_fields, $i_fields, $d_fields, $c_fields, $_POST);
    
    // proceed to database updates
    if (!empty($_GET["user_id"])) {
      // UPDATE
      $db->update('busers', $sdata, "USER_ID=".$_GET["user_id"]);
      header('Location: '.$_POST['referer']);
      exit;
    }
    else {
      // INSERT
      $db->insert('busers', $sdata);
      $player_id = $db->id();
      header('Location: ppl_edit_members.php?type=2&user_id='.$player_id);
      exit;
    }   
  }
}

if (isset($_POST['move_from']) && !$ro){
  $db->select('members', '*', "ID=".$_POST['move_from']);
  if ($row = $db->nextRow()) {
    $move_from = $row;
  }
  unset($sdata);
  $sdata['DATE_EXPIRED'] = 'NOW()';
  $db->update('members', $sdata, "ID=".$_POST['move_from']);
  unset($sdata);
  $sdata['USER_ID'] = $move_from['USER_ID'];
  $sdata['POSITION_ID1'] = $move_from['POSITION_ID1'];
  if (!empty($move_from['POSITION_ID2'])) 
    $sdata['POSITION_ID2'] = $move_from['POSITION_ID2'];
  $sdata['APPROVED'] = "'".$move_from['APPROVED']."'";
  $sdata['DATE_STARTED'] = 'NOW()';
  $sdata['DATE_EXPIRED'] = 'NULL';
  unset($sdata['NUM']);
  $sdata['TEAM_ID'] = $_POST['team_id'];
  $db->insert('members', $sdata);

}

if (isset($_POST['prolong']) && !$ro){
  unset($sdata);
  $sdata['DATE_EXPIRED'] = 'NULL';
  $db->update('members', $sdata, "ID=".$_POST['prolong']);
}

if (isset($_POST['synchronise']) && !$ro) {
    $sql = "SELECT DISTINCT MM.TEAM_ID, MM.TEAM_NAME2
        FROM manager_market MM 
        WHERE MM.SEASON_ID=".$_POST['season_id']."
             AND MM.USER_ID = ".$_POST['player_id'];
    $db->query($sql);
    $rowold = $db->nextRow();
    // get new team

    $sql ="SELECT T.TEAM_NAME2, T.TEAM_ID
                FROM teams T
                WHERE T.TEAM_ID = ".$_POST['team_id'];

    $db->query($sql);
    $rownew = $db->nextRow();
    // perform synchronisation
    unset($sdata);
    $sdata['TEAM_ID'] = $rownew['TEAM_ID'];
    $sdata['TEAM_NAME2'] = "'".$rownew['TEAM_NAME2']."'";
    $db->update('manager_market', $sdata, "USER_ID = ".$_POST['player_id']." AND SEASON_ID=".$_POST['season_id']);
    
    if ($rowold['TEAM_ID'] != $rownew['TEAM_ID']) {
      $manager_log = new ManagerLog();
      $manager_log->logEvent($_POST['player_id'], 5, 0, $_POST['season_id'], $rowold['TEAM_ID'], $rownew['TEAM_ID']);
    }
}


if (isset($_POST['set_price']) && !$ro) {
  if (!isset($_POST['season_id']))
  {
    header('Location: manager_season.php');
    exit;
  }
  $manager = new Manager($_POST['season_id']);

  foreach ($_POST as $key => $value) {
    if (strpos($key, 'player_') !== false && !empty($value)) {
      echo substr($key, strpos($key, '_') + 1).":".$value."<br>";
      $player_id = substr($key, strpos($key, '_') + 1);
      $manager->setPrice($player_id, $value);
    }
  }
  
}

// --- END SAVE ---------------------------------------------------------------


// build data
$data['menu'] = getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER'] = getReferer($_POST);

// new or edit?
if (isset($_GET['user_id'])) {
  // edit
  $fields = 'USER_ID, FIRST_NAME, LAST_NAME, ORIGINAL_NAME, HEIGHT, WEIGHT, 
             MALE, SUBSTRING(BIRTH_DATE, 1, 10) BIRTH_DATE, SUBSTRING(DEATH_DATE, 1, 10) DEATH_DATE, 
             CITIZENSHIP, PUBLISH, SPORT_ID
             ';
  $db->select('busers', $fields, "USER_ID=".$_GET['user_id']);
  if (!$row = $db->nextRow()) {
    // ERROR! No such record. redirect to list
//    header('Location: ppl.php');
    exit;
  }
  else {
    // populate $PRESET_VARS with data so form class can use their values
    while (list($key, $val) = each($row)) {
      $PRESET_VARS[strtolower($key)] = $val;
    }
  }
  $db->free();
   
  $data['TEAMS'][0]['USER_ID'] = $_GET['user_id'];

  $team_list = inputTeamsFiltered('team_id', '', 'SPORT_ID='.$PRESET_VARS['sport_id']);    
  // generate membership list
  $sql='SELECT M.ID, M.USER_ID, M.TEAM_ID, M.USER_TYPE, M.APPROVED,
           M.POSITION_ID1, M.POSITION_ID2, M.NUM, SUBSTRING(M.DATE_STARTED, 1, 10) as DATE_STARTED, SUBSTRING(M.DATE_EXPIRED,1,10) AS DATE_EXPIRED,
           T.TEAM_NAME
        FROM members M LEFT JOIN teams T ON M.TEAM_ID=T.TEAM_ID 
        WHERE USER_ID='.$_GET['user_id'].' 
        ORDER BY DATE_STARTED DESC';
  $db->query($sql);
  $t=0;
  while ($row = $db->nextRow()) {
    if ($row['TEAM_ID'] > 0) {
      // team membership
      $data['TEAMS'][0]['TEAMS'][$t]=$row;
      if (empty($row['DATE_EXPIRED']))
        $data['TEAMS'][0]['TEAMS'][$t]['DATE_EXPIRED'] = "Iki dabar";
      if (strcmp($_SESSION["_admin"][MENU_BASKET], 'FA') == 0)  
        $data['TEAMS'][0]['TEAMS'][$t]['DEL'][0]['DEL_URL'] = $_SERVER['PHP_SELF'].url('del', $row['ID']);
      if ($row['APPROVED'] == 'Y')
        $data['TEAMS'][0]['TEAMS'][$t]['ACT_URL'] = $_SERVER['PHP_SELF'].url('deactivate', $row['ID']);
      else
        $data['TEAMS'][0]['TEAMS'][$t]['ACT_URL'] = $_SERVER['PHP_SELF'].url('activate', $row['ID']);

      if ($t & 2 > 0) 
        $data['TEAMS'][0]['TEAMS'][$t]['ODD'][0]['X'] = 1;
   
      $data['TEAMS'][0]['TEAMS'][$t]['MOVE_TEAM_ID'] =  $team_list;
      $t++;

    }
  }
  if ($t==0)
    $data['TEAMS'][0]['TEAM_NORECORDS'][0]['X'] = 1;



  $sql = "SELECT DISTINCT M.USER_ID, MSS.SEASON_ID, M.NUM, M.POSITION_ID1, 1 as TYPE, T.TEAM_TYPE,
               M.POSITION_ID2, M.USER_TYPE, U.FIRST_NAME, MSS.SPORT_ID,
               U.LAST_NAME, MM.MALE, T.TEAM_ID, IF(T.TEAM_TYPE=1, T.TEAM_NAME2, CD.COUNTRY_NAME) as TEAM_NAME2, MP.START_VALUE, MP.START_VALUE as START_VALUE_MONEY,
               M.DATE_EXPIRED, S.END_DATE, MM.CURRENT_VALUE_MONEY, MP.PUBLISH, MM.INJURY, MM.TEAM_NAME2 AS TEAM_NAME_MARKET, MM.PLAYER_STATE
        FROM team_seasons TS, teams T
	     LEFT JOIN countries_details CD on CD.ID=T.COUNTRY AND CD.LANG_ID=".$_SESSION['lang_id'].",
		 seasons S, members M, manager_subseasons MSB, manager_seasons MSS 
             LEFT JOIN manager_market MM ON MM.USER_ID = ".$_GET['user_id']." and MM.SEASON_ID=MSS.SEASON_ID
             LEFT JOIN manager_players MP ON MP.PLAYER_ID = MM.USER_ID and MP.SEASON_ID=MSS.SEASON_ID
             LEFT JOIN busers U ON U.USER_ID = ".$_GET['user_id']." 
        WHERE S.SEASON_ID = TS.SEASON_ID 
	     AND S.SEASON_ID=MSB.SEASON_ID
	     AND MSB.MSEASON_ID=MSS.SEASON_ID
 	     AND MSS.START_DATE <=NOW()
 	     AND MSS.END_DATE >= NOW()
	     AND M.USER_ID=".$_GET['user_id']."
             AND M.TEAM_ID = TS.TEAM_ID 
             AND T.TEAM_ID = TS.TEAM_ID 
           AND ((M.DATE_STARTED >= MSS.START_DATE AND M.DATE_STARTED <= MSS.END_DATE) 
                OR (M.DATE_EXPIRED >= MSS.START_DATE AND M.DATE_EXPIRED  <= MSS.END_DATE)  
                OR (M.DATE_STARTED <= MSS.START_DATE 
                 AND (M.DATE_EXPIRED >= MSS.END_DATE OR M.DATE_EXPIRED IS NULL))
               ) 
            AND (M.DATE_EXPIRED IS NULL OR M.DATE_EXPIRED > NOW())";

//echo $sql;
  $db->query($sql);

  $c = 0;
  while ($row = $db->nextRow()) {
    $c = $row['SEASON_ID'];
    $data['MANAGERS'][0]['ITEMS'][$c] = $row;
    $data['MANAGERS'][0]['ITEMS'][$c]['SEASON_ID'] = $row['SEASON_ID'];
    if (!empty($row['POSITION_ID2'])) {
      $data['MANAGERS'][0]['ITEMS'][$c]['TYPE_NAME'] = $position_types[$row['SPORT_ID']][$row['POSITION_ID1']]."/".$position_types[$row['SPORT_ID']][$row['POSITION_ID2']];
    }
    else if (!empty($row['POSITION_ID1'])) {
      $data['MANAGERS'][0]['ITEMS'][$c]['TYPE_NAME'] = $position_types[$row['SPORT_ID']][$row['POSITION_ID1']];
    }

    if ($row['TEAM_TYPE'] == 1 && $row['TEAM_NAME2'] != $row['TEAM_NAME_MARKET'] && $row['PUBLISH'] != '') {
	$data['MANAGERS'][0]['ITEMS'][$c]['SYNCHRONISE'][0]['PLAYER_ID'] = $row['USER_ID'];
	$data['MANAGERS'][0]['ITEMS'][$c]['SYNCHRONISE'][0]['TEAM_ID'] = $row['TEAM_ID'];
	$data['MANAGERS'][0]['ITEMS'][$c]['SYNCHRONISE'][0]['SEASON_ID'] = $row['SEASON_ID'];
    }

    if (empty($row['START_VALUE'])) {
      $data['MANAGERS'][0]['ITEMS'][$c]['NO_VALUE'][0]['USER_ID'] = $row['USER_ID'];
      $data['MANAGERS'][0]['ITEMS'][$c]['NO_VALUE'][0]['SEASON_ID'] = $row['SEASON_ID'];
    }

    if ($c & 2 > 0)
      $data['MANAGERS'][0]['ITEMS'][$c]['ODD'][0]['X'] = 1;
    else
      $data['MANAGERS'][0]['ITEMS'][$c]['EVEN'][0]['X'] = 1;  

    if ($row['PUBLISH'] == 'Y') {
      $data['MANAGERS'][0]['ITEMS'][$c]['ACTIVATED'][0]['TYPE'] = 'manager_price';
      $data['MANAGERS'][0]['ITEMS'][$c]['ACTIVATED'][0]['USER_ID'] = $row['USER_ID'];
      $data['MANAGERS'][0]['ITEMS'][$c]['ACTIVATED'][0]['SEASON_ID'] = $row['SEASON_ID'];
    }
    else {
      $data['MANAGERS'][0]['ITEMS'][$c]['DEACTIVATED'][0]['TYPE'] = 'manager_price';
      $data['MANAGERS'][0]['ITEMS'][$c]['DEACTIVATED'][0]['USER_ID'] = $row['USER_ID'];
      $data['MANAGERS'][0]['ITEMS'][$c]['DEACTIVATED'][0]['SEASON_ID'] = $row['SEASON_ID'];
    }

    $data['MANAGERS'][0]['ITEMS'][$c]['CNT'] = 0;
    $managerbox = new ManagerBox($langs, $_SESSION["_lang"]);

    $data['MANAGERS'][0]['ITEMS'][$c]['PLAYER_STATE_DIV'] = $managerbox->getPlayerStateDiv($row['USER_ID'], $row['SEASON_ID'], $row['PLAYER_STATE'], true);
  }

  foreach ($data['MANAGERS'][0]['ITEMS'] as &$manager) {
     $sql="SELECT count(MT.entry_id) CNT, MT.PLAYER_ID
		FROM manager_teams MT, manager_market MM
		where MT.season_id=".$manager['SEASON_ID']."	
		and MT.player_id=MM.user_id
		and MT.PLAYER_ID =".$_GET['user_id']."
		and MM.season_id=MT.season_id
		and MT.selling_date is null
	   	group by MT.player_id
	   UNION

	SELECT count(MT.entry_id) CNT, MT.PLAYER_ID
		FROM manager_teams_substitutes MT, manager_market MM
		where MT.season_id=".$manager['SEASON_ID']."	
		and MT.player_id=MM.user_id
		and MT.PLAYER_ID =".$_GET['user_id']."
		and MM.season_id=MT.season_id
		and MT.selling_date is null
		group by MT.player_id
               ";
     $db->query($sql);
     if ($row = $db->nextRow()) {
       $manager['CNT'] += $row['CNT'];
     }

  }

  $db->free();
}
else {
  // adding records
  $PRESET_VARS['publish'] = 'Y';
}

$data['CITIZENSHIP']=inputCountries('citizenship');
$data['SPORT_ID'] = inputManagerSportTypes('sport_id', $PRESET_VARS['sport_id']);

// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/ppl_edit.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>