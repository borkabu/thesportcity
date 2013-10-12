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


if (isset ( $_POST['update'] ) && ! $ro)
{
	//set_time_limit(6000);
	//  ignore_user_abort(true);
	echo ".......<br>";
	ob_flush ();
	flush ();
	
	$db->showquery = true;

	$sql = "SELECT * FROM manager_tours MTR
		WHERE MTR.SEASON_ID=" . $_POST['season_id'] . "
		      AND MTR.NUMBER=" . $_POST['tour_id'];
	$db->query ( $sql );
        $row = $db->nextRow ();
        $tour_start_date = $row['START_DATE'];
        $tour_end_date = $row['END_DATE'];
      	
	echo "<br>";
	$sql = "SELECT SUM( MPS.TOTAL_POINTS ) as TOTAL_POINTS, MPS.PLAYER_ID, 
                  SUM(MPS.PLAYED) as PLAYED, MPS1.CURRENT_VALUE_MONEY, 
                  MPS1.TOTAL_POINTS_PREV, " . $_POST['season_id'] . " AS SEASON_ID, MPS1.KOEFF,
                  MPS2.CURRENT_VALUE_MONEY AS PREV_VALUE_MONEY
	     FROM manager_player_stats MPS
		LEFT JOIN manager_player_stats MPS1 ON 
				MPS1.PLAYER_ID=MPS.PLAYER_ID
				AND MPS1.SEASON_ID=MPS.SEASON_ID
				AND MPS1.TOUR_ID=" . $_POST['tour_id'] . "
		LEFT JOIN manager_player_stats MPS2 ON MPS2.PLAYER_ID = MPS.PLAYER_ID
				AND MPS2.SEASON_ID = MPS.SEASON_ID
				AND MPS2.TOUR_ID =" . ($_POST['tour_id'] - 1) . "
		WHERE MPS.SEASON_ID =" . $_POST['season_id'] . "
		GROUP BY MPS.PLAYER_ID";
	//echo $sql;
	$db->query ( $sql );
	$market_players='';
	while ( $row = $db->nextRow () )
	{
		$market_players [$row ['PLAYER_ID']] = $row;
	}
	$db->free ();
	foreach ( $market_players as $marketplayer )
	{
		//print_r($marketplayer);
		$sdata ['TOTAL_POINTS'] = $marketplayer ['TOTAL_POINTS'];
		$sdata ['USER_ID'] = $marketplayer ['PLAYER_ID'];
		$sdata ['PLAYED'] = $marketplayer ['PLAYED'];
		$sdata ['KOEFF'] = $marketplayer ['KOEFF'];
		$sdata ['CURRENT_VALUE_MONEY'] = $marketplayer ['CURRENT_VALUE_MONEY'];
		$sdata ['TOTAL_POINTS_PREV'] = $marketplayer ['TOTAL_POINTS_PREV'];
		$sdata ['PREV_VALUE_MONEY'] = $marketplayer ['PREV_VALUE_MONEY'];
		$sdata ['SEASON_ID'] = $_POST['season_id'];
		$db->update ( "manager_market", $sdata, "USER_ID=" . $marketplayer ['PLAYER_ID'] . " AND SEASON_ID=" . $_POST['season_id'] );
		echo ".";
	}
	$db->free ();
	unset ( $sdata );
	echo "<br>........<br>";
	ob_flush ();
	flush ();
        
	// update users
	if ($_POST['tour_id'] > 1)
	{
		$sql = "SELECT MU.USER_ID, ".$_POST['tour_id'].", SUM(IF(MC.ENTRY_ID IS NULL,1,2)*MPS.TOTAL_POINTS) AS POINTS, COUNT(MPS.TOTAL_POINTS) VALID_TEAM, MUT1.USED_CHANGES,
			sum(MPS.CURRENT_VALUE_MONEY)+MU.MONEY AS WEALTH
	        FROM manager_teams MT
			left join manager_captain MC on MT.ENTRY_ID=MC.ENTRY_ID
			     AND '".$tour_start_date."' > MC.START_DATE 
	 	      	     AND ('".$tour_end_date."' < MC.END_DATE OR MC.END_DATE IS NULL)
			, manager_player_stats MPS, manager_users MU
        	     LEFT JOIN manager_users_tours MUT ON MUT.USER_ID=MU.USER_ID 
                                               	AND MUT.SEASON_ID=" . $_POST['season_id'] . "
						AND MUT.TOUR_ID=" . ($_POST['tour_id'] - 1) . "
        	     LEFT JOIN manager_users_tours MUT1 ON MUT1.USER_ID=MU.USER_ID 
                                               	AND MUT1.SEASON_ID=" . $_POST['season_id'] . "
						AND MUT1.TOUR_ID=" . ($_POST['tour_id']) . "
	        WHERE MT.USER_ID=MU.USER_ID
       		      AND MT.SEASON_ID=" . $_POST['season_id'] . "
	  	      AND MPS.SEASON_ID=" . $_POST['season_id'] . "
  		      AND MPS.TOUR_ID=" . $_POST['tour_id'] . "
	  	      AND MU.SEASON_ID=" . $_POST['season_id'] . "
        	      AND MT.PLAYER_ID=MPS.PLAYER_ID 
        	      AND '".$tour_start_date."' > MT.BUYING_DATE 
	      	      AND ('".$tour_end_date."' < MT.SELLING_DATE OR MT.SELLING_DATE IS NULL)
		GROUP BY MU.USER_ID";
	} else {
		$sql = "SELECT MU.USER_ID, ".$_POST['tour_id'].", SUM(IF(MC.ENTRY_ID IS NULL,1,2)*MPS.TOTAL_POINTS) AS POINTS, 0 AS USED_CHANGES,
			sum(MPS.CURRENT_VALUE_MONEY)+MU.MONEY AS WEALTH
	        FROM manager_teams MT
			left join manager_captain MC on MT.ENTRY_ID=MC.ENTRY_ID
			     AND '".$tour_start_date."' > MC.START_DATE 
	 	      	     AND ('".$tour_end_date."' < MC.END_DATE OR MC.END_DATE IS NULL)
			, manager_player_stats MPS, manager_users MU        	     
	        WHERE MT.USER_ID=MU.USER_ID
       		      AND MT.SEASON_ID=" . $_POST['season_id'] . "
	  	      AND MPS.SEASON_ID=" . $_POST['season_id'] . "
  		      AND MPS.TOUR_ID=" . $_POST['tour_id'] . "
	  	      AND MU.SEASON_ID=" . $_POST['season_id'] . "
        	      AND MT.PLAYER_ID=MPS.PLAYER_ID 
        	      AND '".$tour_start_date."' > MT.BUYING_DATE 
	      	      AND ('".$tour_end_date."' < MT.SELLING_DATE OR MT.SELLING_DATE IS NULL)
	GROUP BY MU.USER_ID";
	}
echo $sql;
	echo "process users<br>";
	$db->query ( $sql );
	$c = 0;
	while ( $row = $db->nextRow () )
	{
		$musers [$row['USER_ID']] = $row;
		$c ++;
		echo ".";
		ob_flush ();
		flush ();
	}

	//print_r($musers); exit;
	$db->free ();
	foreach($musers as $muser) {
		//      $sdata['POINTS'] = $musers[$i]['POINTS'];
		if (empty ( $muser ['USED_CHANGES'] ))
			$muser ['USED_CHANGES'] = 0;
		$sql = "REPLACE INTO manager_users_tours
			(USER_ID, TOUR_ID, SEASON_ID, USED_CHANGES, PLACE, PLACE_TOUR, POINTS, POINTS_MAIN, MONEY, RATING)
	              VALUES (" . $muser ['USER_ID'] . 
				"," . $_POST['tour_id'] . 
				"," . $_POST['season_id'] . 
				"," . $muser ['USED_CHANGES'] . 
				",0,0," . $muser ['POINTS'] . 
				",0,". $muser ['WEALTH'] .  ", 0)";
		$db->query ( $sql );
	}
	unset ( $sdata );
	$db->delete ( "manager_standings", "MSEASON_ID=" . $_POST['season_id'] );
	
	$sql = "REPLACE INTO manager_standings 
            SELECT MTR.USER_ID, SUM(MTR.POINTS) as POINTS, 0, " . $_POST['season_id'] . ",
                MTR1.PLACE AS PLACE_PREV, MTR2.MONEY, MTR1.MONEY AS WEALTH_PREV, 0
		FROM manager_users_tours MTR
			LEFT JOIN manager_users_tours MTR1
				ON MTR.SEASON_ID=MTR1.SEASON_ID
				AND MTR1.TOUR_ID=" . ($_POST['tour_id'] - 1) . "
				AND MTR.USER_ID=MTR1.USER_ID
			LEFT JOIN manager_users_tours MTR2
				ON MTR.SEASON_ID=MTR2.SEASON_ID
				AND MTR2.TOUR_ID=" . ($_POST['tour_id']) . "
				AND MTR.USER_ID=MTR2.USER_ID
                WHERE MTR.SEASON_ID=" . $_POST['season_id'] . "
                      AND MTR.TOUR_ID <= " . $_POST['tour_id'] . "
		GROUP BY MTR.USER_ID
		ORDER BY POINTS DESC";
	$db->query ( $sql );
	echo "update standings<br>";
	//    $db->free();
	

	$sql = "SELECT USER_ID , POINTS
            FROM manager_standings         
           WHERE MSEASON_ID=" . $_POST['season_id'] . "
           ORDER BY POINTS DESC";
	$db->query ( $sql );
	$c = 0;
	while ( $row = $db->nextRow () )
	{
		$people [$c] = $row;
		$people [$c] ['PLACE'] = $c + 1;
		$c ++;
	}
	$db->free ();

        unset($sdata);
        $sdata['MAX_POINTS'] = $people[0]['POINTS'];
        $max_points = $people[0]['POINTS'];
	$db->update ( 'manager_statistics', $sdata, "SEASON_ID=" . $_POST['season_id']);
        unset($sdata);
	
	//$db->showquery = true;
	for($i = 0; $i < $c; $i ++)
	{
		$sdata ['PLACE'] = $people [$i] ['PLACE'];
		$db->update ( 'manager_users_tours', $sdata, "SEASON_ID=" . $_POST['season_id'] . " AND USER_ID=" . $people [$i] ['USER_ID'] . " AND TOUR_ID=" . $_POST['tour_id'] );
		$sdata ['RATING'] = 100 * $people [$i] ['POINTS'] / $max_points;
		$db->update ( 'manager_standings', $sdata, "MSEASON_ID=" . $_POST['season_id'] . " AND USER_ID=" . $people [$i] ['USER_ID'] );
		unset ( $sdata );
	}
	$db->free ();

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
	
	//$db->showquery = true;
	for($i = 0; $i < $c; $i ++)
	{
		$sdata ['PLACE_TOUR'] = $i+1;
		$db->update ( 'manager_users_tours', $sdata, "SEASON_ID=" . $_POST['season_id'] . " AND USER_ID=" . $people [$i] ['USER_ID'] . " AND TOUR_ID=" . $_POST['tour_id'] );
		unset ( $sdata );
	}
	$db->free ();

    $manager_log = new ManagerLog();
    $manager_log->logEvent('', 3, 0, $_POST['season_id'], '', '');

    echo "Updating ratings<br>";
    // update ratings  
    $rating = new ManagerRating($_POST['season_id']);
    $rating->updateSportRating();
    $rating->updateTournamentRating();
    $rating->updateTotalRating();


    echo "Updating tournaments";
    $db->showquery = true;
    $sql = "SELECT MT_ID, START_TOUR, END_TOUR FROM manager_tournament where season_id=".$_POST['season_id']." AND STATUS=2";
    $db->query ( $sql );
    while ($row = $db->nextRow ()) {
      $mt_id = $row['MT_ID'];
      $start_tour = $row['START_TOUR'];
      $end_tour = $row['END_TOUR'];

        // update users
 	    $sql = "SELECT MU.USER_ID, MU.POINTS
	        FROM manager_tournament_users MTU
                       left join manager_users_tours MU on MU.SEASON_ID=".$_POST['season_id']."
							AND MU.TOUR_ID=".$_POST['tour_id']."
							AND MTU.USER_ID=MU.USER_ID
	        WHERE MTU.MT_ID=".$mt_id."
			AND MTU.TOUR=".($_POST['tour_id'] - $start_tour+1);

//	echo $sql;
	$db->query ( $sql );
	$c = 0;
	while ( $row = $db->nextRow () )
	{
		$musers [$c] = $row;
		$c ++;
	}
	//print_r($musers); exit;

	for($i = 0; $i < $c; $i ++)
	{
		unset ( $sdata );		
		$sdata['SCORE'] = $musers[$i]['POINTS'];
                $db->update("manager_tournament_results", $sdata, "USER_ID= ".$musers[$i]['USER_ID']." AND mt_id=".$mt_id." AND tour=".($_POST['tour_id'] - $start_tour + 1)." AND round=1");
	}

      $manager_tournament_log = new ManagerTournamentLog();
      $manager_tournament_log->logEvent('', 3, ($_POST['tour_id'] - $start_tour + 1), 1, $mt_id);
      echo "Tournament ".$mt_id." updated<br>";
    }

    echo "Done<br>";    
}

?>