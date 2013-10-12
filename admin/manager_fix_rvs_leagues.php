<?php
ini_set('display_errors', 1);
error_reporting (E_ALL);
/*
===============================================================================
season_edit.php
-------------------------------------------------------------------------------
RESPONSIBILITIES:
  - edit tournament seasons
  - create new tournament season

TABLES USED: 
  - BASKET.SEASONS

STATUS:
  - [STAT:FINSHD] finished
===============================================================================
*/
ob_start ();
// includes
include ('../class/conf.inc.php');
include ('../class/func.inc.php');
include ('../class/adm_menu.php');
include ('../class/update.inc.php');

// classes
include ('../class/db.class.php');
include ('../class/template.class.php');
include ('../class/language.class.php');
include ('../class/form.class.php');

// connections
include ('../class/db_connect.inc.php');
$tpl = new template ( );
$frm = new form ( );

// security layer
include('../class/inputs.inc.php');
include('../class/session.inc.php');
include('../class/headers.inc.php');
include('../class/rvs_manager_log.inc.php');
include('../class/rvs_manager_users_log.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------

$db->showquery=true;
if (!$ro) {
//  select current challenges
   $sql= "SELECT count( PLAYER_ID ) , RML.LEAGUE_ID, RMT.USER_ID, RML.TEAM_SIZE, MS.SPORT_ID, MS.SEASON_ID
		FROM rvs_manager_teams RMT, rvs_manager_leagues RML, manager_seasons MS
		WHERE RMT.SELLING_DATE IS NULL
		AND RML.LEAGUE_ID = RMT.LEAGUE_ID
		AND RML.SEASON_ID=MS.SEASON_ID
		GROUP BY RMT.LEAGUE_ID, RMT.USER_ID
		HAVING count( PLAYER_ID ) < RML.TEAM_SIZE";
   $db->query ( $sql );     
   $leagues = array();
   while ($row = $db->nextRow()) {
     $leagues[] = $row;
   }

   $rvs_manager_user_log = new RvsManagerUserLog();
   $rvs_manager_log = new RvsManagerLog();

   foreach ($leagues as $league) {
     // add additinal players
     $where_price = "";
     if ($league['SPORT_ID'] == 1)    
       $where_price = " AND CURRENT_VALUE_MONEY > 4000";


//		".$where_price."      
     $sql= "SELECT USER_ID, POSITION_ID1, CURRENT_VALUE_MONEY, 0 as USED 
	      FROM manager_market 
	     WHERE season_id=".$league['SEASON_ID']."
	  	   AND PLAYER_STATE = 0
		   AND PLAYED > 0
		   AND PUBLISH='Y'
		   AND USER_ID NOT IN (SELECT PLAYER_ID FROM rvs_manager_teams WHERE LEAGUE_ID=".$league['LEAGUE_ID']." AND SELLING_DATE IS NULL)
                 ORDER BY RAND() LIMIT 1";
      $db->query($sql); 
      if ($row = $db->nextRow()) {
        unset($sdata);
        $sdata['USER_ID'] = $league['USER_ID'];
        $sdata['LEAGUE_ID'] = $league['LEAGUE_ID'];
        $sdata['PLAYER_ID'] = $row['USER_ID'];
        $sdata['BUYING_PRICE'] = $row['CURRENT_VALUE_MONEY'];
        $sdata['SELLING_PRICE'] = $row['CURRENT_VALUE_MONEY'];
        $sdata['BUYING_DATE'] = "NOW()";
        $db->insert("rvs_manager_teams", $sdata);
        $rvs_manager_user_log->logEvent($league['USER_ID'], 2, $league['SEASON_ID'], $league['LEAGUE_ID'], $row['USER_ID']);
        $rvs_manager_log->logEvent ($league['USER_ID'], 10, $league['SEASON_ID'], $league['LEAGUE_ID'], $row['USER_ID']); 

        echo "Fixing user ".$league['USER_ID']." in league ".$league['LEAGUE_ID'].": added player ".$row['USER_ID']."<br>";
      }
   }

}

?>