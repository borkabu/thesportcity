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

$db->showquery=true;
// activate

if (isset($_POST['load_res']) && !$ro) {
  if (!isset($_GET['season_id']))
  {
    header('Location: manager_season.php');
    exit;
  }

  foreach ($_POST as $key => $value) {
   if ($_POST['tour_id'] > 0) {
     if (strpos($key, 'rating_') !== false && !empty($value)) {
       echo substr($key, strpos($key, '_') + 1).":".$value."<br>";
       $player_id = substr($key, strpos($key, '_') + 1);
        
       unset($sdata);
       $sdata['season_id'] = $_POST['season_id'];
       $sdata['tour_id'] = $_POST['tour_id'];
       $sdata['player_id'] = $player_id;
       $sdata['current_value_money'] = $value;        
       $sdata['start_value_money'] = $_POST['start_'.$player_id];
       $sdata['total_points'] = $value - $_POST['start_'.$player_id];
       $sdata['koeff'] = 0;
       $sdata['played'] = 0;
       $sdata['played_prev'] = 0;
       $sdata['total_points_prev'] = $_POST['total_prev_'.$player_id];
       $db->replace('manager_player_stats', $sdata);
     } 
   } else if ($_POST['tour_id'] == 0) {
      if (strpos($key, 'rating_') !== false && !empty($value)) {
        echo substr($key, strpos($key, '_') + 1).":".$value."<br>";
        $player_id = substr($key, strpos($key, '_') + 1);
       
        $manager->setPrice($player_id, $value/1000 - 1);
      }
   }
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
$data['TOUR_ID'] = $_GET['tour_id'];
$data['MENU']=getMenu(scriptName($_SERVER['PHP_SELF']));
$data['REFERER']=getReferer($_POST);

  $where = "";
  if ($_GET['tour_id'] == 0)
    $where = " AND MM.CURRENT_VALUE_MONEY is NULL";

  $sql = "SELECT DISTINCT M.USER_ID, M.NUM, M.POSITION_ID1, 1 as TYPE, T.TEAM_TYPE,
               M.POSITION_ID2, M.USER_TYPE, U.FIRST_NAME, MSS.SPORT_ID,
               U.LAST_NAME, T.TEAM_ID, T.TEAM_NAME2, MP.START_VALUE, MPS2.CURRENT_VALUE_MONEY  as PREV_VALUE_MONEY,
               M.DATE_EXPIRED, S.END_DATE, MPS.CURRENT_VALUE_MONEY, MPS2.TOTAL_POINTS 
        FROM team_seasons TS, teams T,
		 seasons S, manager_seasons MSS, members M 
             LEFT JOIN manager_market MM ON MM.USER_ID = M.USER_ID AND MM.SEASON_ID=".$_GET['season_id']." 
             LEFT JOIN manager_players MP ON MP.PLAYER_ID = MM.USER_ID AND MP.SEASON_ID=".$_GET['season_id']." 
             LEFT JOIN manager_player_stats MPS ON MPS.PLAYER_ID = MM.USER_ID 
			AND MPS.SEASON_ID=".$_GET['season_id']." 
			AND MPS.TOUR_ID=".($_GET['tour_id'])."
             LEFT JOIN manager_player_stats MPS2 ON MPS2.PLAYER_ID = MM.USER_ID 
			AND MPS2.SEASON_ID=".$_GET['season_id']." 
			AND MPS2.TOUR_ID=".($_GET['tour_id']-1)."
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
            AND (M.DATE_EXPIRED IS NULL OR M.DATE_EXPIRED > NOW())
            ".$where."
        GROUP BY M.USER_ID";

echo $sql;
  $db->query($sql);

  $c = 0;
  $t = 0;
  while ($row = $db->nextRow()) {
    $data['ITEMS'][$c] = $row;
    $data['ITEMS'][$c]['SEASON_ID'] = $_GET['season_id'];
    if (!empty($row['POSITION_ID2'])) {
      $data['ITEMS'][$c]['TYPE_NAME'] = $position_types[$row['SPORT_ID']][$row['POSITION_ID1']]."/".$position_types[$row['SPORT_ID']][$row['POSITION_ID2']];
    }
    else if (!empty($row['POSITION_ID1'])) {
      $data['ITEMS'][$c]['TYPE_NAME'] = $position_types[$row['SPORT_ID']][$row['POSITION_ID1']];
    }

    $data['ITEMS'][$c]['TOTAL_POINTS_PREV'] = $row['TOTAL_POINTS'];
    $data['ITEMS'][$c]['START_VALUE_MONEY'] = $row['PREV_VALUE_MONEY'];
    $data['ITEMS'][$c]['PREV_VALUE_MONEY'] = $row['PREV_VALUE_MONEY'];

    if ($c & 2 > 0)
      $data['ITEMS'][$c]['ODD'][0]['X'] = 1;
    else
      $data['ITEMS'][$c]['EVEN'][0]['X'] = 1;  

    $c++;
  }
  $db->free();
    
// content
$tpl->setCacheLevel(TPL_CACHE_NOTHING);
$tpl->setTemplateFile('../tpl/adm/manager_ind_load_res.tpl.html');
$tpl->addData($data);
echo $tpl->parse();

// close connections
include('../class/db_close.inc.php');
?>