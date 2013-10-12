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
include('../class/manager_log.inc.php');
include('../class/manager.inc.php');
include('../class/manager_users_log.inc.php');

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
if (isset ( $_POST['adjust_price'] ) && ! $ro) {
  if (isset($_POST['season_id'])) {
    $manager = new Manager($_POST['season_id']);
    $current_tour = $manager->getCurrentTour();


   // $db->select("manager_statistics", "*", "SEASON_ID=".$_POST['season_id']);
//    $row = $db->nextRow();

/*    if ($row['PRICE_ADJUSTMENT'] == '1') {
      echo "Price adjustment already took place once";
      exit;
    }*/
    if ($current_tour >= 5)
      $db->select("manager_tours", "*", "SEASON_ID=".$_POST['season_id']." AND NUMBER=".($current_tour-4));
      $row = $db->nextRow();
      $start_date = $row['START_DATE'];

//  select current challenges
      $sql="SELECT MM.USER_ID, MM.START_VALUE, MAX(BUYING_DATE) LAST_BUYING_DATE
		from manager_players MP, manager_market MM
		LEFT JOIN manager_teams MT ON MT.season_id=".$_POST['season_id']." 
			and MT.PLAYER_ID=MM.USER_ID	
		LEFT JOIN manager_market_stats MMS ON MMS.season_id=".$_POST['season_id']." 
						AND MM.USER_ID = MMS.player_id 
	  WHERE MM.PLAYED=0
		AND MM.PUBLISH='Y'
		and MM.season_id=".$_POST['season_id']."
		and MP.player_id=MM.USER_ID
		AND MP.season_id=".$_POST['season_id']."
		AND MP.PRICE_ADJUSTED=0
		and (MMS.TEAMS = 0 OR MMS.TEAMS IS NULL
		      OR MT.SELLING_DATE is null)
	group by MM.USER_ID
	having LAST_BUYING_DATE is null or LAST_BUYING_DATE < '".$start_date."'";

      $db->query($sql);
      $players='';
      while ( $row = $db->nextRow () ) {
	$players[$row['USER_ID']] = $row;
      }

      // print_r($players);
      foreach ( $players as $player ) {
        $manager->setPrice($player['USER_ID'], $player['START_VALUE']*0.6);
        unset($sdata);
	$sdata['PRICE_ADJUSTED'] = 1;
	$db->update('manager_players', $sdata, 'PLAYER_ID='.$player['USER_ID']." AND SEASON_ID=".$_POST['season_id']);
	echo "Set player ".$player['USER_ID']." to ".$player['START_VALUE']*0.6."<br>";
      }

      echo "Price adjustment complete";
   } else {
    echo "Too early for price adjustment";
   }
}

?>