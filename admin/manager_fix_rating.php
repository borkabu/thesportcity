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
include('../class/manager.inc.php');
include('../lib/manager_config.inc.php');
include('../class/manager_log.inc.php');
include('../class/manager_rating.inc.php');
include('../class/manager_tournament_log.inc.php');

if (empty($_SESSION["_admin"][MENU_ACTIONS_MANAGER]) || strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'NA') == 0)
{
  header('Location: access_denied.php');
  exit;
}

$ro = FALSE;
if (strcmp($_SESSION["_admin"][MENU_ACTIONS_MANAGER], 'RO') == 0)
  $ro = TRUE;

// --- BEGIN UPDATE -----------------------------------------------------------

// get seasons
$db->select ( 'manager_subseasons', 'SEASON_ID', 'MSEASON_ID=' . $_POST['season_id']);
$c = 0;
$ulist = '';
$pre = '';
while ( $row = $db->nextRow () )
{
	$ulist .= $pre . $row ['SEASON_ID'];
	$pre = ',';
	
	$c ++;
}
$db->free ();

//==== UPDATE!!!!!!!!!!!!!!!!!


if (isset ( $_POST['update'] ) && ! $ro) {
	set_time_limit(6000);
	//  ignore_user_abort(true);
	echo ".......<br>";
	ob_flush ();
	flush ();
	
	$db->showquery = true;

        $manager = new Manager($_POST['season_id']);
	$sql = "SELECT * FROM manager_tours MTR
		WHERE MTR.SEASON_ID=" . $_POST['season_id'] . "
		      AND MTR.NUMBER=" . $_POST['tour_id'];
	$db->query ( $sql );
        $row = $db->nextRow ();
        $tour_start_date = $row['START_DATE'];
        $tour_end_date = $row['END_DATE'];

	$sql = "SELECT USER_ID, POINTS 
            FROM manager_users_tours         
           WHERE SEASON_ID=" . $_POST['season_id'] . "
		 AND TOUR_ID=" . $_POST['tour_id'] . "
           ORDER BY POINTS DESC";
//echo $sql;
	$db->query ( $sql );
	$c = 0;
	while ( $row = $db->nextRow () )
	{
		$people [$c] = $row;
		$people [$c] ['PLACE'] = $c + 1;
		$c ++;
	}
	$db->free ();

        $max_points = $people[0]['POINTS'];
        unset($sdata);
	
        $manager_rating = new ManagerRating($_POST['season_id']);
	//$db->showquery = true;
	for($i = 0; $i < $c; $i ++)
	{
		$sdata ['PLACE_TOUR'] = $i+1;
		$sdata ['RATING'] = 100 * $people [$i] ['POINTS'] / $max_points;
		$db->update ( 'manager_users_tours', $sdata, "SEASON_ID=" . $_POST['season_id'] . " AND USER_ID=" . $people [$i] ['USER_ID'] . " AND TOUR_ID=" . $_POST['tour_id'] );
		unset ( $sdata );

		$sdata ['RATING_POINTS'] = 100 * $people [$i] ['POINTS'] / $max_points;
		$sdata ['SEASON_ID'] = $_POST['season_id'];
		$sdata ['USER_ID'] = $people [$i] ['USER_ID'];
		$sdata ['TOUR_ID'] = $_POST['tour_id'];
		$sdata ['SPORT_ID'] = $manager->sport_id;
		$sdata ['TOURNAMENT_ID'] = $manager_rating->tournament_id;				
		$sdata ['TOUR_END_DATE'] = "'".substr($tour_end_date, 0, 10)."'";				
		$db->replace ( 'manager_rating_points', $sdata);
		unset ( $sdata );

	}

    echo "Done<br>";    
}

?>